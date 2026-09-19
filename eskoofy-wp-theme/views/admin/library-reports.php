<?php
/**
 * Library reports — currently issued, overdue, history.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$tab = sanitize_text_field( $_GET['tab'] ?? 'current' );

if ( 'current' === $tab ) {
	$rows = $wpdb->get_results(
		"SELECT i.*, b.title AS book_title, u.display_name AS student_name
		FROM {$wpdb->prefix}esk_book_issues i
		JOIN {$wpdb->prefix}esk_books b ON i.book_id = b.id
		LEFT JOIN {$wpdb->prefix}esk_students s ON i.student_id = s.id
		LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		WHERE i.return_date IS NULL ORDER BY i.due_date ASC"
	);
} elseif ( 'overdue' === $tab ) {
	$rows = $wpdb->get_results(
		"SELECT i.*, b.title AS book_title, u.display_name AS student_name
		FROM {$wpdb->prefix}esk_book_issues i
		JOIN {$wpdb->prefix}esk_books b ON i.book_id = b.id
		LEFT JOIN {$wpdb->prefix}esk_students s ON i.student_id = s.id
		LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		WHERE i.return_date IS NULL AND i.due_date < CURDATE() ORDER BY i.due_date ASC"
	);
} else {
	$rows = $wpdb->get_results(
		"SELECT i.*, b.title AS book_title, u.display_name AS student_name
		FROM {$wpdb->prefix}esk_book_issues i
		JOIN {$wpdb->prefix}esk_books b ON i.book_id = b.id
		LEFT JOIN {$wpdb->prefix}esk_students s ON i.student_id = s.id
		LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
		WHERE i.return_date IS NOT NULL ORDER BY i.return_date DESC LIMIT 200"
	);
}
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Library Reports', 'eskoofy' ); ?></h1>

	<nav class="nav-tab-wrapper esk-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-library-reports&tab=current' ) ); ?>" class="nav-tab <?php echo 'current' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Currently Issued', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-library-reports&tab=overdue' ) ); ?>" class="nav-tab <?php echo 'overdue' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Overdue', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-library-reports&tab=history' ) ); ?>" class="nav-tab <?php echo 'history' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'History', 'eskoofy' ); ?></a>
	</nav>

	<table class="wp-list-table widefat striped esk-table">
		<thead><tr>
			<th><?php esc_html_e( 'Book', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Borrower', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Issued', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Due', 'eskoofy' ); ?></th>
			<th><?php esc_html_e( 'Returned', 'eskoofy' ); ?></th>
		</tr></thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No records.', 'eskoofy' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->book_title ); ?></td>
						<td><?php echo esc_html( $r->student_name ?? '—' ); ?></td>
						<td><?php echo esc_html( esk_date_format( $r->issue_date ) ); ?></td>
						<td><?php echo esc_html( esk_date_format( $r->due_date ) ); ?></td>
						<td><?php echo $r->return_date ? esc_html( esk_date_format( $r->return_date ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>