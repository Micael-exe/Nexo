<?php

namespace Tests\Feature;

use App\Livewire\Cadastro;
use App\Livewire\Entrar;
use App\Livewire\Painel;
use App\Services\JsonReportRepository;
use App\Services\JsonUserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AreaMunicipalTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->dir = storage_path('framework/testing/canal-'.Str::uuid());
        File::makeDirectory($this->dir, 0700, true);
        config(['canal.data_path' => $this->dir, 'canal.registration_code' => 'codigo-secreto']);
        Storage::fake('local');
        RateLimiter::clear('cadastro|127.0.0.1');
    }

    protected function tearDown(): void
    {
        if (str_starts_with($this->dir, storage_path('framework/testing/canal-'))) {
            File::deleteDirectory($this->dir);
        }
        parent::tearDown();
    }

    private function staff(): array
    {
        return app(JsonUserRepository::class)->create('Maria Servidora', 'maria@paulista.test', 'senha-forte-123');
    }

    private function report(): string
    {
        $r = app(JsonReportRepository::class);
        $r->create([
            'category' => 'residuos', 'description' => 'Lixo bloqueando a boca de lobo na esquina.',
            'address' => 'Rua das Flores, 10', 'reference' => 'Perto da escola', 'latitude' => null, 'longitude' => null,
            'email' => 'a@b.test', 'phone' => '', 'observed_at' => CarbonImmutable::now()->toIso8601String(),
        ], [], 'chave-'.Str::random(8));

        return $r->all()[0]['id'];
    }

    public function test_panel_requires_login(): void
    {
        $this->get('/painel')->assertRedirect('/entrar');
        $this->post('/sair')->assertRedirect('/entrar');
    }

    public function test_staff_can_login_and_logout(): void
    {
        $this->staff();
        Livewire::test(Entrar::class)->set('email', 'maria@paulista.test')->set('password', 'senha-forte-123')
            ->call('entrar')->assertRedirect('/painel');
        $this->assertAuthenticated();
        $this->get('/painel')->assertOk()->assertSee('Relatos recebidos');
        $this->get('/entrar')->assertRedirect('/painel');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->be(app('auth')->createUserProvider('users')->retrieveById($this->staff()['id']));
        $this->post('/sair')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_and_rate_limited(): void
    {
        $this->staff();
        $c = Livewire::test(Entrar::class)->set('email', 'maria@paulista.test');
        for ($i = 0; $i < 5; $i++) {
            $c->set('password', 'errada')->call('entrar')->assertHasErrors('email');
        }
        $c->set('password', 'senha-forte-123')->call('entrar')->assertHasErrors('email');
        $this->assertGuest();
        RateLimiter::clear('login:maria@paulista.test|127.0.0.1');
    }

    public function test_registration_needs_the_access_code(): void
    {
        Livewire::test(Cadastro::class)->set('name', 'João Silva')->set('email', 'joao@paulista.test')
            ->set('password', 'senha-forte-123')->set('password_confirmation', 'senha-forte-123')
            ->set('accessCode', 'errado')->call('cadastrar')->assertHasErrors('accessCode');
        $this->assertNull(app(JsonUserRepository::class)->findByEmail('joao@paulista.test'));

        Livewire::test(Cadastro::class)->set('name', 'João Silva')->set('email', 'JOAO@paulista.test')
            ->set('password', 'senha-forte-123')->set('password_confirmation', 'senha-forte-123')
            ->set('accessCode', 'codigo-secreto')->call('cadastrar')->assertRedirect('/painel');
        $user = app(JsonUserRepository::class)->findByEmail('joao@paulista.test');
        $this->assertNotSame('senha-forte-123', $user['password']);
        $this->assertAuthenticated();
    }

    public function test_registration_is_disabled_without_a_configured_code(): void
    {
        config(['canal.registration_code' => null]);
        Livewire::test(Cadastro::class)->assertSee('desativado');
        Livewire::test(Cadastro::class)->set('accessCode', '')->call('cadastrar')->assertNotFound();
    }

    public function test_duplicate_email_and_short_password_are_rejected(): void
    {
        $this->staff();
        Livewire::test(Cadastro::class)->set('name', 'Outra')->set('email', 'maria@paulista.test')
            ->set('password', 'senha-forte-123')->set('password_confirmation', 'senha-forte-123')
            ->set('accessCode', 'codigo-secreto')->call('cadastrar')->assertHasErrors('email');
        Livewire::test(Cadastro::class)->set('name', 'Outra')->set('email', 'x@paulista.test')
            ->set('password', 'curta')->set('password_confirmation', 'curta')
            ->set('accessCode', 'codigo-secreto')->call('cadastrar')->assertHasErrors('password');
    }

    public function test_staff_updates_status_with_history_and_secrets_stay_hidden(): void
    {
        $id = $this->report();
        $user = $this->staff();
        $this->be(new \Illuminate\Auth\GenericUser($user));

        Livewire::test(Painel::class)->assertSee('Rua das Flores, 10')
            ->call('select', $id)->assertSee('Perto da escola')
            ->set('newStatus', 'Encaminhado')->set('note', 'Equipe 3 acionada')->call('saveStatus')->assertHasNoErrors();

        $report = app(JsonReportRepository::class)->find($id);
        $this->assertSame('Encaminhado', $report['status']);
        $this->assertSame('Maria Servidora', $report['history'][0]['by']);
        $this->assertArrayNotHasKey('credential_hash', $report);
        $this->assertArrayNotHasKey('receipt_secret_encrypted', $report);

        Livewire::test(Painel::class)->call('select', $id)->set('newStatus', 'Inválido')->call('saveStatus')->assertHasErrors('newStatus');
    }

    public function test_filters_narrow_the_list(): void
    {
        $this->report();
        $this->be(new \Illuminate\Auth\GenericUser($this->staff()));
        Livewire::test(Painel::class)->set('filterStatus', 'Resolvido')->assertSee('Nenhum relato corresponde')
            ->set('filterStatus', '')->set('search', 'flores')->assertSee('Rua das Flores');
    }

    public function test_attachments_are_only_served_to_staff(): void
    {
        $id = Str::uuid()->toString();
        $this->get("/painel/anexos/$id/$id")->assertRedirect('/entrar');
        $this->be(new \Illuminate\Auth\GenericUser($this->staff()));
        $this->get("/painel/anexos/$id/$id")->assertNotFound();
        $this->get('/painel/anexos/../x/y')->assertNotFound();
    }
}
