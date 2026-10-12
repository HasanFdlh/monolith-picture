<?php

namespace App\Http\Controllers;

use App\Models\FrameBackground;
use App\Services\ImageResizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Perpustakaan gambar background (berlaku untuk semua frame di aplikasi).
 *
 * Semua background dijadikan SATU format ukuran (config photobooth.background_width
 * x background_height, bawaan 465x659 px): gambar dipotong di tengah agar pas
 * (tidak gepeng). Jadi ukuran dan bentuk lembar di aplikasi selalu seragam.
 */
class FrameBackgroundController extends Controller
{
    public function index()
    {
        $backgrounds = FrameBackground::where('frame_key', FrameBackground::UNIVERSAL_KEY)
            ->ordered()
            ->get();

        [$w, $h] = $this->standardSize();

        return view('frame-backgrounds.index', [
            'title' => 'Background',
            'subtitle' => 'Upload gambar background yang sudah jadi; customer memilihnya di aplikasi',
            'backgrounds' => $backgrounds,
            'standardWidth' => $w,
            'standardHeight' => $h,
            'mismatched' => $backgrounds->filter(fn (FrameBackground $b) => !$this->matchesStandard($b))->count(),
            'gdAvailable' => ImageResizer::available(),
        ]);
    }

    /** Upload satu atau banyak gambar background sekaligus. */
    public function store(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $query = FrameBackground::where('frame_key', FrameBackground::UNIVERSAL_KEY);
        $order = (int) $query->max('sort_order');
        $count = $query->count();

        $created = 0;
        $failed = [];
        foreach ($request->file('files') as $file) {
            $order++;
            $count++;

            try {
                [$path, $width, $height] = $this->storeStandardized($file);
            } catch (Throwable $e) {
                $failed[] = $file->getClientOriginalName() . ' (' . $e->getMessage() . ')';
                continue;
            }

            FrameBackground::create([
                'frame_key' => FrameBackground::UNIVERSAL_KEY,
                'name' => $this->nameFromFile($file, $count),
                'image_path' => $path,
                'width' => $width,
                'height' => $height,
                'is_active' => true,
                'sort_order' => $order,
            ]);
            $created++;
        }

        [$sw, $sh] = $this->standardSize();
        $message = "{$created} background berhasil diupload"
            . (ImageResizer::available() ? " dan dijadikan {$sw}×{$sh} px." : '. (GD belum aktif, ukuran asli dipakai.)');

        $redirect = redirect()->route('frame-backgrounds.index')->with('success', $message);

        return $failed === []
            ? $redirect
            : $redirect->withErrors(['file' => 'Gagal: ' . implode('; ', $failed)]);
    }

    /** Ganti gambar sebuah background (nama dan area foto tetap). */
    public function replace(Request $request, FrameBackground $background)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        try {
            [$path, $width, $height] = $this->storeStandardized($request->file('file'));
        } catch (Throwable $e) {
            return back()->withErrors(['file' => 'Gagal: ' . $e->getMessage()]);
        }

        if ($background->image_path) {
            Storage::disk('public')->delete($background->image_path);
        }

        $background->update(['image_path' => $path, 'width' => $width, 'height' => $height]);
        // updated_at berubah walau path sama, supaya cache aplikasi diperbarui.
        $background->touch();

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Gambar \"{$background->name}\" diganti.");
    }

    /**
     * Samakan SEMUA background yang ukurannya belum sesuai format standar
     * (mis. yang diupload sebelum format ini ditetapkan). File gambar ditimpa;
     * bila bentuknya berubah, area foto dikembalikan ke bawaan.
     */
    public function normalize()
    {
        if (!ImageResizer::available()) {
            return back()->withErrors(['file' => 'Ekstensi PHP GD belum aktif (aktifkan extension=gd di php.ini).']);
        }

        [$w, $h] = $this->standardSize();
        $disk = Storage::disk('public');

        $done = 0;
        $failed = [];

        $rows = FrameBackground::where('frame_key', FrameBackground::UNIVERSAL_KEY)->ordered()->get();
        foreach ($rows as $row) {
            if ($this->matchesStandard($row)) {
                continue;
            }

            $path = ltrim((string) $row->image_path, '/');
            if ($path === '' || !$disk->exists($path)) {
                $failed[] = "{$row->name} (file tidak ditemukan)";
                continue;
            }

            $oldAspect = $row->aspect();

            try {
                $disk->put($path, ImageResizer::resize(
                    $disk->get($path),
                    $w,
                    $h,
                    ImageResizer::FIT_COVER,
                    pathinfo($path, PATHINFO_EXTENSION) ?: 'png'
                ));
            } catch (Throwable $e) {
                $failed[] = "{$row->name} ({$e->getMessage()})";
                continue;
            }

            $values = ['width' => $w, 'height' => $h];
            if ($oldAspect && abs($oldAspect - $w / $h) > 0.01) {
                // Area foto lama mengacu ke bentuk gambar yang lama.
                $values += ['area_x' => null, 'area_y' => null, 'area_w' => null, 'area_h' => null];
            }
            $row->update($values);
            $row->touch();
            $done++;
        }

        $redirect = redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "{$done} background disamakan ke {$w}×{$h} px.");

        return $failed === []
            ? $redirect
            : $redirect->withErrors(['file' => 'Gagal: ' . implode('; ', $failed)]);
    }

    /** Ukuran standar background (px): [lebar, tinggi]. */
    private function standardSize(): array
    {
        return [
            max(100, (int) config('photobooth.background_width', 465)),
            max(100, (int) config('photobooth.background_height', 659)),
        ];
    }

    private function matchesStandard(FrameBackground $background): bool
    {
        [$w, $h] = $this->standardSize();

        return (int) $background->width === $w && (int) $background->height === $h;
    }

    /**
     * Simpan gambar upload ke disk `public` dengan ukuran standar (dipotong di
     * tengah agar pas). Tanpa GD, file disimpan apa adanya.
     *
     * @return array{0: string, 1: ?int, 2: ?int}  [path, lebar, tinggi]
     */
    private function storeStandardized(UploadedFile $file): array
    {
        if (!ImageResizer::available()) {
            $size = @getimagesize($file->getRealPath());

            return [$file->store('frame-backgrounds', 'public'), $size[0] ?? null, $size[1] ?? null];
        }

        [$w, $h] = $this->standardSize();
        $ext = $this->extensionOf($file);
        $path = 'frame-backgrounds/' . Str::uuid() . '.' . $ext;

        Storage::disk('public')->put($path, ImageResizer::resize(
            (string) file_get_contents($file->getRealPath()),
            $w,
            $h,
            ImageResizer::FIT_COVER,
            $ext
        ));

        return [$path, $w, $h];
    }

    private function extensionOf(UploadedFile $file): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png');

        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    /** Nama awal dari nama file ("pantai_biru.png" -> "pantai biru"). */
    private function nameFromFile(UploadedFile $file, int $number): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = trim((string) preg_replace('/[\s_\-]+/', ' ', $base));

        return $name !== '' ? Str::limit($name, 60, '') : 'Background ' . $number;
    }

    public function rename(Request $request, FrameBackground $background)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        $background->update(['name' => trim($data['name'])]);

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', 'Nama background disimpan.');
    }

    /** Aktif/nonaktif: background nonaktif tidak muncul di aplikasi. */
    public function toggle(FrameBackground $background)
    {
        $background->update(['is_active' => !$background->is_active]);
        $background->touch();

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Background \"{$background->name}\" " . ($background->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    /** Editor area foto: geser/tarik kotak di atas gambar background. */
    public function edit(FrameBackground $background)
    {
        $frame = FrameBackground::entry($background->frame_key);
        abort_if($frame === null || !$background->image_path, 404);

        return view('frame-backgrounds.edit', [
            'title' => 'Area Foto - ' . $background->name,
            'subtitle' => 'Atur di mana foto ditempatkan di atas background "' . $background->name . '"',
            'frame' => $frame,
            'background' => $background,
        ]);
    }

    public function saveArea(Request $request, FrameBackground $background)
    {
        $frame = FrameBackground::entry($background->frame_key);
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

        $background->update([
            'area_x' => round((float) $data['area_x'], 3),
            'area_y' => round((float) $data['area_y'], 3),
            'area_w' => round((float) $data['area_w'], 3),
            'area_h' => round((float) $data['area_h'], 3),
        ]);
        $background->touch();

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Area foto background \"{$background->name}\" disimpan.");
    }

    public function destroy(FrameBackground $background)
    {
        $name = $background->name;

        if ($background->image_path) {
            Storage::disk('public')->delete($background->image_path);
        }
        $background->delete();

        return redirect()
            ->route('frame-backgrounds.index')
            ->with('success', "Background \"{$name}\" dihapus.");
    }
}
