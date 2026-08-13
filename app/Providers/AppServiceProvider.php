<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Calculate storage usage (sum of files in storage/app/public)
        try {
            $storageDir = storage_path('app/public');
            $usedBytes = 0;
            if (is_dir($storageDir)) {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storageDir, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    if ($file->isFile()) {
                        $usedBytes += $file->getSize();
                    }
                }
            }
        } catch (\Throwable $e) {
            $usedBytes = 0;
        }

        $maxGb = 4; // cap in GB
        $maxBytes = $maxGb * 1024 * 1024 * 1024;
        $usedGb = $usedBytes / (1024 * 1024 * 1024);
        $usedDisplay = number_format($usedGb, 2);
        $percent = $maxBytes > 0 ? (int) round(($usedBytes / $maxBytes) * 100) : 0;

        View::share('storageStats', [
            'usedBytes' => $usedBytes,
            'usedGb' => $usedGb,
            'usedDisplay' => $usedDisplay,
            'percent' => $percent,
            'maxGb' => $maxGb,
        ]);
    }
}
