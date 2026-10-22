# Verificação da entrega

Executada em 22 de outubro de 2026 no Windows, com PHP portátil 8.3.36, Laravel 13.38.0, Livewire 4.4.8, Composer 2.10.3 e Node.js 24.11.1. Dados sintéticos; nenhum banco de dados configurado.

| Verificação | Resultado |
|---|---|
| `composer validate --strict` | Aprovado |
| `composer check-platform-reqs` | Aprovado |
| `php artisan route:list --except-vendor` | Somente `/` e `/novo-relato` como páginas |
| `php artisan test --compact` | 21 testes aprovados, 133 assertions |
| `npm run build` | Aprovado; Bootstrap e assets locais compilados |
| Envio manual no navegador | Sem foto, GPS ou contato: recibo gerado com protocolo e credencial separados |
| Formulário vazio | Erros em português por campo; foco levado ao resumo de correções |
| Novo formulário após sucesso | Campos e seleções reiniciados |
| Navegação em tela estreita | Menu abre; módulos pendentes permanecem desabilitados |
| Home e formulário a 360 px | Sem rolagem horizontal; conteúdo dentro da largura disponível |
| Home em desktop a 1.440 px | Sem rolagem horizontal; ilustração local carregada |

Os testes automatizados verificam validações, ciência do aviso, instantes UTC, segredos fora de texto puro, retornos idempotentes, repetição com limite já alcançado ou temporário indisponível, anexos JPEG/PNG privados, rejeição de arquivos inválidos e grandes, preservação de JSON corrompido e limite às duas páginas atribuídas.

Também foi realizado ensaio isolado do repositório com seis processos PHP: 120 gravações de chaves distintas resultaram em 120 registros e 120 anexos, sem perda; seis chamadas com uma mesma chave resultaram em um registro, um anexo e o mesmo recibo. O JSON permaneceu válido e não restaram arquivos temporários de gravação. Esse ensaio não representa homologação de capacidade ou disponibilidade para operação municipal.

O navegador confirmou o percurso funcional com um relato explicitamente fictício. O ZIP de entrega não inclui esse registro, sessões, credenciais, logs, `.env`, `vendor` ou `node_modules`. Inclui os assets compilados em `public/build/`, além das fontes e dos arquivos de lock. É necessário instalar as dependências PHP e gerar uma chave local antes de executar em outro computador.

Não foram testados envio de mensagens, recuperação de credenciais, autenticação, acompanhamento, integração municipal ou outros módulos, pois não fazem parte desta entrega. A localização por GPS depende das permissões e do dispositivo; o envio por endereço e referência foi testado sem essa dependência. Não foi homologada a meta de 20 sessões e 10 mil relatos do PDF.
