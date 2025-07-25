<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID do palete não fornecido."]);
    exit();
}

// VERIFICAÇÃO DE INTEGRIDADE: Checa se o palete está em uso
$check_query = "SELECT COUNT(*) FROM vales WHERE tipo_palete = (SELECT tipo_palete FROM valor_unitario WHERE id = :id)";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
$check_stmt->execute();
if ($check_stmt->fetchColumn() > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "Não é possível excluir. Este tipo de palete já está sendo usado em um ou mais vales."]);
    exit();
}

$query = "DELETE FROM valor_unitario WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Tipo de palete excluído com sucesso."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao excluir o tipo de palete."]);
}