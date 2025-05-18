<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
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

        // Ambil semua item keranjang user
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Keranjang kosong'
            ], 400);
        }

        // Hitung total harga
        $totalPrice = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        DB::beginTransaction();
        try {
            // Buat transaksi
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_price' => $totalPrice,
            ]);

            // Tambahkan item transaksi
            foreach ($cartItems as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            // Kosongkan keranjang
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

    // ... method lain: index(), show(), dll
}
