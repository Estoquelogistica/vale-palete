<?php
// Inclui o arquivo de conexão
include_once '../../config/database.php';

// Cria a conexão com o banco de dados
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Método não permitido."]);
    exit();
}

$query = "SELECT id, cpf_cnpj, nome_razao_social FROM clientes ORDER BY nome_razao_social ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($clientes);
