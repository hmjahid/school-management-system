<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class OnboardingController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $checks = [
            [
                'title'    => 'Configure School Information',
                'description' => 'Set your school name, address, and contact details.',
                'done'     => (bool) $db->fetch("SELECT id FROM website_settings LIMIT 1"),
                'link'     => '/dashboard/settings',
            ],
            [
                'title'    => 'Create Academic Session',
                'description' => 'Add the current academic year and mark it as current.',
                'done'     => (bool) $db->fetch("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1"),
                'link'     => '/dashboard/academic-sessions',
            ],
            [
                'title'    => 'Add Classes',
                'description' => 'Create classes for the current academic year.',
                'done'     => (int) $db->count('school_classes') > 0,
                'link'     => '/dashboard/classes',
            ],
            [
                'title'    => 'Add Subjects',
                'description' => 'Set up subjects taught in your school.',
                'done'     => (int) $db->count('subjects') > 0,
                'link'     => '/dashboard/subjects',
            ],
            [
                'title'    => 'Add Teachers',
                'description' => 'Add teacher profiles with their qualifications and subjects.',
                'done'     => (int) $db->count('teachers') > 0,
                'link'     => '/dashboard/teachers',
            ],
            [
                'title'    => 'Add Students',
                'description' => 'Enroll students into classes.',
                'done'     => (int) $db->count('students') > 0,
                'link'     => '/dashboard/students',
            ],
            [
                'title'    => 'Configure Fee Structure',
                'description' => 'Set up fees for each class.',
                'done'     => (int) $db->count('fees') > 0,
                'link'     => '/dashboard/fees',
            ],
            [
                'title'    => 'Configure Payment Gateways',
                'description' => 'Enable at least one payment gateway.',
                'done'     => (int) $db->count('payment_gateways', 'is_active = 1') > 0,
                'link'     => '/dashboard/payment-gateways',
            ],
            [
                'title'    => 'Publish News / Notices',
                'description' => 'Share something with your community.',
                'done'     => (int) $db->count('news') > 0,
                'link'     => '/dashboard/news',
            ],
        ];

        $completed = count(array_filter($checks, fn($c) => $c['done']));
        $total = count($checks);
        $percent = $total > 0 ? (int) round(100 * $completed / $total) : 0;

        $this->view('dashboard.onboarding.index', [
            'checks'    => $checks,
            'completed' => $completed,
            'total'     => $total,
            'percent'   => $percent,
        ]);
    }
}
