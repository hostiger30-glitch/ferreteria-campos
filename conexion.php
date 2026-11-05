<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = mysqli_connect("localhost", "root", "", "bd_refacciones");
    mysqli_set_charset($conexion, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die(json_encode(["error" => "error al conectar: " . $e->getMessage()]));
}

function leerJson() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}
?>