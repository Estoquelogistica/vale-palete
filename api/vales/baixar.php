<?php
header('Content-Type: application/json');
include_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

// 1. Validação básica dos dados de entrada
if (empty($data->n_vale) || !isset($data->quantidade_a_baixar) || $data->quantidade_a_baixar <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos. Verifique o número do vale e a quantidade.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // 2. Iniciar transação para garantir a consistência dos dados
    $db->beginTransaction();

    // 3. Buscar o vale e bloquear a linha para evitar que dois usuários baixem ao mesmo tempo
    $stmt = $db->prepare("SELECT * FROM vales WHERE n_vale = :n_vale FOR UPDATE");
    $stmt->bindParam(':n_vale', $data->n_vale);
    $stmt->execute();
    $vale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vale) {
        throw new Exception("Vale não encontrado.");
    }

    // 4. Validar a lógica de negócio
    $saldo_atual = $vale['quantidade_emitida'] - $vale['quantidade_baixada'];
    if ($data->quantidade_a_baixar > $saldo_atual) {
        throw new Exception("A quantidade a baixar ({$data->quantidade_a_baixar}) é maior que o saldo disponível ({$saldo_atual}).");
    }

    // 5. Preparar o registro de baixa detalhado
    $registros_atuais = !empty($vale['registro_baixas_detalhadas']) ? json_decode($vale['registro_baixas_detalhadas'], true) : [];
    
    $novo_registro = [
        "data" => date('Y-m-d H:i:s'),
        "quantidade" => $data->quantidade_a_baixar,
        "usuario" => $data->usuario_baixa ?? 'Sistema',
        "descricao" => $data->descricao_baixa ?? ''
    ];
    $registros_atuais[] = $novo_registro;
    $novos_registros_json = json_encode($registros_atuais);

    // 6. Calcular os novos totais e o novo status
    $nova_quantidade_baixada = $vale['quantidade_baixada'] + $data->quantidade_a_baixar;
    $novo_saldo = $vale['quantidade_emitida'] - $nova_quantidade_baixada;
    $novo_status = ($novo_saldo <= 0) ? 'Fechado' : 'Aberto';

    // 7. Atualizar o vale no banco de dados
    $update_stmt = $db->prepare("UPDATE vales SET quantidade_baixada = :qb, status_vale = :sv, registro_baixas_detalhadas = :rbd WHERE n_vale = :nv");
    $update_stmt->bindParam(':qb', $nova_quantidade_baixada);
    $update_stmt->bindParam(':sv', $novo_status);
    $update_stmt->bindParam(':rbd', $novos_registros_json);
    $update_stmt->bindParam(':nv', $data->n_vale);
    $update_stmt->execute();

    // 8. Confirmar a transação
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Baixa do vale registrada com sucesso!']);

} catch (Exception $e) {
    // 9. Em caso de erro, reverter a transação e informar o usuário
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}