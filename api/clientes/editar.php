<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->cpf_cnpj) || empty($data->nome_razao_social)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dados incompletos."]);
    exit();
}

$query = "UPDATE clientes SET nome_razao_social = :nome_razao_social WHERE cpf_cnpj = :cpf_cnpj";
$stmt = $db->prepare($query);

$stmt->bindParam(':nome_razao_social', $data->nome_razao_social);
$stmt->bindParam(':cpf_cnpj', $data->cpf_cnpj);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cliente atualizado com sucesso."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao atualizar o cliente."]);
}
?>