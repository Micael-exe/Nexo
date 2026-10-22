<?php

namespace Tests\Feature;

use App\Livewire\AcompanharRelato;
use App\Livewire\Canais;
use App\Services\JsonReportRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AcompanharCanaisTest extends TestCase
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
        $safePrefix = storage_path('framework/testing/canal-');
        if (isset($this->dataDirectory) && str_starts_with($this->dataDirectory, $safePrefix)) {
            File::deleteDirectory($this->dataDirectory);
        }
        parent::tearDown();
    }

    public function test_tracking_with_no_records_shows_empty_state(): void
    {
        Livewire::test(AcompanharRelato::class)
            ->assertSee('Nenhum protocolo consultado nesta sessão')
            ->assertDontSee('Adicionar informações');
    }

    public function test_correct_protocol_and_key_opens_only_the_public_view(): void
    {
        $receipt = $this->createReport(['email' => 'contato.ficticio@example.com', 'phone' => '(81) 99999-0000']);

        Livewire::test(AcompanharRelato::class)
            ->set('protocol', strtolower($receipt['protocol']))
            ->set('credential', ' '.$receipt['credential'].' ')
            ->call('consult')
            ->assertHasNoErrors()
            ->assertSet('credential', '')
            ->assertSet('protocol', '')
            ->assertSee($receipt['protocol'])
            ->assertSee('Recebido')
            ->assertSee('Relato recebido')
            ->assertSee('Adicionar informações')
            ->assertSee('Não informado')
            ->assertDontSee('contato.ficticio@example.com')
            ->assertDontSee('99999-0000')
            ->assertDontSee($receipt['credential']);
    }

    public function test_protocol_alone_or_wrong_key_does_not_reveal_the_report(): void
    {
        $receipt = $this->createReport();
        $wrongKey = str_repeat('a', 43);

        $message = 'Não encontramos um relato com esse protocolo e essa chave.';
        Livewire::test(AcompanharRelato::class)
            ->set('protocol', $receipt['protocol'])
            ->set('credential', $wrongKey)
            ->call('consult')
            ->assertHasErrors('lookup')
            ->assertSet('openId', null)
            ->assertSee($message)
            ->assertDontSee('Dados ficticios');

        // Protocolo inexistente recebe exatamente a mesma resposta: não confirma qual dado falhou.
        Livewire::test(AcompanharRelato::class)
            ->set('protocol', 'CED-20260101-AAAAAAAAAAAA')
            ->set('credential', $wrongKey)
            ->call('consult')
            ->assertHasErrors('lookup')
            ->assertSee($message);

        // A chave de outro relato também não abre este protocolo.
        $other = $this->createReport();
        Livewire::test(AcompanharRelato::class)
            ->set('protocol', $receipt['protocol'])
            ->set('credential', $other['credential'])
            ->call('consult')
            ->assertHasErrors('lookup')
            ->assertSet('openId', null);
    }

    public function test_input_format_is_validated_before_any_lookup(): void
    {
        Livewire::test(AcompanharRelato::class)->call('consult')
            ->assertHasErrors(['protocol' => 'required', 'credential' => 'required']);
        Livewire::test(AcompanharRelato::class)
            ->set('protocol', 'qualquer coisa')->set('credential', 'curta')->call('consult')
            ->assertHasErrors(['protocol' => 'regex', 'credential' => 'regex']);
        $this->assertFileDoesNotExist($this->dataDirectory.'/relatos.json');
    }

    public function test_repeated_wrong_keys_for_one_protocol_are_rate_limited(): void
    {
        $receipt = $this->createReport();
        $component = Livewire::test(AcompanharRelato::class)->set('protocol', $receipt['protocol']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->set('credential', str_repeat('b', 43))->call('consult');
        }
        $component->set('credential', $receipt['credential'])->call('consult')
            ->assertHasErrors('lookup')
            ->assertSet('openId', null)
            ->assertSee('Muitas tentativas');
    }

    public function test_a_report_cannot_be_opened_without_proving_the_key_in_this_session(): void
    {
        $this->createReport();
        $id = json_decode(File::get($this->dataDirectory.'/relatos.json'), true)['relatos'][0]['id'];

        Livewire::test(AcompanharRelato::class)->call('open', $id)->assertSet('openId', null);
    }

    public function test_ending_the_consultation_clears_the_session_list(): void
    {
        $receipt = $this->createReport();
        Livewire::test(AcompanharRelato::class)
            ->set('protocol', $receipt['protocol'])->set('credential', $receipt['credential'])->call('consult')
            ->assertSee($receipt['protocol'])
            ->call('endSession')
            ->assertSet('openId', null)
            ->assertDontSee($receipt['protocol'])
            ->assertSee('Nenhum protocolo consultado nesta sessão');
    }

    public function test_corrupted_storage_is_reported_without_replacing_the_file(): void
    {
        File::put($this->dataDirectory.'/relatos.json', '{ invalido');
        Livewire::test(AcompanharRelato::class)
            ->set('protocol', 'CED-20260101-AAAAAAAAAAAA')->set('credential', str_repeat('c', 43))->call('consult')
            ->assertHasErrors('lookup')->assertSee('Não foi possível consultar agora');
        $this->assertSame('{ invalido', File::get($this->dataDirectory.'/relatos.json'));
    }

    public function test_channels_list_is_labelled_as_demonstration_and_shows_text_status(): void
    {
        Livewire::test(Canais::class)
            ->assertSee('Canais de Paulista')
            ->assertSee('Canal do Centro')->assertSee('Canal do Pina')->assertSee('Canal de Janga')
            ->assertSee('Canal de Abacaxi')->assertSee('Canal de Maranguape')
            ->assertSee('Dados fictícios')
            ->assertSee('Em dia')->assertSee('Atenção')->assertSee('Com obstrução')
            ->assertSee('observação técnica datada')
            ->assertSee('5 de 5 canais no piloto');
    }

    public function test_search_ignores_case_and_accents_and_matches_district_or_section(): void
    {
        Livewire::test(Canais::class)->set('search', 'MARANGUAPÊ')
            ->assertSee('Canal de Maranguape')->assertDontSee('Canal do Centro');
        Livewire::test(Canais::class)->set('search', 'trecho 06')
            ->assertSee('Canal de Abacaxi')->assertDontSee('Canal de Janga');
        Livewire::test(Canais::class)->set('search', 'pina')
            ->assertSee('Canal do Pina')->assertSee('1 de 5 canais com estes filtros');
    }

    public function test_status_filter_and_invalid_status_values(): void
    {
        Livewire::test(Canais::class)->set('status', 'com_obstrucao')
            ->assertSee('Canal de Abacaxi')->assertDontSee('Canal do Pina');
        Livewire::test(Canais::class)->set('status', '<script>')
            ->assertSee('5 de 5 canais no piloto');
    }

    public function test_empty_result_can_be_cleared(): void
    {
        Livewire::test(Canais::class)->set('search', 'inexistente')
            ->assertSee('Nenhum canal encontrado')
            ->call('clearFilters')
            ->assertSet('search', '')->assertSee('Canal do Centro');
    }

    private function createReport(array $overrides = []): array
    {
        $receipt = app(JsonReportRepository::class)->create(array_merge([
            'category' => 'residuos',
            'description' => 'Dados ficticios: residuos dificultam a passagem de agua no trecho.',
            'address' => 'Rua Exemplo Ficticio, 100',
            'reference' => 'Ao lado de uma escola ficticia',
            'email' => null, 'phone' => null,
            'latitude' => null, 'longitude' => null,
            'observed_at' => null,
        ], $overrides), [], (string) Str::uuid());

        return $receipt;
    }
}
