<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Controllers;

use RedInsumos\Modules\Catalog\Application\BrowseCatalog;
use RedInsumos\Modules\Catalog\Application\GetProduct;
use RedInsumos\Modules\Catalog\Presentation\Support\ProductImageResolver;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class CatalogController
{
    public function __construct(
        private BrowseCatalog $browseCatalog,
        private GetProduct $getProduct,
        private ViewRenderer $views,
        private ProductImageResolver $productImages
    ) {
    }

    public function index(Request $request): Response
    {
        $term = trim((string) $request->input('q', ''));

        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/index.php',
            [
                'title' => 'Catálogo',
                'products' => $this->browseCatalog->execute($term),
                'term' => $term,
                'productImages' => $this->productImages,
            ]
        );
    }

    public function show(Request $request): Response
    {
        $product = $this->getProduct->execute((int) $request->route('id', 0));

        if ($product === null) {
            return $this->views->render(
                'src/Shared/Presentation/Views/errors/404.php',
                ['title' => 'Producto no encontrado'],
                404
            );
        }

        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/detail.php',
            [
                'title' => $product->name,
                'product' => $product,
                'productImages' => $this->productImages,
            ]
        );
    }
}
