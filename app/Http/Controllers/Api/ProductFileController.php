<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

use function in_array;
use function pathinfo;
use function strtolower;
use function strlen;
use function substr;

class ProductFileController extends Controller
{
    private const PREVIEW_MAX_BYTES = 200 * 1024;

    private const PREVIEW_ALLOWLIST = [
        'md', 'txt', 'mdx', 'json', 'yaml', 'yml', 'csv',
        'ts', 'tsx', 'js', 'jsx', 'mjs', 'cjs', 'css', 'scss', 'sass', 'less',
        'html', 'htm', 'php', 'py', 'rb', 'go', 'rs', 'java', 'c', 'cpp',
    ];

    public function tree(Product $product)
    {
        $files = $product->files()
            ->orderBy('path')
            ->get(['path', 'type', 'size', 'is_previewable']);

        return response()->json([
            'items' => $files->map(static function ($file) {
                return [
                    'path' => $file->path,
                    'type' => $file->type ?? 'file',
                    'size' => $file->size,
                    'is_previewable' => (bool) $file->is_previewable,
                ];
            }),
            'count' => $files->count(),
        ]);
    }

    public function preview(Request $request, Product $product)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2000'],
        ]);

        $path = $validated['path'];

        $file = $product->files()->where('path', $path)->first();
        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        if (!$file->is_previewable || !$this->isAllowlisted($file->path)) {
            return response()->json(['message' => 'Preview not allowed'], 403);
        }

        $previewText = $file->preview_text ?? '';
        $maxBytes = self::PREVIEW_MAX_BYTES;
        $isTruncated = strlen($previewText) > $maxBytes;
        $content = $isTruncated ? substr($previewText, 0, $maxBytes) : $previewText;

        return response()->json([
            'path' => $file->path,
            'content' => $content,
            'truncated' => $isTruncated,
        ]);
    }

    public function downloadIntent(Request $request, Product $product)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2000'],
        ]);

        $file = $product->files()->where('path', $validated['path'])->first();
        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        if ($product->file_status !== 'approved') {
            return response()->json([
                'message' => 'Downloads are currently restricted for this product.',
            ], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function upsertReadme(Request $request, Product $product)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:200000'],
        ]);

        $user = $request->user();
        if (!$user || (int) $product->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $content = $validated['content'];

        $file = $product->files()->updateOrCreate(
            ['path' => 'README.md'],
            [
                'type' => 'file',
                'size' => strlen($content),
                'mime' => 'text/markdown',
                'is_previewable' => true,
                'preview_text' => $content,
            ],
        );

        return response()->json([
            'ok' => true,
            'path' => $file->path,
        ]);
    }

    private function isAllowlisted(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        return $extension !== '' && in_array($extension, self::PREVIEW_ALLOWLIST, true);
    }
}
