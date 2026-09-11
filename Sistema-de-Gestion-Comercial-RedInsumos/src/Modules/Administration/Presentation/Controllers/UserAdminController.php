<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Administration\Presentation\Controllers;

use RedInsumos\Modules\Administration\Application\ManageUsers;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use Throwable;

final class UserAdminController
{
    public function __construct(private ManageUsers $users, private Session $session, private ViewRenderer $views)
    {
    }

    public function index(Request $request): Response
    {
        return $this->render();
    }

    public function edit(Request $request): Response
    {
        $editing = $this->users->find((int) $request->route('id', 0));
        return $editing === null
            ? $this->views->render('src/Shared/Presentation/Views/errors/404.php', ['title' => 'Usuario no encontrado'], 404)
            : $this->render($editing);
    }

    public function create(Request $request): Response
    {
        return $this->write($request, fn () => $this->users->create($request->body()), 'Usuario creado correctamente.');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->route('id', 0);
        return $this->write(
            $request,
            fn () => $this->users->update($id, (int) $this->session->user()['id'], $request->body()),
            'Usuario actualizado correctamente.'
        );
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->route('id', 0);
        return $this->write(
            $request,
            fn () => $this->users->delete($id, (int) $this->session->user()['id']),
            'Usuario eliminado correctamente.'
        );
    }

    private function render(?array $editing = null): Response
    {
        return $this->views->render('src/Modules/Administration/Presentation/Views/users.php', [
            'title' => 'Administrar usuarios',
            'users' => $this->users->all(),
            'roles' => $this->users->roles(),
            'permissions' => ManageUsers::PERMISSIONS,
            'editingUser' => $editing,
        ]);
    }

    private function write(Request $request, callable $operation, string $success): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException('La sesión del formulario expiró. Recarga la página.');
            }
            $operation();
            $this->session->flash('success', $success);
        } catch (Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }

        return Response::redirect('/admin/usuarios');
    }
}
