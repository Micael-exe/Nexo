<?php

namespace App\Http\Controllers;

use App\Services\JsonReportRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Foto privada: o caminho vem do JSON, nunca da URL. */
    public function attachment(JsonReportRepository $reports, string $report, string $attachment)
    {
        $item = collect($reports->find($report)['attachments'] ?? [])->firstWhere('id', $attachment);
        abort_unless($item && Storage::disk('local')->exists($item['path']), 404);

        return Storage::disk('local')->response($item['path'], null, [
            'Content-Type' => $item['mime'],
            'Content-Disposition' => 'inline',
        ]);
    }
}
