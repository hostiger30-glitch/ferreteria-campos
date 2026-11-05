<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

if (!function_exists('responder')) {
    function responder($datos, $codigo = 200) {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('leerJson')) {
    function leerJson() {
        $datos = json_decode(file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }
}

$metodo = $_SERVER['REQUEST_METHOD'];
$transaccion = false;

try {
    // LISTAR PEDIDOS
    if ($metodo === 'GET') {
        $res = mysqli_query($conexion, 'SELECT * FROM pedidos ORDER BY id DESC');
        $pedidos = mysqli_fetch_all($res, MYSQLI_ASSOC);

        $res = mysqli_query($conexion, 'SELECT * FROM pedido_items');
        $items = mysqli_fetch_all($res, MYSQLI_ASSOC);

        foreach ($pedidos as &$p) {
            $p['items'] = array_values(array_filter(
                $items,
                function ($i) use ($p) {
                    return $i['pedido_id'] == $p['id'];
                }
            ));
        }
        unset($p);
        responder($pedidos);
    }

    // REGISTRAR PEDIDO
    if ($metodo === 'POST') {
        $d = leerJson();
        $nombre = trim($d['nombre'] ?? '');
        $direccion = trim($d['direccion'] ?? '');
        $telefono = trim($d['telefono'] ?? '');
        $items = $d['items'] ?? [];

        if ($nombre === '' || $direccion === '' || $telefono === '' || !is_array($items) || count($items) === 0) {
            responder(['error' => 'Datos incompletos'], 400);
        }

        $total = 0;
        foreach ($items as $i) {
            $precio = (float)($i['precio'] ?? 0);
            $cantidad = (int)($i['cantidad'] ?? 1);
            if ($precio < 0 || $cantidad < 1) {
                responder(['error' => 'Producto o cantidad inválida'], 400);
            }
            $total += $precio * $cantidad;
        }

        mysqli_begin_transaction($conexion);
        $transaccion = true;

        $stmt = mysqli_prepare($conexion, 'INSERT INTO pedidos (nombre, direccion, telefono, total) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssd', $nombre, $direccion, $telefono, $total);
        mysqli_stmt_execute($stmt);
        $pedidoId = mysqli_insert_id($conexion);

        $stmtItem = mysqli_prepare($conexion, 'INSERT INTO pedido_items (pedido_id, producto, precio) VALUES (?, ?, ?)');

        foreach ($items as $i) {
            $producto = (string)$i['nombre'];
            $precio = (float)$i['precio'];
            $cantidad = (int)($i['cantidad'] ?? 1);

            for ($j = 0; $j < $cantidad; $j++) {
                mysqli_stmt_bind_param($stmtItem, 'isd', $pedidoId, $producto, $precio);
                mysqli_stmt_execute($stmtItem);
            }
        }

        mysqli_commit($conexion);
        $transaccion = false;

        responder([
            'ok' => true,
            'pedidoId' => $pedidoId,
            'total' => $total
        ]);
    }

    // ELIMINAR PEDIDO
    if ($metodo === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            responder(['error' => 'ID inválido'], 400);
        }

        mysqli_begin_transaction($conexion);
        $transaccion = true;

        $stmt = mysqli_prepare($conexion, 'DELETE FROM pedidos WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 0) {
            mysqli_rollback($conexion);
            $transaccion = false;
            responder(['error' => 'Pedido no encontrado'], 404);
        }

        mysqli_commit($conexion);
        $transaccion = false;

        responder(['ok' => true]);
    }

    responder(['error' => 'Método no permitido'], 405);

} catch (Throwable $e) {
    if ($transaccion) {
        mysqli_rollback($conexion);
    }
    error_log($e->getMessage());
    responder(['error' => 'Error en el servidor. Revisa los registros de PHP.'], 500);
}
?>