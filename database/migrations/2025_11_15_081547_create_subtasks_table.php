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
        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')      // 1. Ini sub-tugas milik siapa?
                ->constrained('tasks')      // 2. Terhubung ke tabel 'tasks'
                ->onDelete('cascade');    // 3. Jika task dihapus, checklist-nya ikut

            $table->string('title');            // 4. Judul (misal: "Beli Susu")
            $table->boolean('is_completed')->default(false); // 5. Status (sudah/belum)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subtasks');
    }
};
