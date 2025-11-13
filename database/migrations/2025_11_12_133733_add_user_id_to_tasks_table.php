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
        Schema::table('tasks', function (Blueprint $table) {
            // Catatan: Menambahkan kolom 'user_id'
            $table->foreignId('user_id')      // 1. Buat kolom 'user_id'
                ->after('id')                 // 2. (Opsional) Posisikan setelah 'id'
                ->constrained()               // 3. Hubungkan ke 'id' di tabel 'users'
                ->onDelete('cascade');        // 4. Jika user dihapus, hapus juga task-nya
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            //
        });
    }
};
