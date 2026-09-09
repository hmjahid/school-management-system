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

        $total = $db->fetch("SELECT COUNT(*) as cnt FROM news WHERE is_event = 0 AND status = 'published'")['cnt'] ?? 0;
        $rows = $db->fetchAll(
            "SELECT * FROM news WHERE is_event = 0 AND status = 'published' ORDER BY published_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('site.news', [
            'rows'      => $rows,
            'total'     => (int) $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function newsShow(string $slug): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM news WHERE slug = ? AND status = 'published' LIMIT 1", [$slug]);

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

        $this->view('site.notices', ['rows' => $rows]);
    }

    public function events(): void
    {
        $db = Database::getInstance();
        $upcoming = $db->fetchAll(
            "SELECT * FROM events WHERE status = 'published' AND start_date >= CURDATE() ORDER BY start_date ASC LIMIT 50"
        );
        $past = $db->fetchAll(
            "SELECT * FROM events WHERE status = 'published' AND start_date < CURDATE() ORDER BY start_date DESC LIMIT 20"
        );

        $this->view('site.events', [
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    public function gallery(): void
    {
        $db = Database::getInstance();
        $albums = $db->fetchAll(
            "SELECT * FROM gallery_albums WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 50"
        );

        $this->view('site.gallery', ['albums' => $albums]);
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
        $this->view('site.results');
    }

    public function routine(): void
    {
        $db = Database::getInstance();
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sectionId = (int) ($_GET['section_id'] ?? 0);

        $where = '1=1';
        $params = [];
        if ($classId > 0) {
            $where .= ' AND r.class_id = ?';
            $params[] = $classId;
        }
        if ($sectionId > 0) {
            $where .= ' AND r.section_id = ?';
            $params[] = $sectionId;
        }

        $routines = $db->fetchAll(
            "SELECT r.*, c.name as class_name, s.name as section_name, sub.name as subject_name
             FROM routines r
             LEFT JOIN school_classes c ON r.class_id = c.id
             LEFT JOIN sections s ON r.section_id = s.id
             LEFT JOIN subjects sub ON r.subject_id = sub.id
             WHERE {$where}
             ORDER BY r.day_of_week ASC, r.start_time ASC",
            $params
        );

        $classes = $db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('site.routine', [
            'routines' => $routines,
            'classes'  => $classes,
            'sections' => $sections,
        ]);
    }

    public function admission(): void
    {
        $db = Database::getInstance();
        $settings = $db->fetch("SELECT * FROM admission_settings ORDER BY id DESC LIMIT 1");

        $this->view('site.admission', ['settings' => $settings]);
    }

    public function submitAdmission(): void
    {
        $data = $this->validate([
            'first_name'   => 'required|max:100',
            'last_name'    => 'required|max:100',
            'email'        => 'required|email',
            'phone'        => 'max:30',
            'date_of_birth'=> 'required|date',
            'gender'       => 'required|in:male,female,other',
            'address'      => 'max:500',
            'class_id'     => 'required|numeric',
            'previous_school' => 'max:255',
            'guardian_name'   => 'max:255',
            'guardian_phone'  => 'max:30',
            'guardian_email'  => 'email',
            'notes'           => 'max:2000',
        ]);

        $db = Database::getInstance();

        $appNumber = 'ADM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $admissionId = $db->insert('admissions', [
            'application_number' => $appNumber,
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'email'              => $data['email'],
            'phone'              => $data['phone'] ?? null,
            'date_of_birth'      => $data['date_of_birth'],
            'gender'             => $data['gender'],
            'address'            => $data['address'] ?? null,
            'class_id'           => $data['class_id'],
            'previous_school'    => $data['previous_school'] ?? null,
            'guardian_name'      => $data['guardian_name'] ?? null,
            'guardian_phone'     => $data['guardian_phone'] ?? null,
            'guardian_email'     => $data['guardian_email'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'status'             => 'submitted',
            'submitted_at'       => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Admission application submitted. Application number: ' . $appNumber);
        $this->redirect('/admission');
    }

    public function payments(): void
    {
        $this->view('site.payments');
    }

    public function about(): void
    {
        $db = Database::getInstance();
        $content = $db->fetch("SELECT * FROM website_contents WHERE page = 'about' LIMIT 1");
        $committeeMembers = $db->fetchAll(
            "SELECT * FROM committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 20"
        );

        $this->view('site.about', [
            'content'          => $content,
            'committeeMembers' => $committeeMembers,
        ]);
    }

    public function careers(): void
    {
        $db = Database::getInstance();
        $jobs = $db->fetchAll(
            "SELECT * FROM careers WHERE status = 'open' ORDER BY created_at DESC LIMIT 50"
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
}
