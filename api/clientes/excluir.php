<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->cpf_cnpj)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "CPF/CNPJ do cliente não fornecido."]);
    exit();
}

// VERIFICAÇÃO DE INTEGRIDADE: Checa se o cliente possui vales associados.
$check_query = "SELECT COUNT(*) FROM vales WHERE cpf_cnpj_cliente = :cpf_cnpj";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':cpf_cnpj', $data->cpf_cnpj);
$check_stmt->execute();
if ($check_stmt->fetchColumn() > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "Não é possível excluir. Este cliente possui vales associados."]);
    exit();
}

$query = "DELETE FROM clientes WHERE cpf_cnpj = :cpf_cnpj";
$stmt = $db->prepare($query);
$stmt->bindParam(':cpf_cnpj', $data->cpf_cnpj);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cliente excluído com sucesso."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao excluir o cliente."]);
}
?>