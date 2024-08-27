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
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->text('deskripsi');
            $table->integer('harga');
            $table->double('rating_barang')->nullable()->default(0.0);
            $table->foreignId('category_id');
            $table->foreignId('mitra_id')->constrained('mitras')->onDelete('cascade');
            $table->string('status_post');
            $table->integer('stock_barang')->default(0);
            $table->integer('quantity')->default(1);
            $table->string('image_barang');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
