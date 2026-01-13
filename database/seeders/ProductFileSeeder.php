<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductFile;
use Illuminate\Database\Seeder;

class ProductFileSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()->orderBy('id')->first();

        if (!$product) {
            return;
        }

        $product->update(['file_status' => 'approved']);

        $files = [
            [
                'path' => 'README.md',
                'type' => 'file',
                'mime' => 'text/markdown',
                'is_previewable' => true,
                'preview_text' => "# Sample Project\n\nThis is a demo README seeded for preview.\n\n- Built with Laravel + React\n- Includes DM, reviews, and product catalog\n",
            ],
            [
                'path' => 'src/pages/index.tsx',
                'type' => 'file',
                'mime' => 'text/x-typescript',
                'is_previewable' => true,
                'preview_text' => "import React from 'react';\n\nexport const App = () => {\n  return <h1>Hello from seeded preview file</h1>;\n};\n",
            ],
            [
                'path' => 'docs/architecture.pdf',
                'type' => 'file',
                'mime' => 'application/pdf',
                'is_previewable' => false,
                'preview_text' => null,
            ],
        ];

        foreach ($files as $file) {
            $size = $file['preview_text'] !== null ? strlen($file['preview_text']) : null;

            ProductFile::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'path' => $file['path'],
                ],
                [
                    'type' => $file['type'],
                    'size' => $size,
                    'mime' => $file['mime'],
                    'is_previewable' => $file['is_previewable'],
                    'preview_text' => $file['preview_text'],
                ]
            );
        }
    }
}
