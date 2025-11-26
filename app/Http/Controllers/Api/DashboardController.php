<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Ringkasan Dashboard (total tugas, selesai, kategori, kalender)
     */
    public function summary()
    {
        $userId = auth()->id();

        return response()->json([
            'total_tasks' => Task::where('user_id', $userId)->count(),

            'completed_tasks' => Task::where('user_id', $userId)
                ->where('is_completed', true)
                ->count(),

            'pending_tasks' => Task::where('user_id', $userId)
                ->where('is_completed', false)
                ->count(),

            'today_tasks' => Task::where('user_id', $userId)
                ->whereDate('deadline', Carbon::today())
                ->count(),
        ]);
    }

    /**
     * Tugas per tanggal untuk halaman Kalender
     */
    public function calendarTasks(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $userId = auth()->id();
        $date = Carbon::parse($request->date)->toDateString();

        $tasks = Task::where('user_id', $userId)
            ->whereDate('deadline', $date)
            ->with('subtasks')
            ->get();

        return response()->json($tasks);
    }
}
