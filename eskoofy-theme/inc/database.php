<?php
/**
 * Database table creation for Eskoofy.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Create all custom tables on plugin/theme activation.
 */
function esk_create_tables(): void {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = array();

	// ─── Students ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_students (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		class_id BIGINT UNSIGNED NOT NULL,
		section_id BIGINT UNSIGNED DEFAULT NULL,
		batch_id BIGINT UNSIGNED DEFAULT NULL,
		guardian_id BIGINT UNSIGNED DEFAULT NULL,
		admission_number VARCHAR(50) NOT NULL,
		admission_date DATE NOT NULL,
		roll_number VARCHAR(20) DEFAULT NULL,
		blood_group VARCHAR(5) DEFAULT NULL,
		religion VARCHAR(50) DEFAULT NULL,
		nationality VARCHAR(100) DEFAULT 'Bangladeshi',
		nid_number VARCHAR(50) DEFAULT NULL,
		birth_certificate_number VARCHAR(50) DEFAULT NULL,
		permanent_address TEXT DEFAULT NULL,
		present_address TEXT DEFAULT NULL,
		city VARCHAR(100) DEFAULT NULL,
		state VARCHAR(100) DEFAULT NULL,
		zip_code VARCHAR(20) DEFAULT NULL,
		country VARCHAR(100) DEFAULT 'Bangladesh',
		phone_1 VARCHAR(20) DEFAULT NULL,
		phone_2 VARCHAR(20) DEFAULT NULL,
		email VARCHAR(191) DEFAULT NULL,
		parent_name VARCHAR(100) DEFAULT NULL,
		parent_phone VARCHAR(20) DEFAULT NULL,
		parent_email VARCHAR(100) DEFAULT NULL,
		parent_occupation VARCHAR(100) DEFAULT NULL,
		parent_address TEXT DEFAULT NULL,
		monthly_fee DECIMAL(10,2) DEFAULT 0,
		transport_fee DECIMAL(10,2) DEFAULT 0,
		discount DECIMAL(10,2) DEFAULT 0,
		status VARCHAR(20) DEFAULT 'active',
		notes TEXT DEFAULT NULL,
		attendance_percentage DECIMAL(5,2) DEFAULT NULL,
		is_notable TINYINT(1) DEFAULT 0,
		achievement TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		deleted_at DATETIME DEFAULT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY uk_admission_number (admission_number),
		KEY idx_user_id (user_id),
		KEY idx_class_id (class_id),
		KEY idx_section_id (section_id),
		KEY idx_batch_id (batch_id),
		KEY idx_guardian_id (guardian_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Teachers ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_teachers (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		qualification VARCHAR(255) DEFAULT NULL,
		subjects TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_id (user_id)
	) {$charset_collate}";

	// ─── Guardians ──────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_guardians (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_id (user_id)
	) {$charset_collate}";

	// ─── Classes ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_classes (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		seating_capacity INT DEFAULT NULL,
		teacher_id BIGINT UNSIGNED DEFAULT NULL,
		parent_id BIGINT UNSIGNED DEFAULT NULL,
		code VARCHAR(20) DEFAULT NULL,
		shift VARCHAR(50) DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_teacher_id (teacher_id),
		KEY idx_parent_id (parent_id)
	) {$charset_collate}";

	// ─── Sections ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_sections (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		slug VARCHAR(255) NOT NULL,
		capacity INT DEFAULT 30,
		description TEXT DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 1,
		class_teacher_id BIGINT UNSIGNED DEFAULT NULL,
		academic_year_id BIGINT UNSIGNED NOT NULL,
		class_id BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_slug (slug),
		KEY idx_class_id (class_id),
		KEY idx_academic_year_id (academic_year_id)
	) {$charset_collate}";

	// ─── Batches ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_batches (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		code VARCHAR(20) DEFAULT NULL,
		description TEXT DEFAULT NULL,
		start_date DATE DEFAULT NULL,
		end_date DATE DEFAULT NULL,
		academic_session_id BIGINT UNSIGNED DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 1,
		status VARCHAR(20) DEFAULT NULL,
		notes TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_academic_session_id (academic_session_id)
	) {$charset_collate}";

	// ─── Subjects ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_subjects (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		code VARCHAR(20) NOT NULL,
		teacher_id BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_code (code),
		KEY idx_teacher_id (teacher_id)
	) {$charset_collate}";

	// ─── Class-Subject pivot ────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_class_subject (
		school_class_id BIGINT UNSIGNED NOT NULL,
		subject_id BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (school_class_id, subject_id),
		KEY idx_subject_id (subject_id)
	) {$charset_collate}";

	// ─── Academic Sessions ──────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_academic_sessions (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		code VARCHAR(20) NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		description TEXT DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 0,
		is_current TINYINT(1) DEFAULT 0,
		status VARCHAR(20) DEFAULT 'upcoming',
		metadata TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_name (name),
		UNIQUE KEY uk_code (code)
	) {$charset_collate}";

	// ─── Academic Years ─────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_academic_years (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		session VARCHAR(255) NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		is_current TINYINT(1) DEFAULT 0,
		description TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_session (session)
	) {$charset_collate}";

	// ─── Exams ──────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_exams (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		code VARCHAR(20) NOT NULL,
		description TEXT DEFAULT NULL,
		class_id BIGINT UNSIGNED NOT NULL,
		subject_id BIGINT UNSIGNED NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		start_time TIME NOT NULL,
		end_time TIME NOT NULL,
		total_marks INT NOT NULL,
		passing_marks INT NOT NULL,
		exam_room VARCHAR(100) DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'upcoming',
		publish_results TINYINT(1) DEFAULT 0,
		is_published TINYINT(1) DEFAULT 0,
		grade_scale TEXT DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL,
		batch_id BIGINT UNSIGNED DEFAULT NULL,
		academic_session_id BIGINT UNSIGNED DEFAULT NULL,
		section_id BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_code (code),
		KEY idx_class_id (class_id),
		KEY idx_subject_id (subject_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Exam Results ───────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_exam_results (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		exam_id BIGINT UNSIGNED NOT NULL,
		student_id BIGINT UNSIGNED NOT NULL,
		obtained_marks DECIMAL(8,2) DEFAULT 0,
		grade VARCHAR(10) DEFAULT NULL,
		grade_point DECIMAL(4,2) DEFAULT NULL,
		remarks TEXT DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'pending',
		submitted_by BIGINT UNSIGNED DEFAULT NULL,
		submitted_at DATETIME DEFAULT NULL,
		reviewed_by BIGINT UNSIGNED DEFAULT NULL,
		reviewed_at DATETIME DEFAULT NULL,
		review_remarks TEXT DEFAULT NULL,
		is_published TINYINT(1) DEFAULT 0,
		published_at DATETIME DEFAULT NULL,
		published_by BIGINT UNSIGNED DEFAULT NULL,
		publish_remarks TEXT DEFAULT NULL,
		unpublished_at DATETIME DEFAULT NULL,
		unpublished_by BIGINT UNSIGNED DEFAULT NULL,
		unpublish_remarks TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_exam_id (exam_id),
		KEY idx_student_id (student_id),
		KEY idx_status (status),
		KEY idx_is_published (is_published)
	) {$charset_collate}";

	// ─── Attendances ────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_attendances (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		student_id BIGINT UNSIGNED NOT NULL,
		school_class_id BIGINT UNSIGNED NOT NULL,
		subject_id BIGINT UNSIGNED DEFAULT NULL,
		date DATE NOT NULL,
		status VARCHAR(10) NOT NULL,
		marked_by BIGINT UNSIGNED NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_student_id (student_id),
		KEY idx_class_id (school_class_id),
		KEY idx_date (date),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Fees ───────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_fees (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		code VARCHAR(20) NOT NULL,
		description TEXT DEFAULT NULL,
		class_id BIGINT UNSIGNED NOT NULL,
		section_id BIGINT UNSIGNED DEFAULT NULL,
		student_id BIGINT UNSIGNED DEFAULT NULL,
		amount DECIMAL(10,2) NOT NULL,
		fee_type VARCHAR(50) NOT NULL,
		frequency VARCHAR(30) DEFAULT 'one_time',
		start_date DATE DEFAULT NULL,
		end_date DATE DEFAULT NULL,
		fine_amount DECIMAL(10,2) DEFAULT 0,
		fine_type VARCHAR(10) DEFAULT 'fixed',
		fine_grace_days INT DEFAULT 0,
		discount_amount DECIMAL(10,2) DEFAULT 0,
		discount_type VARCHAR(10) DEFAULT 'fixed',
		status VARCHAR(20) DEFAULT 'active',
		metadata TEXT DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_code (code),
		KEY idx_class_id (class_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Fee Payments ───────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_fee_payments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		invoice_number VARCHAR(50) NOT NULL,
		student_id BIGINT UNSIGNED NOT NULL,
		fee_id BIGINT UNSIGNED NOT NULL,
		amount DECIMAL(10,2) NOT NULL,
		discount_amount DECIMAL(10,2) DEFAULT 0,
		fine_amount DECIMAL(10,2) DEFAULT 0,
		paid_amount DECIMAL(10,2) NOT NULL,
		balance DECIMAL(10,2) DEFAULT 0,
		payment_date DATE NOT NULL,
		month VARCHAR(20) DEFAULT NULL,
		year INT DEFAULT NULL,
		payment_method VARCHAR(50) NOT NULL,
		transaction_id VARCHAR(100) DEFAULT NULL,
		bank_name VARCHAR(100) DEFAULT NULL,
		check_number VARCHAR(50) DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'pending',
		notes TEXT DEFAULT NULL,
		metadata TEXT DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL,
		approved_by BIGINT UNSIGNED DEFAULT NULL,
		approved_at DATETIME DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_invoice (invoice_number),
		KEY idx_student_id (student_id),
		KEY idx_fee_id (fee_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Payments ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_payments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		paymentable_type VARCHAR(100) DEFAULT NULL,
		paymentable_id BIGINT UNSIGNED DEFAULT NULL,
		invoice_number VARCHAR(50) NOT NULL,
		reference_number VARCHAR(100) DEFAULT NULL,
		transaction_id VARCHAR(255) DEFAULT NULL,
		amount DECIMAL(12,2) NOT NULL,
		paid_amount DECIMAL(12,2) DEFAULT 0,
		due_amount DECIMAL(12,2) DEFAULT 0,
		discount_amount DECIMAL(12,2) DEFAULT 0,
		fine_amount DECIMAL(12,2) DEFAULT 0,
		tax_amount DECIMAL(12,2) DEFAULT 0,
		total_amount DECIMAL(12,2) NOT NULL,
		payment_method VARCHAR(30) NOT NULL,
		payment_status VARCHAR(20) DEFAULT 'pending',
		payment_date DATE DEFAULT NULL,
		due_date DATE DEFAULT NULL,
		payment_details TEXT DEFAULT NULL,
		notes TEXT DEFAULT NULL,
		metadata TEXT DEFAULT NULL,
		created_by BIGINT UNSIGNED DEFAULT NULL,
		updated_by BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_invoice (invoice_number),
		KEY idx_payment_status (payment_status),
		KEY idx_paymentable (paymentable_type, paymentable_id)
	) {$charset_collate}";

	// ─── Payment Gateways ───────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_payment_gateways (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		code VARCHAR(50) NOT NULL,
		type VARCHAR(30) NOT NULL,
		is_active TINYINT(1) DEFAULT 1,
		is_online TINYINT(1) DEFAULT 0,
		has_api TINYINT(1) DEFAULT 0,
		sandbox_url VARCHAR(500) DEFAULT NULL,
		live_url VARCHAR(500) DEFAULT NULL,
		test_mode TINYINT(1) DEFAULT 1,
		api_key TEXT DEFAULT NULL,
		api_secret TEXT DEFAULT NULL,
		api_username VARCHAR(100) DEFAULT NULL,
		api_password TEXT DEFAULT NULL,
		callback_url VARCHAR(500) DEFAULT NULL,
		webhook_url VARCHAR(500) DEFAULT NULL,
		success_url VARCHAR(500) DEFAULT NULL,
		cancel_url VARCHAR(500) DEFAULT NULL,
		ipn_url VARCHAR(500) DEFAULT NULL,
		logo VARCHAR(500) DEFAULT NULL,
		description TEXT DEFAULT NULL,
		instructions TEXT DEFAULT NULL,
		currency CHAR(3) DEFAULT 'BDT',
		fee_percentage DECIMAL(5,2) DEFAULT 0,
		fee_fixed DECIMAL(12,2) DEFAULT 0,
		min_amount DECIMAL(12,2) DEFAULT NULL,
		max_amount DECIMAL(12,2) DEFAULT NULL,
		supported_currencies TEXT DEFAULT NULL,
		extra_attributes TEXT DEFAULT NULL,
		sort_order INT DEFAULT 0,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_code (code),
		KEY idx_active (is_active, type)
	) {$charset_collate}";

	// ─── Refunds ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_refunds (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		payment_id BIGINT UNSIGNED NOT NULL,
		user_id BIGINT UNSIGNED NOT NULL,
		processed_by BIGINT UNSIGNED DEFAULT NULL,
		amount DECIMAL(12,2) NOT NULL,
		currency CHAR(3) DEFAULT 'BDT',
		transaction_id VARCHAR(255) DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'pending',
		reason VARCHAR(255) DEFAULT NULL,
		processed_at DATETIME DEFAULT NULL,
		metadata TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_payment_id (payment_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Admissions ─────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_admissions (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		application_number VARCHAR(50) NOT NULL,
		academic_session_id BIGINT UNSIGNED NOT NULL,
		batch_id BIGINT UNSIGNED NOT NULL,
		first_name VARCHAR(100) NOT NULL,
		last_name VARCHAR(100) NOT NULL,
		gender VARCHAR(10) NOT NULL,
		date_of_birth DATE NOT NULL,
		blood_group VARCHAR(10) DEFAULT NULL,
		religion VARCHAR(50) DEFAULT NULL,
		nationality VARCHAR(100) DEFAULT 'Bangladeshi',
		photo VARCHAR(500) DEFAULT NULL,
		email VARCHAR(191) NOT NULL,
		phone VARCHAR(20) NOT NULL,
		address TEXT NOT NULL,
		city VARCHAR(100) NOT NULL,
		state VARCHAR(100) DEFAULT NULL,
		country VARCHAR(100) DEFAULT 'Bangladesh',
		postal_code VARCHAR(20) NOT NULL,
		father_name VARCHAR(100) NOT NULL,
		father_phone VARCHAR(20) NOT NULL,
		father_occupation VARCHAR(100) DEFAULT NULL,
		mother_name VARCHAR(100) NOT NULL,
		mother_phone VARCHAR(20) NOT NULL,
		mother_occupation VARCHAR(100) DEFAULT NULL,
		guardian_name VARCHAR(100) DEFAULT NULL,
		guardian_relation VARCHAR(50) DEFAULT NULL,
		guardian_phone VARCHAR(20) DEFAULT NULL,
		previous_school VARCHAR(255) DEFAULT NULL,
		previous_class VARCHAR(100) DEFAULT NULL,
		previous_grade VARCHAR(50) DEFAULT NULL,
		transfer_certificate VARCHAR(500) DEFAULT NULL,
		birth_certificate VARCHAR(500) DEFAULT NULL,
		other_documents TEXT DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'draft',
		rejection_reason TEXT DEFAULT NULL,
		admission_date DATE DEFAULT NULL,
		admission_notes TEXT DEFAULT NULL,
		submitted_at DATETIME DEFAULT NULL,
		approved_at DATETIME DEFAULT NULL,
		rejected_at DATETIME DEFAULT NULL,
		enrolled_at DATETIME DEFAULT NULL,
		cancelled_at DATETIME DEFAULT NULL,
		created_by BIGINT UNSIGNED DEFAULT NULL,
		updated_by BIGINT UNSIGNED DEFAULT NULL,
		approved_by BIGINT UNSIGNED DEFAULT NULL,
		rejected_by BIGINT UNSIGNED DEFAULT NULL,
		metadata TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_application_number (application_number),
		KEY idx_status (status),
		KEY idx_email (email),
		KEY idx_phone (phone)
	) {$charset_collate}";

	// ─── Admission Documents ────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_admission_documents (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		admission_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(50) NOT NULL,
		name VARCHAR(255) NOT NULL,
		file_path VARCHAR(500) NOT NULL,
		file_type VARCHAR(100) NOT NULL,
		file_size INT NOT NULL,
		description TEXT DEFAULT NULL,
		is_approved TINYINT(1) DEFAULT 0,
		review_notes TEXT DEFAULT NULL,
		reviewed_by BIGINT UNSIGNED DEFAULT NULL,
		reviewed_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_admission_id (admission_id),
		KEY idx_type (type)
	) {$charset_collate}";

	// ─── Admission Settings ─────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_admission_settings (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		is_open TINYINT(1) DEFAULT 1,
		closed_message_en TEXT DEFAULT NULL,
		closed_message_bn TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── News ───────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_news (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(500) NOT NULL,
		slug VARCHAR(500) NOT NULL,
		content TEXT NOT NULL,
		image_url VARCHAR(500) DEFAULT NULL,
		category VARCHAR(100) DEFAULT NULL,
		is_published TINYINT(1) DEFAULT 0,
		is_event TINYINT(1) DEFAULT 0,
		published_at DATETIME DEFAULT NULL,
		event_date DATETIME DEFAULT NULL,
		event_location VARCHAR(255) DEFAULT NULL,
		author_name VARCHAR(100) DEFAULT NULL,
		author_avatar VARCHAR(500) DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_slug (slug),
		KEY idx_is_published (is_published)
	) {$charset_collate}";

	// ─── Events ─────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_events (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		created_by BIGINT UNSIGNED NOT NULL,
		title VARCHAR(500) NOT NULL,
		description TEXT DEFAULT NULL,
		location VARCHAR(255) DEFAULT NULL,
		start_date DATETIME NOT NULL,
		end_date DATETIME DEFAULT NULL,
		registration_deadline DATETIME DEFAULT NULL,
		max_attendees INT DEFAULT NULL,
		is_virtual TINYINT(1) DEFAULT 0,
		meeting_url VARCHAR(500) DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'draft',
		image VARCHAR(500) DEFAULT NULL,
		metadata TEXT DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_status (status),
		KEY idx_start_date (start_date)
	) {$charset_collate}";

	// ─── Notices ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_notices (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(500) NOT NULL,
		title_bn VARCHAR(500) DEFAULT NULL,
		content TEXT NOT NULL,
		content_bn TEXT DEFAULT NULL,
		attachments TEXT DEFAULT NULL,
		pinned TINYINT(1) DEFAULT 0,
		audience TEXT DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_pinned (pinned)
	) {$charset_collate}";

	// ─── Announcements ──────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_announcements (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(500) NOT NULL,
		body TEXT DEFAULT NULL,
		audience VARCHAR(20) DEFAULT 'all',
		is_published TINYINT(1) DEFAULT 1,
		display_target VARCHAR(30) DEFAULT 'all',
		starts_at DATETIME DEFAULT NULL,
		ends_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── Galleries ──────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_galleries (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL,
		description TEXT DEFAULT NULL,
		image_path VARCHAR(500) NOT NULL,
		category VARCHAR(100) NOT NULL,
		is_published TINYINT(1) DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_category (category)
	) {$charset_collate}";

	// ─── Certificates ───────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_certificates (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		template TEXT NOT NULL,
		created_by BIGINT UNSIGNED NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── Admit Cards ────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_admit_cards (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		exam_id BIGINT UNSIGNED NOT NULL,
		student_id BIGINT UNSIGNED NOT NULL,
		admit_card_number VARCHAR(50) NOT NULL,
		issue_date DATE NOT NULL,
		details TEXT DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'issued',
		generated_by BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_admit_card_number (admit_card_number),
		UNIQUE KEY uk_exam_student (exam_id, student_id)
	) {$charset_collate}";

	// ─── Student ID Cards ───────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_student_id_cards (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		student_id BIGINT UNSIGNED NOT NULL,
		id_card_number VARCHAR(50) NOT NULL,
		issue_date DATE NOT NULL,
		expiry_date DATE DEFAULT NULL,
		blood_group VARCHAR(10) DEFAULT NULL,
		photo_url TEXT DEFAULT NULL,
		details TEXT DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'active',
		generated_by BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_id_card_number (id_card_number),
		KEY idx_student_id (student_id)
	) {$charset_collate}";

	// ─── Assignments ────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_assignments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL,
		description TEXT DEFAULT NULL,
		batch_id BIGINT UNSIGNED NOT NULL,
		subject_id BIGINT UNSIGNED NOT NULL,
		due_date DATETIME NOT NULL,
		total_marks INT DEFAULT NULL,
		file_path TEXT DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL,
		is_homework TINYINT(1) DEFAULT 0,
		homework_date DATE DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_batch_id (batch_id),
		KEY idx_subject_id (subject_id)
	) {$charset_collate}";

	// ─── Assignment Submissions ──────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_assignment_submissions (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		assignment_id BIGINT UNSIGNED NOT NULL,
		student_id BIGINT UNSIGNED NOT NULL,
		file_path VARCHAR(500) DEFAULT NULL,
		notes TEXT DEFAULT NULL,
		submitted_at DATETIME DEFAULT NULL,
		marks INT DEFAULT NULL,
		feedback TEXT DEFAULT NULL,
		guardian_notes TEXT DEFAULT NULL,
		graded_by BIGINT UNSIGNED DEFAULT NULL,
		graded_at DATETIME DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'submitted',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_assignment_id (assignment_id),
		KEY idx_student_id (student_id)
	) {$charset_collate}";

	// ─── Routines ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_routines (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		school_class_id BIGINT UNSIGNED NOT NULL,
		subject_id BIGINT UNSIGNED NOT NULL,
		teacher_id BIGINT UNSIGNED NOT NULL,
		day_of_week TINYINT UNSIGNED NOT NULL,
		start_time TIME NOT NULL,
		end_time TIME NOT NULL,
		room_number VARCHAR(50) DEFAULT NULL,
		section_id BIGINT UNSIGNED DEFAULT NULL,
		academic_session_id BIGINT UNSIGNED DEFAULT NULL,
		type VARCHAR(20) DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_class_id (school_class_id),
		KEY idx_day_of_week (day_of_week)
	) {$charset_collate}";

	// ─── Expenses ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_expenses (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		category VARCHAR(64) NOT NULL,
		amount DECIMAL(12,2) NOT NULL,
		date DATE NOT NULL,
		vendor VARCHAR(191) DEFAULT NULL,
		payment_method VARCHAR(32) DEFAULT 'cash',
		note TEXT DEFAULT NULL,
		expense_category_id BIGINT UNSIGNED DEFAULT NULL,
		created_by BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_date (date),
		KEY idx_category (category)
	) {$charset_collate}";

	// ─── Expense Categories ─────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_expense_categories (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		description TEXT DEFAULT NULL,
		color VARCHAR(10) DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 1,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_name (name)
	) {$charset_collate}";

	// ─── Vehicles ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_vehicles (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		number VARCHAR(64) NOT NULL,
		type VARCHAR(64) DEFAULT NULL,
		capacity SMALLINT DEFAULT 0,
		driver_name VARCHAR(191) DEFAULT NULL,
		driver_phone VARCHAR(32) DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_number (number)
	) {$charset_collate}";

	// ─── Transport Routes ───────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_transport_routes (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(191) NOT NULL,
		code VARCHAR(32) NOT NULL,
		fare DECIMAL(10,2) DEFAULT 0,
		vehicle_id BIGINT UNSIGNED DEFAULT NULL,
		is_active TINYINT(1) DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_code (code),
		KEY idx_vehicle_id (vehicle_id)
	) {$charset_collate}";

	// ─── Transport Stops ────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_transport_stops (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		route_id BIGINT UNSIGNED NOT NULL,
		name VARCHAR(191) NOT NULL,
		pickup_time TIME DEFAULT NULL,
		drop_time TIME DEFAULT NULL,
		sort SMALLINT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_route_id (route_id)
	) {$charset_collate}";

	// ─── Transport Assignments ──────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_transport_assignments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		student_id BIGINT UNSIGNED NOT NULL,
		route_id BIGINT UNSIGNED NOT NULL,
		stop_id BIGINT UNSIGNED DEFAULT NULL,
		effective_from DATE NOT NULL,
		effective_to DATE DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_student_id (student_id),
		KEY idx_effective_from (effective_from)
	) {$charset_collate}";

	// ─── Hostels ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_hostels (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		address TEXT DEFAULT NULL,
		description TEXT DEFAULT NULL,
		total_rooms INT DEFAULT 0,
		warden_name VARCHAR(100) DEFAULT NULL,
		warden_phone VARCHAR(20) DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'active',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── Hostel Rooms ───────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_hostel_rooms (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		hostel_id BIGINT UNSIGNED NOT NULL,
		room_number VARCHAR(50) NOT NULL,
		room_type VARCHAR(20) DEFAULT 'double',
		capacity INT DEFAULT 2,
		occupied INT DEFAULT 0,
		status VARCHAR(20) DEFAULT 'available',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_hostel_room (hostel_id, room_number)
	) {$charset_collate}";

	// ─── Hostel Assignments ─────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_hostel_assignments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		student_id BIGINT UNSIGNED NOT NULL,
		room_id BIGINT UNSIGNED NOT NULL,
		check_in_date DATE NOT NULL,
		check_out_date DATE DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'active',
		notes TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_student_id (student_id),
		KEY idx_room_id (room_id)
	) {$charset_collate}";

	// ─── Books ──────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_books (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL,
		author VARCHAR(255) DEFAULT NULL,
		publisher VARCHAR(255) DEFAULT NULL,
		isbn VARCHAR(50) DEFAULT NULL,
		category_id BIGINT UNSIGNED DEFAULT NULL,
		shelf_location VARCHAR(100) DEFAULT NULL,
		quantity INT DEFAULT 1,
		available_quantity INT DEFAULT 1,
		purchase_date DATE DEFAULT NULL,
		price DECIMAL(10,2) DEFAULT NULL,
		description TEXT DEFAULT NULL,
		cover_image VARCHAR(500) DEFAULT NULL,
		status TINYINT(1) DEFAULT 1,
		created_by BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_isbn (isbn),
		KEY idx_category_id (category_id)
	) {$charset_collate}";

	// ─── Book Categories ────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_book_categories (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		description TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── Book Issues ────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_book_issues (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		book_id BIGINT UNSIGNED NOT NULL,
		student_id BIGINT UNSIGNED DEFAULT NULL,
		teacher_id BIGINT UNSIGNED DEFAULT NULL,
		issue_date DATE NOT NULL,
		due_date DATE NOT NULL,
		return_date DATE DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'issued',
		late_fee DECIMAL(10,2) DEFAULT NULL,
		fine_paid TINYINT(1) DEFAULT 0,
		notes TEXT DEFAULT NULL,
		issued_by BIGINT UNSIGNED DEFAULT NULL,
		deleted_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_book_id (book_id),
		KEY idx_student_id (student_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── SMS Campaigns ──────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_sms_campaigns (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(191) NOT NULL,
		audience_type VARCHAR(32) NOT NULL,
		school_class_id BIGINT UNSIGNED DEFAULT NULL,
		section_id BIGINT UNSIGNED DEFAULT NULL,
		message TEXT NOT NULL,
		scheduled_at DATETIME DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'draft',
		sent_at DATETIME DEFAULT NULL,
		created_by BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── SMS Campaign Recipients ────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_sms_campaign_recipients (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		sms_campaign_id BIGINT UNSIGNED NOT NULL,
		phone VARCHAR(32) NOT NULL,
		user_type VARCHAR(32) DEFAULT NULL,
		user_id BIGINT UNSIGNED DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'queued',
		error TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_campaign_id (sms_campaign_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── SMS Logs ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_sms_logs (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		recipient VARCHAR(32) NOT NULL,
		message TEXT NOT NULL,
		provider VARCHAR(32) DEFAULT 'log',
		driver VARCHAR(32) DEFAULT 'log',
		status VARCHAR(20) DEFAULT 'sent',
		message_id VARCHAR(191) DEFAULT NULL,
		error TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_recipient (recipient),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Messages ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_messages (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		sender_id BIGINT UNSIGNED NOT NULL,
		receiver_id BIGINT UNSIGNED NOT NULL,
		subject VARCHAR(255) DEFAULT NULL,
		body TEXT NOT NULL,
		read_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_sender (sender_id, created_at),
		KEY idx_receiver (receiver_id, created_at)
	) {$charset_collate}";

	// ─── Testimonials ───────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_testimonials (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		student_id BIGINT UNSIGNED DEFAULT NULL,
		author_name VARCHAR(255) NOT NULL,
		author_designation VARCHAR(255) DEFAULT NULL,
		content TEXT NOT NULL,
		rating TINYINT DEFAULT 5,
		photo VARCHAR(500) DEFAULT NULL,
		is_visible TINYINT(1) DEFAULT 1,
		sort_order INT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_student_id (student_id),
		KEY idx_is_visible (is_visible)
	) {$charset_collate}";

	// ─── Committee Members ──────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_committee_members (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		name_bn VARCHAR(255) DEFAULT NULL,
		designation VARCHAR(255) NOT NULL,
		designation_bn VARCHAR(255) DEFAULT NULL,
		photo VARCHAR(500) DEFAULT NULL,
		phone VARCHAR(20) DEFAULT NULL,
		email VARCHAR(191) DEFAULT NULL,
		bio TEXT DEFAULT NULL,
		bio_bn TEXT DEFAULT NULL,
		sort_order INT DEFAULT 0,
		is_active TINYINT(1) DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_is_active (is_active)
	) {$charset_collate}";

	// ─── Visitor Logs ───────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_visitor_logs (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		ip VARCHAR(45) NOT NULL,
		url TEXT NOT NULL,
		method VARCHAR(10) NOT NULL,
		user_agent TEXT DEFAULT NULL,
		referer TEXT DEFAULT NULL,
		user_id BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_created_at (created_at),
		KEY idx_ip (ip)
	) {$charset_collate}";

	// ─── Careers ────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_careers (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL,
		description TEXT NOT NULL,
		requirements TEXT NOT NULL,
		type VARCHAR(20) NOT NULL,
		location VARCHAR(255) NOT NULL,
		salary_min DECIMAL(10,2) DEFAULT NULL,
		salary_max DECIMAL(10,2) DEFAULT NULL,
		deadline DATE NOT NULL,
		is_published TINYINT(1) DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── Job Applications ───────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_job_applications (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		career_id BIGINT UNSIGNED NOT NULL,
		name VARCHAR(255) NOT NULL,
		email VARCHAR(191) NOT NULL,
		phone VARCHAR(20) NOT NULL,
		resume_path VARCHAR(500) NOT NULL,
		cover_letter TEXT DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'pending',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_career_id (career_id),
		KEY idx_email (email),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Activities ─────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_activities (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(50) NOT NULL,
		title VARCHAR(255) NOT NULL,
		message TEXT NOT NULL,
		icon VARCHAR(50) DEFAULT NULL,
		color VARCHAR(20) DEFAULT 'primary',
		properties TEXT DEFAULT NULL,
		read_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_id (user_id),
		KEY idx_type (type)
	) {$charset_collate}";

	// ─── Contact Submissions ────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_contact_submissions (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		type VARCHAR(32) DEFAULT 'contact',
		name VARCHAR(255) NOT NULL,
		email VARCHAR(191) NOT NULL,
		phone VARCHAR(50) DEFAULT NULL,
		subject VARCHAR(255) DEFAULT NULL,
		message TEXT NOT NULL,
		meta TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_type (type)
	) {$charset_collate}";

	// ─── Salary Structures ──────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_salary_structures (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		basic_salary DECIMAL(10,2) DEFAULT 0,
		house_allowance DECIMAL(10,2) DEFAULT 0,
		medical_allowance DECIMAL(10,2) DEFAULT 0,
		transport_allowance DECIMAL(10,2) DEFAULT 0,
		other_allowance DECIMAL(10,2) DEFAULT 0,
		tax_deduction DECIMAL(10,2) DEFAULT 0,
		provident_fund DECIMAL(10,2) DEFAULT 0,
		other_deduction DECIMAL(10,2) DEFAULT 0,
		effective_from DATE,
		effective_to DATE,
		notes TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_user_effective (user_id, effective_from),
		KEY idx_user (user_id)
	) {$charset_collate}";

	// ─── Leave Types ────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_leave_types (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(100) NOT NULL,
		days_per_year INT DEFAULT 0,
		is_paid TINYINT(1) DEFAULT 1,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_name (name)
	) {$charset_collate}";

	// ─── Leave Requests ─────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_leave_requests (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		leave_type_id BIGINT UNSIGNED DEFAULT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		days INT DEFAULT 0,
		reason TEXT DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'pending',
		approved_by BIGINT UNSIGNED DEFAULT NULL,
		approved_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_id (user_id),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Staff Attendances ──────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_staff_attendances (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		date DATE NOT NULL,
		check_in TIME DEFAULT NULL,
		check_out TIME DEFAULT NULL,
		status VARCHAR(20) DEFAULT 'present',
		notes TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_date (user_id, date),
		KEY idx_status (status)
	) {$charset_collate}";

	// ─── Notifications ──────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_notifications (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(50) NOT NULL,
		title VARCHAR(255) NOT NULL,
		message TEXT DEFAULT NULL,
		link VARCHAR(500) DEFAULT NULL,
		read_at DATETIME DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_read (user_id, read_at)
	) {$charset_collate}";

	// ─── Ledger Entries ─────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_ledger_entries (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		account VARCHAR(100) NOT NULL,
		type VARCHAR(20) NOT NULL,
		amount DECIMAL(12,2) NOT NULL,
		date DATE NOT NULL,
		description TEXT DEFAULT NULL,
		reference_id BIGINT UNSIGNED DEFAULT NULL,
		reference_type VARCHAR(50) DEFAULT NULL,
		created_by BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_account_date (account, date),
		KEY idx_type (type)
	) {$charset_collate}";

	// ─── Bank Statements ────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_bank_statements (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		bank_account VARCHAR(100) NOT NULL,
		transaction_date DATE NOT NULL,
		description TEXT DEFAULT NULL,
		amount DECIMAL(12,2) NOT NULL,
		type VARCHAR(10) DEFAULT 'credit',
		reconciled TINYINT(1) DEFAULT 0,
		ledger_entry_id BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_bank (bank_account, transaction_date),
		KEY idx_reconciled (reconciled)
	) {$charset_collate}";

	// ─── Payslips ───────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_payslips (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		salary_structure_id BIGINT UNSIGNED DEFAULT NULL,
		period_start DATE NOT NULL,
		period_end DATE NOT NULL,
		basic_salary DECIMAL(10,2) DEFAULT 0,
		total_allowances DECIMAL(10,2) DEFAULT 0,
		total_deductions DECIMAL(10,2) DEFAULT 0,
		net_salary DECIMAL(10,2) DEFAULT 0,
		status VARCHAR(20) DEFAULT 'pending',
		paid_at DATETIME DEFAULT NULL,
		notes TEXT DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_user_period (user_id, period_start, period_end)
	) {$charset_collate}";

	// ─── Website Contents ───────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_website_contents (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		page VARCHAR(100) NOT NULL,
		section VARCHAR(100) NOT NULL,
		title VARCHAR(255) DEFAULT NULL,
		content LONGTEXT DEFAULT NULL,
		image VARCHAR(500) DEFAULT NULL,
		meta TEXT DEFAULT NULL,
		sort_order INT DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY uk_page_section (page, section)
	) {$charset_collate}";

	// ─── Documents ──────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_documents (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL,
		category VARCHAR(100) DEFAULT NULL,
		file_path VARCHAR(500) NOT NULL,
		file_type VARCHAR(50) DEFAULT NULL,
		file_size INT DEFAULT NULL,
		description TEXT DEFAULT NULL,
		uploaded_by BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_category (category)
	) {$charset_collate}";

	// ─── Media ──────────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_media (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) DEFAULT NULL,
		file_path VARCHAR(500) NOT NULL,
		file_type VARCHAR(50) DEFAULT NULL,
		file_size INT DEFAULT NULL,
		alt_text VARCHAR(255) DEFAULT NULL,
		caption TEXT DEFAULT NULL,
		uploaded_by BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate}";

	// ─── Seat Plans ─────────────────────────────────────────────
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}esk_seat_plans (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		exam_id BIGINT UNSIGNED NOT NULL,
		room_number VARCHAR(50) NOT NULL,
		row_number INT DEFAULT 0,
		column_number INT DEFAULT 0,
		student_id BIGINT UNSIGNED DEFAULT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_exam_room (exam_id, room_number)
	) {$charset_collate}";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	foreach ( $sql as $query ) {
		dbDelta( $query );
	}

	update_option( 'esk_db_version', '1.0.0' );
}
