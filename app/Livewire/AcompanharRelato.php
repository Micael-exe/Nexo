<?php

namespace App\Livewire;

use App\Services\JsonReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class AcompanharRelato extends Component
{
    private const SESSION_KEY = 'canal.consultados';
    private const MAX_REMEMBERED = 5;

    public string $protocol = '';
    public string $credential = '';

    #[Locked]
    public ?string $openId = null;

    protected function rules(): array
    {
        return [
            'protocol' => ['required', 'regex:/^CED-\d{8}-[A-F0-9]{12}$/'],
            'credential' => ['required', 'regex:/^[A-Za-z0-9_-]{43}$/'],
        ];
    }

    protected function messages(): array
    {
        return [
            'protocol.required' => 'Informe o protocolo recebido ao enviar o relato.',
            'protocol.regex' => 'O protocolo tem o formato CED-AAAAMMDD-XXXXXXXXXXXX. Confira o que foi copiado.',
            'credential.required' => 'Informe a chave de acesso do recibo.',
            'credential.regex' => 'A chave de acesso tem 43 caracteres, sem espaços. Confira o que foi copiado.',
        ];
    }

    public function consult(JsonReportRepository $repository): void
    {
        $this->resetErrorBag();
        $this->protocol = strtoupper((string) preg_replace('/\s+/', '', $this->protocol));
        $this->credential = (string) preg_replace('/\s+/', '', $this->credential);
        try {
            $this->validate();
        } catch (ValidationException $exception) {
            $this->dispatch('validation-failed');
            throw $exception;
        }

        // Limita tentativas por origem e por protocolo para dificultar adivinhação da chave.
        $ipKey = 'acompanhar:ip:'.hash('sha256', request()->ip() ?? 'local');
        $protocolKey = 'acompanhar:protocolo:'.hash('sha256', $this->protocol);
        if (RateLimiter::tooManyAttempts($ipKey, 8) || RateLimiter::tooManyAttempts($protocolKey, 5)) {
            $this->addError('lookup', 'Muitas tentativas de consulta. Aguarde alguns minutos e tente novamente.');
            $this->dispatch('validation-failed');

            return;
        }

        try {
            $report = $repository->findVerified($this->protocol, $this->credential);
        } catch (Throwable $exception) {
            // Não registrar protocolo, chave ou conteúdo do relato nos logs.
            Log::warning('Falha ao consultar relato demonstrativo.', ['type' => $exception::class]);
            $this->addError('lookup', 'Não foi possível consultar agora. Seus campos foram mantidos. Tente novamente em instantes.');
            $this->dispatch('validation-failed');

            return;
        }

        if ($report === null) {
            RateLimiter::hit($ipKey, 600);
            RateLimiter::hit($protocolKey, 600);
            // Mesma mensagem para protocolo inexistente e chave incorreta.
            $this->addError('lookup', 'Não encontramos um relato com esse protocolo e essa chave. Confira os dois campos e tente novamente.');
            $this->dispatch('validation-failed');

            return;
        }

        RateLimiter::clear($protocolKey);
        $ids = array_values(array_unique([$report['id'], ...$this->rememberedIds()]));
        session()->put(self::SESSION_KEY, array_slice($ids, 0, self::MAX_REMEMBERED));
        $this->openId = $report['id'];
        $this->reset('protocol', 'credential');
        $this->dispatch('report-opened');
    }

    public function open(string $id): void
    {
        // Só abre relatos cuja chave já foi comprovada nesta sessão.
        if (in_array($id, $this->rememberedIds(), true)) {
            $this->openId = $id;
            $this->dispatch('report-opened');
        }
    }

    public function close(): void
    {
        $this->openId = null;
    }

    public function endSession(): void
    {
        session()->forget(self::SESSION_KEY);
        $this->reset('openId', 'protocol', 'credential');
        $this->resetErrorBag();
        $this->dispatch('session-cleared');
    }

    private function rememberedIds(): array
    {
        $ids = session()->get(self::SESSION_KEY, []);

        return is_array($ids) ? array_values(array_filter($ids, 'is_string')) : [];
    }

    private function present(array $report): array
    {
        $statuses = config('canal.report_statuses');
        $steps = array_keys($statuses);
        $index = array_search($report['status'], $steps, true);

        return array_merge($report, [
            'status_meta' => $statuses[$report['status']] ?? ['tone' => 'neutral', 'icon' => 'info', 'summary' => 'Situação registrada nesta demonstração.'],
            'step_index' => $index === false ? null : $index,
            'received_label' => $this->date($report['received_at'], false),
            'updated_label' => $this->date($report['updated_at'], true),
            'deadline_label' => $report['deadline'] !== null ? $this->date($report['deadline'], false) : 'Não informado',
            'timeline' => array_map(fn (array $entry) => array_merge($entry, ['label' => $this->date($entry['at'], true)]), $report['timeline']),
        ]);
    }

    private function date(string $iso, bool $withTime): string
    {
        try {
            return CarbonImmutable::parse($iso)->setTimezone('America/Sao_Paulo')->format($withTime ? 'd/m/Y \à\s H:i' : 'd/m/Y');
        } catch (Throwable) {
            return 'Não informado';
        }
    }

    public function render()
    {
        $reports = [];
        $storageFailed = false;
        $ids = $this->rememberedIds();
        if ($ids !== []) {
            try {
                $reports = array_map(fn (array $report) => $this->present($report), app(JsonReportRepository::class)->summaries($ids));
            } catch (Throwable $exception) {
                Log::warning('Falha ao ler relatos para acompanhamento.', ['type' => $exception::class]);
                $storageFailed = true;
            }
        }
        $selected = null;
        foreach ($reports as $report) {
            if ($report['id'] === $this->openId) {
                $selected = $report;
            }
        }

        return view('livewire.acompanhar-relato', [
            'reports' => $reports,
            'selected' => $selected,
            'storageFailed' => $storageFailed,
            'steps' => array_keys(config('canal.report_statuses')),
        ])->layout('components.layouts.app', ['title' => 'Acompanhar protocolo']);
    }
}
