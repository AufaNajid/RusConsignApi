<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\Invoice;
use Xendit\Invoice\InvoiceApi;
use App\Models\User;

class PaymentController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    public function createInvoice(Request $request)
    {
        // Validasi input dengan validate
        $validatedData = $request->validate([
            'barang_id' => 'required|integer|exists:barangs,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Ambil data dari validated request
        $barang_id = $validatedData['barang_id'];
        $quantity = $validatedData['quantity'];

        // Ambil data barang dari database
        $barang = Barang::find($barang_id);
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang not found'
            ], 404);
        }

        // Hitung total harga
        $totalAmount = $barang->price * $quantity;

        // Persiapkan data untuk invoice
        $invoiceData = [
            "external_id" => Str::uuid()->toString(),
            'amount' => $totalAmount,
            'description' => 'Invoice for Barang ID ' . $barang_id,
            'invoice_duration' => 3600, // Durasi invoice dalam detik
            'items' => [
                [
                    'name' => $barang->name,
                    'price' => $barang->price,
                    'quantity' => $quantity,
                    'total' => $totalAmount,
                ]
            ],
        ];

        try {
            // Buat invoice dengan Xendit
            $createInvoice = Invoice::create($invoiceData);

            return response()->json([
                'status' => 'success',
                'invoice_url' => $createInvoice->invoice_url,
                'message' => 'Invoice created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


//    public function createInvoice(Request $request)
//    {
//        try {
//            $validatedData = $request->validate([
//                'barang_id' => 'required|integer|exists:barangs,id',
//                'quantity' => 'required|integer|min:1',
//            ]);
//
//                $barang = Barang::findOrFail($validatedData['barang_id']);
//            $user = Auth::user();
//
//            $totalAmount = $barang->harga * $validatedData['quantity'];
//            $no_transaction = 'Inv-' . uniqid();
//
//            $createdInvoice = [
//                'external_id' => $no_transaction,
//                'amount' => $totalAmount,
//                'payer_email' => $user->email,
//                'description' => 'Invoice for user ' . $user->name,
//            ];
//
//            // Generate the invoice using the API instance
//            $apiInstance = new InvoiceApi();
//            $generateInvoice = $apiInstance->createInvoice($createdInvoice);
//
//            // Check if the response has the necessary property
//            if (!isset($generateInvoice['invoice_url'])) {
//                throw new \Exception('Invoice URL not found in the response');
//            }
//
//            // Save the payment order to the database
//            $order = new Payment([
//                'barang_id' => $validatedData['barang_id'],
//                'user_id' => $user->id,
//                'no_transaction' => $no_transaction,
//                'external_id' => $no_transaction,
//                'name_barang' => $barang->nama_barang,
//                'quantity' => $validatedData['quantity'],
//                'harga_barang' => $barang->harga,
//                'grand_total' => $totalAmount,
//                'invoice_url' => $generateInvoice['invoice_url'],
//                'status' => 'pending',
//            ]);
//            $order->save();
//
//            return response()->json($generateInvoice, 201);
//        } catch (\Exception $e) {
//            return response()->json(['error' => $e->getMessage()], 500);
//        }
//    }

    public function webhook(Request $request)
    {
        $getInvoice = \Xendit\Invoice::retrieve($request->id);

        $payment = Payment::where('external_id',$request->external_id)->firstOrFail();

        if ($payment->status == 'settled'){
            return response()->json([
                "data" => "Payment has been already processed"
            ]);

            $payment->status = strtolower($getInvoice['status']);
            $payment->save();
            return response()->json([]);
        }

    }

}

