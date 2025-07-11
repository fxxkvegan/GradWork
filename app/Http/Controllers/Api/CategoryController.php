<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /categories
    public function index()
    {
        $categories = Category::all();
        
        return response()->json([
            'message' => 'List of categories',
            'items' => $categories,
            'total' => $categories->count()
        ]);
    }

    // POST /categories
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

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    // GET /categories/{categoryId}
    public function show($categoryId)
    {
        $categoryId = intval($categoryId);
        if ($categoryId <= 0) {
            return response()->json([
                'message' => 'Invalid category ID',
                'data' => $categoryId
            ], 400);
        }

        $category = Category::findOrFail($categoryId);

        return response()->json([
            'message' => 'Category details',
            'data' => $category
        ]);
    }

    // PUT /categories/{categoryId}
    public function update(Request $request, $categoryId)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $categoryId,
            'description' => 'nullable|string',
        ]);

        $categoryId = intval($categoryId);
        if ($categoryId <= 0) {
            return response()->json([
                'message' => 'Invalid category ID',
                'data' => $categoryId
            ], 400);
        }

        $category = Category::findOrFail($categoryId);
        $category->name = $request->name;
        $category->description = $request->description;
        $category->save();

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    // DELETE /categories/{categoryId}
    public function destroy($categoryId)
    {
        $categoryId = intval($categoryId);
        if ($categoryId <= 0) {
            return response()->json([
                'message' => 'Invalid category ID',
                'data' => $categoryId
            ], 400);
        }

        $category = Category::findOrFail($categoryId);
        $category->delete();

        return response()->json(null, 204);
    }

    // GET /categories/{categoryId}/products
    public function products($categoryId)
    {
        $categoryId = intval($categoryId);
        if ($categoryId <= 0) {
            return response()->json([
                'message' => 'Invalid category ID',
                'data' => $categoryId
            ], 400);
        }

        $category = Category::with('products')->findOrFail($categoryId);

        return response()->json([
            'message' => 'Products in category',
            'items' => $category->products,
            'total' => $category->products->count()
        ]);
    }
}
