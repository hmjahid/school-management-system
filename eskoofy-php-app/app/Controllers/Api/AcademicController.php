<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

/**
 * Public academic endpoints — curriculum, programs, faculty, result filters.
 * Parity with eskoofy-laravel-app Api\AcademicController + ResultController::filters.
 */
class AcademicController extends Controller
{
    public function getCurriculum(): void
    {
        $this->success([
            'pageTitle'   => 'Our Curriculum',
            'heroTitle'   => 'Comprehensive Learning Experience',
            'heroSubtitle' => 'A well-rounded curriculum designed to foster academic excellence and personal growth',
            'overview'    => 'Our curriculum is carefully designed to provide students with a balanced education that combines academic rigor with practical skills and character development.',
            'programs'    => [
                [
                    'id'          => 1,
                    'title'       => 'Core Subjects',
                    'description' => 'Strong foundation in Mathematics, Science, Languages, and Social Studies',
                    'features'    => [
                        'Mathematics & Statistics',
                        'Sciences (Physics, Chemistry, Biology)',
                        'Languages & Literature',
                        'Social Studies & Humanities',
                    ],
                ],
                [
                    'id'          => 2,
                    'title'       => 'STEM & Innovation',
                    'description' => 'Hands-on learning in science, technology, engineering and mathematics',
                    'features'    => [
                        'Computer Science & Coding',
                        'Robotics & Electronics',
                        'Research Projects',
                        'Mathematics Olympiad',
                    ],
                ],
            ],
        ], 'Curriculum retrieved');
    }

    public function getPrograms(): void
    {
        $this->success([
            'pageTitle'    => 'Academic Programs',
            'heroTitle'    => 'Diverse Learning Opportunities',
            'heroSubtitle' => 'Explore our range of academic programs designed to meet every student\'s needs',
            'programs'     => [
                ['id' => 1, 'name' => 'Elementary School', 'description' => 'Foundational learning for young minds (Grades 1-5)', 'features' => ['Literacy & Numeracy Focus', 'Exploratory Learning', 'Social-Emotional Development', 'Creative Expression']],
                ['id' => 2, 'name' => 'Middle School', 'description' => 'Building critical thinking and subject mastery (Grades 6-8)', 'features' => ['Advanced Core Subjects', 'Elective Exploration', 'Project-Based Learning']],
                ['id' => 3, 'name' => 'High School', 'description' => 'College preparation and specialization (Grades 9-12)', 'features' => ['STEM Track', 'Humanities Track', 'College Counselling']],
            ],
        ], 'Programs retrieved');
    }

    public function getFaculty(): void
    {
        $db   = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT t.id, u.name, u.email, t.qualification, t.subjects
             FROM teachers t
             JOIN users u ON u.id = t.user_id
             WHERE u.deleted_at IS NULL
             ORDER BY u.name ASC"
        );
        $faculty = array_map(static function (array $r): array {
            return [
                'id'            => $r['id'],
                'name'          => $r['name'],
                'email'         => $r['email'],
                'qualification' => $r['qualification'] ?? null,
                'subjects'      => $r['subjects'] ? (json_decode($r['subjects'], true) ?? []) : [],
            ];
        }, $rows);

        $this->success([
            'pageTitle'   => 'Our Faculty',
            'heroTitle'   => 'Dedicated Educators',
            'heroSubtitle' => 'Meet our team of experienced and passionate educators',
            'faculty'     => $faculty,
        ], 'Faculty retrieved');
    }

    public function resultFilters(): void
    {
        $db = Database::getInstance();
        $sessions = [];
        $classes  = [];
        try {
            if ($db->hasTable('academic_sessions')) {
                $sessions = $db->fetchAll("SELECT id, name FROM academic_sessions WHERE deleted_at IS NULL ORDER BY name DESC");
            }
        } catch (\Throwable) {
            $sessions = [];
        }
        try {
            if ($db->hasTable('school_classes')) {
                $classes = $db->fetchAll("SELECT id, name FROM school_classes WHERE deleted_at IS NULL ORDER BY name");
            }
        } catch (\Throwable) {
            $classes = [];
        }
        $this->success([
            'sessions' => $sessions,
            'classes'  => $classes,
        ], 'Result filters retrieved');
    }
}