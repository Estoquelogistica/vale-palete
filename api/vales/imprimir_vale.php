<?php
// Inclui o autoload do Composer para carregar a biblioteca mPDF
require_once '../../vendor/autoload.php';
include_once '../../config/database.php';


// 1. Validar e obter o número do vale da URL
if (!isset($_GET['n_vale']) || empty($_GET['n_vale'])) {
    die("Erro: Número do vale não fornecido.");
}
$n_vale = $_GET['n_vale'];

// 2. Conectar ao banco e buscar os dados completos do vale
$database = new Database();
$db = $database->getConnection();

$query = "
    SELECT 
        v.*, 
        c.nome_razao_social
    FROM 
        vales v
    LEFT JOIN 
        clientes c ON v.cpf_cnpj_cliente = c.cpf_cnpj
    WHERE 
        v.n_vale = :n_vale
";

$stmt = $db->prepare($query);
$stmt->bindParam(':n_vale', $n_vale);
$stmt->execute();

$vale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vale) {
    die("Erro: Vale com o número " . htmlspecialchars($n_vale) . " não encontrado.");
}

// 3. Preparar os dados para o template
$saldo = $vale['quantidade_emitida'] - $vale['quantidade_baixada'];
$registros_baixas = !empty($vale['registro_baixas_detalhadas']) ? json_decode($vale['registro_baixas_detalhadas'], true) : [];

// Função para formatar data
function formatarData($dataISO) {
    if (!$dataISO) return '';
    $data = new DateTime($dataISO);
    return $data->format('d/m/Y');
}

// Função para formatar moeda
function formatarMoeda($valor) {
    return number_format($valor, 2, ',', '.');
}

// Busca o valor unitário do palete na tabela correta 'valor_unitario'
$palete_query = "SELECT valor FROM valor_unitario WHERE tipo_palete = :tipo_palete";
$palete_stmt = $db->prepare($palete_query);
$palete_stmt->bindParam(':tipo_palete', $vale['tipo_palete']);
$palete_stmt->execute();
$palete_info = $palete_stmt->fetch(PDO::FETCH_ASSOC);
$valor_unitario = $palete_info ? $palete_info['valor'] : 0;
$valor_total = $valor_unitario * $vale['quantidade_emitida'];

// 4. Iniciar a captura do buffer de saída para montar o HTML
ob_start();
?>

<!-- O HTML do seu template começa aqui. Ele será capturado como uma string. -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vale Palete <?= htmlspecialchars($vale['n_vale']) ?></title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; color: #333; }
        .page { width: 100%; display: flex; flex-direction: column; justify-content: space-between; }
        .via { width: 100%; border: 1px solid #005555; border-radius: 5px; padding: 8px; box-sizing: border-box; background-color: #f9f9f9; page-break-inside: avoid; }
        .via-motorista { margin-bottom: 15mm; }
        .header { text-align: center; border-bottom: 1px solid #005555; padding-bottom: 5px; margin-bottom: 6px; }
        .header img { width: 80px; display: block; margin: 0 auto 4px auto; }
        .header h1 { margin: 0; font-size: 13pt; font-weight: bold; color: #003333; }
        .header p { margin: 2px 0; font-size: 8pt; color: #666; }
        .details { margin-bottom: 6px; }
        .details p { margin: 3px 0; font-size: 8pt; line-height: 1.2; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 7pt; }
        .details-table th, .details-table td { border: 1px solid #005555; padding: 3px; text-align: left; }
        .details-table th { background-color: #e6f0f0; font-weight: bold; color: #003333; }
        .signature { margin-top: 10px; border-top: 1px solid #005555; padding-top: 4px; text-align: center; font-size: 8pt; color: #333; }
        .footer { text-align: center; font-size: 7pt; color: #666; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="page">
        <?php for ($i = 0; $i < 2; $i++): // Loop para criar as duas vias ?>
        <div class="via <?= $i === 0 ? 'via-motorista' : '' ?>">
            <div class="header">
                <!-- O mPDF precisa de um caminho absoluto ou URL completa para a imagem -->
                <img src="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/vale-palete/public/images/logo.png' ?>" alt="Logo da Empresa" width="80">
                <h1>Vale Palete Nº <?= htmlspecialchars($vale['n_vale']) ?> - Via <?= $i === 0 ? 'Motorista' : 'Empresa' ?></h1>
                <p>Data de Emissão: <?= formatarData($vale['data_emissao']) ?></p>
                <p>Data de Geração: <?= date('d/m/Y H:i:s') ?></p>
            </div>

            <div class="details">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($vale['nome_razao_social']) ?></p>
                <p><strong>CPF/CNPJ:</strong> <?= htmlspecialchars($vale['cpf_cnpj_cliente']) ?></p>
                <p><strong>Tipo de Palete:</strong> <?= htmlspecialchars($vale['tipo_palete']) ?></p>
                <p><strong>Quantidade Emitida:</strong> <?= htmlspecialchars($vale['quantidade_emitida']) ?></p>
                <p><strong>Quantidade Baixada:</strong> <?= htmlspecialchars($vale['quantidade_baixada']) ?></p>
                <p><strong>Saldo:</strong> <?= htmlspecialchars($saldo) ?></p>
                <p><strong>Valor Unitário:</strong> R$ <?= formatarMoeda($valor_unitario) ?></p>
                <p><strong>Valor Total:</strong> R$ <?= formatarMoeda($valor_total) ?></p>
                <?php if (!empty($vale['descricao'])): ?>
                    <p><strong>Descrição:</strong> <?= htmlspecialchars($vale['descricao']) ?></p>
                <?php endif; ?>
            </div>

            <table class="details-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Quantidade</th>
                        <th>Usuário</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($registros_baixas)): ?>
                        <?php foreach ($registros_baixas as $baixa): ?>
                        <tr>
                            <td><?= isset($baixa['data']) ? htmlspecialchars(formatarData(explode(' ', $baixa['data'])[0])) : '' ?></td>
                            <td><?= isset($baixa['quantidade']) ? htmlspecialchars($baixa['quantidade']) : '' ?></td>
                            <td><?= isset($baixa['usuario']) ? htmlspecialchars($baixa['usuario']) : '' ?></td>
                            <td><?= isset($baixa['descricao']) ? htmlspecialchars($baixa['descricao']) : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">Nenhuma baixa registrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="signature">
                <p>Assinatura do <?= $i === 0 ? 'Motorista' : 'Responsável' ?>: ______________________________</p>
            </div>

            <div class="footer">
                <p>Gerado pelo Sistema Vale Palete</p>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</body>
</html>

<?php
// 5. Captura o HTML gerado, limpa o buffer e o armazena na variável $html
$html = ob_get_clean();

// 6. Gera o PDF a partir do HTML
try {
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
    $mpdf->WriteHTML($html);
    
    // Define o nome do arquivo
    $filename = 'vale_palete_' . $n_vale . '.pdf';
    
    // 'I' envia o arquivo para o navegador para ser exibido.
    // 'D' força o download.
    $mpdf->Output($filename, 'I');

} catch (\Mpdf\MpdfException $e) {
    die("Erro ao gerar o PDF: " . $e->getMessage());
}