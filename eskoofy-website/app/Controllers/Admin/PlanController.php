<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class PlanController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll("SELECT * FROM plans ORDER BY product ASC, sort_order ASC");

        $this->view('admin.plans', ['admin' => Auth::user(), 'plans' => $rows]);
    }

    public function create(): void
    {
        $this->view('admin.plan-form', ['admin' => Auth::user(), 'plan' => null]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'product' => 'required|in:app,theme',
            'name'    => 'required|max:191',
            'slug'    => 'required|max:191',
            'price'   => 'required|numeric',
            'period'  => 'required|in:one-time,monthly,yearly',
        ]);

        Database::getInstance()->insert('plans', [
            'product'         => $data['product'],
            'name'            => $data['name'],
            'slug'            => $data['slug'],
            'description'     => $_POST['description'] ?? null,
            'price'           => (float) $data['price'],
            'currency'        => $_ENV['LICENSE_CURRENCY'] ?? 'USD',
            'period'          => $data['period'],
            'max_activations' => max(1, (int) ($_POST['max_activations'] ?? 1)),
            'sort_order'      => (int) ($_POST['sort_order'] ?? 0),
            'active'          => (int) ($_POST['active'] ?? 0) ? 1 : 0,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('admin.created_plan', 'admin', (int) Auth::id(), ['name' => $data['name']]);

        $this->withSuccess('Plan created.');
        $this->redirect('/admin/plans');
    }

    public function edit(int $id): void
    {
        $plan = Database::getInstance()->fetch("SELECT * FROM plans WHERE id = ?", [$id]);
        if (!$plan) {
            $this->withError('Plan not found.');
            $this->redirect('/admin/plans');
        }

        $this->view('admin.plan-form', ['admin' => Auth::user(), 'plan' => $plan]);
    }

    public function update(int $id): void
    {
        $plan = Database::getInstance()->fetch("SELECT * FROM plans WHERE id = ?", [$id]);
        if (!$plan) {
            $this->withError('Plan not found.');
            $this->redirect('/admin/plans');
        }

        $data = $this->validate([
            'product' => 'required|in:app,theme',
            'name'    => 'required|max:191',
            'price'   => 'required|numeric',
            'period'  => 'required|in:one-time,monthly,yearly',
        ]);

        Database::getInstance()->update('plans', [
            'product'         => $data['product'],
            'name'            => $data['name'],
            'description'     => $_POST['description'] ?? null,
            'price'           => (float) $data['price'],
            'period'          => $data['period'],
            'max_activations' => max(1, (int) ($_POST['max_activations'] ?? 1)),
            'sort_order'      => (int) ($_POST['sort_order'] ?? 0),
            'active'          => (int) ($_POST['active'] ?? 0) ? 1 : 0,
            'updated_at'      => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.updated_plan', 'admin', (int) Auth::id(), ['id' => $id]);

        $this->withSuccess('Plan updated.');
        $this->redirect('/admin/plans');
    }
}
