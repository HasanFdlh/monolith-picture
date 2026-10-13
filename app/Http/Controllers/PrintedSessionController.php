<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\Session;
use Illuminate\Http\Request;

/**
 * Halaman "Hasil Print": semua sesi yang dicetak dari kiosk, lengkap dengan
 * foto yang tersimpan saat pelanggan menekan tombol Cetak.
 */
class PrintedSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = Session::printed()
            ->with(['booth', 'photoFrame', 'media'])
            ->latest('printed_at');

        if ($request->filled('booth_id')) {
            $query->where('booth_id', $request->booth_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('booth', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('printed_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59',
            ]);
        }

        $limit = in_array((int) $request->input('limit', 12), [12, 24, 48, 96], true)
            ? (int) $request->input('limit', 12)
            : 12;

        return view('printed.index', [
            'title' => 'Hasil Print',
            'subtitle' => 'Foto yang tersimpan saat tombol Cetak ditekan di kiosk',
            'sessions' => $query->paginate($limit)->withQueryString(),
            'booths' => Booth::get(),
            'totalPrinted' => Session::printed()->count(),
            'printedToday' => Session::printed()->whereDate('printed_at', today())->count(),
        ]);
    }

    /**
     * POST /printed-sessions/{session}/unmark
     * Keluarkan dari daftar Hasil Print (sesi dan foto tetap tersimpan).
     */
    public function unmark(Session $session)
    {
        $session->unmarkPrinted();

        return back()->with('success', "Sesi {$session->session_code} dikeluarkan dari Hasil Print.");
    }

    /**
     * DELETE /printed-sessions/{session}
     * Hapus sesi permanen beserta fotonya.
     */
    public function destroy(Session $session)
    {
        $code = $session->session_code;
        $session->load('media')->deleteWithMedia();

        return back()->with('success', "Sesi {$code} dihapus permanen.");
    }

    /**
     * POST /printed-sessions/bulk
     * action = unmark (keluarkan dari daftar) | delete (hapus permanen).
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:unmark,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        // Hanya sesi yang memang ada di daftar Hasil Print.
        $sessions = Session::printed()->with('media')->whereIn('id', $data['ids'])->get();

        foreach ($sessions as $session) {
            $data['action'] === 'delete' ? $session->deleteWithMedia() : $session->unmarkPrinted();
        }

        $n = $sessions->count();

        return back()->with(
            'success',
            $data['action'] === 'delete'
                ? "{$n} sesi dihapus permanen."
                : "{$n} sesi dikeluarkan dari Hasil Print."
        );
    }
}
