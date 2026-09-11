<?php

declare(strict_types=1);

use RedInsumos\Modules\Catalog\Application\BrowseCatalog;
use RedInsumos\Modules\Catalog\Application\GetProduct;
use RedInsumos\Modules\Catalog\Application\ListActiveCategories;
use RedInsumos\Modules\Catalog\Application\ManageBrands;
use RedInsumos\Modules\Catalog\Application\ManageCategories;
use RedInsumos\Modules\Catalog\Application\ManageSuppliers;
use RedInsumos\Modules\Catalog\Infrastructure\MariaDbBrandRepository;
use RedInsumos\Modules\Catalog\Infrastructure\MariaDbCategoryRepository;
use RedInsumos\Modules\Catalog\Infrastructure\MariaDbProductRepository;
use RedInsumos\Modules\Catalog\Infrastructure\MariaDbSupplierRepository;
use RedInsumos\Modules\Catalog\Presentation\Controllers\BrandAdminController;
use RedInsumos\Modules\Catalog\Presentation\Controllers\CategoryAdminController;
use RedInsumos\Modules\Catalog\Presentation\Controllers\CatalogController;
use RedInsumos\Modules\Catalog\Presentation\Controllers\HomeController;
use RedInsumos\Modules\Catalog\Presentation\Controllers\SupplierAdminController;
use RedInsumos\Modules\Catalog\Presentation\Support\ProductImageResolver;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Assets\AssetManifest;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\UrlGenerator;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (
    Router $router,
    \PDO $pdo,
    Session $session,
    ViewRenderer $views
): void {
    $projectRoot = dirname(__DIR__, 4);

    /*
     * CATÁLOGO PÚBLICO
     */

    $productRepository = new MariaDbProductRepository($pdo);

    $browseCatalog = new BrowseCatalog($productRepository);

    $assets = new AssetManifest(
        $projectRoot . '/public/assets/images/assets-manifest.json',
        $projectRoot . '/public/assets/images'
    );

    $productImages = new ProductImageResolver(
        $projectRoot . '/public',
        $assets,
        UrlGenerator::fromEnvironment()
    );

    $catalog = new CatalogController(
        $browseCatalog,
        new GetProduct($productRepository),
        $views,
        $productImages
    );

    $home = new HomeController(
        $browseCatalog,
        new ListActiveCategories(
            new MariaDbCategoryRepository($pdo)
        ),
        $views,
        $assets,
        $productImages
    );

    $router->get('/', [$home, 'index']);
    $router->get('/catalogo', [$catalog, 'index']);
    $router->get('/productos/{id}', [$catalog, 'show']);

    /*
     * ADMINISTRACIÓN DEL CATÁLOGO
     */

    $roles = new RoleMiddleware($session, $views, $pdo);

    /*
     * CATEGORÍAS
     */


    $categoryController = new CategoryAdminController(
        new ManageCategories(
            new MariaDbCategoryRepository($pdo)
        ),
        $views,
        $session
    );

    $router->get(
        '/admin/categorias',
        static function ($request) use (
            $roles,
            $categoryController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $categoryController->index($request);
        }
    );

    $router->post(
        '/admin/categorias',
        static function ($request) use (
            $roles,
            $categoryController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $categoryController->create($request);
        }
    );

    $router->get(
        '/admin/categorias/{id}/editar',
        static function ($request) use (
            $roles,
            $categoryController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $categoryController->edit($request);
        }
    );

    $router->post(
        '/admin/categorias/{id}',
        static function ($request) use (
            $roles,
            $categoryController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $categoryController->update($request);
        }
    );

    $router->post(
        '/admin/categorias/{id}/estado',
        static function ($request) use (
            $roles,
            $categoryController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $categoryController->toggleStatus($request);
        }
    );

    $router->post(
        '/admin/categorias/{id}/eliminar',
        static function ($request) use ($roles, $categoryController) {
            return $roles->handle(['ADMIN'])
                ?? $categoryController->delete($request);
        }
    );

    /*
     * MARCAS
     */

    $brandController = new BrandAdminController(
        new ManageBrands(
            new MariaDbBrandRepository($pdo)
        ),
        $session,
        $views
    );

    $router->get(
        '/admin/marcas',
        static function ($request) use (
            $roles,
            $brandController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $brandController->index($request);
        }
    );

    $router->post(
        '/admin/marcas',
        static function ($request) use (
            $roles,
            $brandController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $brandController->create($request);
        }
    );

    $router->get(
        '/admin/marcas/{id}/editar',
        static function ($request) use (
            $roles,
            $brandController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $brandController->edit($request);
        }
    );

    $router->post(
        '/admin/marcas/{id}',
        static function ($request) use (
            $roles,
            $brandController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $brandController->update($request);
        }
    );

    $router->post(
        '/admin/marcas/{id}/estado',
        static function ($request) use (
            $roles,
            $brandController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $brandController->toggle($request);
        }
    );

    /*
     * PROVEEDORES
     */

    $supplierController = new SupplierAdminController(
        new ManageSuppliers(
            new MariaDbSupplierRepository($pdo)
        ),
        $session,
        $views
    );

    $router->get(
        '/admin/proveedores',
        static function ($request) use (
            $roles,
            $supplierController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $supplierController->index($request);
        }
    );

    $router->post(
        '/admin/proveedores',
        static function ($request) use (
            $roles,
            $supplierController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $supplierController->create($request);
        }
    );

    $router->get(
        '/admin/proveedores/{id}/editar',
        static function ($request) use (
            $roles,
            $supplierController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $supplierController->edit($request);
        }
    );

    $router->post(
        '/admin/proveedores/{id}',
        static function ($request) use (
            $roles,
            $supplierController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $supplierController->update($request);
        }
    );

    $router->post(
        '/admin/proveedores/{id}/estado',
        static function ($request) use (
            $roles,
            $supplierController
        ) {
            return $roles->handle(['ADMIN'])
                ?? $supplierController->toggle($request);
        }
    );
};
