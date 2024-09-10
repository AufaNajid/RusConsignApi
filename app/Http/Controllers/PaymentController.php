<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Komentar;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

class PaymentController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    public function createInvoice(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'barang_id' => 'required|integer|exists:barangs,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $barang = Barang::findOrFail($validatedData['barang_id']);
            $user = Auth::user();

            $totalAmount = $barang->harga * $validatedData['quantity'];
            $no_transaction = 'Inv-' . uniqid();

            $createdInvoice = [
                'external_id' => $no_transaction,
                'amount' => $totalAmount,
                'payer_email' => $user->email,
                'description' => 'Invoice for user ' . $user->name,
            ];

            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($createdInvoice);

            if (!isset($generateInvoice['invoice_url'])) {
                throw new \Exception('Invoice URL not found in the response');
            }

            $order = new Payment([
                'barang_id' => $validatedData['barang_id'],
                'user_id' => $user->id,
                'no_transaction' => $no_transaction,
                'external_id' => $no_transaction,
                'name_barang' => $barang->nama_barang,
                'quantity' => $validatedData['quantity'],
                'harga_barang' => $barang->harga,
                'grand_total' => $totalAmount,
                'invoice_url' => $generateInvoice['invoice_url'],
                'status' => 'progres',
            ]);
            $order->save();

            return response()->json($generateInvoice, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function createInvoicemultiple(Request $request)
    {
        try {
            $request->merge([
                'barang_id' => json_decode($request->input('barang_id')),
                'quantity' => json_decode($request->input('quantity')),
            ]);

            $validatedData = $request->validate([
                'barang_id' => 'required|array',
                'barang_id.*' => 'exists:barangs,id',
                'quantity' => 'required|array',
                'quantity.*' => 'integer|min:1',
            ]);

            $user = Auth::user();
            $totalAmount = 0;
            $orders = [];

            foreach ($validatedData['barang_id'] as $index => $barangId) {
                $quantity = $validatedData['quantity'][$index];
                $barang = Barang::findOrFail($barangId);

                $totalAmount += $barang->harga * $quantity;

                $orders[] = [
                    'barang_id' => $barangId,
                    'user_id' => $user->id,
                    'name_barang' => $barang->nama_barang,
                    'quantity' => $quantity,
                    'harga_barang' => $barang->harga,
                    'grand_total' => $barang->harga * $quantity,
                    'status' => 'progres',
                ];
            }

            $no_transaction = 'Inv-' . uniqid();

            $createdInvoice = [
                'external_id' => $no_transaction,
                'amount' => $totalAmount,
                'payer_email' => $user->email,
                'description' => 'Invoice for user ' . $user->name,
            ];

            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($createdInvoice);

            if (!isset($generateInvoice['invoice_url'])) {
                throw new \Exception('Invoice URL not found in the response');
            }

            foreach ($orders as &$order) {
                $order['no_transaction'] = $no_transaction;
                $order['external_id'] = $no_transaction;
                $order['invoice_url'] = $generateInvoice['invoice_url'];
                Payment::create($order);
            }

            return response()->json($generateInvoice, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function notificationCallback(Request $request)
    {
        $getToken = $request->headers->get('x-callback-token');
        $callbackToken = env('XENDIT_CALLBACK_TOKEN');

        try {
            if ($getToken !== $callbackToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid callback token',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Callback token verified',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'external_id' => 'required|string',
                'status' => 'required|string',
            ]);

            $payment = Payment::where('external_id', $validatedData['external_id'])->firstOrFail();

            if ($payment->status == 'settled') {
                return response()->json([
                    "data" => "Payment has already been processed"
                ], 200);
            }

            // Cek status dari webhook dan ubah statusnya
            if (strtolower($validatedData['status']) == 'paid') {
                $payment->status = 'selesai';
            } else {
                $payment->status = strtolower($validatedData['status']);
            }

            $payment->save();

            return response()->json([
                "data" => "Payment status updated successfully"
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                "error" => "Payment not found"
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                "error" => $e->getMessage()
            ], 500);
        }
    }


    public function getallByStatus($status)
    {
        try {
            $payments = Payment::where('status', $status)
                ->with(['barang', 'user']) // Memuat relasi barang dan user
                ->get();

            if ($payments->isEmpty()) {
                return response()->json(['message' => 'No payments found for the given status'], 404);
            }

            $rate = Komentar::select(
                DB::raw('count(1) as total'),
                'rate'
            )
                ->where('barang_id', $barang->id)
                ->groupBy('rate')
                ->get();

            $total = $rate->sum('total');
            $avg = $rate->reduce(function ($carry, $item) {
                    return $carry + ($item->total * $item->rate);
                }, 0) / ($total ?: 1);

            $detailedPayments = $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'barang' => [
                        'id' => $payment-> barang->id,
                        'nama_barang' => $payment-> barang->nama_barang,
                        'deskripsi' =>$payment-> barang->deskripsi,
                        'harga' =>$payment-> barang->harga,
                        'rating_barang' =>$payment-> $rate,
                        'category_id' =>$payment->barang->category->id,
                        'category_nama' =>$payment-> barang->category->name,
                        'image_barang' =>$payment-> barang->image_barang,
                        'status' =>$payment-> barang->status_post,
                        'stock' =>$payment-> barang->stock_barang,
                        'quantity' =>$payment-> barang->quantity,
                        'created_at' =>$payment-> barang->created_at,
                        'updated_at' =>$payment-> barang->updated_at,
                        'mitra' => [
                            'id' => $payment->barang->mitra->id,
                            'nama_toko' =>$payment-> barang->mitra->nama_toko,
                            'nama_lengkap' => $payment->barang->mitra->nama_lengkap,
                            'jumlah_product' => $payment->barang->mitra->jumlah_product,
                            'jumlah_jasa' =>$payment-> barang->mitra->jumlah_jasa,
                            'pengikut' => $payment->barang->mitra->pengikut,
                            'penilaian' => $payment->barang->mitra->penilaian,
                            'no_whatsapp'=> $payment->barang->mitra->no_whatsapp,
                            'profile_image' => $payment->barang->mitra->profileImage->image_profile ?? null
                        ],
                    ],
                    'user' => [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name,
                        'email' => $payment->user->email,
                    ],
                    'external_id' => $payment->external_id,
                    'no_transaction' => $payment->no_transaction,
                    'quantity' => $payment->quantity,
                    'invoice_url' => $payment->invoice_url,
                    'grand_total' => $payment->grand_total,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
            });


            return response()->json($detailedPayments, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPaymentsByStatus($role, $status, $id, Request $request)
    {
        try {
            // Validasi role
            if (!in_array($role, ['user', 'mitra'])) {
                return response()->json(['message' => 'Invalid role'], 400);
            }

            // Validasi status
            if (!in_array($status, ['belum_pembayaran', 'progres', 'selesai', 'batal_pesanan'])) {
                return response()->json(['message' => 'Invalid payment status'], 400);
            }

            $query = Payment::where('status', $status);

            // Filter berdasarkan user_id jika role adalah 'user'
            if ($role === 'user') {
                $query->where('user_id', $id);
            }

            // Filter berdasarkan mitra_id jika role adalah 'mitra'
            if ($role === 'mitra') {
                $query->whereHas('barang', function($q) use ($id) {
                    $q->where('mitra_id', $id);
                });
            }

            $payments = $query->with(['barang', 'user'])->get();

            if ($payments->isEmpty()) {
                return response()->json(['message' => 'No payments found for the given status'], 404);
            }

            $detailedPayments = $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'barang' => [
                        'id' => $payment->barang->id,
                        'nama_barang' => $payment->barang->nama_barang,
                        'harga_barang' => $payment->barang->harga,
                        'rating_barang' => $payment->barang->rating,
                    ],
                    'user' => [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name,
                        'email' => $payment->user->email,
                    ],
                    'external_id' => $payment->external_id,
                    'no_transaction' => $payment->no_transaction,
                    'quantity' => $payment->quantity,
                    'invoice_url' => $payment->invoice_url,
                    'grand_total' => $payment->grand_total,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
            });

            return response()->json($detailedPayments, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
