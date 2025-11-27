<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return Category::where('user_id', $request->user()->id)
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50'
        ]);

        $validated['user_id'] = $request->user()->id;

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    public function show(Request $request, Category $category)
    {
        $this->authorizeCategory($request, $category);
        return $category->load('tasks');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeCategory($request, $category);

        $validated = $request->validate([
            'name' => 'required|string|max:50'
        ]);

        $category->update($validated);

        // PERBAIKAN DISINI:
        // Kembalikan object category langsung agar konsisten dengan Flutter.
        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorizeCategory($request, $category);
        $category->tasks()->detach();
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }

    private function authorizeCategory(Request $request, Category $category)
    {
        if ((int)$category->user_id !== (int)$request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}