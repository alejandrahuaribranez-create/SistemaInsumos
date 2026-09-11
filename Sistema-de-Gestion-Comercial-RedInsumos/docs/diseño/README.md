# Paquete visual RedInsumos

Recursos preparados para implementar la tercera propuesta visual de la página principal.

## Organización

- `home/`: ilustraciones exclusivas de la portada.
- `products/800/`: imágenes principales para tarjetas destacadas y fichas de producto.
- `products/320/`: miniaturas para categorías, listados y resultados de búsqueda.
- `assets-manifest.json`: relación entre cada categoría y sus archivos.
- `vista-previa-catalogo.webp`: hoja visual de referencia de las trece familias.

## Uso recomendado

- Utilizar las versiones de 320 px en carruseles, categorías y cuadrículas.
- Utilizar las versiones de 800 px en productos destacados y vistas ampliadas.
- Mantener los nombres almacenados en la base de datos como rutas relativas; no guardar la imagen como dato binario.
- Cargar únicamente la imagen visible en la portada con prioridad. Las demás deben usar carga diferida (`loading="lazy"`).
- Las imágenes no contienen precios, nombres comerciales ni textos de interfaz. Todo ese contenido debe provenir de HTML y de la base de datos.

## Integración de la portada

La ilustración `hero-network` posee transparencia real y puede colocarse sobre el degradado azul del bloque principal.

## Crecimiento del inventario

Cuando se agregue un producto real, se recomienda fotografiarlo o usar la imagen oficial del proveedor, convertirla a WebP y generar las mismas variantes de 320 y 800 px. Las imágenes de este paquete funcionan como recursos iniciales, categorías genéricas y respaldo visual.
