<?php

namespace App\Livewire;

use App\Services\JsonReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class NovoRelato extends Component
{
    use WithFileUploads;

    public string $category = '';
    public string $description = '';
    public string $address = '';
    public string $reference = '';
    public string $email = '';
    public string $phone = '';
    public string $observedAt = '';
    public string $latitude = '';
    public string $longitude = '';
    public string $website = '';
    public array $photos = [];
    public bool $privacyAcknowledged = false;

    #[Locked]
    public string $submissionKey;

    #[Locked]
    public ?array $receipt = null;

    public function mount(): void
    {
        $this->submissionKey = (string) Str::uuid();
    }

    protected function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(config('canal.categories')))],
            'description' => ['required', 'string', 'min:20', 'max:1000'],
            'address' => ['required', 'string', 'min:5', 'max:200'],
            'reference' => ['required', 'string', 'min:3', 'max:200'],
            'observedAt' => ['nullable', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\D/', '', $value);
                if (! preg_match('/^[+()\d\s.-]+$/', $value) || strlen($digits) < 10 || strlen($digits) > 13) {
                    $fail('Informe um telefone com DDD ou deixe o campo vazio.');
                }
            }],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'photos' => ['array', 'max:3'],
            'photos.*' => ['bail', 'required', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120', 'dimensions:max_width=8000,max_height=8000', function ($attribute, $value, $fail) {
                $size = @getimagesize($value->getRealPath());
                if (! $size || $size[0] * $size[1] > 16000000) {
                    $fail('Cada imagem deve ter até 16 megapixels. Reduza a resolução e tente novamente.');
                }
            }],
            'privacyAcknowledged' => ['accepted'],
            'website' => ['max:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'category.required' => 'Escolha o tipo de problema observado.',
            'category.in' => 'Escolha uma das categorias disponíveis.',
            'description.required' => 'Descreva o que você observou.',
            'description.min' => 'A descrição precisa ter pelo menos 20 caracteres.',
            'description.max' => 'A descrição pode ter até 1.000 caracteres.',
            'address.required' => 'Informe o endereço ou o nome da via.',
            'address.min' => 'Informe um endereço com pelo menos 5 caracteres.',
            'address.max' => 'O endereço pode ter até 200 caracteres.',
            'reference.required' => 'Informe um ponto de referência para localizar o trecho.',
            'reference.min' => 'O ponto de referência precisa ter pelo menos 3 caracteres.',
            'reference.max' => 'O ponto de referência pode ter até 200 caracteres.',
            'observedAt.date_format' => 'Informe uma data e hora válidas ou deixe em branco.',
            'observedAt.before_or_equal' => 'O momento da observação não pode estar no futuro.',
            'email.email' => 'Informe um e-mail válido ou deixe o campo vazio.',
            'email.max' => 'O e-mail pode ter até 254 caracteres.',
            'phone.max' => 'O telefone pode ter até 30 caracteres.',
            'latitude.required_with' => 'As duas coordenadas devem ser informadas juntas.',
            'longitude.required_with' => 'As duas coordenadas devem ser informadas juntas.',
            'latitude.numeric' => 'A latitude precisa ser numérica.',
            'longitude.numeric' => 'A longitude precisa ser numérica.',
            'latitude.between' => 'Latitude inválida. Remova as coordenadas e tente novamente.',
            'longitude.between' => 'Longitude inválida. Remova as coordenadas e tente novamente.',
            'photos.max' => 'Você pode enviar até 3 fotografias.',
            'photos.*.image' => 'Envie apenas fotografias JPEG ou PNG válidas.',
            'photos.*.mimes' => 'Envie apenas fotografias JPEG ou PNG.',
            'photos.*.mimetypes' => 'O conteúdo do arquivo precisa ser JPEG ou PNG.',
            'photos.*.max' => 'Cada fotografia pode ter até 5 MB.',
            'photos.*.dimensions' => 'A largura e a altura da imagem podem ter até 8.000 pixels.',
            'privacyAcknowledged.accepted' => 'Confirme que leu o aviso de privacidade antes de enviar.',
            'website.max' => 'Não foi possível validar o envio. Atualize a página e tente novamente.',
        ];
    }

    public function submit(JsonReportRepository $repository): void
    {
        if ($this->receipt !== null) {
            return;
        }
        $this->resetErrorBag();
        foreach (['description', 'address', 'reference', 'email', 'phone', 'observedAt', 'latitude', 'longitude'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
        $rateKey = 'relatos:'.hash('sha256', request()->ip() ?? 'local');
        try {
            $receipt = $repository->create([
                'category' => $this->category,
                'description' => $this->description,
                'address' => $this->address,
                'reference' => $this->reference,
                'email' => $this->email !== '' ? $this->email : null,
                'phone' => $this->phone !== '' ? $this->phone : null,
                'latitude' => $this->latitude !== '' ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== '' ? (float) $this->longitude : null,
                'observed_at' => null,
            ], $this->photos, $this->submissionKey, function (array &$data) use ($rateKey): void {
                // Executar somente para uma tentativa nova: um retry pode referenciar
                // anexos temporários já removidos após a primeira gravação.
                $this->validate();
                $data['observed_at'] = $this->observedAt !== ''
                    ? CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $this->observedAt, 'America/Sao_Paulo')->utc()->toIso8601String()
                    : null;
                if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                    throw ValidationException::withMessages(['submission' => 'Muitos envios neste momento. Aguarde alguns minutos e tente novamente. Seus campos foram mantidos.']);
                }
                RateLimiter::hit($rateKey, 600);
            });
        } catch (ValidationException $exception) {
            $this->dispatch('validation-failed');
            throw $exception;
        } catch (Throwable $exception) {
            // Não registrar conteúdo, contato, chave de envio ou credencial nos logs.
            Log::warning('Falha ao persistir relato demonstrativo.', ['type' => $exception::class]);
            $this->addError('submission', 'Não foi possível salvar agora. Seus campos foram mantidos. Tente enviar novamente; a mesma tentativa não cria outro relato.');
            $this->dispatch('validation-failed');
            return;
        }

        foreach ($this->photos as $photo) {
            try { $photo->delete(); } catch (Throwable) { /* Limpeza posterior pelo Livewire. */ }
        }
        $this->reset('category', 'description', 'address', 'reference', 'email', 'phone', 'observedAt', 'latitude', 'longitude', 'website', 'photos', 'privacyAcknowledged');
        unset($receipt['created']);
        $receipt['received_at'] = CarbonImmutable::parse($receipt['received_at'])->setTimezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i');
        $this->receipt = $receipt;
        $this->dispatch('receipt-created');
    }

    public function removePhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            try { $this->photos[$index]->delete(); } catch (Throwable) { /* Limpeza posterior pelo Livewire. */ }
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
            $this->resetValidation('photos');
        }
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->resetErrorBag();
        $this->submissionKey = (string) Str::uuid();
        $this->dispatch('form-reset');
    }

    public function render()
    {
        return view('livewire.novo-relato', ['categories' => config('canal.categories')])
            ->layout('components.layouts.app', ['title' => 'Novo relato']);
    }
}
