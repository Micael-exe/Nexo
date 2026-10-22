<?php

namespace Tests\Feature;

use App\Livewire\NovoRelato;
use App\Services\JsonReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NovoRelatoTest extends TestCase
{
    private string $dataDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->dataDirectory = storage_path('framework/testing/canal-'.Str::uuid());
        File::makeDirectory($this->dataDirectory, 0700, true);
        config(['canal.data_path' => $this->dataDirectory]);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        $safePrefix = storage_path('framework/testing/canal-');
        if (isset($this->dataDirectory) && str_starts_with($this->dataDirectory, $safePrefix)) {
            File::deleteDirectory($this->dataDirectory);
        }
        parent::tearDown();
    }

    public function test_only_the_two_assigned_pages_are_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/novo-relato')->assertOk();
        foreach (['/acompanhar', '/canais', '/login', '/dashboard'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_a_resident_can_submit_without_gps_photos_or_contact(): void
    {
        $component = $this->validForm()->call('submit')->assertHasNoErrors();
        $receipt = $component->get('receipt');
        $this->assertSame('Recebido', $receipt['status']);
        $this->assertMatchesRegularExpression('/^CED-\d{8}-[A-F0-9]{12}$/', $receipt['protocol']);
        $this->assertNotSame($receipt['protocol'], $receipt['credential']);
        $this->assertGreaterThanOrEqual(40, strlen($receipt['credential']));

        $records = $this->records();
        $this->assertCount(1, $records);
        $this->assertNull($records[0]['location']['latitude']);
        $this->assertNull($records[0]['location']['longitude']);
        $this->assertNull($records[0]['contact']['email']);
        $this->assertNull($records[0]['contact']['phone']);
        $this->assertSame([], $records[0]['attachments']);
        $this->assertSame('address', $records[0]['location']['source']);
        $this->assertTrue($records[0]['is_demo']);
        $this->assertSame(config('canal.privacy_version'), $records[0]['privacy_notice']['version']);
        $this->assertNotEmpty($records[0]['privacy_notice']['acknowledged_at']);
    }

    public function test_required_fields_and_privacy_acknowledgement_are_validated(): void
    {
        Livewire::test(NovoRelato::class)->call('submit')->assertHasErrors([
            'category' => 'required',
            'description' => 'required',
            'address' => 'required',
            'reference' => 'required',
            'privacyAcknowledged' => 'accepted',
        ]);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_unlisted_category_and_description_limits_are_rejected(): void
    {
        $this->validForm()->set('category', 'categoria-inexistente')
            ->set('description', 'Curta')->call('submit')
            ->assertHasErrors(['category' => 'in', 'description' => 'min']);
        $this->validForm()->set('description', str_repeat('a', 1001))
            ->call('submit')->assertHasErrors(['description' => 'max']);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_coordinate_pair_and_optional_contacts_are_validated(): void
    {
        $this->validForm()->set('latitude', '-7.94')->set('email', 'email-invalido')
            ->set('phone', '123')->call('submit')->assertHasErrors([
                'longitude' => 'required_with', 'email' => 'email', 'phone',
            ]);
        $this->validForm()->set('latitude', '91')->set('longitude', '-34.88')
            ->call('submit')->assertHasErrors(['latitude' => 'between']);
    }

    public function test_observed_time_is_converted_from_paulista_to_utc(): void
    {
        $this->validForm()->set('observedAt', '2025-08-10T09:30')
            ->call('submit')->assertHasNoErrors();
        $record = $this->records()[0];
        $this->assertSame('2025-08-10T12:30:00+00:00', $record['observed_at']);
        $this->assertStringEndsWith('+00:00', $record['received_at']);
        $this->assertNotSame($record['observed_at'], $record['received_at']);
    }

    public function test_future_observation_is_not_accepted(): void
    {
        $this->validForm()->set('observedAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('submit')->assertHasErrors(['observedAt' => 'before_or_equal']);
    }

    public function test_secrets_and_submission_keys_are_not_stored_as_plaintext(): void
    {
        $component = $this->validForm();
        $key = $component->get('submissionKey');
        $component->call('submit')->assertHasNoErrors();
        $credential = $component->get('receipt')['credential'];
        $contents = File::get($this->dataDirectory.'/relatos.json');
        $record = $this->records()[0];
        $this->assertStringNotContainsString($credential, $contents);
        $this->assertStringNotContainsString($key, $contents);
        $this->assertSame(hash('sha256', $credential), $record['credential_hash']);
        $this->assertSame(hash('sha256', $key), $record['submission_key_hash']);
        $this->assertNotEmpty($record['receipt_secret_encrypted']);
    }

    public function test_repository_retry_returns_the_same_receipt_without_another_record(): void
    {
        $repository = app(JsonReportRepository::class);
        $key = (string) Str::uuid();
        $first = $repository->create($this->reportData(), [], $key);
        $retry = $repository->create($this->reportData(), [], $key);
        $this->assertTrue($first['created']);
        $this->assertFalse($retry['created']);
        $this->assertSame($first['protocol'], $retry['protocol']);
        $this->assertSame($first['credential'], $retry['credential']);
        $this->assertSame($first['received_at'], $retry['received_at']);
        $this->assertCount(1, $this->records());
    }

    public function test_different_submissions_have_different_protocols_and_credentials(): void
    {
        $repository = app(JsonReportRepository::class);
        $first = $repository->create($this->reportData(), [], (string) Str::uuid());
        $second = $repository->create($this->reportData(), [], (string) Str::uuid());
        $this->assertNotSame($first['protocol'], $second['protocol']);
        $this->assertNotSame($first['credential'], $second['credential']);
        $this->assertCount(2, $this->records());
    }

    public function test_repository_retry_does_not_run_the_new_submission_guard(): void
    {
        $repository = app(JsonReportRepository::class);
        $key = (string) Str::uuid();
        $first = $repository->create($this->reportData(), [], $key);
        $guardCalls = 0;
        $guard = function () use (&$guardCalls): void {
            $guardCalls++;
            throw new \RuntimeException('Novo envio bloqueado para este teste.');
        };
        $retry = $repository->create($this->reportData(), [], $key, $guard);
        $this->assertSame(0, $guardCalls);
        $this->assertSame($first['protocol'], $retry['protocol']);
        $this->assertSame($first['credential'], $retry['credential']);
        $this->assertCount(1, $this->records());
    }

    public function test_new_submission_guard_can_block_writing_a_record(): void
    {
        $guardCalled = false;
        try {
            app(JsonReportRepository::class)->create($this->reportData(), [], (string) Str::uuid(), function () use (&$guardCalled): void {
                $guardCalled = true;
                throw new \RuntimeException('Envio bloqueado antes da gravacao.');
            });
            $this->fail('O bloqueio deveria impedir a gravacao.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Envio bloqueado antes da gravacao.', $exception->getMessage());
        }
        $this->assertTrue($guardCalled);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_component_recovers_an_existing_receipt_even_when_new_submissions_are_limited(): void
    {
        $component = $this->validForm();
        $first = app(JsonReportRepository::class)->create($this->reportData(), [], $component->get('submissionKey'));
        $rateKey = 'relatos:'.hash('sha256', request()->ip() ?? 'local');
        for ($attempt = 0; $attempt < 6; $attempt++) {
            RateLimiter::hit($rateKey, 600);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts($rateKey, 5));
        $component->call('submit')->assertHasNoErrors();
        $this->assertSame($first['protocol'], $component->get('receipt')['protocol']);
        $this->assertSame($first['credential'], $component->get('receipt')['credential']);
        $this->assertCount(1, $this->records());
    }

    public function test_retry_recovers_the_receipt_even_when_temporary_upload_is_no_longer_available(): void
    {
        $component = $this->validForm();
        $first = app(JsonReportRepository::class)->create($this->reportData(), [], $component->get('submissionKey'));
        $component->set('photos', ['arquivo-temporario-ja-removido'])->call('submit')->assertHasNoErrors();
        $this->assertSame($first['protocol'], $component->get('receipt')['protocol']);
        $this->assertSame($first['credential'], $component->get('receipt')['credential']);
        $this->assertCount(1, $this->records());
    }

    public function test_new_submission_with_a_non_file_photo_is_a_validation_error(): void
    {
        $this->validForm()->set('photos', ['isto-nao-e-arquivo'])->call('submit')
            ->assertHasErrors(['photos.0' => 'file'])->assertSet('receipt', null);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_repeated_submit_on_the_success_screen_does_not_duplicate(): void
    {
        $component = $this->validForm()->call('submit')->assertHasNoErrors();
        $firstReceipt = $component->get('receipt');
        $component->call('submit')->assertHasNoErrors()->assertSet('receipt', $firstReceipt);
        $this->assertCount(1, $this->records());
    }

    public function test_jpeg_and_png_evidence_is_stored_privately(): void
    {
        $this->validForm()->set('photos', [
            UploadedFile::fake()->image('exemplo.jpg', 40, 40),
            UploadedFile::fake()->image('exemplo.png', 40, 40),
        ])->call('submit')->assertHasNoErrors();
        $attachments = $this->records()[0]['attachments'];
        $this->assertCount(2, $attachments);
        $this->assertSame(['image/jpeg', 'image/png'], array_column($attachments, 'mime'));
        foreach ($attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment['path']);
            $this->assertSame('private', $attachment['visibility']);
            $this->assertFalse(config('filesystems.disks.local.serve'));
            $this->get('/storage/'.$attachment['path'])->assertNotFound();
        }
    }

    public function test_invalid_image_content_is_rejected(): void
    {
        $this->validForm()->set('photos', [
            UploadedFile::fake()->createWithContent('falsa.jpg', 'Isto nao e uma imagem.'),
        ])->call('submit')->assertHasErrors(['photos.0']);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_an_image_above_five_megabytes_is_rejected(): void
    {
        $this->validForm()->set('photos', [
            UploadedFile::fake()->image('grande.jpg', 40, 40)->size(5121),
        ])->assertHasErrors(['photos.0']);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_more_than_three_photos_is_rejected(): void
    {
        $this->validForm()->set('photos', [
            UploadedFile::fake()->image('um.jpg', 20, 20),
            UploadedFile::fake()->image('dois.jpg', 20, 20),
            UploadedFile::fake()->image('tres.jpg', 20, 20),
            UploadedFile::fake()->image('quatro.jpg', 20, 20),
        ])->call('submit')->assertHasErrors(['photos' => 'max']);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_corrupted_json_is_preserved_and_form_can_be_retried(): void
    {
        $corrupt = '{ arquivo deliberadamente corrompido';
        File::put($this->dataDirectory.'/relatos.json', $corrupt);
        $component = $this->validForm();
        $key = $component->get('submissionKey');
        $component->call('submit')->assertHasErrors(['submission'])
            ->assertSet('description', $this->reportData()['description'])
            ->assertSet('address', $this->reportData()['address'])
            ->assertSet('reference', $this->reportData()['reference'])
            ->assertSet('submissionKey', $key)->assertSet('receipt', null);
        $this->assertSame($corrupt, File::get($this->dataDirectory.'/relatos.json'));
        $this->assertSame([], glob($this->dataDirectory.'/.relatos-*.tmp'));
    }

    private function validForm()
    {
        return Livewire::test(NovoRelato::class)
            ->set('category', 'residuos')
            ->set('description', $this->reportData()['description'])
            ->set('address', $this->reportData()['address'])
            ->set('reference', $this->reportData()['reference'])
            ->set('privacyAcknowledged', true);
    }

    private function reportData(): array
    {
        return [
            'category' => 'residuos',
            'description' => 'Dados ficticios: residuos dificultam a passagem de agua no trecho.',
            'address' => 'Rua Exemplo Ficticio, 100',
            'reference' => 'Ao lado de uma escola ficticia',
            'email' => null, 'phone' => null,
            'latitude' => null, 'longitude' => null,
            'observed_at' => null,
        ];
    }

    private function records(): array
    {
        return json_decode(File::get($this->dataDirectory.'/relatos.json'), true, 512, JSON_THROW_ON_ERROR)['relatos'];
    }
}
