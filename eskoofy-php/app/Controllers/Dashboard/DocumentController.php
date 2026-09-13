<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class DocumentController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $documents = $this->db->fetchAll(
            "SELECT * FROM website_documents ORDER BY created_at DESC LIMIT 100"
        );

        $this->view('dashboard.documents.index', [
            'documents' => $documents,
            'rows' => $this->paginateRows($documents, count($documents), max(1, count($documents)), 1, \App\Models\WebsiteDocument::class),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.documents.create', [
            'document' => new \App\Models\WebsiteDocument(['is_published' => true]),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'       => 'required|max:191',
            'description' => 'max:500',
            'category'    => 'max:100',
        ]);

        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Session::getInstance()->flash('error', 'File upload failed.');
            $this->back();
            return;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['file']['name']));
        move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $filename);
        $path = 'uploads/documents/' . $filename;

        $this->db->insert('website_documents', [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'category'    => $data['category'] ?? null,
            'file_path'   => $path,
            'is_published'=> isset($_POST['is_published']) ? 1 : 0,
            'uploaded_by' => Auth::id(),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Document uploaded.');
        $this->redirect('/dashboard/documents');
    }

    public function edit(string $id): void
    {
        Auth::requireAuth();
        $row = $this->db->fetch("SELECT * FROM website_documents WHERE id = ? LIMIT 1", [$id]);
        if (!$row) {
            Session::getInstance()->flash('error', 'Document not found.');
            $this->redirect('/dashboard/documents');
            return;
        }
        $document = \App\Models\WebsiteDocument::newFromRow($row);
        $document->setAttribute('exists', true);

        $this->view('dashboard.documents.edit', ['document' => $document]);
    }

    public function update(string $id): void
    {
        Auth::requireAuth();
        $document = $this->db->fetch("SELECT * FROM website_documents WHERE id = ? LIMIT 1", [$id]);
        if (!$document) {
            Session::getInstance()->flash('error', 'Document not found.');
            $this->redirect('/dashboard/documents');
            return;
        }

        $data = $this->validate([
            'title'       => 'required|max:191',
            'description' => 'max:500',
            'category'    => 'max:100',
        ]);

        $payload = [
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'category'     => $data['category'] ?? null,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        if (!empty($_FILES['file']['tmp_name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            if (!empty($document['file_path']) && str_starts_with((string) $document['file_path'], 'uploads/documents/')) {
                $old = __DIR__ . '/../../public/' . ltrim($document['file_path'], '/');
                if (file_exists($old)) {
                    @unlink($old);
                }
            }
            $uploadDir = __DIR__ . '/../../public/uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['file']['name']));
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $filename);
            $payload['file_path'] = 'uploads/documents/' . $filename;
            $payload['mime_type'] = $_FILES['file']['type'] ?? null;
            $payload['file_size'] = $_FILES['file']['size'] ?? null;
        }

        $this->db->update('website_documents', $payload, 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Document updated.');
        $this->redirect('/dashboard/documents');
    }

    public function destroy(string $id): void
    {
        Auth::requireAuth();
        $this->db->delete('website_documents', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Document removed.');
        $this->redirect('/dashboard/documents');
    }
}
