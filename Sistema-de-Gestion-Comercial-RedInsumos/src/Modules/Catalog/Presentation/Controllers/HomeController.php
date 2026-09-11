<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Controllers;

use RedInsumos\Modules\Catalog\Application\BrowseCatalog;
use RedInsumos\Modules\Catalog\Application\ListActiveCategories;
use RedInsumos\Modules\Catalog\Presentation\Support\ProductImageResolver;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Assets\AssetManifest;

final class HomeController
{
    public function __construct(
        private BrowseCatalog $browseCatalog,
        private ListActiveCategories $listCategories,
        private ViewRenderer $views,
        private AssetManifest $assets,
        private ProductImageResolver $productImages
    ) {
    }

    public function index(Request $request): Response
    {
        $products = $this->browseCatalog->execute();
        $featuredProduct = null;

        foreach ($products as $product) {
            if ($product->stock > 0) {
                $featuredProduct = $product;
                break;
            }
        }

        $featuredProduct ??= $products[0] ?? null;
        $categories = array_map(
            fn ($category): array => [
                'category' => $category,
                'image' => $this->productImages->category($category->name, 'thumb'),
            ],
            $this->listCategories->execute()
        );

        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/home.php',
            [
                'title' => 'Inicio',
                'featuredProduct' => $featuredProduct,
                'productImages' => $this->productImages,
                'categories' => $categories,
                'familyHighlights' => array_slice(
                    array_values(array_filter($categories, static fn (array $item): bool => $item['image'] !== null)),
                    0,
                    2
                ),
                'heroDesktop' => $this->assets->hero('desktop'),
                'heroCompact' => $this->assets->hero('compact'),
            ]
        );
    }
}
