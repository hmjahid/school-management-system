<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class SiteController extends Controller
{
    public function news(): void
    {
        $db = Database::getInstance();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM news WHERE is_event = 0 AND is_published = 1")['cnt'] ?? 0;
        $rows = $db->fetchAll(
            "SELECT * FROM news WHERE is_event = 0 AND is_published = 1 ORDER BY published_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('site.news', [
            'news'     => $rows,
            'rows'     => $rows,
            'total'    => (int) $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function newsShow(string $slug): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM news WHERE slug = ? AND is_published = 1 LIMIT 1", [$slug]);

        if (!$row) {
            http_response_code(404);
            echo 'News article not found.';
            return;
        }

        $this->view('site.news_show', ['row' => $row]);
    }

    public function notices(): void
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT * FROM notices ORDER BY pinned DESC, id DESC LIMIT 50"
        );

        $this->view('site.notices', [
            'notices' => $rows,
            'rows'    => $rows,
        ]);
    }

    public function events(): void
    {
        $db = Database::getInstance();
        $upcoming = $db->fetchAll(
            "SELECT * FROM events WHERE status = 'published' AND start_date >= CURRENT_DATE ORDER BY start_date ASC LIMIT 50"
        );
        $past = $db->fetchAll(
            "SELECT * FROM events WHERE status = 'published' AND start_date < CURRENT_DATE ORDER BY start_date DESC LIMIT 20"
        );
        $allRows = array_merge($upcoming, $past);

        $this->view('site.events', [
            'events'   => $allRows,
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    public function gallery(): void
    {
        $db = Database::getInstance();
        // The raw-PHP port has a single `galleries` table (no separate
        // `gallery_albums`); treat the published galleries list as the album
        // list so the view can iterate. View key `albums` is preserved.
        $albums = $db->fetchAll(
            "SELECT * FROM galleries WHERE is_published = 1 ORDER BY id DESC LIMIT 50"
        );
        $photos = $db->fetchAll(
            "SELECT * FROM galleries WHERE is_published = 1 ORDER BY id DESC LIMIT 100"
        );
        $cats = [];
        foreach ($photos as $p) {
            if (!empty($p['category']) && !isset($cats[$p['category']])) {
                $cats[$p['category']] = ['slug' => slugify($p['category']), 'name' => $p['category']];
            }
        }
        $cats = array_values($cats);

        $this->view('site.gallery', [
            'albums'     => $albums,
            'categories' => $cats,
            'photos'     => $photos,
        ]);
    }

    public function contact(): void
    {
        $this->view('site.contact');
    }

    public function submitContact(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email',
            'phone'   => 'max:30',
            'subject' => 'max:200',
            'message' => 'required|max:5000',
        ]);

        $db = Database::getInstance();
        $db->insert('contact_submissions', [
            'type'       => 'contact',
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'subject'    => $data['subject'] ?? null,
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Thank you. We will get back to you soon.');
        $this->redirect('/contact');
    }

    public function results(): void
    {
        $db = Database::getInstance();

        $exams = $db->fetchAll(
            "SELECT id, name, start_date FROM exams ORDER BY start_date DESC LIMIT 30"
        );

        $search = trim((string) ($_GET['search'] ?? ''));
        $examId = (int) ($_GET['exam_id'] ?? 0);

        $student = null;
        $results = [];
        $totalMarks = null;
        $overallGrade = null;
        $overallGpa = null;
        $position = null;

        if ($search !== '') {
            $student = $db->fetch(
                "SELECT s.*, u.name, c.name as class_name, sec.name as section_name
                 FROM students s
                 LEFT JOIN users u ON s.user_id = u.id
                 LEFT JOIN school_classes c ON s.class_id = c.id
                 LEFT JOIN sections sec ON s.section_id = sec.id
                 WHERE s.admission_number LIKE ? OR s.roll_number LIKE ? OR u.name LIKE ?
                 LIMIT 1",
                ["%{$search}%", "%{$search}%", "%{$search}%"]
            );

            if ($student) {
                $where = 'er.student_id = ?';
                $params = [$student['id']];
                if ($examId > 0) {
                    $where .= ' AND er.exam_id = ?';
                    $params[] = $examId;
                }
                $results = $db->fetchAll(
                    "SELECT er.*, sub.name as subject_name, sub.total_marks as total_marks
                     FROM exam_results er
                     LEFT JOIN subjects sub ON er.subject_id = sub.id
                     WHERE {$where}
                     ORDER BY sub.name ASC",
                    $params
                );

                if (!empty($results)) {
                    $sum = 0;
                    $cnt = 0;
                    foreach ($results as $r) {
                        $sum += (float) ($r['marks'] ?? 0);
                        $cnt++;
                    }
                    if ($cnt > 0) {
                        $totalMarks = number_format($sum, 2);
                        $avg = $sum / $cnt;
                        $overallGpa = number_format($avg / 20, 2);
                        $overallGrade = $avg >= 80 ? 'A+' : ($avg >= 70 ? 'A' : ($avg >= 60 ? 'B' : ($avg >= 50 ? 'C' : 'F')));
                    }
                }
            }
        }

        $this->view('site.results', [
            'exams'        => $exams,
            'results'      => $results,
            'student'      => $student,
            'search'       => $search,
            'examId'       => $examId,
            'totalMarks'   => $totalMarks,
            'overallGrade' => $overallGrade,
            'overallGpa'   => $overallGpa,
            'position'     => $position,
        ]);
    }

    public function routine(): void
    {
        $db = Database::getInstance();
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sectionId = (int) ($_GET['section_id'] ?? 0);

        $where = '1=1';
        $params = [];
        if ($classId > 0) {
            $where .= ' AND r.school_class_id = ?';
            $params[] = $classId;
        }
        if ($sectionId > 0) {
            $where .= ' AND r.section_id = ?';
            $params[] = $sectionId;
        }

        $routines = $db->fetchAll(
            "SELECT r.*, c.name as class_name, s.name as section_name, sub.name as subject_name, u.name as teacher_name
             FROM routines r
             LEFT JOIN school_classes c ON r.school_class_id = c.id
             LEFT JOIN sections s ON r.section_id = s.id
             LEFT JOIN subjects sub ON r.subject_id = sub.id
             LEFT JOIN teachers t ON r.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE {$where}
             ORDER BY r.day_of_week ASC, r.start_time ASC",
            $params
        );

        $classes = $db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periodMap = [];
        foreach ($routines as $r) {
            $periodKey = ($r['start_time'] ?? '') . '-' . ($r['end_time'] ?? '');
            if (!isset($periodMap[$periodKey])) {
                $periodMap[$periodKey] = [
                    'id'         => count($periodMap) + 1,
                    'start_time' => $r['start_time'] ?? '',
                    'end_time'   => $r['end_time'] ?? '',
                ];
            }
        }
        $periods = array_values($periodMap);

        $routine = [];
        foreach ($routines as $r) {
            $day = $r['day_of_week'] ?? '';
            $periodKey = ($r['start_time'] ?? '') . '-' . ($r['end_time'] ?? '');
            if (isset($periodMap[$periodKey])) {
                $routine[$day][$periodMap[$periodKey]['id']] = $r;
            }
        }

        $this->view('site.routine', [
            'routines'  => $routines,
            'routine'   => $routine,
            'classes'   => $classes,
            'sections'  => $sections,
            'days'      => $days,
            'periods'   => $periods,
            'classId'   => $classId,
            'sectionId' => $sectionId,
        ]);
    }

    public function admission(): void
    {
        $db = Database::getInstance();
        $settings = $db->fetch("SELECT * FROM admission_settings ORDER BY id DESC LIMIT 1");
        $classes = $db->fetchAll(
            "SELECT c.id, c.name, c.code FROM school_classes c ORDER BY c.name ASC"
        );

        $currentSession = $db->fetch(
            "SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1"
        );
        $currentBatch = $db->fetch(
            "SELECT id FROM batches ORDER BY id DESC LIMIT 1"
        );

        $this->view('site.admission', [
            'settings' => $settings,
            'classes'  => $classes,
            'academicSessionId' => $currentSession['id'] ?? 1,
            'batchId'           => $currentBatch['id'] ?? 1,
        ]);
    }

    public function submitAdmission(): void
    {
        $data = $this->validate([
            'first_name'        => 'required|max:100',
            'last_name'         => 'required|max:100',
            'gender'            => 'required|in:male,female,other',
            'date_of_birth'     => 'required|date',
            'email'             => 'required|email',
            'phone'             => 'required|max:30',
            'address'           => 'required|max:500',
            'city'              => 'max:100',
            'state'             => 'max:100',
            'country'           => 'max:100',
            'postal_code'       => 'max:20',
            'blood_group'       => 'max:10',
            'religion'          => 'max:50',
            'nationality'       => 'max:100',
            'class_id'          => 'required|numeric',
            'father_name'       => 'required|max:191',
            'father_phone'      => 'required|max:30',
            'father_occupation' => 'max:191',
            'mother_name'       => 'required|max:191',
            'mother_phone'      => 'max:30',
            'mother_occupation' => 'max:191',
            'guardian_name'     => 'max:191',
            'guardian_relation' => 'max:50',
            'guardian_phone'    => 'max:30',
            'previous_school'   => 'max:255',
            'previous_class'    => 'max:100',
            'previous_grade'    => 'max:50',
            'notes'             => 'max:2000',
        ]);

        try {
            $admissionId = \App\Services\AdmissionSubmitter::submitPublicApplication($data, $_FILES ?? []);
        } catch (\Throwable $e) {
            Session::getInstance()->flash('error', 'Could not submit application: ' . $e->getMessage());
            $this->back();
            return;
        }

        $db = Database::getInstance();
        $row = $db->fetch("SELECT application_number FROM admissions WHERE id = ?", [$admissionId]);
        $appNumber = $row['application_number'] ?? '';

        Session::getInstance()->flash('success', 'Admission application submitted. Application number: ' . $appNumber);
        $this->redirect('/admission');
    }

    public function payments(): void
    {
        $db = Database::getInstance();
        $gateways = $db->fetchAll(
            "SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
        );
        $fees = $db->fetchAll(
            "SELECT f.*, c.name as class_name
             FROM fees f
             LEFT JOIN school_classes c ON f.class_id = c.id
             WHERE f.status = 'active'
             ORDER BY f.amount DESC
             LIMIT 50"
        );

        $this->view('site.payments', [
            'gateways' => $gateways,
            'fees'     => $fees,
        ]);
    }

    public function about(): void
    {
        $db = Database::getInstance();
        $content = $db->fetch("SELECT * FROM website_contents WHERE page = 'about' LIMIT 1");
        $about = $db->fetch("SELECT * FROM about_contents ORDER BY id DESC LIMIT 1");
        $settings = $db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $school = array_merge((array) ($settings ?? []), (array) ($about ?? []), (array) ($content ?? []));

        $committeeMembers = $db->fetchAll(
            "SELECT * FROM committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 20"
        );

        $this->view('site.about', [
            'content'          => $content,
            'school'           => $school,
            'committeeMembers' => $committeeMembers,
        ]);
    }

    public function careers(): void
    {
        $db = Database::getInstance();
        $jobs = $db->fetchAll(
            "SELECT * FROM careers WHERE is_published = 1 ORDER BY created_at DESC LIMIT 50"
        );

        $this->view('site.careers', ['jobs' => $jobs]);
    }

    public function applyCareer(): void
    {
        $data = $this->validate([
            'career_id' => 'required|numeric',
            'name'      => 'required|max:255',
            'email'     => 'required|email',
            'phone'     => 'max:30',
            'resume'    => 'required|max:2048',
            'cover_letter' => 'max:5000',
        ]);

        $db = Database::getInstance();

        $filePath = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/resumes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $filename = 'resume-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['resume']['tmp_name'], $uploadDir . $filename);
            $filePath = 'uploads/resumes/' . $filename;
        }

        $db->insert('career_applications', [
            'career_id'    => $data['career_id'],
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone'        => $data['phone'] ?? null,
            'resume_path'  => $filePath,
            'cover_letter' => $data['cover_letter'] ?? null,
            'status'       => 'pending',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Your application has been submitted.');
        $this->redirect('/careers');
    }

    public function sitemap(): void
    {
        $db = Database::getInstance();
        $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $staticPages = ['', '/about', '/contact', '/news', '/notices', '/events', '/admission', '/careers', '/payments'];
        foreach ($staticPages as $page) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . $page) . '</loc>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.8</priority>';
            echo '</url>';
        }

        $news = $db->fetchAll("SELECT slug, updated_at FROM news WHERE status = 'published' AND slug IS NOT NULL");
        foreach ($news as $item) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/news/' . $item['slug']) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($item['updated_at'])) . '</lastmod>';
            echo '<changefreq>monthly</changefreq>';
            echo '</url>';
        }

        $events = $db->fetchAll("SELECT slug, updated_at FROM events WHERE status = 'published' AND slug IS NOT NULL");
        foreach ($events as $item) {
            echo '<url>';
            echo '<loc>' . htmlspecialchars($baseUrl . '/events/' . $item['slug']) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($item['updated_at'])) . '</lastmod>';
            echo '<changefreq>monthly</changefreq>';
            echo '</url>';
        }

        echo '</urlset>';
        exit;
    }

    public function academics(): void
    {
        $db = Database::getInstance();
        $classes = $db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'active') as student_count
             FROM school_classes c
             WHERE c.is_active = 1 OR c.is_active IS NULL
             ORDER BY c.name ASC"
        );
        $settings = $db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $subjects = $db->fetchAll("SELECT * FROM subjects ORDER BY name ASC LIMIT 50");

        $this->view('site.academics', [
            'classes'  => $classes,
            'settings' => $settings,
            'subjects' => $subjects,
        ]);
    }

    public function studentsLife(): void
    {
        $db = Database::getInstance();
        $activities = $db->fetchAll(
            "SELECT * FROM website_contents WHERE page = 'student_activities' LIMIT 1"
        );
        $clubs = $db->fetchAll(
            "SELECT * FROM website_contents WHERE page LIKE 'club_%' AND is_active = 1 LIMIT 12"
        );
        $recentStudents = $db->fetchAll(
            "SELECT s.*, u.name, u.photo, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             ORDER BY s.id DESC LIMIT 12"
        );

        $this->view('site.students_life', [
            'activities'     => $activities[0] ?? null,
            'clubs'          => $clubs,
            'recentStudents' => $recentStudents,
        ]);
    }

    public function faculty(): void
    {
        $db = Database::getInstance();
        $teachers = $db->fetchAll(
            "SELECT t.id, t.qualification, u.name, u.email, u.photo, u.phone as user_phone
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.status = 'active'
             ORDER BY u.name ASC
             LIMIT 100"
        );

        foreach ($teachers as &$t) {
            $subs = $db->fetchAll(
                "SELECT DISTINCT sub.name FROM class_subject_teacher cst
                 LEFT JOIN subjects sub ON cst.subject_id = sub.id
                 WHERE cst.teacher_id = ?",
                [$t['id']]
            );
            $t['subjects'] = implode(', ', array_column($subs, 'name'));
        }
        unset($t);

        $this->view('site.faculty', ['teachers' => $teachers]);
    }

    public function transport(): void
    {
        $db = Database::getInstance();
        $routes = $db->fetchAll(
            "SELECT tr.*, v.number as vehicle_number, v.driver_name, v.driver_phone, v.capacity
             FROM transport_routes tr
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id
             WHERE tr.is_active = 1
             ORDER BY tr.name ASC"
        );
        $vehicles = $db->fetchAll(
            "SELECT * FROM vehicles WHERE is_active = 1 ORDER BY number ASC"
        );

        $stops = [];
        foreach ($routes as $r) {
            $stops[$r['id']] = $db->fetchAll(
                "SELECT * FROM transport_stops WHERE route_id = ? ORDER BY sort ASC, id ASC",
                [$r['id']]
            );
        }

        $this->view('site.transport', [
            'routes'   => $routes,
            'vehicles' => $vehicles,
            'stops'    => $stops,
        ]);
    }

    public function committee(): void
    {
        $db = Database::getInstance();
        $members = $db->fetchAll(
            "SELECT * FROM committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 50"
        );

        $this->view('site.committee', ['members' => $members]);
    }

    public function terms(): void
    {
        $db = Database::getInstance();
        $content = $db->fetch("SELECT * FROM website_contents WHERE page = 'terms' AND is_active = 1 LIMIT 1");
        if (!$content) {
            $content = [
                'title'   => 'Terms of Service',
                'content' => json_encode(['Default terms of service. Please update via CMS.']),
            ];
        }
        $contentDecoded = $content;
        if (!empty($content['content']) && is_string($content['content'])) {
            $decoded = json_decode($content['content'], true);
            if (is_array($decoded)) {
                $contentDecoded['content'] = $decoded;
            }
        }
        $this->view('site.terms', ['content' => $contentDecoded]);
    }

    public function privacy(): void
    {
        $db = Database::getInstance();
        $content = $db->fetch("SELECT * FROM website_contents WHERE page = 'privacy' AND is_active = 1 LIMIT 1");
        if (!$content) {
            $content = [
                'title'   => 'Privacy Policy',
                'content' => json_encode(['Default privacy policy. Please update via CMS.']),
            ];
        }
        $contentDecoded = $content;
        if (!empty($content['content']) && is_string($content['content'])) {
            $decoded = json_decode($content['content'], true);
            if (is_array($decoded)) {
                $contentDecoded['content'] = $decoded;
            }
        }
        $this->view('site.privacy', ['content' => $contentDecoded]);
    }

    public function portal(): void
    {
        $this->view('site.portal');
    }

    public function search(): void
    {
        $db = Database::getInstance();
        $term = trim((string) ($_GET['q'] ?? ''));

        $results = [
            'news'    => [],
            'notices' => [],
            'events'  => [],
            'pages'   => [],
        ];

        if ($term !== '') {
            $like = "%{$term}%";
            $results['news'] = $db->fetchAll(
                "SELECT id, title, slug, content, created_at FROM news
                 WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)
                 ORDER BY created_at DESC LIMIT 20",
                [$like, $like]
            );
            $results['notices'] = $db->fetchAll(
                "SELECT id, title, content, created_at FROM notices
                 WHERE title LIKE ? OR content LIKE ?
                 ORDER BY pinned DESC, id DESC LIMIT 20",
                [$like, $like]
            );
            $results['events'] = $db->fetchAll(
                "SELECT id, title, description, start_date FROM events
                 WHERE status = 'published' AND (title LIKE ? OR description LIKE ?)
                 ORDER BY start_date DESC LIMIT 20",
                [$like, $like]
            );
            $results['pages'] = $db->fetchAll(
                "SELECT id, page as slug, title, content_en, content FROM website_contents
                 WHERE is_active = 1 AND (title LIKE ? OR title_en LIKE ? OR content LIKE ? OR content_en LIKE ?)
                 LIMIT 20",
                [$like, $like, $like, $like]
            );
        }

        $this->view('site.search', [
            'term'    => $term,
            'results' => $results,
        ]);
    }

    public function paymentStatus(int $id): void
    {
        $db = Database::getInstance();
        $payment = $db->fetch("SELECT * FROM payments WHERE id = ?", [$id]);
        $verified = false;
        $gateway = null;
        $status = 'pending';
        if ($payment) {
            $status = $payment['payment_status'] ?? 'pending';
            $gatewayRow = $db->fetch(
                "SELECT * FROM payment_gateways WHERE code = ? LIMIT 1",
                [$payment['payment_method'] ?? 'offline']
            );
            try {
                if ($gatewayRow) {
                    $adapter = \App\Gateways\GatewayFactory::makeFromPaymentRecord($payment, $gatewayRow);
                    $verified = $adapter->verifyPayment($payment['transaction_id'] ?? '');
                    $gateway = $gatewayRow['name'] ?? 'Gateway';
                }
            } catch (\Throwable $e) {
                $verified = false;
            }
        }

        $this->view('site.payment_status', [
            'payment'  => $payment,
            'verified' => $verified,
            'gateway'  => $gateway,
            'status'   => $status,
        ]);
    }

    public function feeReceipt(int $id): void
    {
        $db = Database::getInstance();
        $payment = $db->fetch(
            "SELECT fp.*, s.admission_number, u.name as student_name, u.email as student_email,
                    f.name as fee_name, c.name as class_name, sec.name as section_name,
                    ru.name as receipt_name
             FROM fee_payments fp
             LEFT JOIN students s ON fp.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN fees f ON fp.fee_id = f.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             LEFT JOIN users ru ON fp.created_by = ru.id
             WHERE fp.id = ?",
            [$id]
        );

        if (!$payment) {
            http_response_code(404);
            echo 'Receipt not found.';
            return;
        }

        $school = $db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('site.fee_receipt', [
            'payment' => $payment,
            'school'  => $school,
        ]);
    }
}
