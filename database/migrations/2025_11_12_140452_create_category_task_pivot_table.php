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
        Schema::create('category_task', function (Blueprint $table) {
            // Ini tidak butuh 'id', hanya 2 foreign key

            // 1. Kunci untuk Category
            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            // 2. Kunci untuk Task
            $table->foreignId('task_id')
                ->constrained()
                ->onDelete('cascade');

            // (Opsional) Tetapkan kedua kolom sebagai "Primary Key"
            // untuk mencegah duplikasi (Task 1 tidak bisa dilink ke Kategori 2 dua kali)
            $table->primary(['category_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_task_pivot');
    }
};
