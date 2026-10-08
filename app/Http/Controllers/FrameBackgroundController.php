<?php

namespace App\Http\Controllers;

use App\Models\FrameBackground;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FrameBackgroundController extends Controller
{
    public function index()
    {
        $rows = FrameBackground::all()->keyBy('frame_key');

        $frames = collect(FrameBackground::CATALOG)
            ->map(fn (array $frame) => $frame + ['background' => $rows->get($frame['key'])])
            ->all();

        return view('frame-backgrounds.index', [
            'title' => 'Frame Backgrounds',
            'subtitle' => 'Upload gambar yang mengisi area putih pada frame bawaan aplikasi',
            'frames' => $frames,
        ]);
    }

    public function update(Request $request, string $key)
    {
        $frame = FrameBackground::catalogEntry($key);
        abort_if($frame === null, 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $file = $request->file('file');
        // Ukuran asli gambar menentukan rasio lembar di aplikasi.
        $size = @getimagesize($file->getRealPath());

        $background = FrameBackground::firstOrNew(['frame_key' => $key]);

        if ($background->image_path) {
            Storage::disk('public')->delete($background->image_path);
        }

        $background->name = $frame['name'];
        $background->image_path = $file->store('frame-backgrounds', 'public');
        $background->width = $size[0] ?? null;
        $background->height = $size[1] ?? null;
        $background->is_active = true;
        $background->save();
        // updated_at ikut berubah walau path sama, supaya cache aplikasi diperbarui.
        $background->touch();

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Background {$frame['name']} berhasil diupload.");
    }

    /** Editor area foto: geser/tarik kotak di atas gambar background. */
    public function edit(string $key)
    {
        $frame = FrameBackground::catalogEntry($key);
        abort_if($frame === null, 404);

        $background = FrameBackground::where('frame_key', $key)->first();
        if (!$background || !$background->image_path) {
            return redirect()
                ->route('frame-backgrounds.index')
                ->withErrors(['file' => 'Upload gambar background dulu sebelum mengatur area foto.']);
        }

        return view('frame-backgrounds.edit', [
            'title' => 'Area Foto - ' . $frame['name'],
            'subtitle' => 'Atur di mana foto ditempatkan di atas background',
            'frame' => $frame,
            'background' => $background,
        ]);
    }

    public function saveArea(Request $request, string $key)
    {
        $frame = FrameBackground::catalogEntry($key);
        abort_if($frame === null, 404);

        $data = $request->validate([
            'area_x' => ['required', 'numeric', 'min:0', 'max:95'],
            'area_y' => ['required', 'numeric', 'min:0', 'max:95'],
            'area_w' => ['required', 'numeric', 'min:5', 'max:100'],
            'area_h' => ['required', 'numeric', 'min:5', 'max:100'],
        ]);

        abort_if(
            $data['area_x'] + $data['area_w'] > 100.001 || $data['area_y'] + $data['area_h'] > 100.001,
            422,
            'Area foto harus berada di dalam frame.'
        );

        $background = FrameBackground::where('frame_key', $key)->firstOrFail();
        $background->update([
            'area_x' => round((float) $data['area_x'], 3),
            'area_y' => round((float) $data['area_y'], 3),
            'area_w' => round((float) $data['area_w'], 3),
            'area_h' => round((float) $data['area_h'], 3),
        ]);
        $background->touch();

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Area foto {$frame['name']} disimpan.");
    }

    public function destroy(string $key)
    {
        $frame = FrameBackground::catalogEntry($key);
        abort_if($frame === null, 404);

        $background = FrameBackground::where('frame_key', $key)->first();

        if ($background) {
            if ($background->image_path) {
                Storage::disk('public')->delete($background->image_path);
            }
            $background->delete();
        }

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Background {$frame['name']} dihapus (kembali putih).");
    }
}
