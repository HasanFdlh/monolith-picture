<?php

namespace App\Http\Controllers;

use App\Models\PrinterSetting;
use Illuminate\Http\Request;

class PrinterSettingController extends Controller
{
    public function index()
    {
        $setting = PrinterSetting::first();

        return view('settings.printer.index', [
            'title' => 'Printer Settings',
            'subtitle' => 'Configure your connected printer',
            'setting' => $setting,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'printer_name' => ['nullable', 'string', 'max:255'],
            'printer_command' => ['nullable', 'string', 'max:1000'],
            'copies' => ['nullable', 'integer', 'min:1', 'max:10'],
            'paper_size' => ['nullable', 'string', 'max:50'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');

        PrinterSetting::updateOrCreate([], $validated);

        return redirect()->route('printer.settings')->with('success', 'Printer settings saved.');
    }
}
