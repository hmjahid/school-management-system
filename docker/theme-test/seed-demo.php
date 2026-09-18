<?php
/**
 * Seeds esk_* demo content that mirrors the Laravel app's seeded homepage
 * data (school settings, homepage CMS rows, 30 teachers, notices, events,
 * galleries, notable students, testimonials).
 *
 * Idempotent — guarded by the `esk_demo_seeded` option.
 * Run from the wp-cli container:
 *   wp eval-file /wptest/seed-demo.php
 */

global $wpdb;
$prefix = $wpdb->prefix;

if ( get_option( 'esk_demo_seeded' ) ) {
	WP_CLI::log( 'Demo data already seeded; skipping.' );
	return;
}

/* 1. School settings (mirror app website_settings) */
update_option( 'esk_school_name', 'Example School' );
update_option( 'esk_school_tagline', 'Empowering Future Leaders' );
update_option( 'esk_school_phone', '+1 (555) 123-4567' );
update_option( 'esk_school_email', 'info@exampleschool.edu' );
update_option( 'esk_school_address', '123 Education Street, Learning City' );
update_option( 'esk_established_year', '1990' );
update_option( 'esk_currency', '$' );
update_option( 'esk_stats_awards', '5' );
update_option( 'esk_hero_title', 'Welcome to our school community' );
update_option( 'esk_hero_tagline', 'Where curiosity meets excellence and every learner matters.' );
update_option( 'esk_hero_cta_text', 'Apply Now' );

/* 2. Homepage CMS rows (hero, principal, slider) */
$contents = $prefix . 'esk_website_contents';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$contents}'" ) === $contents ) {
	$home_rows = array(
		array(
			'page'    => 'home',
			'section' => 'hero',
			'title'   => 'Home',
			'content' => null,
			'image'   => '',
			'meta'    => wp_json_encode( array(
				'hero_design' => 'design-1',
				'headline'    => 'Welcome to our school community',
				'subtitle'    => 'Where curiosity meets excellence and every learner matters.',
			) ),
			'sort_order' => 1,
		),
		array(
			'page'    => 'home',
			'section' => 'principal',
			'title'   => 'Principal',
			'content' => "We are delighted to welcome you to our school's website. Our dedicated faculty, rich programmes, and supportive environment help students grow academically and as responsible citizens.\n\nWe invite you to explore admissions, meet our faculty, and stay connected through the parent portal.",
			'image'   => '',
			'meta'    => null,
			'sort_order' => 2,
		),
		array(
			'page'    => 'home',
			'section' => 'slider',
			'title'   => 'Recent events & activities',
			'content' => wp_json_encode( array(
				array( 'image' => 'https://picsum.photos/seed/school-campus/1600/900',  'title' => 'Modern campus facilities', 'caption' => 'Purpose-built classrooms and labs for hands-on learning',     'link' => '/about/' ),
				array( 'image' => 'https://picsum.photos/seed/school-sports/1600/900',  'title' => 'Annual sports day',       'caption' => 'Celebrating teamwork, sportsmanship and school spirit', 'link' => '/news/' ),
				array( 'image' => 'https://picsum.photos/seed/school-science/1600/900', 'title' => 'Science fair 2026',        'caption' => 'Students showcase innovative projects',                 'link' => '/news/' ),
				array( 'image' => 'https://picsum.photos/seed/school-culture/1600/900', 'title' => 'Cultural programs',        'caption' => 'A stage for art, music and creativity',                 'link' => '/gallery/' ),
			) ),
			'image'   => '',
			'meta'    => null,
			'sort_order' => 3,
		),
	);
	foreach ( $home_rows as $r ) {
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$contents} WHERE page = %s AND section = %s", $r['page'], $r['section'] ) );
		if ( $exists ) {
			continue;
		}
		$wpdb->insert( $contents, $r );
	}
}

/* 3. Teachers (30 wp users + esk_teachers rows) */
$teachers = array(
	array( 'teacher1',  'Md. Abdul Rahman',   'M.Ed' ),
	array( 'teacher2',  'Fatima Begum',       'B.Ed' ),
	array( 'teacher3',  'Mohammad Hossain',   'M.Sc in Mathematics' ),
	array( 'teacher4',  'Ayesha Islam',       'M.A in English' ),
	array( 'teacher5',  'Md. Kamal Khan',     'M.Sc in Physics' ),
	array( 'teacher6',  'Shahinur Akter',     'M.Sc in Chemistry' ),
	array( 'teacher7',  'Md. Rafiq Haque',    'M.A in Bengali' ),
	array( 'teacher8',  'Nasrin Mollah',      'M.Sc in ICT' ),
	array( 'teacher9',  'Md. Jashim Sarker',  'B.Sc in Computer Science' ),
	array( 'teacher10', 'Shamima Chowdhury',  'M.Phil in Education' ),
	array( 'teacher11', 'Hasan Ahmed',        'PhD in Education' ),
	array( 'teacher12', 'Parvin Khatun',      'MBA' ),
	array( 'teacher13', 'Mizanur Siddique',   'MSS in Economics' ),
	array( 'teacher14', 'Shahnaz Jahan',      'BSS in Political Science' ),
	array( 'teacher15', 'Jahangir Mahmud',    'M.Sc in Biology' ),
	array( 'teacher16', 'Laili Pervin',       'M.Ed' ),
	array( 'teacher17', 'Tariqul Kabir',      'B.Ed' ),
	array( 'teacher18', 'Rokeya Nahar',       'M.Sc in Mathematics' ),
	array( 'teacher19', 'Shahidul Bhuiyan',   'M.A in English' ),
	array( 'teacher20', 'Mahbuba Hasan',      'M.Sc in Physics' ),
	array( 'teacher21', 'Anwar Rahman',       'M.Sc in Chemistry' ),
	array( 'teacher22', 'Selina Begum',       'M.A in Bengali' ),
	array( 'teacher23', 'Kawsar Hossain',     'M.Sc in ICT' ),
	array( 'teacher24', 'Jannatul Islam',     'B.Sc in Computer Science' ),
	array( 'teacher25', 'Riaz Khan',          'M.Phil in Education' ),
	array( 'teacher26', 'Maksuda Akter',      'PhD in Education' ),
	array( 'teacher27', 'Firoz Haque',        'MBA' ),
	array( 'teacher28', 'Tahmina Mollah',     'MSS in Economics' ),
	array( 'teacher29', 'Nazmul Sarker',      'BSS in Political Science' ),
	array( 'teacher30', 'Sharmin Chowdhury',  'M.Sc in Biology' ),
);

$teachers_table = $prefix . 'esk_teachers';
foreach ( $teachers as $i => $t ) {
	list( $login, $display, $qualification ) = $t;
	$existing = get_user_by( 'email', $login . '@school.com' );
	if ( $existing ) {
		$uid = $existing->ID;
	} else {
		$uid = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $login . '@school.com',
			'user_pass'    => 'password',
			'display_name' => $display,
			'role'         => 'teacher',
		) );
	}
	if ( is_wp_error( $uid ) ) {
		WP_CLI::warning( 'Teacher user failed: ' . $uid->get_error_message() );
		continue;
	}
	$row = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$teachers_table} WHERE user_id = %d", $uid ) );
	if ( ! $row ) {
		$wpdb->insert( $teachers_table, array(
			'user_id'       => $uid,
			'qualification' => $qualification,
			'subjects'      => null,
		) );
	}
}

/* 4. Notices */
$notices = array(
	array( 'Admission Open for 2026', 'Online admission is now open for the academic year 2026. Apply before the deadline to secure your seat.' ),
	array( 'Parent-Teacher Meeting — August 10', 'All parents are requested to attend the parent-teacher meeting scheduled for August 10, 2026 at 10:00 AM.' ),
	array( 'Mid-Term Examination Schedule Released', 'The mid-term examination schedule has been published. Students are advised to check the routine and prepare accordingly.' ),
	array( 'School Closure — National Holiday', 'The school will remain closed on August 15 due to the national holiday. Regular classes will resume on August 16.' ),
	array( 'Annual Sports Day — September 5', 'Annual sports day will be held on September 5. All students must participate in at least one event.' ),
);
$notices_table = $prefix . 'esk_notices';
foreach ( $notices as $n ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$notices_table} WHERE title = %s", $n[0] ) ) ) {
		$wpdb->insert( $notices_table, array(
			'title'      => $n[0],
			'content'    => $n[1],
			'pinned'     => 0,
			'created_by' => 1,
		) );
	}
}

/* 5. Events */
$events = array(
	array( 'Annual Sports Day', 'Annual sports competition with athletics, races, and team games.', 'School Playground', '2026-08-14 03:19:22' ),
	array( 'Cultural Program 2025', 'Students showcase their talents in music, dance, and drama.', 'School Auditorium', '2026-09-13 03:19:22' ),
	array( 'Parent-Teacher Meeting', 'Quarterly meeting to discuss student progress and development.', 'School Hall', '2026-08-06 03:19:22' ),
	array( 'Science Fair', 'Students present their science projects and innovations.', 'Science Building', '2026-09-30 03:19:22' ),
	array( 'Independence Day Celebration', 'Celebrating Bangladesh Independence Day with parade and cultural events.', 'School Field', '2026-03-26 03:19:22' ),
	array( 'Victory Day Program', 'Celebrating Victory Day with special assembly and performances.', 'School Field', '2026-12-16 03:19:22' ),
	array( 'Educational Tour', 'Annual educational tour for senior students.', 'Various Locations', '2026-10-30 03:19:22' ),
	array( 'Book Fair', 'Annual book fair at school library with discounts from publishers.', 'School Library', '2026-09-28 03:19:22' ),
	array( 'ICT Olympiad', 'Inter-school ICT competition including programming and robotics.', 'Computer Lab', '2026-08-29 03:19:22' ),
	array( 'Annual Prize Giving Ceremony', 'Recognizing academic and extracurricular achievements.', 'School Auditorium', '2026-11-30 03:19:22' ),
	array( 'Eid Reunion', 'Eid celebration with teachers and students.', 'School Campus', '2026-10-28 03:19:22' ),
	array( 'Debate Competition', 'Inter-class debate competition on current topics.', 'School Auditorium', '2026-08-19 03:19:22' ),
	array( 'Art Exhibition', 'Showcasing student artwork from all classes.', 'Art Gallery', '2026-10-13 03:19:22' ),
	array( 'Health & Hygiene Workshop', 'Workshop on personal hygiene and health awareness.', 'School Hall', '2026-08-09 03:19:22' ),
	array( 'Summer Sports Camp', 'Summer camp featuring coaching in various sports.', 'Sports Complex', '2026-08-30 03:19:22' ),
);
$events_table = $prefix . 'esk_events';
foreach ( $events as $e ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$events_table} WHERE title = %s", $e[0] ) ) ) {
		$wpdb->insert( $events_table, array(
			'created_by' => 1,
			'title'      => $e[0],
			'description' => $e[1],
			'location'   => $e[2],
			'start_date' => $e[3],
			'status'     => 'published',
		) );
	}
}

/* 6. Galleries */
$galleries = array(
	array( 'Annual Sports Day 2025', 'https://picsum.photos/seed/Annual-Sports-Day-2025/800/600', 'sports' ),
	array( 'Cultural Program', 'https://picsum.photos/seed/Cultural-Program/800/600', 'cultural' ),
	array( 'Science Fair Exhibition', 'https://picsum.photos/seed/Science-Fair-Exhibition/800/600', 'academic' ),
	array( 'Independence Day Celebration', 'https://picsum.photos/seed/Independence-Day-Celebration/800/600', 'cultural' ),
	array( 'Classroom Activities', 'https://picsum.photos/seed/Classroom-Activities/800/600', 'academic' ),
	array( 'Field Trip 2025', 'https://picsum.photos/seed/Field-Trip-2025/800/600', 'academic' ),
	array( 'Graduation Ceremony', 'https://picsum.photos/seed/Graduation-Ceremony/800/600', 'cultural' ),
	array( 'Art & Craft Exhibition', 'https://picsum.photos/seed/Art-&-Craft-Exhibition/800/600', 'cultural' ),
);
$galleries_table = $prefix . 'esk_galleries';
foreach ( $galleries as $g ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$galleries_table} WHERE title = %s", $g[0] ) ) ) {
		$wpdb->insert( $galleries_table, array(
			'title'        => $g[0],
			'image_path'   => $g[1],
			'category'     => $g[2],
			'is_published' => 1,
		) );
	}
}

/* 7. Classes (app's full list) + students (2 notable = app's "Remarkable Students") */
$classes_table = $prefix . 'esk_classes';
$app_classes   = array(
	'Play'    => 'PLAY', 'Nursery' => 'NUR', 'KG-1' => 'KG1', 'KG-2' => 'KG2',
	'Class 1' => 'C1', 'Class 2' => 'C2', 'Class 3' => 'C3', 'Class 4' => 'C4',
	'Class 5' => 'C5', 'Class 6' => 'C6', 'Class 7' => 'C7', 'Class 8' => 'C8',
	'Class 9' => 'C9', 'Class 10' => 'C10',
);
foreach ( $app_classes as $cname => $ccode ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$classes_table} WHERE name = %s", $cname ) ) ) {
		$wpdb->insert( $classes_table, array( 'name' => $cname, 'code' => $ccode, 'shift' => 'Morning' ) );
	}
}
$class_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$classes_table} WHERE name = %s", 'Class 1' ) );

$students = array(
	array( 'student1', 'Rahim Hossain', '2026-01-15', '1', 1 ),
	array( 'student2', 'Karim Rahman',  '2026-01-15', '2', 0 ),
	array( 'student3', 'Fahim Khan',    '2026-01-15', '3', 1 ),
	array( 'student4', 'Nabila Islam',  '2026-01-15', '4', 0 ),
	array( 'student5', 'Tanvir Ahmed',  '2026-01-15', '5', 0 ),
);
$students_table = $prefix . 'esk_students';
foreach ( $students as $s ) {
	list( $login, $display, $adm_date, $adm_no, $notable ) = $s;
	$existing = get_user_by( 'email', $login . '@school.com' );
	if ( $existing ) {
		$uid = $existing->ID;
	} else {
		$uid = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $login . '@school.com',
			'user_pass'    => 'student123',
			'display_name' => $display,
			'role'         => 'subscriber',
		) );
	}
	if ( is_wp_error( $uid ) ) {
		continue;
	}
	$row = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$students_table} WHERE user_id = %d", $uid ) );
	if ( ! $row ) {
		$wpdb->insert( $students_table, array(
			'user_id'          => $uid,
			'class_id'         => $class_id,
			'admission_number' => 'STU-' . $adm_no,
			'admission_date'   => $adm_date,
			'roll_number'      => $adm_no,
			'status'           => 'active',
			'is_notable'       => (int) $notable,
			'achievement'      => $notable ? 'Academic excellence' : null,
		) );
	}
}

/* 7b. Announcements (app demo) */
$ann_table = $prefix . 'esk_announcements';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$ann_table}'" ) === $ann_table ) {
	$announcements = array(
		'Welcome to the 2026 academic year',
		'New online payment option now available',
		'Annual prize giving ceremony announced',
	);
	foreach ( $announcements as $atitle ) {
		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$ann_table} WHERE title = %s", $atitle ) ) ) {
			$wpdb->insert( $ann_table, array(
				'title'          => $atitle,
				'body'           => 'Demo announcement: ' . $atitle,
				'audience'       => 'all',
				'is_published'   => 1,
				'display_target' => 'all',
				'starts_at'      => current_time( 'mysql' ),
			) );
		}
	}
}

/* 7c. Fees for Class 1 (app demo fee types) */
$fees_table = $prefix . 'esk_fees';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$fees_table}'" ) === $fees_table ) {
	$fee_types = array(
		array( 'Tuition', 'TUITION', 1200 ), array( 'Transport', 'TRANSPORT', 500 ),
		array( 'Library', 'LIBRARY', 100 ), array( 'Lab', 'LAB', 200 ),
		array( 'Sports', 'SPORTS', 150 ), array( 'Activity', 'ACTIVITY', 100 ),
	);
	foreach ( $fee_types as $f ) {
		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$fees_table} WHERE code = %s", $f[1] ) ) ) {
			$wpdb->insert( $fees_table, array(
				'name'       => $f[0],
				'code'       => $f[1],
				'class_id'   => $class_id,
				'amount'     => $f[2],
				'fee_type'   => strtolower( $f[0] ),
				'frequency'  => 'monthly',
				'fine_amount' => 0,
				'fine_type'  => 'fixed',
			) );
		}
	}
}

/* 7d. Subjects + exams for Class 1 (app demo exam types) */
$subjects_table = $prefix . 'esk_subjects';
$exams_table    = $prefix . 'esk_exams';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$subjects_table}'" ) === $subjects_table ) {
	$subjects = array( 'Bangla', 'English', 'Mathematics' );
	$subject_ids = array();
	foreach ( $subjects as $sname ) {
		$sid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$subjects_table} WHERE name = %s", $sname ) );
		if ( ! $sid ) {
			$wpdb->insert( $subjects_table, array( 'name' => $sname, 'code' => strtoupper( substr( $sname, 0, 3 ) ) ) );
			$sid = (int) $wpdb->insert_id;
		}
		$subject_ids[ $sname ] = $sid;
	}
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$exams_table}'" ) === $exams_table ) {
		$exam_types = array(
			array( 'Midterm', 'MID', 50 ), array( 'Final', 'FINAL', 100 ), array( 'Pre-Test', 'PRETEST', 100 ),
		);
		foreach ( $exam_types as $e ) {
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$exams_table} WHERE code = %s", $e[1] ) ) ) {
				$wpdb->insert( $exams_table, array(
					'name'          => $e[0],
					'code'          => $e[1],
					'class_id'      => $class_id,
					'subject_id'    => $subject_ids['Mathematics'],
					'start_date'    => gmdate( 'Y-m-d' ),
					'end_date'      => gmdate( 'Y-m-d', strtotime( '+2 days' ) ),
					'start_time'    => '09:00:00',
					'end_time'      => '11:00:00',
					'total_marks'   => $e[2],
					'passing_marks' => (int) ( $e[2] * 0.33 ),
					'status'        => 'upcoming',
				) );
			}
		}
	}
}

/* 8. Testimonials (app CMS quotes) */
$testimonials = array(
	array( 'A. Rahman', 'Parent',  'The teachers genuinely care — my daughter has flourished here.' ),
	array( 'S. Khan',   'Alumni',  'Strong academics and clubs — I found my passion for science.' ),
);
$testimonials_table = $prefix . 'esk_testimonials';
foreach ( $testimonials as $ti => $t ) {
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$testimonials_table} WHERE author_name = %s", $t[0] ) ) ) {
		$wpdb->insert( $testimonials_table, array(
			'author_name'        => $t[0],
			'author_designation' => $t[1],
			'content'            => $t[2],
			'is_visible'         => 1,
			'sort_order'         => $ti,
		) );
	}
}

/* 9. Payments (dashboard revenue chart + dues) */
$pay_table = $prefix . 'esk_payments';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$pay_table}'" ) === $pay_table ) {
	for ( $i = 0; $i < 8; $i++ ) {
		$inv    = 'INV-10' . $i . '01';
		$amount = 5000 + ( $i * 750 );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$pay_table} WHERE invoice_number = %s", $inv ) ) ) {
			continue;
		}
		$wpdb->insert( $pay_table, array(
			'invoice_number' => $inv,
			'amount'         => $amount,
			'paid_amount'    => $amount,
			'due_amount'     => 0,
			'total_amount'   => $amount,
			'payment_method' => 'cash',
			'payment_status' => 'completed',
			'payment_date'   => gmdate( 'Y-m-d', strtotime( '-' . $i . ' months' ) ),
			'created_by'     => 1,
		) );
	}
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$pay_table} WHERE invoice_number = %s", 'INV-PEND-01' ) ) ) {
		$wpdb->insert( $pay_table, array(
			'invoice_number' => 'INV-PEND-01',
			'amount'         => 3000,
			'paid_amount'    => 1000,
			'due_amount'     => 2000,
			'total_amount'   => 3000,
			'payment_method' => 'cash',
			'payment_status' => 'partial',
			'payment_date'   => gmdate( 'Y-m-d' ),
			'created_by'     => 1,
		) );
	}
}

/* 10. Expenses (dashboard revenue vs expense chart) */
$exp_table = $prefix . 'esk_expenses';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$exp_table}'" ) === $exp_table ) {
	$cats = array( 'Utilities', 'Salary', 'Maintenance', 'Supplies' );
	foreach ( $cats as $ci => $cat ) {
		for ( $m = 0; $m < 6; $m++ ) {
			$amount = 1500 + ( $ci * 400 ) + ( $m * 150 );
			$date   = gmdate( 'Y-m-d', strtotime( '-' . $m . ' months' ) );
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$exp_table} WHERE category = %s AND date = %s", $cat, $date ) );
			if ( $exists ) {
				continue;
			}
			$wpdb->insert( $exp_table, array(
				'category' => $cat,
				'amount'   => $amount,
				'date'     => $date,
				'created_by' => 1,
			) );
		}
	}
}

/* 11. Attendances (dashboard today + 7-day trend) */
$att_table = $prefix . 'esk_attendances';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$att_table}'" ) === $att_table ) {
	$student_rows = $wpdb->get_results( "SELECT id FROM {$students_table} ORDER BY id LIMIT 5" );
	$class_id     = $class_id ?: 1;
	foreach ( $student_rows as $si => $s ) {
		for ( $d = 6; $d >= 0; $d-- ) {
			$date   = gmdate( 'Y-m-d', strtotime( '-' . $d . ' days' ) );
			$status = 'present';
			if ( 0 === ( $si + $d ) % 5 ) { $status = 'absent'; }
			if ( 3 === ( $si + $d ) % 5 ) { $status = 'late'; }
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$att_table} WHERE student_id = %d AND date = %s", $s->id, $date ) );
			if ( $exists ) {
				continue;
			}
			$wpdb->insert( $att_table, array(
				'student_id'      => $s->id,
				'school_class_id' => $class_id,
				'date'            => $date,
				'status'          => $status,
				'marked_by'       => 1,
			) );
		}
	}
}

update_option( 'esk_demo_seeded', '1' );
WP_CLI::success( 'Demo data seeded.' );