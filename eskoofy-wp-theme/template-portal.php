<?php
/**
 * Template Name: Student / Parent Portal
 *
 * Tabbed portal for logged-in students and parents. Mirrors the app's
 * `/portal` page: Profile, Attendance, Exams, Fees, Routine, Announcements,
 * Events. Access is gated to the user's own student / linked children.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

global $wpdb;

if ( ! is_user_logged_in() ) {
	get_header();
	get_template_part(
		'template-parts/inner-hero',
		null,
		array(
			'title'    => (string) esk_site_ui( 'pages.portal_heading', __( 'Parent / Student portal', 'eskoofy' ) ),
			'subtitle' => (string) esk_site_ui( 'pages.portal_intro', '' ),
		)
	);
	?>
	<div class="esk-page-sections">
		<div class="esk-container">
			<div class="esk-panel-grid">
				<div class="esk-panel-card esk-card">
					<span class="esk-panel-icon" aria-hidden="true">&#128274;</span>
					<h2 class="esk-card-title"><?php echo esc_html( esk_site_ui( 'pages.login', __( 'Login', 'eskoofy' ) ) ); ?></h2>
					<p class="esk-card-text"><?php echo esc_html( esk_site_ui( 'pages.portal_intro', '' ) ); ?></p>
					<a class="esk-btn" href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php echo esc_html( esk_site_ui( 'pages.login', '' ) ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php
	get_footer();
	exit;
}

$user = wp_get_current_user();

// Student: their own record. Parent: children via esk_students.guardian_id.
$students = array();
$student  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_students WHERE user_id = %d AND deleted_at IS NULL", $user->ID ) );
if ( $student ) {
	$students[] = $student;
} else {
	$guardian = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}esk_guardians WHERE user_id = %d", $user->ID ) );
	if ( $guardian ) {
		$students = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_students WHERE guardian_id = %d AND deleted_at IS NULL ORDER BY id", $guardian->id ) );
	}
}

get_header();
get_template_part(
	'template-parts/inner-hero',
	null,
	array(
		'title'    => (string) esk_site_ui( 'pages.portal_heading', __( 'Student Portal', 'eskoofy' ) ),
		'subtitle' => (string) esk_site_ui( 'pages.portal_intro', '' ),
	)
);
?>
<div class="esk-page-sections">
	<div class="esk-container">
		<?php if ( empty( $students ) ) : ?>
			<div class="esk-notice esk-notice-info">
				<p><?php esc_html_e( 'No student record is linked to your account. Please contact the school office.', 'eskoofy' ); ?></p>
			</div>
		<?php else : ?>
			<div class="esk-portal" data-esk-portal>
				<?php if ( count( $students ) > 1 ) : ?>
					<div class="esk-portal-student-switch">
						<?php foreach ( $students as $idx => $s ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'student', $s->id, get_permalink() ) ); ?>" class="esk-btn <?php echo 0 === $idx && empty( $_GET['student'] ) ? 'esk-button-primary' : ''; ?>">
								<?php echo esc_html( $s->first_name . ' ' . $s->last_name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				$requested = absint( $_GET['student'] ?? 0 );
				$active    = null;
				foreach ( $students as $s ) {
					if ( $requested && (int) $s->id === $requested ) {
						$active = $s;
						break;
					}
				}
				if ( ! $active ) {
					$active = $students[0];
				}
				$sid = (int) $active->id;

				$class_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}esk_classes WHERE id = %d", $active->class_id ) );
				$section_name = $active->section_id ? $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}esk_sections WHERE id = %d", $active->section_id ) ) : '';

				$attendance = $wpdb->get_results( $wpdb->prepare(
					"SELECT a.date, a.status FROM {$wpdb->prefix}esk_attendances a WHERE a.student_id = %d ORDER BY a.date DESC LIMIT 30",
					$sid
				) );
				$present_count = 0;
				foreach ( $attendance as $a ) {
					if ( 'present' === $a->status ) {
						++$present_count;
					}
				}

				$results = $wpdb->get_results( $wpdb->prepare(
					"SELECT er.*, e.name AS exam_name, e.total_marks, s.name AS subject_name
					FROM {$wpdb->prefix}esk_exam_results er
					JOIN {$wpdb->prefix}esk_exams e ON er.exam_id = e.id
					LEFT JOIN {$wpdb->prefix}esk_subjects s ON er.subject_id = s.id
					WHERE er.student_id = %d AND e.is_published = 1
					ORDER BY e.end_date DESC, e.id DESC",
					$sid
				) );

				$fees = $wpdb->get_results( $wpdb->prepare(
					"SELECT fp.*, f.name AS fee_name FROM {$wpdb->prefix}esk_fee_payments fp
					JOIN {$wpdb->prefix}esk_fees f ON fp.fee_id = f.id
					WHERE fp.student_id = %d AND fp.deleted_at IS NULL ORDER BY fp.payment_date DESC LIMIT 20",
					$sid
				) );

				$routine = $wpdb->get_results( $wpdb->prepare(
					"SELECT r.*, s.name AS subject_name FROM {$wpdb->prefix}esk_routines r
					JOIN {$wpdb->prefix}esk_subjects s ON r.subject_id = s.id
					WHERE r.school_class_id = %d ORDER BY r.day_of_week, r.start_time",
					$active->class_id
				) );

				$announcements = $wpdb->get_results(
					"SELECT * FROM {$wpdb->prefix}esk_announcements WHERE is_published = 1 ORDER BY id DESC LIMIT 10"
				);

				$events = $wpdb->get_results(
					"SELECT * FROM {$wpdb->prefix}esk_events WHERE status = 'published' AND start_date >= CURDATE() ORDER BY start_date ASC LIMIT 10"
				);

				$days = array( 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun' );
				?>

				<div class="esk-portal-header">
					<h2><?php echo esc_html( $active->first_name . ' ' . $active->last_name ); ?></h2>
					<p class="esk-portal-subtitle">
						<?php echo esc_html( implode( ' ', array_filter( array( $class_name, $section_name ) ) ) ); ?>
						&middot; <?php echo esc_html__( 'Roll', 'eskoofy' ); ?> <?php echo esc_html( $active->roll_number ); ?>
					</p>
				</div>

				<div class="esk-portal-stats">
					<div class="esk-portal-stat"><span class="esk-portal-stat-num"><?php echo esc_html( $present_count ); ?>/<?php echo esc_html( count( $attendance ) ); ?></span><span class="esk-portal-stat-label"><?php esc_html_e( 'Attendance (30 days)', 'eskoofy' ); ?></span></div>
					<div class="esk-portal-stat"><span class="esk-portal-stat-num"><?php echo esc_html( count( $results ) ); ?></span><span class="esk-portal-stat-label"><?php esc_html_e( 'Results', 'eskoofy' ); ?></span></div>
					<div class="esk-portal-stat"><span class="esk-portal-stat-num"><?php echo esc_html( count( $fees ) ); ?></span><span class="esk-portal-stat-label"><?php esc_html_e( 'Fee Records', 'eskoofy' ); ?></span></div>
				</div>

				<nav class="esk-portal-tabs" data-esk-portal-tabs>
					<button type="button" data-esk-portal-tab="profile" class="is-active"><?php esc_html_e( 'Profile', 'eskoofy' ); ?></button>
					<button type="button" data-esk-portal-tab="attendance"><?php esc_html_e( 'Attendance', 'eskoofy' ); ?></button>
					<button type="button" data-esk-portal-tab="exams"><?php esc_html_e( 'Exams', 'eskoofy' ); ?></button>
					<button type="button" data-esk-portal-tab="fees"><?php esc_html_e( 'Fees', 'eskoofy' ); ?></button>
					<button type="button" data-esk-portal-tab="routine"><?php esc_html_e( 'Routine', 'eskoofy' ); ?></button>
					<button type="button" data-esk-portal-tab="announcements"><?php esc_html_e( 'Announcements', 'eskoofy' ); ?></button>
					<button type="button" data-esk-portal-tab="events"><?php esc_html_e( 'Events', 'eskoofy' ); ?></button>
				</nav>

				<div class="esk-portal-panels">
					<div data-esk-portal-panel="profile" class="esk-portal-panel is-active">
						<table class="esk-table">
							<tr><td><strong><?php esc_html_e( 'Admission No', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $active->admission_number ); ?></td></tr>
							<tr><td><strong><?php esc_html_e( 'Class', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $class_name ); ?></td></tr>
							<tr><td><strong><?php esc_html_e( 'Roll Number', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $active->roll_number ); ?></td></tr>
							<tr><td><strong><?php esc_html_e( 'Phone', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $active->phone_1 ); ?></td></tr>
							<tr><td><strong><?php esc_html_e( 'Email', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $active->email ); ?></td></tr>
							<tr><td><strong><?php esc_html_e( 'Blood Group', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $active->blood_group ); ?></td></tr>
							<tr><td><strong><?php esc_html_e( 'Religion', 'eskoofy' ); ?></strong></td><td><?php echo esc_html( $active->religion ); ?></td></tr>
						</table>
					</div>

					<div data-esk-portal-panel="attendance" class="esk-portal-panel" hidden>
						<?php if ( empty( $attendance ) ) : ?>
							<p><?php esc_html_e( 'No attendance records yet.', 'eskoofy' ); ?></p>
						<?php else : ?>
							<table class="esk-table esk-table-striped">
								<thead><tr><th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $attendance as $a ) : ?>
										<tr>
											<td><?php echo esc_html( esk_date_format( $a->date ) ); ?></td>
											<td><span class="esk-badge esk-badge-<?php echo esc_attr( $a->status ); ?>"><?php echo esc_html( ucfirst( $a->status ) ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div data-esk-portal-panel="exams" class="esk-portal-panel" hidden>
						<?php if ( empty( $results ) ) : ?>
							<p><?php esc_html_e( 'No published results yet.', 'eskoofy' ); ?></p>
						<?php else : ?>
							<table class="esk-table esk-table-striped">
								<thead><tr><th><?php esc_html_e( 'Exam', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Marks', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Grade', 'eskoofy' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $results as $r ) : ?>
										<tr>
											<td><?php echo esc_html( $r->exam_name ); ?></td>
											<td><?php echo esc_html( $r->subject_name ); ?></td>
											<td><?php echo esc_html( $r->obtained_marks . '/' . $r->total_marks ); ?></td>
											<td><?php echo esc_html( $r->grade ?: '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div data-esk-portal-panel="fees" class="esk-portal-panel" hidden>
						<?php if ( empty( $fees ) ) : ?>
							<p><?php esc_html_e( 'No fee records yet.', 'eskoofy' ); ?></p>
						<?php else : ?>
							<table class="esk-table esk-table-striped">
								<thead><tr><th><?php esc_html_e( 'Fee', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Amount', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Paid', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $fees as $f ) : ?>
										<tr>
											<td><?php echo esc_html( $f->fee_name ); ?></td>
											<td><?php echo esc_html( esk_format_currency( $f->amount ) ); ?></td>
											<td><?php echo esc_html( esk_format_currency( $f->paid_amount ) ); ?></td>
											<td><?php echo esc_html( esk_date_format( $f->payment_date ) ); ?></td>
											<td><span class="esk-badge esk-badge-<?php echo esc_attr( $f->status ); ?>"><?php echo esc_html( ucfirst( $f->status ) ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div data-esk-portal-panel="routine" class="esk-portal-panel" hidden>
						<?php if ( empty( $routine ) ) : ?>
							<p><?php esc_html_e( 'No routine published yet.', 'eskoofy' ); ?></p>
						<?php else : ?>
							<table class="esk-table esk-table-striped">
								<thead><tr><th><?php esc_html_e( 'Day', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Time', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Room', 'eskoofy' ); ?></th></tr></thead>
								<tbody>
									<?php foreach ( $routine as $r ) : ?>
										<tr>
											<td><?php echo esc_html( $days[ (int) $r->day_of_week ] ?? $r->day_of_week ); ?></td>
											<td><?php echo esc_html( $r->subject_name ); ?></td>
											<td><?php echo esc_html( $r->start_time . ' – ' . $r->end_time ); ?></td>
											<td><?php echo esc_html( $r->room_number ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div data-esk-portal-panel="announcements" class="esk-portal-panel" hidden>
						<?php if ( empty( $announcements ) ) : ?>
							<p><?php esc_html_e( 'No announcements.', 'eskoofy' ); ?></p>
						<?php else : ?>
							<ul class="esk-portal-list">
								<?php foreach ( $announcements as $an ) : ?>
									<li class="esk-portal-list-item">
										<strong><?php echo esc_html( $an->title ); ?></strong>
										<?php if ( $an->body ) : ?><p><?php echo wp_kses_post( $an->body ); ?></p><?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

					<div data-esk-portal-panel="events" class="esk-portal-panel" hidden>
						<?php if ( empty( $events ) ) : ?>
							<p><?php esc_html_e( 'No upcoming events.', 'eskoofy' ); ?></p>
						<?php else : ?>
							<ul class="esk-portal-list">
								<?php foreach ( $events as $ev ) : ?>
									<li class="esk-portal-list-item">
										<strong><?php echo esc_html( $ev->title ); ?></strong>
										<p><?php echo esc_html( esk_date_format( $ev->start_date, 'M j, Y g:i A' ) ); ?><?php echo $ev->location ? ' &middot; ' . esc_html( $ev->location ) : ''; ?></p>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();