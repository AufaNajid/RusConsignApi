<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id');
            $table->foreignId('user_id');
            $table->string('external_id');
            $table->string('no_transaction');
            $table->integer('quantity');
            $table->string('invoice_url');
            $table->string('grand_total');
            $table->string('status')->default('belum_pembayaran');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
