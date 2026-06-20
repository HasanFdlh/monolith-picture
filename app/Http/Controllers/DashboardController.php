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
        $booths = Booth::all();

        $totalBooth = Booth::count();
        $totalSession = Session::count();
        $totalMedia = Media::count();
        $totalShare = Share::count();

        $latestSessions = Session::with('booth')
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard.index', compact(
            'booths',
            'totalBooth',
            'totalSession',
            'totalMedia',
            'totalShare',
            'latestSessions'
        ));
    }
}
