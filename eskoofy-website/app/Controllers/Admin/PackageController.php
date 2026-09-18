<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\Mailer;

class PackageController extends Controller
{
    private const PRODUCTS = ['app', 'php', 'theme'];

    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT p.*, (SELECT COUNT(*) FROM licenses l WHERE l.product = p.product AND l.status = 'active' AND l.deleted_at IS NULL) AS active_licenses FROM packages p ORDER BY p.created_at DESC");
        $this->view('admin.packages', ['admin' => Auth::user(), 'packages' => $rows]);
    }

    public function store(): void
    {
        $product = (string) ($_POST['product'] ?? '');
        $version = trim((string) ($_POST['version'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $file = $_FILES['package'] ?? null;

        if (!in_array($product, self::PRODUCTS, true) || $version === '') {
            $this->withError('Product and version are required.');
            $this->redirect('/admin/packages');
        }
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->withError('Choose a ZIP file to upload.');
            $this->redirect('/admin/packages');
        }
        if (($file['size'] ?? 0) <= 0) {
            $this->withError('The uploaded file is empty.');
            $this->redirect('/admin/packages');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            $this->withError('Only ZIP package files are supported.');
            $this->redirect('/admin/packages');
        }

        $dir = dirname(__DIR__, 2) . '/storage/packages';
        $filename = 'package-' . $product . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $version) . '-' . date('Ymd-His') . '.zip';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $filename)) {
            $this->withError('Could not store the uploaded file.');
            $this->redirect('/admin/packages');
        }

        Database::getInstance()->insert('packages', [
            'product'    => $product,
            'version'    => $version,
            'filename'   => $filename,
            'size'       => (int) filesize($dir . '/' . $filename),
            'notes'      => $notes,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('package.uploaded', 'admin', (int) Auth::id(), [
            'product' => $product,
            'version' => $version,
            'file'    => $filename,
        ]);

        $this->withSuccess('Package uploaded: ' . $product . ' v' . $version);
        $this->redirect('/admin/packages');
    }

    public function delete(int $id): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM packages WHERE id = ?", [$id]);
        if ($row) {
            @unlink(dirname(__DIR__, 2) . '/storage/packages/' . $row['filename']);
            $db->delete('packages', 'id = ?', [$id]);
            ActivityLog::log('package.deleted', 'admin', (int) Auth::id(), ['file' => $row['filename']]);
            $this->withSuccess('Package deleted.');
        } else {
            $this->withError('Package not found.');
        }
        $this->redirect('/admin/packages');
    }

    public function toggle(int $id): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM packages WHERE id = ?", [$id]);
        if ($row) {
            $db->update('packages', [
                'is_active'  => $row['is_active'] ? 0 : 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            $this->withSuccess('Package ' . ($row['is_active'] ? 'disabled' : 'enabled') . '.');
        } else {
            $this->withError('Package not found.');
        }
        $this->redirect('/admin/packages');
    }

    public function download(int $id): void
    {
        $this->streamPackage($id);
    }

    public function send(int $id): void
    {
        $db = Database::getInstance();
        $package = $db->fetch("SELECT * FROM packages WHERE id = ? AND is_active = 1", [$id]);
        if (!$package) {
            $this->withError('Package not found.');
            $this->redirect('/admin/packages');
        }

        $customers = $db->fetchAll(
            "SELECT DISTINCT c.id, c.name, c.email FROM customers c
             JOIN licenses l ON l.customer_id = c.id
             WHERE l.product = ? AND l.status = 'active' AND l.deleted_at IS NULL
               AND c.deleted_at IS NULL AND c.email <> ''",
            [(string) $package['product']]
        );

        $sent = 0;
        foreach ($customers as $c) {
            $ok = Mailer::sendView(
                (string) $c['email'],
                'Your ' . ucfirst((string) $package['product']) . ' package is ready to download',
                'package_available',
                [
                    'name'    => $c['name'] ?: 'there',
                    'product' => ucfirst((string) $package['product']),
                    'version' => (string) $package['version'],
                    'notes'   => (string) ($package['notes'] ?? ''),
                ]
            );
            if ($ok) {
                $sent++;
            }
        }

        ActivityLog::log('package.sent', 'admin', (int) Auth::id(), [
            'package_id' => (int) $package['id'],
            'product'    => (string) $package['product'],
            'recipients' => $sent,
        ]);

        $this->withSuccess("Package email sent to {$sent} licensed client(s).");
        $this->redirect('/admin/packages');
    }

    private function streamPackage(int $id): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM packages WHERE id = ?", [$id]);
        $path = $row ? dirname(__DIR__, 2) . '/storage/packages/' . $row['filename'] : null;
        if (!$row || !is_file((string) $path)) {
            $this->withError('Package file not found.');
            $this->redirect('/admin/packages');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename((string) $path) . '"');
        header('Content-Length: ' . filesize((string) $path));
        header('X-Content-Type-Options: nosniff');
        readfile((string) $path);
        exit;
    }
}