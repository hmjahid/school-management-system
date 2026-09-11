<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $template, array $data = []): void
    {
        if (str_starts_with($template, 'dashboard.')) {
            $data = array_merge($this->dashboardShellData(), $data);
        }
        View::render($template, $data);
    }

    /**
     * Shared data every dashboard view needs (mirrors Laravel's sidebar/topbar
     * view composers). Computed per-request so auth() is current.
     *
     * @return array<string,mixed>
     */
    protected function dashboardShellData(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $user = \App\Core\Auth::user();
        $pending = [
            'admissions'           => 0,
            'leaves'               => 0,
            'unreadMessages'       => 0,
            'unreadNotifications'  => 0,
            'pendingFeeApprovals'  => 0,
        ];
        try {
            if (\App\Core\Schema::hasTable('admissions')) {
                $pending['admissions'] = (int) \App\Models\Admission::query()->where('status', 'submitted')->count();
            }
            if (\App\Core\Schema::hasTable('leave_requests')) {
                $pending['leaves'] = (int) \App\Models\LeaveRequest::query()->where('status', 'pending')->count();
            }
            if (\App\Core\Schema::hasTable('messages')) {
                $pending['unreadMessages'] = (int) \App\Models\Message::query()->where('receiver_id', $user->id ?? 0)->where('is_read', false)->count();
            }
            if (\App\Core\Schema::hasTable('fee_payments')) {
                $pending['pendingFeeApprovals'] = (int) \App\Models\FeePayment::query()->where('status', 'pending')->count();
            }
        } catch (\Throwable) {
            $pending = array_fill_keys(array_keys($pending), 0);
        }

        $favorites = new \App\Core\Support\Collection();
        try {
            if ($user && \App\Core\Schema::hasTable('dashboard_favorites')) {
                $favorites = new \App\Core\Support\Collection(\App\Models\DashboardFavorite::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->limit(12)
                    ->get());
            }
        } catch (\Throwable) {
            $favorites = new \App\Core\Support\Collection();
        }

        $cached = [
            'dashboardFavorites'    => $favorites,
            'dashboardHelpSection'  => dashboard_help_section_for_route((string) ($_SERVER['REQUEST_URI'] ?? '')),
            'sidebarPendingCounts'  => $pending,
        ];
        return $cached;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function success(mixed $data = null, string $message = 'Success'): void
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function error(string $message = 'Error', int $status = 400, mixed $data = null): void
    {
        $this->json(['success' => false, 'message' => $message, 'data' => $data], $status);
    }

    protected function paginated(array $pagination, string $message = 'Success'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $pagination['data'],
            'meta'    => [
                'pagination' => [
                    'current_page' => $pagination['current_page'],
                    'per_page'     => $pagination['per_page'],
                    'total'        => $pagination['total'],
                    'last_page'    => $pagination['last_page'],
                ],
            ],
        ]);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function withSuccess(string $message): void
    {
        Session::getInstance()->flash('success', $message);
    }

    protected function withError(string $message): void
    {
        Session::getInstance()->flash('error', $message);
    }

    protected function validate(array $rules): array
    {
        $validator = new Validator($_POST, $rules);
        if ($validator->fails()) {
            Session::getInstance()->flash('errors', $validator->errors());
            $this->back();
            exit;
        }
        return $validator->validated();
    }
}
