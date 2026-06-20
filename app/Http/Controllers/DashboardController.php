<?php

namespace App\Http\Controllers;


use App\Models\Booth;
use App\Models\Session;
use App\Models\Media;
use App\Models\Share;

class DashboardController extends Controller
{
    public function index()
    {
        $sessions = Session::with(['booth', 'media'])
            ->latest()
            ->get()
            ->map(function ($session) {
                $session->total_files = $session->media->count();
                $session->total_size = $session->media->sum('size');
                return $session;
            });

        return view('dashboard.index', compact('sessions'));
    }
}
