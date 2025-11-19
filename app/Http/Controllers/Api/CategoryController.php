<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use APP\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * GET /categories - カテゴリ一覧
     * 各カテゴリの商品数と画像URLを含む
     */
    public function index()
    {
        $categories = Category::withCount('products')
            ->get(['id', 'name', 'image'])
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => $category->image ? url($category->image) : null, 
                    'products_count' => $category->products_count, 
                ];
            });
        
        return response()->json([
            'items' => $categories,
            'total' => $categories->count()
        ], 200);
    }

    /**
     * POST /categories - カテゴリ作成
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048', 
        ]);

        // 画像ファイルの処理
        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $imagePath = Storage::url($path);
        }

        $category = Category::create([
            'name' => $request->name,
            'image' => $imagePath,
        ]);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'image' => $category->image ? url($category->image) : null,
            'products_count' => 0,  // 新規作成時は0
        ], 201);
    }

    /**
     * PUT /categories/{id} - カテゴリ更新
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $id,
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // 画像ファイルの処理
        if ($request->hasFile('image')) {
            // 古い画像を削除
            if ($category->image) {
                $oldPath = str_replace('/storage/', '', parse_url($category->image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }
            // 新しい画像を保存
            $path = $request->file('image')->store('categories', 'public');
            $category->image = Storage::url($path);
        }
        // 名前の更新
        if ($request->has('name')) {
            $category->name = $request->name;
        }

        $category->save();

        // 商品数を再取得
        $category->loadCount('products');

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'image' => $category->image ? url($category->image) : null,
            'products_count' => $category->products_count,
        ], 200);
    }

    /**
     * DELETE /categories/{id} - カテゴリ削除
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // 画像ファイルを削除
        if ($category->image) {
            $path = str_replace('/storage/', '', parse_url($category->image, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
