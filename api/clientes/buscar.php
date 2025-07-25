<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['term']) || empty($_GET['term'])) {
    http_response_code(400);
    echo json_encode(null);
    exit();
}

$term = $_GET['term'];
// Limpa o termo para o caso de ser um CPF/CNPJ com formatação
$cleaned_term = preg_replace('/[^0-9]/', '', $term);

$query = "SELECT cpf_cnpj, nome_razao_social FROM clientes WHERE id = :term OR cpf_cnpj = :cleaned_term LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(':term', $term);
$stmt->bindParam(':cleaned_term', $cleaned_term);
$stmt->execute();

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Retorna o cliente encontrado ou null se não houver correspondência.
echo json_encode($cliente ?: null);
?>