<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Controllers;

use InvalidArgumentException;
use RedInsumos\Modules\Catalog\Application\ManageBrands;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class BrandAdminController
{
    public function __construct(
        private ManageBrands $brands,
        private Session $session,
        private ViewRenderer $views
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/brands.php',
            [
                'title' => 'Marcas',
                'pageHeading' => 'Gestión de marcas',
                'pageEyebrow' => 'Administración',
                'pageDescription' => 'Administra las marcas disponibles en el catálogo.',
                'brands' => $this->brands->list(),
                'editingBrand' => null,
                'form' => [],
                'error' => null,
            ]
        );
    }

    public function create(Request $request): Response
    {
        if (!$this->session->isValidCsrf($request->input('_token'))) {
            return Response::text('Token CSRF inválido.', 419);
        }

        try {
            $this->brands->create(
                (string) $request->input('nombre', '')
            );

            $this->session->flash(
                'success',
                'La marca fue creada correctamente.'
            );

            return Response::redirect('/admin/marcas');
        } catch (InvalidArgumentException $exception) {
            return $this->renderError($request, null, $exception->getMessage());
        }
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->route('id', 0);
        $brand = $this->brands->find($id);

        if ($brand === null) {
            return $this->views->render(
                'src/Shared/Presentation/Views/errors/404.php',
                ['title' => 'Marca no encontrada'],
                404
            );
        }

        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/brands.php',
            [
                'title' => 'Editar marca',
                'pageHeading' => 'Editar marca',
                'pageEyebrow' => 'Administración',
                'pageDescription' => 'Modifica la marca seleccionada.',
                'brands' => $this->brands->list(),
                'editingBrand' => $brand,
                'form' => [
                    'nombre' => $brand->name,
                ],
                'error' => null,
            ]
        );
    }

    public function update(Request $request): Response
    {
        if (!$this->session->isValidCsrf($request->input('_token'))) {
            return Response::text('Token CSRF inválido.', 419);
        }

        $id = (int) $request->route('id', 0);

        try {
            $this->brands->update(
                $id,
                (string) $request->input('nombre', '')
            );

            $this->session->flash(
                'success',
                'La marca fue actualizada correctamente.'
            );

            return Response::redirect('/admin/marcas');
        } catch (InvalidArgumentException $exception) {
            return $this->renderError(
                $request,
                $this->brands->find($id),
                $exception->getMessage()
            );
        }
    }

    public function toggle(Request $request): Response
    {
        if (!$this->session->isValidCsrf($request->input('_token'))) {
            return Response::text('Token CSRF inválido.', 419);
        }

        try {
            $this->brands->toggleStatus(
                (int) $request->route('id', 0)
            );

            $this->session->flash(
                'success',
                'El estado de la marca fue actualizado.'
            );

            return Response::redirect('/admin/marcas');
        } catch (InvalidArgumentException $exception) {
            $this->session->flash(
                'danger',
                $exception->getMessage()
            );

            return Response::redirect('/admin/marcas');
        }
    }

    private function renderError(
        Request $request,
        mixed $brand,
        string $error
    ): Response {
        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/brands.php',
            [
                'title' => $brand !== null
                    ? 'Editar marca'
                    : 'Nueva marca',
                'pageHeading' => $brand !== null
                    ? 'Editar marca'
                    : 'Nueva marca',
                'pageEyebrow' => 'Administración',
                'pageDescription' => 'Administra las marcas del catálogo.',
                'brands' => $this->brands->list(),
                'editingBrand' => $brand,
                'form' => [
                    'nombre' => (string) $request->input('nombre', ''),
                ],
                'error' => $error,
            ],
            422
        );
    }
}