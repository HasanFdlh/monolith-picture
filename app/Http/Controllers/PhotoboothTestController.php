<?php

namespace App\Http\Controllers;

use App\Models\Booth;

class PhotoboothTestController extends Controller
{
    public function index()
    {
        return view('photobooth-test.index', [
            'title' => 'Photobooth API Test',
            'subtitle' => 'Test the capture, template, grid, print, and email flow through the REST API.',
            'booths' => Booth::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
