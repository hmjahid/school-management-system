<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Settings;
use App\Services\ActivityLog;

class CacheController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->view('admin.cache', ['admin' => Auth::user()]);
    }

    public function clear(): void
    {
        $root = dirname(__DIR__, 3);

        $dirs = [
            $root . '/storage/cache',
            $root . '/storage/framework/views',
            $root . '/storage/framework/cache',
        ];
        $cleared = 0;
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach ((array) glob($dir . '/*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $cleared++;
                }
            }
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        Settings::set('cache.version', (string) ((int) Settings::get('cache.version', '0') + 1));

        ActivityLog::log('cache.cleared', 'admin', (int) Auth::id(), ['files' => $cleared]);

        $this->withSuccess("Frontend cache cleared ({$cleared} cached file(s) removed).");
        $this->redirect('/admin/cache');
    }
}