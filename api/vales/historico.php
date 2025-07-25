<?php
// --- Configuração de Erros e Cabeçalhos ---
// Para produção, suprime a exibição de erros para não quebrar o JSON.
// O ideal é configurar o php.ini para logar erros em um arquivo.
error_reporting(0);
ini_set('display_errors', 0);

// Define o tipo de conteúdo da resposta como JSON. Essencial para o frontend.
header('Content-Type: application/json; charset=UTF-8');

// --- Inclusão e Conexão com o Banco de Dados ---
include_once '../../config/database.php';

// --- Validação do Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Método não permitido."]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // --- Coleta e Preparação dos Filtros ---
    $search_term = isset($_GET['search']) && !empty($_GET['search']) ? '%' . $_GET['search'] . '%' : null;
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;
    $status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : null;

    $params = [];

    // --- Construção da Query Base ---
    $query = "
        SELECT 
            v.id,
            v.n_vale,
            v.data_emissao,
            v.tipo_palete,
            v.quantidade_emitida,
            v.quantidade_baixada,
            v.status_vale,
            v.registro_baixas_detalhadas,
            c.nome_razao_social 
        FROM vales AS v
        LEFT JOIN clientes AS c ON v.cpf_cnpj_cliente = c.cpf_cnpj ";

    // --- Adição Dinâmica das Cláusulas WHERE ---
    $where_clauses = [];

    if ($search_term) {
        $where_clauses[] = "(v.n_vale LIKE :search_term OR c.nome_razao_social LIKE :search_term OR v.cpf_cnpj_cliente LIKE :search_term)";
        $params[':search_term'] = $search_term;
    }
    if ($start_date) {
        $where_clauses[] = "v.data_emissao >= :start_date";
        $params[':start_date'] = $start_date;
    }
    if ($end_date) {
        $where_clauses[] = "v.data_emissao <= :end_date";
        $params[':end_date'] = $end_date;
    }
    if ($status) {
        $where_clauses[] = "v.status_vale = :status";
        $params[':status'] = $status;
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(' AND ', $where_clauses);
    }

    // --- Ordenação e Finalização da Query ---
    $query .= " ORDER BY CAST(v.n_vale AS UNSIGNED) DESC, v.id DESC";

    // --- Execução da Consulta ---
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $vales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Envio da Resposta de Sucesso ---
    http_response_code(200); // OK
    echo json_encode($vales);

} catch (PDOException $e) {
    // --- Tratamento de Erros de Banco de Dados ---
    http_response_code(500); // Internal Server Error
    error_log($e->getMessage()); // Loga o erro para análise interna
    echo json_encode([
        "success" => false,
        "message" => "Erro interno no servidor ao consultar o histórico."
    ]);
}
