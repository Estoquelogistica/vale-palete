<?php

echo "<pre>"; // Para formatar a saída de forma legível

include_once './config/database.php';

$database = new Database();
$db = $database->getConnection();

$csvFile = 'clientes.csv'; // O nome do seu arquivo CSV

if (!file_exists($csvFile)) {
    die("Erro: Arquivo $csvFile não encontrado.");
}

// Abre o arquivo para leitura
$handle = fopen($csvFile, "r");
if ($handle === FALSE) {
    die("Erro: Não foi possível abrir o arquivo $csvFile.");
}

try {
    $db->beginTransaction();

    // Pula a primeira linha (cabeçalho)
    fgetcsv($handle, 1000, ",");

    $stmt = $db->prepare("INSERT IGNORE INTO clientes (cpf_cnpj, nome_razao_social) VALUES (:cpf_cnpj, :nome_razao_social)");

    // Loop através das linhas do CSV
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Pula a linha se ela não tiver as duas colunas esperadas ou se a primeira coluna estiver vazia.
        if (count($data) < 2 || empty(trim($data[0]))) {
            echo "Linha ignorada: " . implode(';', $data) . "\n";
            continue;
        }

        $cpf_cnpj = preg_replace('/[^0-9]/', '', $data[0]);
        $nome_razao_social = $data[1];

        $stmt->execute([
            ':cpf_cnpj' => $cpf_cnpj,
            ':nome_razao_social' => $nome_razao_social
        ]);
        echo "Inserido: $nome_razao_social\n";
    }

    $db->commit();
    echo "\nImportação concluída com sucesso!";

} catch (Exception $e) {
    $db->rollBack();
    die("Erro durante a importação: " . $e->getMessage());
} finally {
    fclose($handle);
    echo "</pre>";
}