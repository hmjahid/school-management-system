<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\Mailer;

class ClientDocumentController extends Controller
{
    private const KINDS = ['user_manual', 'setup_guide', 'other'];

    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll("SELECT * FROM client_documents ORDER BY created_at DESC");
        $this->view('admin.client-documents', ['admin' => Auth::user(), 'documents' => $rows]);
    }

    public function store(): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $kind = (string) ($_POST['kind'] ?? 'other');
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $file = $_FILES['document'] ?? null;

        if ($title === '' || !in_array($kind, self::KINDS, true)) {
            $this->withError('Title and type are required.');
            $this->redirect('/admin/client-documents');
        }
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->withError('Choose a file to upload (PDF, ZIP, or Markdown).');
            $this->redirect('/admin/client-documents');
        }
        if (($file['size'] ?? 0) <= 0) {
            $this->withError('The uploaded file is empty.');
            $this->redirect('/admin/client-documents');
        }

        $dir = dirname(__DIR__, 2) . '/storage/documents';
        $filename = 'doc-' . $kind . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $title) . '-' . date('Ymd-His') . '.' . strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $filename)) {
            $this->withError('Could not store the uploaded file.');
            $this->redirect('/admin/client-documents');
        }

        Database::getInstance()->insert('client_documents', [
            'title'      => $title,
            'kind'       => $kind,
            'filename'   => $filename,
            'size'       => (int) filesize($dir . '/' . $filename),
            'notes'      => $notes,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('document.uploaded', 'admin', (int) Auth::id(), ['title' => $title, 'file' => $filename]);

        $this->withSuccess('Document uploaded: ' . $title);
        $this->redirect('/admin/client-documents');
    }

    public function delete(int $id): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM client_documents WHERE id = ?", [$id]);
        if ($row) {
            @unlink(dirname(__DIR__, 2) . '/storage/documents/' . $row['filename']);
            $db->delete('client_documents', 'id = ?', [$id]);
            ActivityLog::log('document.deleted', 'admin', (int) Auth::id(), ['file' => $row['filename']]);
            $this->withSuccess('Document deleted.');
        } else {
            $this->withError('Document not found.');
        }
        $this->redirect('/admin/client-documents');
    }

    public function send(int $id): void
    {
        $db = Database::getInstance();
        $doc = $db->fetch("SELECT * FROM client_documents WHERE id = ? AND is_active = 1", [$id]);
        if (!$doc) {
            $this->withError('Document not found.');
            $this->redirect('/admin/client-documents');
        }

        $customers = $db->fetchAll(
            "SELECT DISTINCT c.id, c.name, c.email FROM customers c
             JOIN licenses l ON l.customer_id = c.id
             WHERE l.status = 'active' AND l.deleted_at IS NULL
               AND c.deleted_at IS NULL AND c.email <> ''"
        );

        $sent = 0;
        foreach ($customers as $c) {
            $ok = Mailer::sendView(
                (string) $c['email'],
                'Helpful document for you: ' . (string) $doc['title'],
                'welcome',
                [
                    'name'     => $c['name'] ?: 'there',
                    'document' => (string) $doc['title'],
                ]
            );
            if ($ok) {
                $sent++;
            }
        }

        ActivityLog::log('document.sent', 'admin', (int) Auth::id(), [
            'document_id' => (int) $doc['id'],
            'recipients'  => $sent,
        ]);

        $this->withSuccess("Document email sent to {$sent} licensed client(s).");
        $this->redirect('/admin/client-documents');
    }

    public function download(int $id): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM client_documents WHERE id = ?", [$id]);
        $path = $row ? dirname(__DIR__, 2) . '/storage/documents/' . $row['filename'] : null;
        if (!$row || !is_file((string) $path)) {
            $this->withError('Document file not found.');
            $this->redirect('/admin/client-documents');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename((string) $path) . '"');
        header('Content-Length: ' . filesize((string) $path));
        header('X-Content-Type-Options: nosniff');
        readfile((string) $path);
        exit;
    }
}