<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Xendit\Configuration;
use App\Models\User;
use Xendit\Invoice\InvoiceApi;
use Xendit\Xendit;

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

            // Generate the invoice using the API instance
            $apiInstance = new InvoiceApi();
            $generateInvoice = $apiInstance->createInvoice($createdInvoice);

            // Check if the response has the necessary property
            if (!isset($generateInvoice['invoice_url'])) {
                throw new \Exception('Invoice URL not found in the response');
            }

            // Save the payment order to the database
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

//    public function webhook(Request $request)
//    {
//        try {
//            // Retrieve the invoice using the ID from the webhook request
//            $getInvoice = \Xendit\Invoice\Invoice::retrieve($request->id);
//
//            // Find the corresponding payment record from the database
//            $payment = Payment::where('external_id', $request->external_id)->firstOrFail();
//
//            // Check if the payment status is already 'settled'
//            if ($payment->status == 'settled') {
//                return response()->json([
//                    "data" => "Payment has already been processed"
//                ], 200); // Return 200 OK if payment is already processed
//            }
//
//            // Update the payment status based on the invoice status
//            $payment->status = strtolower($getInvoice['status']);
//            $payment->save();
//
//            // Return a success response
//            return response()->json([
//                "data" => "Payment status updated successfully"
//            ], 200);
//
//        } catch (\Exception $e) {
//            // Handle errors and return a response with error message
//            return response()->json([
//                "error" => $e->getMessage()
//            ], 500);
//        }
//    }


}

