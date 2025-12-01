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
        // Hapus tabel lama jika ada (untuk memastikan bersih)
        Schema::dropIfExists('tasks');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            
            // 1. RELASI USER (WAJIB ADA agar tidak error Auth)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            
            $table->boolean('status_selesai')->default(false);
            
            // 2. FITUR BINTANG (WAJIB ADA agar tidak error di Flutter)
            $table->boolean('is_starred')->default(false);
            
            $table->timestamp('deadline')->nullable();
            
            // 3. FITUR BERULANG (Kolom Baru)
            $table->string('recurrence')->nullable()->default('none');
            
            $table->timestamps();
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