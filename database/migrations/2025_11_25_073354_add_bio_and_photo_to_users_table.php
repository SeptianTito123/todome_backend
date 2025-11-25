<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('email'); // Tambah kolom Bio
            $table->string('profile_photo_path', 2048)->nullable()->after('bio'); // Tambah kolom Foto
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'profile_photo_path']);
        });
    }
};
