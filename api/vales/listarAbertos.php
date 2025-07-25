<?php
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Método não permitido."]);
    exit();
}

$search_term = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

$query = "
    SELECT 
        v.n_vale, v.data_emissao, v.tipo_palete, v.quantidade_emitida, v.quantidade_baixada, v.status_vale,
        c.nome_razao_social 
    FROM vales AS v
    LEFT JOIN clientes AS c ON v.cpf_cnpj_cliente = c.cpf_cnpj
    WHERE 
        v.status_vale = 'Aberto' AND
        (v.n_vale LIKE :search_term OR c.nome_razao_social LIKE :search_term OR v.cpf_cnpj_cliente LIKE :search_term)
    ORDER BY v.n_vale ASC
";

$stmt = $db->prepare($query);
$stmt->bindParam(':search_term', $search_term);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));