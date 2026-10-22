<?php
return [
    'data_path' => storage_path('app/data'),
    'privacy_version' => 'demonstracao-v1-2026-10',
    'categories' => [
        'residuos' => 'Resíduos e lixo',
        'vegetacao' => 'Vegetação obstruindo o canal',
        'assoreamento' => 'Assoreamento aparente',
        'estrutura' => 'Estrutura danificada',
        'agua_acumulada' => 'Água acumulada',
        'outro' => 'Outro problema',
    ],
    // Ciclo do relato conforme a documentação funcional (seção 5).
    'report_statuses' => [
        'Recebido' => ['tone' => 'neutral', 'icon' => 'check', 'summary' => 'O relato foi registrado. A triagem municipal ainda não começou.'],
        'Em triagem' => ['tone' => 'info', 'icon' => 'clipboard', 'summary' => 'A equipe está analisando o relato, a localização e possíveis registros relacionados.'],
        'Em acompanhamento' => ['tone' => 'info', 'icon' => 'calendar', 'summary' => 'O problema foi vinculado a um atendimento e está sendo acompanhado.'],
        'Solução comunicada' => ['tone' => 'ok', 'icon' => 'check', 'summary' => 'A coordenação comunicou a solução. Você pode pedir revisão se discordar.'],
        'Encerrado' => ['tone' => 'ok', 'icon' => 'shield', 'summary' => 'O atendimento deste relato foi encerrado.'],
    ],
    // Situação observada de um trecho: observação técnica datada, não garantia contra alagamento.
    'channel_statuses' => [
        'em_dia' => ['label' => 'Em dia', 'tone' => 'ok', 'icon' => 'check', 'meaning' => 'Inspeção dentro da periodicidade e nenhuma obstrução observada.'],
        'atencao' => ['label' => 'Atenção', 'tone' => 'warn', 'icon' => 'warning', 'meaning' => 'A observação indica um ponto que pede acompanhamento ou nova verificação.'],
        'com_obstrucao' => ['label' => 'Com obstrução', 'tone' => 'alert', 'icon' => 'block', 'meaning' => 'Obstrução confirmada em observação técnica; a intervenção ainda depende da coordenação.'],
    ],
];
