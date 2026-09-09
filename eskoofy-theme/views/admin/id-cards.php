<?php
/**
 * Student ID Cards generation.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_idcard_generate'] ) ) {
	check_admin_referer( 'esk_idcard_form' );
	$class_id  = absint( $_POST['class_id'] ?? 0 );
	$generated = 0;

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
				"SELECT id FROM {$wpdb->prefix}esk_student_id_cards WHERE student_id = %d AND deleted_at IS NULL",
				$s->id
			)
		);
		if ( ! $existing ) {
			$wpdb->insert( $wpdb->prefix . 'esk_student_id_cards', array(
				'student_id'     => $s->id,
				'id_card_number' => 'ID-' . strtoupper( wp_generate_password( 8, false ) ),
				'issue_date'     => gmdate( 'Y-m-d' ),
				'expiry_date'    => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
				'status'         => 'active',
				'generated_by'   => get_current_user_id(),
			) );
			++$generated;
		}
	}

	esk_flash( 'success', sprintf( __( '%d ID cards generated.', 'eskoofy' ), $generated ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-id-cards' ) );
	exit;
}

$classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}esk_classes ORDER BY name" );
$cards   = $wpdb->get_results(
	"SELECT ic.*, u.display_name AS student_name, c.name AS class_name
	FROM {$wpdb->prefix}esk_student_id_cards ic
	JOIN {$wpdb->prefix}esk_students s ON ic.student_id = s.id
	JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
	LEFT JOIN {$wpdb->prefix}esk_classes c ON s.class_id = c.id
	WHERE ic.deleted_at IS NULL
	ORDER BY ic.id DESC
	LIMIT 50"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'ID Cards', 'eskoofy' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php esc_html_e( 'Generate ID Cards', 'eskoofy' ); ?></h2>
		<form method="post" class="esk-form esk-inline-form">
			<?php wp_nonce_field( 'esk_idcard_form' ); ?>
			<label><?php esc_html_e( 'Class (optional)', 'eskoofy' ); ?>:</label>
			<select name="class_id">
				<option value=""><?php esc_html_e( 'All Classes', 'eskoofy' ); ?></option>
				<?php foreach ( $classes as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" name="esk_idcard_generate" class="button button-primary"><?php esc_html_e( 'Generate', 'eskoofy' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Card #', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Class', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Issue Date', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Expiry', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $cards ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No ID cards generated.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $cards as $card ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $card->id_card_number ); ?></strong></td>
						<td><?php echo esc_html( $card->student_name ); ?></td>
						<td><?php echo esc_html( $card->class_name ); ?></td>
						<td><?php echo esc_html( esk_date_format( $card->issue_date ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $card->expiry_date ) ); ?></td>
						<td><span class="esk-badge esk-badge-<?php echo esc_attr( $card->status ); ?>"><?php echo esc_html( ucfirst( $card->status ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
