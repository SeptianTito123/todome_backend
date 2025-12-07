<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('reminded_60')->default(0);
            $table->boolean('reminded_30')->default(0);
            $table->boolean('reminded_15')->default(0);
            $table->boolean('reminded_5')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'reminded_60',
                'reminded_30',
                'reminded_15',
                'reminded_5',
            ]);
        });
    }
};
