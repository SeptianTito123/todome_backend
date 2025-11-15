<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'task_id',
        'title',
        'is_completed',
    ];

    // Tipe data (agar 'is_completed' jadi true/false di JSON)
    protected $casts = [
        'is_completed' => 'boolean',
    ];

    // Relasi: Satu Subtask dimiliki oleh satu Task
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}