<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Assets;

use JsonException;
use RuntimeException;

final class AssetManifest
{
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(
        private string $manifestPath,
        private string $assetsRoot
    ) {
        if (!is_file($this->manifestPath) || !is_readable($this->manifestPath)) {
            throw new RuntimeException('El manifiesto de imágenes no está disponible.');
        }

        try {
            $decoded = json_decode((string) file_get_contents($this->manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El manifiesto de imágenes no es válido.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('El manifiesto de imágenes no tiene el formato esperado.');
        }

        $this->data = $decoded;
        $resolvedRoot = realpath($this->assetsRoot);
        $this->assetsRoot = $resolvedRoot !== false ? $resolvedRoot : $this->assetsRoot;
    }

    /** @return null|array{path: string, width: int, height: int} */
    public function hero(string $variant): ?array
    {
        $path = $this->data['hero'][$variant] ?? null;

        return is_string($path) ? $this->asset($path) : null;
    }

    /** @return null|array{key: string, label: string, path: string, width: int, height: int} */
    public function family(string $key, string $variant): ?array
    {
        $field = $variant === 'large' ? 'large' : 'thumb';

        foreach ($this->data['products'] ?? [] as $family) {
            if (!is_array($family) || ($family['key'] ?? null) !== $key) {
                continue;
            }

            $path = $family[$field] ?? null;
            $asset = is_string($path) ? $this->asset($path) : null;

            if ($asset === null) {
                return null;
            }

            return [
                'key' => $key,
                'label' => (string) ($family['label'] ?? $key),
                ...$asset,
            ];
        }

        return null;
    }

    /** @return null|array{path: string, width: int, height: int} */
    private function asset(string $relativePath): ?array
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));
        $candidate = $this->assetsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolved = realpath($candidate);

        if (
            $resolved === false
            || !is_file($resolved)
            || !str_starts_with($resolved, $this->assetsRoot . DIRECTORY_SEPARATOR)
            || strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) !== 'webp'
        ) {
            return null;
        }

        $size = getimagesize($resolved);

        if ($size === false) {
            return null;
        }

        return [
            'path' => '/assets/images/' . $relativePath,
            'width' => (int) $size[0],
            'height' => (int) $size[1],
        ];
    }
}
