<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido."]);
    exit();
}

// Pega os dados do corpo da requisição (JSON)
$data = json_decode(file_get_contents("php://input"));

if (empty($data->cpf_cnpj) || empty($data->nome_razao_social)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "CPF/CNPJ e Nome/Razão Social são obrigatórios."]);
    exit();
}

// Limpa o CPF/CNPJ
$cpf_cnpj_limpo = preg_replace('/[^0-9]/', '', $data->cpf_cnpj);

$query = "INSERT INTO clientes (cpf_cnpj, nome_razao_social) VALUES (:cpf_cnpj, :nome_razao_social)";
$stmt = $db->prepare($query);

$stmt->bindParam(':cpf_cnpj', $cpf_cnpj_limpo);
$stmt->bindParam(':nome_razao_social', $data->nome_razao_social);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cliente cadastrado com sucesso!"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao cadastrar cliente."]);
}
