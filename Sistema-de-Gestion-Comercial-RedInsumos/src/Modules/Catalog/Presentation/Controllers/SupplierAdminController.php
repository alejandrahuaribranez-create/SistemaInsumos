<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Controllers;

use InvalidArgumentException;
use RedInsumos\Modules\Catalog\Application\ManageSuppliers;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class SupplierAdminController
{
    public function __construct(
        private ManageSuppliers $suppliers,
        private Session $session,
        private ViewRenderer $views
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/suppliers.php',
            [
                'title' => 'Proveedores',
                'pageHeading' => 'Gestión de proveedores',
                'pageEyebrow' => 'Administración',
                'pageDescription' => 'Administra los proveedores del catálogo.',
                'suppliers' => $this->suppliers->list(),
                'editingSupplier' => null,
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
            $this->suppliers->create($this->formData($request));

            $this->session->flash(
                'success',
                'El proveedor fue creado correctamente.'
            );

            return Response::redirect('/admin/proveedores');
        } catch (InvalidArgumentException $exception) {
            return $this->renderError(
                $request,
                null,
                $exception->getMessage()
            );
        }
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->route('id', 0);
        $supplier = $this->suppliers->find($id);

        if ($supplier === null) {
            return $this->views->render(
                'src/Shared/Presentation/Views/errors/404.php',
                ['title' => 'Proveedor no encontrado'],
                404
            );
        }

        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/suppliers.php',
            [
                'title' => 'Editar proveedor',
                'pageHeading' => 'Editar proveedor',
                'pageEyebrow' => 'Administración',
                'pageDescription' => 'Modifica la información del proveedor.',
                'suppliers' => $this->suppliers->list(),
                'editingSupplier' => $supplier,
                'form' => $this->supplierForm($supplier),
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
            $this->suppliers->update(
                $id,
                $this->formData($request)
            );

            $this->session->flash(
                'success',
                'El proveedor fue actualizado correctamente.'
            );

            return Response::redirect('/admin/proveedores');
        } catch (InvalidArgumentException $exception) {
            return $this->renderError(
                $request,
                $this->suppliers->find($id),
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
            $this->suppliers->toggleStatus(
                (int) $request->route('id', 0)
            );

            $this->session->flash(
                'success',
                'El estado del proveedor fue actualizado.'
            );

            return Response::redirect('/admin/proveedores');
        } catch (InvalidArgumentException $exception) {
            $this->session->flash(
                'danger',
                $exception->getMessage()
            );

            return Response::redirect('/admin/proveedores');
        }
    }

    private function formData(Request $request): array
    {
        return [
            'nit' => $request->input('nit'),
            'razon_social' => $request->input('razon_social'),
            'nombre_comercial' => $request->input('nombre_comercial'),
            'contacto' => $request->input('contacto'),
            'telefono' => $request->input('telefono'),
            'correo' => $request->input('correo'),
            'direccion' => $request->input('direccion'),
            'municipio' => $request->input('municipio'),
            'departamento' => $request->input('departamento'),
            'tiempo_entrega_dias' => $request->input(
                'tiempo_entrega_dias'
            ),
        ];
    }

    private function supplierForm(object $supplier): array
    {
        return [
            'nit' => $supplier->nit ?? '',
            'razon_social' => $supplier->businessName,
            'nombre_comercial' => $supplier->commercialName ?? '',
            'contacto' => $supplier->contact ?? '',
            'telefono' => $supplier->phone ?? '',
            'correo' => $supplier->email ?? '',
            'direccion' => $supplier->address ?? '',
            'municipio' => $supplier->municipality ?? '',
            'departamento' => $supplier->department ?? '',
            'tiempo_entrega_dias' => $supplier->deliveryDays ?? '',
        ];
    }

    private function renderError(
        Request $request,
        mixed $supplier,
        string $error
    ): Response {
        return $this->views->render(
            'src/Modules/Catalog/Presentation/Views/admin/suppliers.php',
            [
                'title' => $supplier !== null
                    ? 'Editar proveedor'
                    : 'Nuevo proveedor',
                'pageHeading' => $supplier !== null
                    ? 'Editar proveedor'
                    : 'Nuevo proveedor',
                'pageEyebrow' => 'Administración',
                'pageDescription' => 'Administra los proveedores del catálogo.',
                'suppliers' => $this->suppliers->list(),
                'editingSupplier' => $supplier,
                'form' => $this->formData($request),
                'error' => $error,
            ],
            422
        );
    }
}