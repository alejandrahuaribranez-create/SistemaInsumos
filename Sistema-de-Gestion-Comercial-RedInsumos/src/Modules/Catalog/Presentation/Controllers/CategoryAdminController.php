<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Controllers;

use RedInsumos\Modules\Catalog\Application\ManageCategories;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use Throwable;

final class CategoryAdminController
{
    public function __construct(
        private ManageCategories $categories,
        private ViewRenderer $views,
        private Session $session
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/categorias.php',
            [
                'title' => 'Gestionar categorías',
                'categories' => $this->categories->list(),
                'editingCategory' => null,
            ]
        );
    }

    public function create(Request $request): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException(
                    'La sesión del formulario no es válida. Recarga la página.'
                );
            }

            $this->categories->create($request->body());

            $this->session->flash(
                'success',
                'La categoría fue creada correctamente.'
            );
        } catch (Throwable $exception) {
            $this->session->flash(
                'danger',
                $exception->getMessage()
            );
        }

        return Response::redirect('/admin/categorias');
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->route('id', 0);
        $category = $this->categories->find($id);

        if ($category === null) {
            return $this->views->render(
                'src/Shared/Presentation/Views/errors/404.php',
                ['title' => 'Categoría no encontrada'],
                404
            );
        }

        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/categorias.php',
            [
                'title' => 'Editar categoría',
                'categories' => $this->categories->list(),
                'editingCategory' => $category,
            ]
        );
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->route('id', 0);

        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException(
                    'La sesión del formulario no es válida. Recarga la página.'
                );
            }

            $this->categories->update(
                $id,
                $request->body()
            );

            $this->session->flash(
                'success',
                'La categoría fue modificada correctamente.'
            );
        } catch (Throwable $exception) {
            $this->session->flash(
                'danger',
                $exception->getMessage()
            );
        }

        return Response::redirect('/admin/categorias');
    }

    public function toggleStatus(Request $request): Response
    {
        $id = (int) $request->route('id', 0);

        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException(
                    'La sesión no es válida. Recarga la página.'
                );
            }

            $this->categories->toggleStatus($id);

            $this->session->flash(
                'success',
                'El estado de la categoría fue actualizado.'
            );
        } catch (Throwable $exception) {
            $this->session->flash(
                'danger',
                $exception->getMessage()
            );
        }

        return Response::redirect('/admin/categorias');
    }

    public function delete(Request $request): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException('La sesión no es válida. Recarga la página.');
            }

            $this->categories->delete((int) $request->route('id', 0));
            $this->session->flash('success', 'La categoría fue eliminada físicamente.');
        } catch (Throwable $exception) {
            $this->session->flash('warning', $exception->getMessage());
        }

        return Response::redirect('/admin/categorias');
    }
}
