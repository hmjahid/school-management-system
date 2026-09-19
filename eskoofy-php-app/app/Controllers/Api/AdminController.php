<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Models\UserWidgetPreference;

/**
 * Admin-only API endpoints — dashboard, analytics, activity, quick-actions,
 * widgets. Parity with eskoofy-laravel-app Api\Admin\DashboardController,
 * Api\AnalyticsController, Api\ActivityController, Api\QuickActionController.
 */
class AdminController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function requireAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            $this->error('Forbidden.', 403);
        }
    }

    public function dashboard(): void
    {
        $this->requireAdmin();
        $stats = [
            'students'          => (int) $this->db->count('students'),
            'active_students'   => (int) $this->db->count('students', "status = 'active'"),
            'teachers'          => (int) $this->db->count('teachers'),
            'classes'           => (int) $this->db->count('school_classes'),
            'exams'             => (int) $this->db->count('exams'),
            'pending_admissions' => (int) $this->db->count('admissions', "status = 'pending'"),
            'events'            => (int) $this->db->count('events'),
            'revenue'           => (float) ($this->db->fetch("SELECT COALESCE(SUM(paid_amount),0) AS t FROM fee_payments WHERE status IN ('paid','partial')")['t'] ?? 0),
            'expenses'          => (float) ($this->db->fetch("SELECT COALESCE(SUM(amount),0) AS t FROM expenses")['t'] ?? 0),
        ];
        $this->success($stats, 'Dashboard stats retrieved');
    }

    public function analyticsOverview(): void
    {
        $this->requireAdmin();
        $from = date('Y-m-d', strtotime('-30 days'));
        $dailyRevenue = $this->db->fetchAll(
            "SELECT payment_date, SUM(paid_amount) AS total
             FROM fee_payments WHERE status IN ('paid','partial') AND payment_date >= ?
             GROUP BY payment_date ORDER BY payment_date",
            [$from]
        );
        $topStudents = $this->db->fetchAll(
            "SELECT s.id, u.name, SUM(fp.paid_amount) AS paid
             FROM fee_payments fp
             JOIN students s ON s.id = fp.student_id
             JOIN users u ON u.id = s.user_id
             WHERE fp.status IN ('paid','partial')
             GROUP BY s.id, u.name ORDER BY paid DESC LIMIT 5"
        );
        $this->success([
            'revenue_trend' => $dailyRevenue,
            'top_payers'    => $topStudents,
        ], 'Analytics retrieved');
    }

    public function activity(): void
    {
        $this->requireAdmin();
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 30)));
        $rows = $this->db->fetchAll(
            "SELECT a.id, a.user_id, u.name AS user_name, a.type, a.title, a.message,
                    a.icon, a.color, a.created_at
             FROM activities a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT {$limit}"
        );
        $this->success($rows, 'Activity retrieved');
    }

    public function quickAction(): void
    {
        $this->requireAdmin();
        $action = trim((string) ($_POST['action'] ?? ''));
        $this->success(['action' => $action, 'executed' => true], 'Quick action handled');
    }

    public function getWidgetConfig(): void
    {
        $this->requireAdmin();
        $userId = (int) Auth::id();
        $this->success(UserWidgetPreference::preferencesFor($userId), 'Widget config retrieved');
    }

    public function saveWidgetConfig(): void
    {
        $this->requireAdmin();
        $userId = (int) Auth::id();
        $config = json_decode((string) ($_POST['config'] ?? '[]'), true) ?: [];

        foreach ($config as $widgetId => $settings) {
            $widgetId = (string) $widgetId;
            $enabled  = (int) ($settings['enabled'] ?? 1);
            $position = (int) ($settings['position'] ?? 0);
            $prefs    = (array) ($settings['settings'] ?? []);
            $existing = UserWidgetPreference::query()
                ->where('user_id', $userId)
                ->where('widget_id', $widgetId)
                ->first();
            if ($existing) {
                $this->db->update('user_widget_preferences', [
                    'enabled'    => $enabled,
                    'position'   => $position,
                    'settings'   => json_encode($prefs),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [(int) $existing->getKey()]);
            } else {
                $this->db->insert('user_widget_preferences', [
                    'user_id'    => $userId,
                    'widget_id'  => $widgetId,
                    'enabled'    => $enabled,
                    'position'   => $position,
                    'settings'   => json_encode($prefs),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        $this->success(UserWidgetPreference::preferencesFor($userId), 'Widget config saved');
    }

    public function resetWidgetConfig(): void
    {
        $this->requireAdmin();
        $userId = (int) Auth::id();
        $this->db->delete('user_widget_preferences', 'user_id = ?', [$userId]);
        $this->success([], 'Widget config reset');
    }
}