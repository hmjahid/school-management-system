<?php
/**
 * Admit Cards generation.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_admit_card_generate'] ) ) {
	check_admin_referer( 'esk_admit_card_form' );
	$exam_id   = absint( $_POST['exam_id'] ?? 0 );
	$class_id  = absint( $_POST['class_id'] ?? 0 );
	$generated = 0;

	if ( $exam_id ) {
		$where  = 'WHERE s.status = \'active\' AND s.deleted_at IS NULL';
		$params = array();
		if ( $class_id ) {
			$where  .= ' AND s.class_id = %d';
			$params[] = $class_id;
		}

		$students = $wpdb->get_results(
			$params
				? $wpdb->prepare( "SELECT s.id, s.admission_number FROM {$wpdb->prefix}esk_students s {$where}", ...$params )
				: "SELECT s.id, s.admission_number FROM {$wpdb->prefix}esk_students s {$where}"
		);

		foreach ( $students as $s ) {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}esk_admit_cards WHERE exam_id = %d AND student_id = %d AND deleted_at IS NULL",
					$exam_id,
					$s->id
				)
			);
			if ( ! $existing ) {
				$wpdb->insert( $wpdb->prefix . 'esk_admit_cards', array(
					'exam_id'            => $exam_id,
					'student_id'         => $s->id,
					'admit_card_number'  => 'AC-' . strtoupper( wp_generate_password( 8, false ) ),
					'issue_date'         => gmdate( 'Y-m-d' ),
					'status'             => 'issued',
					'generated_by'       => get_current_user_id(),
				) );
				++$generated;
			}
		}
	}

	esk_flash( 'success', sprintf( __( '%d admit cards generated.', 'eskoofy' ), $generated ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-admit-cards' ) );
	exit;
}

$exams   = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_exams WHERE deleted_at IS NULL ORDER BY start_date DESC" );
$classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$cards   = $wpdb->get_results(
	"SELECT ac.*, e.name AS exam_name, u.display_name AS student_name
	FROM {$wpdb->prefix}esk_admit_cards ac
	JOIN {$wpdb->prefix}esk_exams e ON ac.exam_id = e.id
	JOIN {$wpdb->prefix}esk_students s ON ac.student_id = s.id
	JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
	WHERE ac.deleted_at IS NULL
	ORDER BY ac.id DESC
	LIMIT 50"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Admit Cards', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Generate Admit Cards', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-inline-form">
			<?php wp_nonce_field( 'esk_admit_card_form' ); ?>
			<label><?php esc_html_e( 'Exam', 'eskoofy' ); ?>:</label>
			<select name="exam_id" required>
				<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
				<?php foreach ( $exams as $e ) : ?>
					<option value="<?php echo esc_attr( $e->id ); ?>"><?php echo esc_html( $e->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><?php esc_html_e( 'Class (optional)', 'eskoofy' ); ?>:</label>
			<select name="class_id">
				<option value=""><?php esc_html_e( 'All Classes', 'eskoofy' ); ?></option>
				<?php foreach ( $classes as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" name="esk_admit_card_generate" class="button button-primary"><?php esc_html_e( 'Generate', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Card #', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Exam', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Issue Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $cards ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No admit cards generated.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $cards as $card ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $card->admit_card_number ); ?></strong></td>
						<td><?php echo esc_html( $card->student_name ); ?></td>
						<td><?php echo esc_html( $card->exam_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $card->issue_date ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $card->status ); ?>"><?php echo esc_html( ucfirst( $card->status ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
