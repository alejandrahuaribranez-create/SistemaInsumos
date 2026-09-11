# Sistema de Gestión Comercial RedInsumos

Base técnica del MVP construida con PHP 8, PDO y una Clean Architecture pragmática organizada por dominios. MVC vive dentro de la capa `Presentation` de cada módulo.

## Requisitos

- PHP 8.1 o superior con la extensión PDO MySQL.
- Composer 2.
- Apache y MariaDB (previstos mediante XAMPP).
- La base oficial `db_redinsumos_bolivia_mvp_v1_1_OFICIAL.sql`.

## Preparación local

1. Copiar `.env.example` como `.env` y completar los datos locales.
2. Ejecutar `composer dump-autoload`.
3. Configurar Apache para exponer únicamente el directorio `public/`.

La aplicación no abre una conexión a la base al arrancar. Cada caso de uso que necesite persistencia deberá crearla mediante `PdoConnectionFactory` e inyectarla en su repositorio.

## Módulos iniciales

- `Identity`: identidad y acceso.
- `Catalog`: catálogo y proveedores.
- `Sales`: ventas y pedidos.
- `Payments`: pagos y conciliación.
- `Inventory`: inventario y compras.
- `Administration`: administración y auditoría.

Esta fase contiene únicamente el núcleo técnico; todavía no implementa funcionalidad de negocio.
