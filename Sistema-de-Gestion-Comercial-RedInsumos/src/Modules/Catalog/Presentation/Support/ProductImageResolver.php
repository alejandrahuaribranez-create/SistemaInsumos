<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Support;

use RedInsumos\Modules\Catalog\Domain\Product;
use RedInsumos\Shared\Presentation\Assets\AssetManifest;
use RedInsumos\Shared\Presentation\Http\UrlGenerator;

final class ProductImageResolver
{
    /** @var array<string, string> */
    private const CATEGORY_FAMILIES = [
        'routers' => 'router',
        'switches' => 'switch',
        'cableado' => 'cableado',
        'fibra optica' => 'fibra',
        'conectores' => 'conectores',
    ];

    public function __construct(
        private string $publicRoot,
        private AssetManifest $manifest,
        private UrlGenerator $urls
    ) {
        $resolvedRoot = realpath($this->publicRoot);
        $this->publicRoot = $resolvedRoot !== false ? $resolvedRoot : $this->publicRoot;
    }

    /** @return null|array{src: string, width: int, height: int, fallback: bool} */
    public function product(Product $product, string $variant = 'thumb'): ?array
    {
        foreach ($this->declaredPaths($product->images, $variant) as $path) {
            $asset = $this->publicAsset($path);

            if ($asset !== null) {
                return [...$asset, 'fallback' => false];
            }
        }

        $fallback = $this->category($product->category, $variant);

        return $fallback === null ? null : [...$fallback, 'fallback' => true];
    }

    /** @return null|array{src: string, width: int, height: int} */
    public function category(string $category, string $variant = 'thumb'): ?array
    {
        $key = self::CATEGORY_FAMILIES[$this->normalize($category)] ?? null;

        if ($key === null) {
            return null;
        }

        $asset = $this->manifest->family($key, $variant === 'large' ? 'large' : 'thumb');

        return $asset === null ? null : [
            'src' => $this->urls->to($asset['path']),
            'width' => $asset['width'],
            'height' => $asset['height'],
        ];
    }

    /** @return list<string> */
    private function declaredPaths(array $images, string $variant): array
    {
        $keys = $variant === 'large'
            ? ['large', '800', 'detail', 'primary']
            : ['thumb', '320', 'thumbnail', 'primary'];
        $paths = [];

        foreach ($keys as $key) {
            if (isset($images[$key]) && is_string($images[$key])) {
                $paths[] = $images[$key];
            }
        }

        foreach ($images as $image) {
            if (is_string($image)) {
                $paths[] = $image;
            }
        }

        return array_values(array_unique($paths));
    }

    /** @return null|array{src: string, width: int, height: int} */
    private function publicAsset(string $relativePath): ?array
    {
        $relativePath = str_replace('\\', '/', ltrim(trim($relativePath), '/'));

        if ($relativePath === '' || !str_ends_with(strtolower($relativePath), '.webp')) {
            return null;
        }

        $candidate = $this->publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolved = realpath($candidate);

        if (
            $resolved === false
            || !is_file($resolved)
            || !str_starts_with($resolved, $this->publicRoot . DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        $normalized = str_replace('\\', '/', substr($resolved, strlen($this->publicRoot) + 1));

        if (!str_starts_with($normalized, 'uploads/products/') && !str_starts_with($normalized, 'assets/images/')) {
            return null;
        }

        $size = getimagesize($resolved);

        if ($size === false) {
            return null;
        }

        return [
            'src' => $this->urls->to('/' . $normalized),
            'width' => (int) $size[0],
            'height' => (int) $size[1],
        ];
    }

    private function normalize(string $value): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($value)));

        return $transliterated === false ? mb_strtolower(trim($value)) : $transliterated;
    }
}
