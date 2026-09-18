<?php
declare(strict_types=1);

/**
 * Demo school content seeder (parity with the Laravel app + WP theme demo data).
 * Run after database/seed_demo.php (which creates the accounts):
 *
 *   php database/seed_demo_content.php
 *
 * Idempotent: every insert is guarded by an existence check on its natural key.
 */

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$now = date('Y-m-d H:i:s');

// ─── website_settings ───────────────────────────────────────────────────────
$db->query("DELETE FROM website_settings WHERE id IS NOT NULL");
$db->insert('website_settings', [
    'school_name'          => 'Example School',
    'school_name_bn'       => 'এক্সাম্পল স্কুল',
    'tagline'              => 'Empowering Future Leaders',
    'tagline_bn'           => 'ভবিষ্যতের নেতৃত্ব গড়ে তুলি',
    'established_year'     => '1990',
    'address'              => '123 Education Street',
    'city'                 => 'Learning City',
    'state'                => 'Dhaka',
    'country'              => 'BD',
    'postal_code'          => '1205',
    'phone'                => '+1 (555) 123-4567',
    'email'                => 'info@exampleschool.edu',
    'website'              => 'https://eskoofy.com',
    'currency'             => 'USD',
    'default_payment_method' => 'cash',
    'theme_primary_color'  => '#2563eb',
    'theme_secondary_color' => '#f97316',
    'show_facebook'        => 1,
    'show_twitter'         => 1,
    'show_instagram'       => 1,
    'show_linkedin'        => 1,
    'show_youtube'         => 1,
    'send_absence_sms'     => 0,
    'theme_style'          => 'modern',
    'timezone'             => 'Asia/Dhaka',
    'default_locale'       => 'en',
    'date_format'          => 'Y-m-d',
    'time_format'          => 'H:i',
    'maintenance_mode'     => 0,
    'bkash_sandbox'        => 1,
    'created_at'           => $now,
    'updated_at'           => $now,
]);

// ─── academic_sessions ───────────────────────────────────────────────────────
$sessionIds = [];
foreach (['2024' => false, '2025' => false, '2026' => true] as $name => $current) {
    $existing = $db->fetch("SELECT id FROM academic_sessions WHERE name = ?", [$name]);
    $data = [
        'name'         => $name,
        'code'         => $name,
        'start_date'   => "{$name}-01-01",
        'end_date'     => "{$name}-12-31",
        'is_active'    => $current ? 1 : 0,
        'is_current'   => $current ? 1 : 0,
        'status'       => $current ? 'active' : 'inactive',
        'created_at'   => $now,
        'updated_at'   => $now,
    ];
    if ($existing) {
        $db->update('academic_sessions', $data, 'id = ?', [$existing['id']]);
        $sessionIds[$name] = (int) $existing['id'];
    } else {
        $sessionIds[$name] = (int) $db->insert('academic_sessions', $data);
    }
}
$sessionId = $sessionIds['2026'];

// ─── academic_years (mirror sessions) ────────────────────────────────────────
$yearIds = [];
foreach ($sessionIds as $name => $sid) {
    $existing = $db->fetch("SELECT id FROM academic_years WHERE name = ?", [$name]);
    $data = [
        'name'       => $name,
        'session'    => $name,
        'start_date' => "{$name}-01-01",
        'end_date'   => "{$name}-12-31",
        'is_current' => $name === '2026' ? 1 : 0,
        'description' => 'Academic year ' . $name,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if ($existing) {
        $db->update('academic_years', $data, 'id = ?', [$existing['id']]);
        $yearIds[$name] = (int) $existing['id'];
    } else {
        $yearIds[$name] = (int) $db->insert('academic_years', $data);
    }
}
$yearId = $yearIds['2026'];

// ─── school_classes ─────────────────────────────────────────────────────────
$classNames = ['Play', 'Nursery', 'KG-1', 'KG-2', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'];
$classFees = [500, 600, 800, 1000, 1200, 1500, 1800, 2200, 2600, 3000, 3500, 4200, 5000, 7500];
$classIds = [];
foreach ($classNames as $i => $name) {
    $existing = $db->fetch("SELECT id FROM school_classes WHERE name = ? AND academic_session_id = ?", [$name, $sessionId]);
    $data = [
        'name'                => $name,
        'code'                => strtoupper(str_replace([' ', '-'], ['_', '_'], $name)),
        'description'         => "Class {$name} — demo",
        'academic_session_id' => $sessionId,
        'max_students'        => 60,
        'is_active'           => 1,
        'monthly_fee'         => $classFees[$i],
        'admission_fee'       => (float) $classFees[$i] * 0.5,
        'exam_fee'            => (float) $classFees[$i] * 0.25,
        'other_fees'          => 0,
        'shift'               => 'Morning',
        'created_at'          => $now,
        'updated_at'          => $now,
    ];
    if ($existing) {
        $db->update('school_classes', $data, 'id = ?', [$existing['id']]);
        $classIds[$name] = (int) $existing['id'];
    } else {
        $classIds[$name] = (int) $db->insert('school_classes', $data);
    }
}

// ─── batches (Alpha/Beta per class) ─────────────────────────────────────────
$batchIds = [];
foreach ($classNames as $name) {
    foreach (['Alpha', 'Beta'] as $b) {
        $existing = $db->fetch("SELECT id FROM batches WHERE name = ? AND academic_session_id = ?", ["{$name} {$b}", $sessionId]);
        $data = [
            'name'                => "{$name} {$b}",
            'code'                => strtoupper(str_replace([' ', '-'], ['_', '_'], "{$name}-{$b}")),
            'academic_session_id' => $sessionId,
            'is_active'           => 1,
            'status'              => 'active',
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
        if ($existing) {
            $db->update('batches', $data, 'id = ?', [$existing['id']]);
            $batchIds["{$name} {$b}"] = (int) $existing['id'];
        } else {
            $batchIds["{$name} {$b}"] = (int) $db->insert('batches', $data);
        }
    }
}

// ─── sections (A/B per batch) ───────────────────────────────────────────────
$sectionIds = [];
foreach ($classNames as $name) {
    foreach (['Alpha', 'Beta'] as $b) {
        foreach (['A', 'B'] as $s) {
            $batchKey = "{$name} {$b}";
            $slug = strtolower(str_replace([' ', '-'], '-', "{$name}-{$b}-{$s}"));
            $existing = $db->fetch("SELECT id FROM sections WHERE slug = ?", [$slug]);
            $data = [
                'name'             => $s,
                'slug'             => $slug,
                'class_id'         => $classIds[$name],
                'capacity'         => 30,
                'is_active'        => 1,
                'academic_year_id' => $yearId,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            if ($existing) {
                $db->update('sections', $data, 'id = ?', [$existing['id']]);
                $sectionIds[$slug] = (int) $existing['id'];
            } else {
                $sectionIds[$slug] = (int) $db->insert('sections', $data);
            }
        }
    }
}

// ─── teachers (30, linked to teacher1..30 users) ────────────────────────────
$teacherNames = [
    'Md. Abdul Rahman', 'Fatima Begum', 'Mohammad Hossain', 'Ayesha Islam', 'Md. Kamal Khan',
    'Shahinur Akter', 'Md. Rafiq Haque', 'Nasrin Mollah', 'Md. Jashim Sarker', 'Shamima Chowdhury',
    'Hasan Ahmed', 'Parvin Khatun', 'Mizanur Siddique', 'Shahnaz Jahan', 'Jahangir Mahmud',
    'Laili Pervin', 'Tariqul Kabir', 'Rokeya Nahar', 'Shahidul Bhuiyan', 'Mahbuba Hasan',
    'Anwar Rahman', 'Selina Begum', 'Kawsar Hossain', 'Jannatul Islam', 'Riaz Khan',
    'Maksuda Akter', 'Firoz Haque', 'Tahmina Mollah', 'Nazmul Sarker', 'Sharmin Chowdhury',
];
$qualifications = ['M.Ed', 'B.Ed', 'M.Sc in Mathematics', 'B.Sc in Physics', 'MA in English', 'M.Sc in Chemistry', 'BA in Bengali'];
for ($i = 1; $i <= 30; $i++) {
    $user = $db->fetch("SELECT id FROM users WHERE email = ?", ["teacher{$i}@school.com"]);
    if (!$user) {
        continue;
    }
    $existing = $db->fetch("SELECT id FROM teachers WHERE user_id = ?", [(int) $user['id']]);
    $data = [
        'user_id'      => (int) $user['id'],
        'employee_id'  => 'TCH-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        'qualification'=> $qualifications[($i - 1) % count($qualifications)],
        'gender'       => $i % 2 ? 'male' : 'female',
        'nationality'  => 'Bangladeshi',
        'country'      => 'BD',
        'joining_date' => '2022-01-10',
        'status'       => 'active',
        'salary'       => 25000 + (($i - 1) % 30) * 1000,
        'salary_type'  => 'monthly',
        'created_at'   => $now,
        'updated_at'   => $now,
    ];
    if ($existing) {
        $db->update('teachers', $data, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('teachers', $data);
    }
}

// ─── guardians (parent1..5) ─────────────────────────────────────────────────
$guardianIds = [];
$guardianData = [
    ['Rahim Hossain', 'father', 'Merchant', 'Abdul Karim'],
    ['Karim Rahman', 'father', 'Teacher', 'Md. Rashed Rahman'],
    ['Fahim Khan', 'father', 'Businessman', 'Salam Khan'],
    ['Nabila Islam', 'mother', 'Doctor', 'Fahmida Islam'],
    ['Tanvir Ahmed', 'father', 'Engineer', 'Jahangir Ahmed'],
];
for ($i = 1; $i <= 5; $i++) {
    $user = $db->fetch("SELECT id FROM users WHERE email = ?", ["parent{$i}@school.com"]);
    if (!$user) {
        continue;
    }
    $existing = $db->fetch("SELECT id FROM guardians WHERE user_id = ?", [(int) $user['id']]);
    $data = [
        'user_id'       => (int) $user['id'],
        'relation_type' => $guardianData[$i - 1][1],
        'occupation'    => $guardianData[$i - 1][2],
        'company_name'  => $guardianData[$i - 1][3],
        'phone'         => '01' . str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT),
        'nationality'   => 'Bangladeshi',
        'country'       => 'BD',
        'is_primary'    => 1,
        'created_at'    => $now,
        'updated_at'    => $now,
    ];
    if ($existing) {
        $db->update('guardians', $data, 'id = ?', [$existing['id']]);
        $guardianIds[$i] = (int) $existing['id'];
    } else {
        $guardianIds[$i] = (int) $db->insert('guardians', $data);
    }
}

// ─── students (5, linked to student1..5 users) ──────────────────────────────
$students = [
    ['Rahim Hossain', 'Rahim', 'Hossain', 'male', '2015-05-12', 1, 1, 'National Science Fair Gold Medalist 2025'],
    ['Karim Rahman', 'Karim', 'Rahman', 'male', '2015-08-03', 0, 1, ''],
    ['Fahim Khan', 'Fahim', 'Khan', 'male', '2015-02-19', 1, 1, 'District Math Olympiad First Place'],
    ['Nabila Islam', 'Nabila', 'Islam', 'female', '2014-11-27', 0, 2, ''],
    ['Tanvir Ahmed', 'Tanvir', 'Ahmed', 'male', '2015-01-09', 0, 1, ''],
];
for ($i = 1; $i <= 5; $i++) {
    $user = $db->fetch("SELECT id FROM users WHERE email = ?", ["student{$i}@school.com"]);
    if (!$user) {
        continue;
    }
    [$full, $first, $last, $gender, $dob, $notable, $batchIdx, $achievement] = $students[$i - 1];
    $classId = $classIds['Class 1'];
    $batchKey = 'Class 1 ' . ($batchIdx === 1 ? 'Alpha' : 'Beta');
    $sectionKey = 'class-1-' . ($batchIdx === 1 ? 'alpha' : 'beta') . '-a';
    $existing = $db->fetch("SELECT id FROM students WHERE user_id = ?", [(int) $user['id']]);
    $data = [
        'user_id'          => (int) $user['id'],
        'guardian_id'      => $guardianIds[$i] ?? null,
        'class_id'         => $classId,
        'batch_id'         => $batchIds[$batchKey] ?? null,
        'section_id'       => $sectionIds[$sectionKey] ?? null,
        'admission_number' => 'STU-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
        'admission_date'   => '2026-01-15',
        'roll_number'      => $i,
        'first_name'       => $first,
        'last_name'        => $last,
        'gender'           => $gender,
        'date_of_birth'    => $dob,
        'email'            => "student{$i}@school.com",
        'phone_1'          => '01' . str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT),
        'nationality'      => 'Bangladeshi',
        'country'          => 'BD',
        'monthly_fee'      => 1200,
        'transport_fee'    => 0,
        'discount'         => 0,
        'status'           => 'active',
        'is_notable'       => $notable,
        'achievement'      => $achievement,
        'created_at'       => $now,
        'updated_at'       => $now,
    ];
    if ($existing) {
        $db->update('students', $data, 'id = ?', [$existing['id']]);
        $studentIds[$i] = (int) $existing['id'];
    } else {
        $studentIds[$i] = (int) $db->insert('students', $data);
    }

    if (isset($studentIds[$i], $guardianIds[$i])) {
        $link = $db->fetch(
            "SELECT id FROM guardian_student WHERE guardian_id = ? AND student_id = ?",
            [$guardianIds[$i], $studentIds[$i]]
        );
        if (!$link) {
            $db->insert('guardian_student', [
                'guardian_id'  => $guardianIds[$i],
                'student_id'   => $studentIds[$i],
                'relationship' => $guardianData[$i - 1][1],
                'is_primary'   => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}

// ─── notices (5) ────────────────────────────────────────────────────────────
$notices = [
    'Admission Open for 2026',
    'Parent-Teacher Meeting — August 10',
    'Mid-Term Examination Schedule Released',
    'School Closure — National Holiday',
    'Annual Sports Day — September 5',
];
$admin = $db->fetch("SELECT id FROM users WHERE email = 'admin@school.com'");
$adminId = $admin ? (int) $admin['id'] : 1;
foreach ($notices as $idx => $title) {
    $existing = $db->fetch("SELECT id FROM notices WHERE title = ?", [$title]);
    $data = [
        'title'      => $title,
        'content'    => 'Demo notice: ' . $title,
        'content_bn' => 'ডেমো নোটিশ: ' . $title,
        'pinned'     => $idx < 2 ? 1 : 0,
        'created_by' => $adminId,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if ($existing) {
        $db->update('notices', $data, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('notices', $data);
    }
}

// ─── events (15) ────────────────────────────────────────────────────────────
$events = [
    'Annual Sports Day', 'Cultural Program 2025', 'Parent-Teacher Meeting', 'Science Fair',
    'Independence Day Celebration', 'Victory Day Program', 'Educational Tour', 'Book Fair',
    'ICT Olympiad', 'Annual Prize Giving Ceremony', 'Eid Reunion', 'Debate Competition',
    'Art Exhibition', 'Health & Hygiene Workshop', 'Summer Sports Camp',
];
foreach ($events as $title) {
    $existing = $db->fetch("SELECT id FROM events WHERE title = ?", [$title]);
    $data = [
        'created_by'   => $adminId,
        'title'        => $title,
        'description'  => 'Demo event: ' . $title,
        'location'     => 'Example School Campus',
        'start_date'   => '2026-09-05 09:00:00',
        'is_virtual'   => 0,
        'status'       => 'published',
        'created_at'   => $now,
        'updated_at'   => $now,
    ];
    if ($existing) {
        $db->update('events', $data, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('events', $data);
    }
}

// ─── announcements (3) ──────────────────────────────────────────────────────
$announcements = [
    'Welcome to the 2026 academic year',
    'New online payment option now available',
    'Annual prize giving ceremony announced',
];
foreach ($announcements as $title) {
    $existing = $db->fetch("SELECT id FROM announcements WHERE title = ?", [$title]);
    $data = [
        'title'          => $title,
        'title_bn'       => 'ডেমো ঘোষণা: ' . $title,
        'body'           => 'Demo announcement: ' . $title,
        'body_bn'        => 'ডেমো ঘোষণা: ' . $title,
        'audience'       => 'all',
        'display_target' => 'site',
        'is_published'   => 1,
        'starts_at'      => $now,
        'ends_at'        => null,
        'created_at'     => $now,
        'updated_at'     => $now,
    ];
    if ($existing) {
        $db->update('announcements', $data, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('announcements', $data);
    }
}

// ─── galleries (8) ──────────────────────────────────────────────────────────
$galleries = [
    'Annual Sports Day 2025' => 'sports',
    'Cultural Program' => 'cultural',
    'Science Fair Exhibition' => 'academic',
    'Independence Day Celebration' => 'cultural',
    'Classroom Activities' => 'academic',
    'Field Trip 2025' => 'cultural',
    'Graduation Ceremony' => 'cultural',
    'Art & Craft Exhibition' => 'cultural',
];
foreach ($galleries as $title => $cat) {
    $existing = $db->fetch("SELECT id FROM galleries WHERE title = ?", [$title]);
    $slug = strtolower(str_replace(' ', '-', $title));
    $data = [
        'title'        => $title,
        'description'  => 'Demo gallery: ' . $title,
        'image_path'   => "https://picsum.photos/seed/{$slug}/800/600",
        'category'     => $cat,
        'is_published' => 1,
        'created_at'   => $now,
        'updated_at'   => $now,
    ];
    if ($existing) {
        $db->update('galleries', $data, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('galleries', $data);
    }
}

// ─── fees (6 types per class) ───────────────────────────────────────────────
$feeTypes = ['Tuition', 'Transport', 'Library', 'Lab', 'Sports', 'Activity'];
$feeAmounts = ['Tuition' => 0, 'Transport' => 500, 'Library' => 100, 'Lab' => 200, 'Sports' => 150, 'Activity' => 100];
foreach ($classNames as $name) {
    foreach ($feeTypes as $ft) {
        $amount = $ft === 'Tuition' ? $classFees[array_search($name, $classNames, true)] : $feeAmounts[$ft];
        $code = strtoupper(substr($ft, 0, 3)) . '-' . str_replace(' ', '-', $name);
        $existing = $db->fetch("SELECT id FROM fees WHERE code = ?", [$code]);
        $data = [
            'name'              => $ft,
            'code'              => $code,
            'class_id'          => $classIds[$name],
            'amount'            => (float) $amount,
            'fee_type'          => strtolower($ft),
            'frequency'         => 'monthly',
            'fine_amount'       => 0,
            'fine_type'         => 'none',
            'fine_grace_days'   => 5,
            'discount_amount'   => 0,
            'discount_type'     => 'none',
            'status'            => 'active',
            'created_by'        => $adminId,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
        if ($existing) {
            $db->update('fees', $data, 'id = ?', [$existing['id']]);
        } else {
            $db->insert('fees', $data);
        }
    }
}

echo "Demo content seed complete.\n";
$counts = [
    'school_classes' => (int) ($db->fetch("SELECT COUNT(*) AS c FROM school_classes")['c'] ?? 0),
    'teachers'       => (int) ($db->fetch("SELECT COUNT(*) AS c FROM teachers")['c'] ?? 0),
    'students'       => (int) ($db->fetch("SELECT COUNT(*) AS c FROM students")['c'] ?? 0),
    'guardians'      => (int) ($db->fetch("SELECT COUNT(*) AS c FROM guardians")['c'] ?? 0),
    'notices'        => (int) ($db->fetch("SELECT COUNT(*) AS c FROM notices")['c'] ?? 0),
    'events'         => (int) ($db->fetch("SELECT COUNT(*) AS c FROM events")['c'] ?? 0),
    'announcements'  => (int) ($db->fetch("SELECT COUNT(*) AS c FROM announcements")['c'] ?? 0),
    'galleries'      => (int) ($db->fetch("SELECT COUNT(*) AS c FROM galleries")['c'] ?? 0),
    'fees'           => (int) ($db->fetch("SELECT COUNT(*) AS c FROM fees")['c'] ?? 0),
];
foreach ($counts as $k => $v) {
    echo "  {$k}: {$v}\n";
}