<?php
require 'conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // LISTAR productos
    if ($metodo === 'GET') {
        $res = mysqli_query($conexion, 'SELECT * FROM productos ORDER BY id');
        responder(mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    // ALTA de producto
    if ($metodo === 'POST') {
        $d = leerJson();
        $nombre = trim($d['nombre'] ?? '');
        $precio = $d['precio'] ?? '';

        if ($nombre === '' || !is_numeric($precio) || $precio < 0) {
            responder(['error' => 'Nombre y precio válidos son obligatorios'], 400);
        }

        $precio = (float) $precio;
        $stmt = mysqli_prepare($conexion, 'INSERT INTO productos (nombre, precio) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'sd', $nombre, $precio);
        mysqli_stmt_execute($stmt);
        responder(['ok' => true, 'id' => mysqli_insert_id($conexion)]);
    }

    // BAJA de producto (productos.php?id=3)
    if ($metodo === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = mysqli_prepare($conexion, 'DELETE FROM productos WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            responder(['error' => 'Producto no encontrado'], 404);
        }
        responder(['ok' => true]);
    }

    responder(['error' => 'Método no permitido'], 405);

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    responder(['error' => 'Error en la base de datos'], 500);
}
?>