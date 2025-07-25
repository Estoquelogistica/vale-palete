<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->tipo_palete) || !isset($data->valor)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dados incompletos."]);
    exit();
}

$query = "INSERT INTO valor_unitario (tipo_palete, valor) VALUES (:tipo_palete, :valor)";
$stmt = $db->prepare($query);

$stmt->bindParam(':tipo_palete', $data->tipo_palete);
$stmt->bindParam(':valor', $data->valor);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Tipo de palete cadastrado com sucesso!"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao cadastrar tipo de palete. Verifique se já não existe."]);
}