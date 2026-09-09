<?php
/**
 * Custom reports builder.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$report    = sanitize_text_field( $_GET['report'] ?? '' );
$date_from = sanitize_text_field( $_GET['date_from'] ?? gmdate( 'Y-m-01' ) );
$date_to   = sanitize_text_field( $_GET['date_to'] ?? gmdate( 'Y-m-d' ) );
$rows      = array();

if ( $report ) {
	switch ( $report ) {
		case 'students_by_class':
			$rows = $wpdb->get_results(
				"SELECT c.name AS class_name, COUNT(s.id) AS total
				FROM {$wpdb->prefix}esk_classes c
				LEFT JOIN {$wpdb->prefix}esk_students s ON s.class_id = c.id AND s.deleted_at IS NULL
				GROUP BY c.id, c.name
				ORDER BY c.name"
			);
			break;

		case 'payments_collected':
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(payment_date) AS d, SUM(paid_amount) AS total
					FROM {$wpdb->prefix}esk_fee_payments
					WHERE deleted_at IS NULL AND payment_date BETWEEN %s AND %s
					GROUP BY DATE(payment_date) ORDER BY d",
					$date_from, $date_to
				)
			);
			break;

		case 'attendance_summary':
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT status, COUNT(*) AS total
					FROM {$wpdb->prefix}esk_attendances
					WHERE date BETWEEN %s AND %s
					GROUP BY status",
					$date_from, $date_to
				)
			);
			break;

		case 'expense_summary':
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT category, SUM(amount) AS total
					FROM {$wpdb->prefix}esk_expenses
					WHERE date BETWEEN %s AND %s
					GROUP BY category ORDER BY total DESC",
					$date_from, $date_to
				)
			);
			break;
	}
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Reports Builder', 'eskoofy' ); ?></h1>

	<form method="get" class="esk-form esk-inline-form" style="margin-bottom:1rem;">
		<input type="hidden" name="page" value="esk-reports-builder">
		<label><?php esc_html_e( 'Report', 'eskoofy' ); ?>:</label>
		<select name="report">
			<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
			<option value="students_by_class" <?php selected( $report, 'students_by_class' ); ?>><?php esc_html_e( 'Students by Class', 'eskoofy' ); ?></option>
			<option value="payments_collected" <?php selected( $report, 'payments_collected' ); ?>><?php esc_html_e( 'Payments Collected', 'eskoofy' ); ?></option>
			<option value="attendance_summary" <?php selected( $report, 'attendance_summary' ); ?>><?php esc_html_e( 'Attendance Summary', 'eskoofy' ); ?></option>
			<option value="expense_summary" <?php selected( $report, 'expense_summary' ); ?>><?php esc_html_e( 'Expense Summary', 'eskoofy' ); ?></option>
		</select>
		<label><?php esc_html_e( 'From', 'eskoofy' ); ?>:</label>
		<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
		<label><?php esc_html_e( 'To', 'eskoofy' ); ?>:</label>
		<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
		<button type="submit" class="button"><?php esc_html_e( 'Run', 'eskoofy' ); ?></button>
	</form>

	<?php if ( $report && ! empty( $rows ) ) : ?>
		<table class="wp-list-table widefat striped esk-table">
			<thead>
				<tr>
					<?php foreach ( array_keys( (array) $rows[0] ) as $col ) : ?>
						<th><?php echo esc_html( ucfirst( str_replace( '_', ' ', $col ) ) ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<?php foreach ( (array) $r as $k => $v ) : ?>
							<td>
								<?php
								if ( in_array( $k, array( 'total' ), true ) ) {
									echo esc_html( is_numeric( $v ) ? number_format_i18n( (float) $v, 2 ) : $v );
								} else {
									echo esc_html( $v );
								}
								?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php elseif ( $report ) : ?>
		<p><?php esc_html_e( 'No data for this report.', 'eskoofy' ); ?></p>
	<?php endif; ?>
</div>