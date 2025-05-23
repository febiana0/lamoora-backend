<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Notification;

class TransactionController extends Controller
{
    public function callback(Request $request)
    {
        $notification = new Notification();

        $status = $notification->transaction_status;
        $orderId = explode('-', $notification->order_id)[0];

        $transaction = Transaction::findOrFail($orderId);

        if ($status == 'settlement') {
            $transaction->status = 'paid';
        } elseif ($status == 'pending') {
            $transaction->status = 'pending';
        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $transaction->status = 'failed';
        }

        $transaction->save();

        return response()->json(['message' => 'Callback handled']);
    }

    public function getSnapToken(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        // Ambil transaksi
        $transaction = Transaction::with('items.product')->findOrFail($request->transaction_id);
        $user = $request->user();

        // Buat payload untuk Midtrans Snap
        $payload = [
            'transaction_details' => [
                'order_id' => $transaction->id . '-' . time(),
                'gross_amount' => $transaction->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => $transaction->items->map(function ($item) {
                return [
                    'id' => $item->product->id,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product->name,
                ];
            })->toArray(),
        ];

        // Ambil Snap Token
        $snapToken = Snap::getSnapToken($payload);

        return response()->json([
            'snap_token' => $snapToken,
        ]);
    }

    public function index()
    {
        $transactions = Transaction::with('items.product')->get();
        return response()->json($transactions);
    }

    public function show($id)
    {
        $transaction = Transaction::with('items.product')->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        return response()->json($transaction);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'shipping' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
        ]);

        $cartItems = Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Keranjang kosong'
            ], 400);
        }

        $totalPrice = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'shipping' => $request->shipping,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            foreach ($cartItems as $item) {
                // Ambil produk langsung dari database agar fresh
                $product = $item->product()->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Produk tidak ditemukan.'
                    ], 400);
                }

                if ($product->stock < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Stok produk tidak mencukupi untuk ' . $product->name
                    ], 400);
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ]);

                $product->stock -= $item->quantity;
                $product->save();
            }

            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Checkout berhasil',
                'transaction' => $transaction->load('items.product')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Checkout gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
