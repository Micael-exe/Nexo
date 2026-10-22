<?php

namespace App\Livewire;

use App\Services\DemoChannelCatalog;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class Canais extends Component
{
    #[Url(as: 'busca', history: true)]
    public string $search = '';

    #[Url(as: 'situacao', history: true)]
    public string $status = '';

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
    }

    private function date(?string $value): string
    {
        if ($value === null) {
            return 'Não informado';
        }
        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value, 'America/Sao_Paulo')->format('d/m/Y');
        } catch (Throwable) {
            return 'Não informado';
        }
    }

    public function render()
    {
        $statuses = config('canal.channel_statuses');
        $term = mb_substr(trim($this->search), 0, 80);
        $status = array_key_exists($this->status, $statuses) ? $this->status : '';

        $channels = array_map(fn (array $channel) => array_merge($channel, [
            'status_meta' => $statuses[$channel['status']],
            'observed_label' => $this->date($channel['observed_at']),
            'maintenance_label' => $this->date($channel['last_maintenance']),
            'next_label' => $this->date($channel['next_inspection']),
        ]), DemoChannelCatalog::search($term, $status));

        return view('livewire.canais', [
            'channels' => $channels,
            'total' => count(DemoChannelCatalog::all()),
            'statuses' => $statuses,
            'filtered' => $term !== '' || $status !== '',
        ])->layout('components.layouts.app', ['title' => 'Canais de Paulista']);
    }
}
