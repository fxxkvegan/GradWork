<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web開発',
                'image' => 'https://img.icons8.com/ios/100/code--v1.png',
            ],
            [
                'name' => 'モバイル開発',
                'image' => 'https://img.icons8.com/ios/100/smartphone-tablet.png',
            ],
            [
                'name' => 'AI',
                'image' => 'https://img.icons8.com/ios/100/artificial-intelligence.png',
            ],
            [
                'name' => 'デザイン',
                'image' => 'https://img.icons8.com/ios/100/design--v1.png',
            ],
            [
                'name' => 'ゲーム',
                'image' => 'https://img.icons8.com/ios/100/controller.png',
            ],
            [
                'name' => 'AR/VR',
                'image' => 'https://img.icons8.com/ios/100/virtual-reality.png',
            ],
            [
                'name' => 'IoT',
                'image' => 'https://img.icons8.com/ios/100/iot-sensor.png',
            ],
            [
                'name' => 'ビジネス',
                'image' => 'https://img.icons8.com/ios/100/business.png',
            ],
            [
                'name' => '便利ツール',
                'image' => 'https://img.icons8.com/ios/100/swiss-army-knife.png',
            ],
            [
                'name' => 'セキュリティ',
                'image' => 'https://img.icons8.com/ios/100/shield.png',
            ],
            [
                'name' => '動画/映像',
                'image' => 'https://img.icons8.com/ios/100/video.png',
            ],
            [
                'name' => '教育/学習',
                'image' => 'https://img.icons8.com/ios/100/graduation-cap.png',
            ],
            [
                'name' => 'ヘルスケア',
                'image' => 'https://img.icons8.com/ios/100/health-book.png',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['image' => $category['image']]
            );
        }
    }
}
