<?php
header('Content-Type: application/json');
$configFile = __DIR__ . '/config.json';

if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['nextValeNumber'])) {
        // Adiciona o número já formatado para ser usado na interface
        $config['formattedValeNumber'] = 'VP' . str_pad($config['nextValeNumber'], 5, '0', STR_PAD_LEFT);
    }
    echo json_encode($config);
} else {
    // Configuração padrão caso o arquivo não exista
    $defaultConfig = ['nextValeNumber' => 1, 'formattedValeNumber' => 'VP00001'];
    echo json_encode($defaultConfig);
}