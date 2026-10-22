# Nexo
Sistema de mapeamento e monitoramento de pontos de descarte irregular de entulho e móveis, com organização de solicitações, acompanhamento de reincidências e otimização das equipes de limpeza urbana, visando reduzir custos operacionais e melhorar a gestão de resíduos.

# Canal em Dia

MVP local para o Ideathon Paulista Sustentável. Esta entrega implementa a Home (`/`), o Novo relato (`/novo-relato`) e a área municipal (`/entrar`, `/cadastro`, `/painel`). É uma demonstração com dados fictícios, sem atendimento municipal oficial.

## Stack e pré-requisitos

- PHP **8.3** com `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `mbstring`, `openssl`, `PDO` (sem driver de banco necessário), `session`, `tokenizer`, `xml`, `xmlwriter` e `zip`.
- Composer 2; Node.js 24 e npm para preparar os assets locais; Git.
- Laravel 13, Blade, Livewire 4 (inclui Alpine.js), Bootstrap 5 e Vite. As versões resolvidas estão em `composer.lock` e `package-lock.json`.
- Para fotografias: `upload_max_filesize=5M`, `post_max_size=24M`, `memory_limit=256M` ou superior no `php.ini`. Sem fotos, o formulário também funciona.

Não instalar pacotes globais, banco de dados, servidor de filas ou bibliotecas frontend adicionais. Não carregar Alpine de CDN: o Livewire já o inclui. Bootstrap fornece layout e componentes; GD reprocessa imagens e remove metadados. As dependências de desenvolvimento PHPUnit, Mockery e Collision servem aos testes.

## Instalar e executar no Linux Mint

Na raiz desta pasta, onde estão `artisan` e `composer.json`:

```bash
composer install --no-interaction
test -f .env || cp .env.example .env
php artisan key:generate
npm ci --ignore-scripts
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abra **http://127.0.0.1:8000**. O Laravel usa seu ponto de entrada padrão `public/index.php`. Não execute migrations nem `storage:link`. O diretório servido deve ser **somente `public/`**, nunca a raiz do projeto. Na primeira configuração, gere a chave; preserve a mesma `APP_KEY` nas próximas execuções, pois recibos e sessões existentes dependem dela.

Para desenvolver os assets, use `npm run dev` em um segundo terminal. Para voltar ao build estático, pare o Vite, remova apenas o arquivo de execução `public/hot` se ele tiver ficado após uma interrupção e execute `npm run build`.

## Executar no Windows

Com PHP 8.3, Composer e Node no PATH, os comandos equivalentes em PowerShell são:

```powershell
composer install --no-interaction
if (-not (Test-Path -LiteralPath '.env')) { Copy-Item -LiteralPath '.env.example' -Destination '.env' }
php artisan key:generate
npm.cmd ci --ignore-scripts
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

Neste workspace também foi preparado um runtime **portátil**, fora do repositório, em `../.tools/`, sem instalação global. Para usá-lo:

```powershell
& '..\.tools\composer.cmd' install --no-interaction
& '..\.tools\php83\php.exe' artisan serve --host=127.0.0.1 --port=8000
```

Esse runtime não acompanha o ZIP de código. Quem já tem PHP/Composer instalados deve usar os comandos comuns. Não é preciso reinstalar dependências a cada início da aplicação.

## O que foi implementado

- Home responsiva, identidade em verde e lima inspirada nas referências, ilustração SVG original local, explicação do fluxo, orientações e CTA para o formulário.
- Registro sem cadastro, nome ou CPF: categoria, endereço, referência e descrição obrigatórios; observação, GPS, fotografias e contatos opcionais.
- Seis categorias do PDF; descrição de 20 a 1.000 caracteres; até três JPEG/PNG de 5 MB cada, conteúdo real conferido no servidor. Limite adicional de 16 megapixels e de 8.000 pixels por dimensão para controlar memória no MVP local.
- Ciência do aviso de privacidade registrada por versão e horário. Essa ciência **não define uma base legal** para eventual uso municipal.
- Estado inicial `Recebido`, sem confirmar tecnicamente a ocorrência. Recibo na mesma página com protocolo público de referência e credencial privada aleatória de 256 bits.
- Validação no PHP, erros por campo, preservação dos valores em falhas, prevenção de cliques repetidos e idempotência no servidor.
- Endereço e referência funcionam sem GPS. O GPS somente acrescenta coordenadas do dispositivo; a negativa de permissão não bloqueia o relato. Não há geocodificador, mapa externo ou verificação automática de território.

Todos os textos de demonstração pedem uso de dados fictícios. Não há contatos de emergência nem horários oficiais inventados. O produto não substitui atendimento de emergência e não oferece garantia contra alagamentos.

## Armazenamento JSON privado

Na primeira gravação, a aplicação cria `storage/app/data/relatos.json` com `schema_version: 1` e uma lista `relatos`. Não utiliza Eloquent, migrations, SQLite ou servidor de banco. Sessões e cache usam arquivos; filas são síncronas; o mailer não envia mensagens.

Cada relato contém identificador UUID, protocolo aleatório, categoria, descrição, localização, contato restrito opcional, instante da observação e do recebimento (UTC), ciência do aviso e referências de anexos privados. O recibo exibe o horário de São Paulo. Contato não é verificado neste escopo.

Uma tentativa mantém a mesma chave de envio, protegida contra alteração pelo cliente no Livewire. O servidor armazena seu hash. Repetir essa tentativa retorna **o mesmo registro e o mesmo recibo**, mesmo quando o limite de novos envios foi alcançado. Ao escolher “Novo relato” após o sucesso ou abrir outro formulário, começa uma tentativa diferente. Relatos de pessoas diferentes sobre o mesmo fato recebem protocolos próprios; agrupá-los é responsabilidade do módulo de triagem.

A credencial não fica em texto puro no JSON: é guardada como hash SHA-256 para futura verificação e como cópia cifrada pela `APP_KEY` somente para devolver o recibo idempotente. O protocolo não concede acesso privado. O JSON não possui rota pública, e esta entrega **não implementa consulta por credencial**. Quem integrar o acompanhamento deve comparar o hash com `hash_equals`, aplicar autorização, limitação de tentativas, política de expiração e nunca devolver o objeto integral do arquivo.

A escrita usa `flock(LOCK_EX)` em `relatos.lock`, leitura sob esse mesmo lock, arquivo temporário no mesmo diretório, `fflush`/`fsync` e `rename`. Falhas preservam o documento anterior. JSON inválido gera erro; não é apagado ou recriado silenciosamente. Todos os futuros escritores devem usar o mesmo repositório/lock, inclusive rotinas de backup consistente. O modelo foi projetado para **um servidor local e filesystem com suporte a locks**, não múltiplos servidores ou pastas sincronizadas concorrentemente.

As fotografias são decodificadas e regravadas por GD, sem EXIF/nome original, em `storage/app/private/evidence/`. São restritas, não usam link público e não aparecem na Home. Temporários ficam no disco privado e a limpeza automática do Livewire está habilitada; o componente tenta removê-los ao excluir a foto ou concluir o envio. Backups precisam incluir JSON, anexos e a chave de forma protegida.

Há limite de cinco novas tentativas válidas por IP a cada dez minutos e limite de upload temporário. Uma tentativa de gravação que falhe pode consumir esse limite. Retorno idempotente não consome outra tentativa. Sessões duram 30 minutos e são cifradas; respostas usam `no-store`. A aplicação não persiste formulário ou credencial em `localStorage`/`sessionStorage`. Copiar o recibo coloca seu conteúdo na área de transferência, sob controle de quem usa a demonstração.

Dados locais, logs, `.env`, dependências e anexos são ignorados pelo Git. O arquivo JSON permanece privado, mas seu conteúdo operacional não é cifrado integralmente: proteja a pasta com permissões do sistema. Não inclua dados pessoais reais nesta demonstração.

## Área municipal (login, cadastro e painel)

- `/entrar` e `/cadastro` (Livewire) e `/painel` (somente autenticado). Usuários ficam em `storage/app/data/usuarios.json` (senha só como hash), com o mesmo lock/gravação atômica dos relatos. Provider de autenticação próprio (`app/Auth/JsonUserProvider.php`), sem Eloquent.
- **Cadastro protegido por código:** defina `CANAL_CODIGO_CADASTRO` no `.env` e informe esse código ao servidor autorizado. Sem o código configurado, o cadastro fica desativado. Reinicie `artisan serve` após alterar o `.env`.
- Painel: contagem por situação, filtros (situação, tipo, busca), detalhe do relato com fotos (servidas só a usuários logados), alteração de situação (Recebido, Em análise, Encaminhado, Resolvido, Descartado) com observação e histórico.
- Limites do MVP: um único perfil de servidor, sem recuperação de senha, sem exclusão de relatos e sem notificação ao cidadão.

## Visual

Tema escuro verde-petróleo (menta e rosa discreto), alinhado ao app mobile do Canal em Dia. Fica em `resources/css/theme.css`, carregado depois de `app.css`; para mudar cores, edite as variáveis no `:root` desse arquivo. A foto do hero está em `public/images/paulista.jpg` (640×479 px; no desktop ela ocupa só a metade direita para não ficar pixelada).

## Estrutura

```text
app/Livewire/NovoRelato.php           formulário e validações no servidor
app/Services/JsonReportRepository.php persistência, idempotência e anexos
app/Http/Middleware/PrivateDemoHeaders.php
config/canal.php                     categorias, caminho e versão do aviso
config/livewire.php                  uploads temporários privados
resources/views/home.blade.php
resources/views/livewire/novo-relato.blade.php
resources/views/components/          layout, navegação, rodapé e ícones
resources/css/app.css
resources/js/app.js
public/images/                       ilustração local
routes/web.php                       somente as duas rotas de página
storage/app/data/                    JSON e lock (ignorados)
storage/app/private/                 anexos e temporários (ignorados)
tests/Feature/                       testes sem banco
```

## Integração com o restante da equipe

**Não implementados:** Acompanhar, Canais, Login, Dashboard, autenticação municipal, triagem, mapa público, indicadores, recuperação de credencial, e-mail/SMS e integração com Monitora Paulista. Navegação futura aparece explicitamente como pendente, sem páginas falsas.

O limite de integração é `JsonReportRepository::create(array $data, array $photos, string $submissionKey, ?callable $beforeCreate = null)`, que retorna apenas o recibo e um sinal interno `created`. Após procurar a chave existente sob lock, o serviço chama `beforeCreate(array &$data)` somente para um registro novo. O componente usa essa função para validar, normalizar o instante da observação e aplicar o limite; dessa forma um reenvio continua recuperável mesmo com temporários já removidos. Outros consumidores devem validar seu payload antes da escrita. As equipes futuras devem preservar IDs, protocolos, estado inicial e privacidade. Não foi adicionado endpoint `/api/v1/relatos`: o envio atual ocorre pelo mecanismo interno do Livewire, protegido por sessão/CSRF e snapshot assinado.

Não existe SLA de atendimento, território operacional aprovado, parceria institucional confirmada ou prazo municipal. A Home situa a proposta em Paulista/PE e informa que o trecho do piloto ainda será definido. Eventual uso real exige definir responsáveis, governança de dados, base legal, retenção, suporte e capacidade operacional. Essas definições não fazem parte desta implementação.

## Verificar

```bash
composer validate --strict
php artisan route:list --except-vendor
php artisan test
npm run build
```

Os testes usam dados e diretórios isolados, sem conexão de banco, e não apagam os relatos da execução local. Confira também o percurso manual: Home → Novo relato → enviar sem GPS, foto e contato → guardar protocolo/credencial → iniciar outro relato. Teste campos vazios, foto inválida, teclado, permissão de localização negada e tela de 360 px.

O servidor `artisan serve` é destinado ao desenvolvimento local. Os limites de capacidade do PDF (20 sessões/10 mil relatos/P95) não foram homologados; não apresentar este protótipo como pronto para operação municipal.
