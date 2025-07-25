<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$configFile = __DIR__ . '/config.json';

if (empty($data) || !isset($data['nextValeNumber'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Nenhuma configuração fornecida ou chave 'nextValeNumber' ausente."]);
    exit();
}

try {
    // Lê o arquivo de configuração existente para não sobrescrever outras configurações
    $config = [];
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
    }

    // Atualiza apenas o valor do próximo vale
    $config['nextValeNumber'] = intval($data['nextValeNumber']);

    // Salva o arquivo de volta
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
        echo json_encode(["success" => true, "message" => "Configurações salvas com sucesso!"]);
    } else {
        throw new Exception("Não foi possível escrever no arquivo de configuração.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao salvar configurações: " . $e->getMessage()]);
}