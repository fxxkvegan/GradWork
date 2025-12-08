<?php

namespace App\Support\Presenters;

use App\Models\Product;
use App\Models\User;

use function array_map;
use function is_string;
use function str_starts_with;
use function url;

class ProductPresenter
{
    /**
     * 製品データをAPIレスポンス用の配列に変換する。
     */
    public static function present(Product $product): array
    {
        $productArray = $product->toArray();
        $productArray['image_url'] = self::imageUrls($product);
        $productArray['owner'] = self::presentOwner($product->user);
        unset($productArray['user']);

        return $productArray;
    }

    /**
     * Product::image_url カラムから完全なURL配列を生成する。
     */
    public static function imageUrls(Product $product): array
    {
        return self::convertToFullUrls(
            Product::decodeImageUrls($product->getRawOriginal('image_url'))
        );
    }

    public static function convertToFullUrls(array $relativePaths): array
    {
        return array_map(static function ($path) {
            if (!is_string($path) || $path === '') {
                return $path;
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            return url($path);
        }, $relativePaths);
    }

    public static function presentOwner(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'displayName' => $user->display_name,
            'avatarUrl' => self::normalizePublicUrl($user->avatar_url),
            'headerUrl' => self::normalizePublicUrl($user->header_url),
            'bio' => $user->bio,
            'location' => $user->location,
            'website' => $user->website,
        ];
    }

    public static function normalizePublicUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
