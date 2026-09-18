<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DownloadController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $customer = Auth::user();

        $db = Database::getInstance();
        $licensedProducts = $db->fetchAll(
            "SELECT DISTINCT product FROM licenses
             WHERE customer_id = ? AND status = 'active' AND deleted_at IS NULL",
            [(int) $customer['id']]
        );
        $productCodes = array_map(fn ($r) => (string) $r['product'], $licensedProducts);

        $packages = [];
        if ($productCodes !== []) {
            $in = implode(',', array_fill(0, count($productCodes), '?'));
            $packages = $db->fetchAll(
                "SELECT * FROM packages WHERE product IN ({$in}) AND is_active = 1 ORDER BY product ASC, created_at DESC",
                $productCodes
            );
        }

        $documents = $db->fetchAll("SELECT * FROM client_documents WHERE is_active = 1 ORDER BY kind ASC, title ASC");

        $this->view('account.downloads', [
            'customer' => Auth::user(),
            'packages' => $packages,
            'documents' => $documents,
        ]);
    }

    public function package(int $id): void
    {
        Auth::requireAuth();
        $customer = Auth::user();
        $db = Database::getInstance();

        $package = $db->fetch("SELECT * FROM packages WHERE id = ? AND is_active = 1", [$id]);
        if (!$package) {
            $this->withError('Package not found.');
            $this->redirect('/account/downloads');
        }

        $licensed = $db->fetch(
            "SELECT id FROM licenses WHERE customer_id = ? AND product = ? AND status = 'active' AND deleted_at IS NULL",
            [(int) $customer['id'], (string) $package['product']]
        );
        if (!$licensed) {
            $this->withError('You need an active ' . ucfirst((string) $package['product']) . ' license to download this package.');
            $this->redirect('/account/downloads');
        }

        $this->stream(dirname(__DIR__, 2) . '/storage/packages/' . $package['filename'], $package['filename'], 'application/zip');
    }

    public function document(int $id): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        $doc = $db->fetch("SELECT * FROM client_documents WHERE id = ? AND is_active = 1", [$id]);
        if (!$doc) {
            $this->withError('Document not found.');
            $this->redirect('/account/downloads');
        }

        $this->stream(dirname(__DIR__, 2) . '/storage/documents/' . $doc['filename'], $doc['filename'], 'application/octet-stream');
    }

    private function stream(string $path, string $filename, string $mime): void
    {
        if (!is_file($path)) {
            $this->withError('File not found.');
            $this->redirect('/account/downloads');
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}