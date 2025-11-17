<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /categories - シンプルなカテゴリ一覧
    public function index()
    {
        $categories = Category::all(['name', 'description']);
        return response()->json([
            'items' => $categories,
            'total' => $categories->count()
        ], 200);
    }

    // POST /categories - カテゴリ作成
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($category, 201);
    }
}
