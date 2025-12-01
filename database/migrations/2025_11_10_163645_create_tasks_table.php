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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); // ID unik untuk setiap tugas
            $table->string('judul'); // Judul tugas
            $table->text('deskripsi')->nullable(); // Deskripsi, boleh kosong
            $table->boolean('status_selesai')->default(false); // Status, default-nya "belum selesai"
            $table->timestamp('deadline')->nullable(); // Batas waktu, boleh kosong
            $table->timestamps(); // Otomatis membuat 'created_at' dan 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
