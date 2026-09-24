<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CustomRequest;
use App\Services\ActivityLog;

class CustomRequestController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $status = (string) ($_GET['status'] ?? '');
        if ($status !== '' && !in_array($status, CustomRequest::STATUSES, true)) {
            $status = '';
        }

        if ($status !== '') {
            $rows = Database::getInstance()->fetchAll(
                "SELECT * FROM custom_requests WHERE status = ? ORDER BY id DESC",
                [$status]
            );
        } else {
            $rows = Database::getInstance()->fetchAll(
                "SELECT * FROM custom_requests ORDER BY id DESC"
            );
        }

        $colors = [
            'new' => 'bg-blue-100 text-blue-800',
            'in_review' => 'bg-amber-100 text-amber-800',
            'quoting' => 'bg-violet-100 text-violet-800',
            'approved' => 'bg-green-100 text-green-800',
            'declined' => 'bg-red-100 text-red-800',
            'done' => 'bg-slate-200 text-slate-700',
        ];

        $this->view('admin.custom-requests', [
            'admin' => Auth::user(),
            'requests' => $rows,
            'currentStatus' => $status,
            'statusColors' => $colors,
            'unread' => CustomRequest::countUnread(),
        ]);
    }

    public function markRead(int $id): void
    {
        $this->getOrFail($id);

        Database::getInstance()->update('custom_requests', [
            'read_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.read_custom_request', 'admin', (int) Auth::id(), ['id' => $id]);

        $this->redirect('/admin/custom-requests');
    }

    public function updateStatus(int $id): void
    {
        $this->getOrFail($id);

        $data = $this->validate(['status' => 'required|in:' . implode(',', CustomRequest::STATUSES)]);

        Database::getInstance()->update('custom_requests', [
            'status' => $data['status'],
            'read_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.status_custom_request', 'admin', (int) Auth::id(), ['id' => $id, 'status' => $data['status']]);

        $this->withSuccess('Custom order status updated.');
        $this->redirect('/admin/custom-requests');
    }

    public function delete(int $id): void
    {
        $this->getOrFail($id);

        Database::getInstance()->update('custom_requests', [
            'status' => 'declined',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->withError('Request archived (status set to declined).');
        $this->redirect('/admin/custom-requests');
    }

    private function getOrFail(int $id): array
    {
        $request = Database::getInstance()->fetch("SELECT * FROM custom_requests WHERE id = ?", [$id]);
        if (!$request) {
            $this->withError('Custom order not found.');
            $this->redirect('/admin/custom-requests');
        }

        return $request;
    }
}