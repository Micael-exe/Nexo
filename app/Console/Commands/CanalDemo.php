<?php

namespace App\Console\Commands;

use App\Services\JsonReportRepository;
use App\Services\JsonUserRepository;
use Illuminate\Console\Command;

/** Dados 100% fictícios para demonstração em hosts com disco temporário. */
class CanalDemo extends Command
{
    protected $signature = 'canal:demo {--force : Cria os dados mesmo que já existam relatos}';

    protected $description = 'Cria relatos fictícios e, se configurado, um servidor de demonstração';

    public function handle(JsonReportRepository $reports, JsonUserRepository $users): int
    {
        if (! $this->option('force') && $reports->all() !== []) {
            $this->info('Já existem relatos; nada a fazer.');

            return self::SUCCESS;
        }

        $samples = [
            ['residuos', 'Sacos de lixo e entulho acumulados na entrada da boca de lobo.', 'Rua das Acácias, 120 (dado fictício)', 'Em frente à padaria'],
            ['vegetacao', 'Mato alto cobrindo a margem do canal e parte da grade.', 'Avenida Exemplo, 340 (dado fictício)', 'Ao lado da ponte pequena'],
            ['assoreamento', 'Areia e barro acumulados reduzindo a passagem da água.', 'Travessa do Canal, 18 (dado fictício)', 'Perto do campo de futebol'],
            ['estrutura', 'Tampa da boca de lobo quebrada na calçada.', 'Rua Modelo, 75 (dado fictício)', 'Esquina com a escola'],
            ['agua_acumulada', 'Água parada no trecho após a chuva de ontem.', 'Rua das Palmeiras, 210 (dado fictício)', 'Atrás do mercado'],
            ['outro', 'Cheiro forte vindo do canal em alguns horários.', 'Avenida Teste, 500 (dado fictício)', 'Próximo ao ponto de ônibus'],
        ];
        foreach ($samples as $i => [$category, $description, $address, $reference]) {
            $reports->create([
                'category' => $category, 'description' => $description, 'address' => $address, 'reference' => $reference,
                'latitude' => null, 'longitude' => null, 'email' => '', 'phone' => '',
                'observed_at' => now()->subDays($i)->toIso8601String(),
            ], [], 'demo-'.$i);
        }
        $ids = array_column($reports->all(), 'id');
        $reports->updateStatus($ids[0], 'Em análise', 'Equipe fará vistoria (demonstração).', 'Equipe Demo');
        $reports->updateStatus($ids[1], 'Encaminhado', 'Encaminhado para limpeza (demonstração).', 'Equipe Demo');
        $reports->updateStatus($ids[2], 'Resolvido', 'Trecho desobstruído (demonstração).', 'Equipe Demo');
        $this->info(count($samples).' relatos fictícios criados.');

        $email = (string) env('CANAL_DEMO_EMAIL');
        $password = (string) env('CANAL_DEMO_PASSWORD');
        if ($email !== '' && strlen($password) >= 10 && $users->findByEmail($email) === null) {
            $users->create('Servidor Demonstração', $email, $password);
            $this->info('Servidor de demonstração criado.');
        }

        return self::SUCCESS;
    }
}
