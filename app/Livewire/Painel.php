<?php

namespace App\Livewire;

use App\Services\JsonReportRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Painel extends Component
{
    private const PER_PAGE = 10;

    public string $filterStatus = '';
    public string $filterCategory = '';
    public string $search = '';
    public int $page = 1;
    public ?string $selectedId = null;
    public string $newStatus = '';
    public string $note = '';
    public ?string $saved = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['filterStatus', 'filterCategory', 'search'], true)) {
            $this->page = 1;
        }
    }

    public function select(string $id, JsonReportRepository $reports): void
    {
        $this->authorizeStaff();
        $report = $reports->find($id);
        $this->selectedId = $report['id'] ?? null;
        $this->newStatus = $report['status'] ?? '';
        $this->note = '';
        $this->saved = null;
        $this->dispatch('report-selected');
    }

    public function close(): void
    {
        $this->selectedId = null;
        $this->saved = null;
    }

    public function previous(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function next(): void
    {
        $this->page++;
    }

    public function saveStatus(JsonReportRepository $reports): void
    {
        $this->authorizeStaff();
        $this->validate([
            'newStatus' => 'required|in:'.implode(',', config('canal.statuses')),
            'note' => 'nullable|string|max:500',
        ], [], ['newStatus' => 'situação', 'note' => 'observação']);

        if ($this->selectedId && $reports->updateStatus($this->selectedId, $this->newStatus, $this->note, Auth::user()->name)) {
            $this->saved = 'Situação atualizada.';
            $this->note = '';
        }
    }

    private function authorizeStaff(): void
    {
        abort_unless(Auth::check(), 403);
    }

    public function render(JsonReportRepository $repository)
    {
        $this->authorizeStaff();
        $all = $repository->all();
        $statuses = config('canal.statuses');

        $counts = array_fill_keys($statuses, 0);
        $byCategory = array_fill_keys(array_keys(config('canal.categories')), 0);
        foreach ($all as $r) {
            $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
            $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + 1;
        }

        $needle = mb_strtolower(trim($this->search));
        $filtered = array_values(array_filter($all, function ($r) use ($needle) {
            if ($this->filterStatus !== '' && $r['status'] !== $this->filterStatus) {
                return false;
            }
            if ($this->filterCategory !== '' && $r['category'] !== $this->filterCategory) {
                return false;
            }

            return $needle === '' || str_contains(mb_strtolower($r['protocol'].' '.$r['location']['address'].' '.$r['location']['reference'].' '.$r['description']), $needle);
        }));

        $lastPage = max(1, (int) ceil(count($filtered) / self::PER_PAGE));
        $this->page = min(max(1, $this->page), $lastPage);

        return view('livewire.painel', [
            'counts' => $counts,
            'byCategory' => $byCategory,
            'total' => count($all),
            'reports' => array_slice($filtered, ($this->page - 1) * self::PER_PAGE, self::PER_PAGE),
            'filteredTotal' => count($filtered),
            'lastPage' => $lastPage,
            'selected' => $this->selectedId ? collect($all)->firstWhere('id', $this->selectedId) : null,
            'categories' => config('canal.categories'),
            'statuses' => $statuses,
        ])->layout('components.layouts.app', ['title' => 'Painel municipal']);
    }
}
