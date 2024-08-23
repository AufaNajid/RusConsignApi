<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                'status' => 'pending',
            ]);
            $order->save();

            return response()->json($generateInvoice, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

//    public function notificationCallback(Request $request)
//    {
//        $getToken = $request->headers->get('x-callback-token');
//        $callbackToken = env('XENDIT_CALLBACK_TOKEN');
//
//        try{
//            return response()->json([
//                'status' => 'check token',
//                'massage' => 'check token from xendit',
//                'token' => $getToken,
//            ],Resposnse::HTTP_OK);
//        }catch (
//
//        )
//    }

}

