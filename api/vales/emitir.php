<?php
header('Content-Type: application/json');
include_once '../../config/database.php';

// --- 1. Obter Configuração e Preparar o Novo Número do Vale ---
$configFile = __DIR__ . '/../config/config.json';
if (!file_exists($configFile)) {
    die(json_encode(['success' => false, 'message' => 'Arquivo de configuração não encontrado.']));
}
$config = json_decode(file_get_contents($configFile), true);
$nextValeNumber = intval($config['nextValeNumber']);
$n_vale_formatado = 'VP' . str_pad($nextValeNumber, 5, '0', STR_PAD_LEFT);

// --- 2. Obter Dados da Requisição ---
$data = json_decode(file_get_contents("php://input"));

if (empty($data->cpf_cnpj_cliente) || empty($data->tipo_palete) || empty($data->quantidade_emitida)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

// --- 3. Inserir no Banco de Dados ---
$database = new Database();
$db = $database->getConnection();

$query = "INSERT INTO vales (n_vale, data_emissao, cpf_cnpj_cliente, tipo_palete, quantidade_emitida, usuario_emissor, descricao, status_vale) 
          VALUES (:n_vale, :data_emissao, :cpf_cnpj_cliente, :tipo_palete, :quantidade_emitida, :usuario_emissor, :descricao, 'Aberto')";

$stmt = $db->prepare($query);

$stmt->bindParam(':n_vale', $n_vale_formatado);
$stmt->bindParam(':data_emissao', $data->data_emissao);
$stmt->bindParam(':cpf_cnpj_cliente', $data->cpf_cnpj_cliente);
$stmt->bindParam(':tipo_palete', $data->tipo_palete);
$stmt->bindParam(':quantidade_emitida', $data->quantidade_emitida);
$stmt->bindParam(':usuario_emissor', $data->usuario_emissor);
$stmt->bindParam(':descricao', $data->descricao);

if ($stmt->execute()) {
    // --- 4. Atualizar o Arquivo de Configuração ---
    $config['nextValeNumber'] = $nextValeNumber + 1;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'message' => 'Vale ' . $n_vale_formatado . ' emitido com sucesso!', 'n_vale' => $n_vale_formatado]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao emitir o vale.']);
}