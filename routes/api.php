<?php

use App\Http\Controllers\Auth\AuthadminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AuthmitraController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OTPController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
// });


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::delete('/users/{id}', [AuthController::class, 'destroy']);
Route::put('/users/{user_id}/edit-bio', [AuthController::class, 'editBio']);
Route::post('forgot-password', [ForgotPasswordController::class, 'forgot']);

Route::post('send-otp', [OTPController::class, 'sendOTP']);
Route::post('verify-otp', [OTPController::class, 'verifyOTP']);
Route::post('registeradmin',[AuthadminController::class,'registeradmin']);
Route::post('loginadmin',[AuthadminController::class,'loginadmin']);

Route::group([
    "middleware" => ["auth:sanctum"]
], function(){

    Route::get('/notifications', [NotificationController::class, 'getUnreadNotifications']);
    Route::get('/notifications/all', [NotificationController::class, 'getAllNotifications']);
    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);
    Route::get('/mitra/{id}/notifications', [AuthmitraController::class, 'getNotifications']);


    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('/verify-email/{token}', [AuthController::class, 'apiVerifyEmail']);
    Route::post('reset-password', [ResetPasswordController::class, 'reset']);

    Route::post('/send-reset-password-email', [AuthController::class, 'sendResetPasswordEmail']);
    Route::post('/reset-password-profile', [AuthController::class, 'resetpassprofile']);
    Route::post('/reset-password', [AuthController::class, 'reset']);

    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/mitra',[AuthmitraController::class, 'index']);
    Route::get('barang', [\App\Http\Controllers\BarangController::class, 'index']);
    Route::get('/barangs/other-mitra', [BarangController::class, 'getAcceptedBarangsFromOtherMitra']);


    Route:: get("profile",[AuthController::class,"profile"]);
    Route::get("logout",[AuthController::class,"logout"]);
    Route::post('tambahpengikut', [ProfileController::class, 'tambahpengikut']);
    Route::get('/test',[ProductController::class,'test']);

    Route::post('add-pembayaran-cod', [\App\Http\Controllers\CODController::class, 'store']);
    Route::post('/checkout', [\App\Http\Controllers\CODController::class, 'multiplebarang']);
    Route::put('/cod/{id}/update-status',[\App\Http\Controllers\CODController::class, 'updateStatus']);
    Route::get('/user/{user_id}/cods', [\App\Http\Controllers\CODController::class, 'getUserCods']);
    Route::get('/mitra/{mitra_id}/cods', [\App\Http\Controllers\CODController::class, 'getMitraCods']);
    Route::put('/cod/{id}/complete', [\App\Http\Controllers\CODController::class, 'updateStatusToCompleted']);
    Route::get('/cods/{role}/{status}/{id}', [\App\Http\Controllers\CODController::class, 'getCodsByStatus']);
    Route::delete('/cod/cancel/{id}', [\App\Http\Controllers\CODController::class, 'cancelOrder']);
    Route::get('/cods/status/{status}', [\App\Http\Controllers\CODController::class, 'getAllByStatus']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
//    Route::post('/cart/select-item', [CartController::class, 'selectCartItems']);
    Route::post('/cart/checkout', [CartController::class, 'checkoutSelectedItems']);

    Route::get('allprofile', [ProfileController::class, 'allprofile']);
    Route::get('mitra/{id}', [ProfileController::class, 'allprofilebymitra']);
    Route::post('edit-profile', [ProfileController::class, 'editProfile']);

    Route::get('likes', [LikeController::class, 'index']);
    Route::post('likes', [LikeController::class, 'favorite']);
    Route::delete('likes/{barang_id}', [LikeController::class, 'unfavorite']);

    Route::post('/mitra/add-barang', [\App\Http\Controllers\BarangController::class, 'addBarang']);
    Route::post('/mitra/edit-barang/{id}', [\App\Http\Controllers\BarangController::class, 'editBarang']);
    Route::delete('/mitra/delete-barang/{id}', [\App\Http\Controllers\BarangController::class, 'deleteBarang']);
    Route::get('mitra/barang/{mitra_id}', [BarangController::class, 'getBarangsByMitraId']);

    Route::post('/create-invoice', [PaymentController::class, 'createInvoice']);
    Route::post('/create-checkout', [PaymentController::class, 'createInvoicemultiple']);
    Route::post('/payments/webhook/xendit', [PaymentController::class, 'webhook']);
    Route::get('/payments/status/{status}', [PaymentController::class, 'getallByStatus']);
    Route::get('/payments/{role}/{status}/{id}', [PaymentController::class, 'getPaymentsByStatus']);

    Route::post('/profiles/image', [ProfileController::class, 'postImageProfile']);
    Route::post('/profile/image/{id}', [ProfileController::class, 'editImageProfile']);
    Route::delete('/profiles/image/{id}', [ProfileController::class, 'destroyImageProfile']);

    Route::get('/komentar', [\App\Http\Controllers\ReviewController::class, 'index']);
    Route::post('/add-komentar', [\App\Http\Controllers\ReviewController::class, 'store']);

    Route::apiResource('chat', ChatController::class)->only(['index', 'store', 'show']);
    Route::apiResource('chat_message', ChatMessageController::class)->only(['index', 'store']);
    Route::apiResource('user', UserController::class)->only(['index']);
}
);

Route::get('cod',[\App\Http\Controllers\CODController::class,'index']);

Route::get('/barang/{id}', [BarangController::class, 'show']);
Route::put('publish/{id}', [BarangController::class, 'publish']);
Route::put('unpublish/{id}', [BarangController::class, 'unpublish']);
Route::get('/accepted-barangs', [BarangController::class, 'getAcceptedBarangs']);
Route::get('/barangs/search', [BarangController::class, 'searchAcceptedBarangs']);
Route::get('/filter-products-by-mitra', [BarangController::class, 'filterProductsByMitra']);


Route::get('dataprofile', [ProfileController::class, 'dataprofile']);



Route::post('tambahjasa', [ProfileController::class, 'tambahjasa']);
Route::post( 'tambahproduct', [ProfileController::class, 'tambahproduct']);
Route::post('add-category', [\App\Http\Controllers\CategoryController::class, 'addCategory']);
Route::get('/barang/filter', [BarangController::class, 'filterProductsByCategory']);

Route::get("index",[AuthController::class,"index"]);



Route::get('lokasi', [\App\Http\Controllers\LokasiController::class, 'index']);
Route::get('/lokasi/{id}', [\App\Http\Controllers\LokasiController::class, 'show']);
Route::post('add-lokasi', [\App\Http\Controllers\LokasiController::class, 'lokasi']);



Route::put('accept/{id}', [AuthmitraController::class, 'accept']);
Route::get("mitra/show/{id}", [AuthmitraController::class, "show"])->middleware('auth:sanctum');
Route::delete('reject/{id}', [AuthmitraController::class, 'reject']);

Route::put('/mitras/{id}', [AuthmitraController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/mitras/{id}', [AuthmitraController::class, 'destroy'])->middleware('auth:sanctum');


Route::get("index", [AuthController::class, "index"]);

Route::post('/registermitra', [AuthmitraController::class, 'registermitra'])->middleware('auth:sanctum');
Route::put('mitra/{id}/accept', [AuthadminController::class, 'acceptMitra'])->middleware('auth:sanctum');

Route::delete('mitra/{id}/reject', [AuthadminController::class, 'rejectMitra']);

Route::post('/mitras/{id}/tambahpengikut', [AuthmitraController::class, 'tambahpengikut']);
Route::post('/mitras/{id}/tambahproduct', [AuthmitraController::class, 'tambahproduct']);
Route::get('storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);

    if (!Storage::exists($path)) {
        abort(404);
    }

    // Baca file dan dapatkan tipe konten
    $file = Storage::get($path);
    $type = Storage::mimeType($path);

    // Kembalikan respons dengan file dan tipe konten
    return response($file, 200)->header('Content-Type', $type);
})->where('path', '.*');



Route::get('/product',[ProductController::class, 'index']);
Route::put('/edit-products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

Route::post('/add-jasa', [JasaController::class, 'addJasa']);
Route::get('/jasa',[JasaController::class, 'index']);
Route::put('/jasas/{id}', [JasaController::class, 'update']);
Route::delete('/jasas/{id}', [JasaController::class, 'destroy']);



