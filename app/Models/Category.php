<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * Catatan: Kolom yang boleh diisi massal
     */
    protected $fillable = [
        'name',
        'user_id',
    ];

    /**
     * Catatan: Relasi "milik-satu" (Many-to-One)
     * Satu Kategori dimiliki oleh satu User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catatan: Relasi "banyak-ke-banyak" (Many-to-Many)
     * Satu Kategori bisa ada di banyak Task
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'category_task');
    }
}