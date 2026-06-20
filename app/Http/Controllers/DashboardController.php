<?php

namespace App\Http\Controllers;


use App\Models\Booth;
use App\Models\Session;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Session::with(['booth', 'media'])
            ->withCount('media')
            ->withSum('media', 'size')
            ->latest();

        // Filter Booth
        if ($request->filled('booth_id')) {
            $query->where('booth_id', $request->booth_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhereHas('booth', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Date Range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59',
            ]);
        }

        $limit = $request->input('limit', 10);

        $sessions = $query
            ->paginate($limit)
            ->withQueryString();

        $sessions->getCollection()->transform(function ($session) {
            $session->total_files = $session->media->count();
            $session->total_size = $session->media->sum('size');

            return $session;
        });

        $booths = Booth::get();

        return view('dashboard.index', compact('sessions', 'booths'));
    }
}
