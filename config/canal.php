<?php
return [
    'data_path' => storage_path('app/data'),
    // Cadastro de servidores só é aberto com este código (definido em .env). Vazio = cadastro desativado.
    'registration_code' => env('CANAL_CODIGO_CADASTRO'),
    'statuses' => ['Recebido', 'Em análise', 'Encaminhado', 'Resolvido', 'Descartado'],
    'privacy_version' => 'demonstracao-v1-2026-10',
    'categories' => [
        'residuos' => 'Resíduos e lixo',
        'vegetacao' => 'Vegetação obstruindo o canal',
        'assoreamento' => 'Assoreamento aparente',
        'estrutura' => 'Estrutura danificada',
        'agua_acumulada' => 'Água acumulada',
        'outro' => 'Outro problema',
    ],
];
