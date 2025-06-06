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
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }


    public function getSnapToken(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $transaction = Transaction::with('items.product')->findOrFail($request->transaction_id);
        $user = $request->user();

        $orderId = $transaction->id . '-' . time(); // ID unik agar tidak bentrok
        $transaction->update(['order_id' => $orderId]); // Simpan agar bisa digunakan di callback

        $itemDetails = $transaction->items->map(function ($item) {
            return [
                'id' => (string) $item->product->id,
                'price' => (int) $item->price,
                'quantity' => (int) $item->quantity,
                'name' => substr($item->product->name, 0, 50), // max 50 char
            ];
        })->toArray();

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $transaction->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $request->phone,
            ],
            'item_details' => $itemDetails,
        ];

        \Log::info('MIDTRANS ENV', [
            'server_key' => config('midtrans.server_key'),
            'is_production' => config('midtrans.is_production'),
            'payload' => $payload,
        ]);

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($payload);
            \Log::info('Snap Token', [$snapToken]);
        } catch (\Exception $e) {
            \Log::error('Midtrans Error', [$e->getMessage()]);
            return response()->json([
                'message' => 'Midtrans error',
                'error' => $e->getMessage(),
            ], 500);
        }

       return response()->json([
            'message' => 'Checkout berhasil',
            'transaction' => $transaction,
            'snap_token' => $snapToken,
]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = \App\Models\Transaction::with('items.product')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $transactions
        ]);
    }

    public function show($id)
    {
        $transaction = Transaction::with('items.product')->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
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

    $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

    if ($cartItems->isEmpty()) {
        return response()->json(['message' => 'Keranjang kosong'], 400);
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
            $product = $item->product;

            if ($product->stock < $item->quantity) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Stok produk tidak mencukupi untuk ' . $product->name,
                ], 400);
            }

            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'price' => $product->price,
            ]);

            $product->decrement('stock', $item->quantity);
        }

        Cart::where('user_id', $user->id)->delete();

        DB::commit();

        // Generate Snap Token
        $orderId = $transaction->id . '-' . time();
        $transaction->update(['order_id' => $orderId]);

        $itemDetails = $transaction->items->map(function ($item) {
            return [
                'id' => (string) $item->product->id,
                'price' => (int) $item->price,
                'quantity' => (int) $item->quantity,
                'name' => substr($item->product->name, 0, 50),
            ];
        })->toArray();

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $transaction->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $request->phone,
            ],
            'item_details' => $itemDetails,
        ];

        \Log::info('MIDTRANS ENV', [
            'server_key' => config('midtrans.server_key'),
            'is_production' => config('midtrans.is_production'),
            'payload' => $payload,
        ]);

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($payload);
            \Log::info('Snap Token', [$snapToken]);
        } catch (\Exception $e) {
            \Log::error('Midtrans Error', [$e->getMessage()]);
            return response()->json([
                'message' => 'Midtrans error',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Checkout berhasil',
            'transaction' => $transaction->load('items.product'),
            'snap_token' => $snapToken,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Checkout gagal',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function midtransCallback(Request $request)
{
    \Log::info('Midtrans callback received - FULL DATA', [
        'all_data' => $request->all(),
        'order_id' => $request->order_id,
        'transaction_status' => $request->transaction_status,
        'payment_type' => $request->payment_type
    ]);

    try {
        // Coba ambil data tanpa Notification class dulu
        $transaction_status = $request->transaction_status;
        $order_id = $request->order_id;
        
        \Log::info('Processing order', ['order_id' => $order_id]);
        
        $trxId = explode('-', $order_id)[0];
        \Log::info('Extracted transaction ID', ['trxId' => $trxId]);
        
        $trx = \App\Models\Transaction::find($trxId);
        
        if (!$trx) {
            \Log::error('Transaction not found in database', [
                'trxId' => $trxId,
                'order_id' => $order_id
            ]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        
        \Log::info('Found transaction', [
            'transaction_id' => $trx->id,
            'current_status' => $trx->status
        ]);

        // Mapping status
        if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
            $trx->status = 'paid';
        } elseif ($transaction_status == 'pending') {
            $trx->status = 'pending';
        } elseif (in_array($transaction_status, ['deny', 'expire', 'cancel'])) {
            $trx->status = 'failed';
        }
        
        $trx->save();
        
        \Log::info('Transaction status updated', [
            'transaction_id' => $trx->id,
            'old_status' => $trx->getOriginal('status'),
            'new_status' => $trx->status
        ]);
        
        return response()->json([
            'message' => 'Notification handled successfully',
            'order_id' => $order_id,
            'status' => $trx->status
        ]);
    } catch (\Exception $e) {
        \Log::error('Midtrans callback error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'message' => 'Error processing notification',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|string|in:pending,paid,shipped,failed',
    ]);

    $transaction = Transaction::find($id);

    if (!$transaction) {
        return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
    }

    $transaction->status = $request->status;
    $transaction->save();

    return response()->json([
        'message' => 'Status transaksi berhasil diupdate',
        'transaction' => $transaction,
    ]);
}

}
