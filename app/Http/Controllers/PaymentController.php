<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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


    public function getPaymentsByStatus($status)
    {
        try {
            $payments = Payment::where('status', $status)->get();

            if ($payments->isEmpty()) {
                return response()->json(['message' => 'No payments found for the given status'], 404);
            }

            return response()->json($payments, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
