<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $column = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $column->execute(['database' => $database, 'table' => 'ventas', 'column' => 'token_confirmacion']);
    if ((int) $column->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE ventas ADD COLUMN token_confirmacion CHAR(64) NULL AFTER codigo_pedido, ADD UNIQUE KEY uq_ventas_token_confirmacion (token_confirmacion)');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS vendedor_carrito_items (
        id_usuario INT NOT NULL,
        id_producto INT NOT NULL,
        cantidad INT NOT NULL,
        fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_usuario, id_producto),
        CONSTRAINT fk_vendedor_carrito_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_vendedor_carrito_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT chk_vendedor_carrito_cantidad CHECK (cantidad > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS envios (
        id_envio INT NOT NULL AUTO_INCREMENT,
        id_venta INT NOT NULL,
        id_usuario INT NOT NULL,
        destinatario VARCHAR(150) NOT NULL,
        telefono VARCHAR(20) DEFAULT NULL,
        direccion VARCHAR(255) NOT NULL,
        transportista VARCHAR(100) DEFAULT NULL,
        numero_guia VARCHAR(100) DEFAULT NULL,
        estado ENUM('pendiente','preparando','despachado','entregado') NOT NULL DEFAULT 'pendiente',
        observacion VARCHAR(500) DEFAULT NULL,
        fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_envio),
        UNIQUE KEY uq_envios_venta (id_venta),
        CONSTRAINT fk_envios_venta FOREIGN KEY (id_venta) REFERENCES ventas (id_venta) ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT fk_envios_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
