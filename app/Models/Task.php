<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * Kolom-kolom yang boleh diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // <-- 1. TAMBAHKAN INI
        'judul',
        'deskripsi',
        'status_selesai',
        'deadline',
    ];

    /**
     * Catatan: Mendefinisikan relasi 'milik-satu'.
     * Satu Task DIMILIKI OLEH satu User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_task');
    }
}