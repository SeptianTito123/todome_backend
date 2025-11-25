<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Wajib untuk mengambil user
use Illuminate\Support\Facades\DB;   // Wajib untuk query statistik
use Carbon\Carbon;                  // Wajib untuk mengelola tanggal

class DashboardController extends Controller
{
    /**
     * Catatan: Mengambil data ringkasan untuk Halaman Profile
     * (GET /api/dashboard/summary)
     */
    public function summary(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 1. Hitung Total Tugas
        $total_selesai = $user->tasks()
            ->where('status_selesai', 1)
            ->count();

        $total_belum_selesai = $user->tasks()
            ->where('status_selesai', 0)
            ->count();

        $total_tertunda = $user->tasks()
            ->where('status_selesai', 0)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', now()->toDateString())
            ->count();

        // 2. Ambil 5 Tugas Belum Selesai (terbaru)
        $tugas_belum_selesai = $user->tasks()
                                ->with('categories') // Ambil kategorinya juga
                                ->where('status_selesai', false)
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
        
        // 3. Data untuk Pie Chart (Tugas yang belum selesai berdasarkan Kategori)
        // Ini query yang sedikit rumit:
        // - Mengambil tugas user yang belum selesai
        // - Bergabung (join) ke tabel pivot 'category_task'
        // - Bergabung (join) ke tabel 'categories'
        // - Mengelompokkan berdasarkan nama kategori dan menghitungnya
        $pie_chart_data = $user->tasks()
                            ->where('status_selesai', false)
                            ->join('category_task', 'tasks.id', '=', 'category_task.task_id')
                            ->join('categories', 'category_task.category_id', '=', 'categories.id')
                            ->select('categories.name', DB::raw('count(*) as total'))
                            ->groupBy('categories.name')
                            ->get();

        // 4. Data untuk Line Chart (Jumlah tugas DIBUAT per 7 hari terakhir)
        $line_chart_data = $user->tasks()
                            ->select(DB::raw('DATE(created_at) as tanggal'), DB::raw('count(*) as total'))
                            ->where('created_at', '>=', $now->copy()->subDays(6)) // 7 hari (termasuk hari ini)
                            ->groupBy('tanggal')
                            ->orderBy('tanggal', 'asc')
                            ->get();


        // Kembalikan semua data dalam satu JSON
        return response()->json([
            'total_selesai' => $total_selesai,
            'total_tertunda' => $total_tertunda,
            'total_belum_selesai' => $total_belum_selesai,
            'tugas_belum_selesai_list' => $tugas_belum_selesai,
            'pie_chart_data' => $pie_chart_data,
            'line_chart_data' => $line_chart_data,
        ]);
    }

    /**
     * Catatan: Mengambil tugas untuk Halaman Kalender
     * Menerima query parameter ?date=YYYY-MM-DD
     * (GET /api/calendar/tasks)
     */
    public function calendarTasks(Request $request)
    {
        $user = Auth::user();

        // 1. Validasi input tanggal
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d', // Wajib format YYYY-MM-DD
        ]);

        $tanggal = $validated['date'];

        // 2. Ambil semua tugas yang 'deadline'-nya pada tanggal tersebut
        $tasks = $user->tasks()
                    ->with('categories')
                    ->whereDate('deadline', $tanggal) // Mencocokkan HANYA tanggal (bukan jam)
                    ->orderBy('deadline', 'asc')
                    ->get();
        
        return response()->json($tasks);
    }
}