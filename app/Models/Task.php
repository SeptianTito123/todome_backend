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
        'user_id', 
        'judul',
        'deskripsi',
        'status_selesai',
        'is_starred',
        'deadline',
        'recurrence',
        'notified_overdue',
        'reminded_60',
        'reminded_30',
        'reminded_15',
        'reminded_5',
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
    public function subtasks()
    {
        return $this->hasMany(Subtask::class);
    }
}