<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Presentation\Controllers;

use RedInsumos\Modules\Catalog\Application\ManageProducts;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RuntimeException;
use Throwable;

final class ProductSellerController
{
    public function __construct(private ManageProducts $products, private Session $session, private ViewRenderer $views, private string $publicRoot)
    {
    }

    public function index(Request $request): Response
    {
        return $this->render();
    }

    public function edit(Request $request): Response
    {
        $product = $this->products->find((int) $request->route('id', 0));
        return $product === null
            ? $this->views->render('src/Shared/Presentation/Views/errors/404.php', ['title' => 'Producto no encontrado'], 404)
            : $this->render($product);
    }

    public function create(Request $request): Response
    {
        return $this->write($request, fn (?string $image) => $this->products->create($request->body(), $image), 'Producto creado con stock inicial 0. Registra su entrada desde almacén.');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->route('id', 0);
        return $this->write($request, fn (?string $image) => $this->products->update($id, $request->body(), $image), 'Producto actualizado. El stock no fue modificado.');
    }

    private function render(?array $editing = null): Response
    {
        return $this->views->render('src/Modules/Catalog/Presentation/Views/seller/products.php', [
            'title' => 'Productos', 'products' => $this->products->all(),
            'relations' => $this->products->relations(), 'editingProduct' => $editing,
        ]);
    }

    private function write(Request $request, callable $operation, string $message): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new RuntimeException('La sesión del formulario expiró.');
            }
            $operation($this->storeUploadedImage());
            $this->session->flash('success', $message);
        } catch (Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect('/vendedor/productos');
    }

    private function storeUploadedImage(): ?string
    {
        $file = $_FILES['imagen'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('La imagen no pudo cargarse o supera 5 MB.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if ($mime !== 'image/webp' || getimagesize($tmp) === false) {
            throw new RuntimeException('La imagen debe ser WebP válida.');
        }
        $directory = $this->publicRoot . '/uploads/products';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
        }
        $name = bin2hex(random_bytes(16)) . '.webp';
        if (!move_uploaded_file($tmp, $directory . '/' . $name)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
        return 'uploads/products/' . $name;
    }
}
