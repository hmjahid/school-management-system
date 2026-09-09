<?php
/**
 * Library management — books and issue/return.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_book_save'] ) ) {
	check_admin_referer( 'esk_book_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_books', array(
		'title'              => sanitize_text_field( $_POST['title'] ?? '' ),
		'author'             => sanitize_text_field( $_POST['author'] ?? '' ),
		'isbn'               => sanitize_text_field( $_POST['isbn'] ?? '' ),
		'quantity'           => absint( $_POST['quantity'] ?? 1 ),
		'available_quantity' => absint( $_POST['quantity'] ?? 1 ),
		'category_id'        => absint( $_POST['category_id'] ?? 0 ) ?: null,
		'description'        => sanitize_textarea_field( $_POST['description'] ?? '' ),
		'created_by'         => get_current_user_id(),
	) );
	esk_flash( 'success', __( 'Book added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

if ( isset( $_POST['esk_book_issue'] ) ) {
	check_admin_referer( 'esk_book_issue_form' );
	$book_id    = absint( $_POST['book_id'] ?? 0 );
	$student_id = absint( $_POST['student_id'] ?? 0 );
	$issue_date = sanitize_text_field( $_POST['issue_date'] ?? gmdate( 'Y-m-d' ) );
	$due_date   = sanitize_text_field( $_POST['due_date'] ?? '' );

	$wpdb->insert( $wpdb->prefix . 'esk_book_issues', array(
		'book_id'     => $book_id,
		'student_id'  => $student_id,
		'issue_date'  => $issue_date,
		'due_date'    => $due_date,
		'status'      => 'issued',
		'issued_by'   => get_current_user_id(),
	) );

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}esk_books SET available_quantity = available_quantity - 1 WHERE id = %d AND available_quantity > 0",
			$book_id
		)
	);

	esk_flash( 'success', __( 'Book issued.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

if ( isset( $_POST['esk_book_return'] ) ) {
	check_admin_referer( 'esk_book_return_' . absint( $_POST['issue_id'] ?? 0 ) );
	$issue_id = absint( $_POST['issue_id'] ?? 0 );
	if ( $issue_id ) {
		$issue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_book_issues WHERE id = %d", $issue_id ) );
		if ( $issue ) {
			$wpdb->update( $wpdb->prefix . 'esk_book_issues', array(
				'return_date' => gmdate( 'Y-m-d' ),
				'status'      => 'returned',
			), array( 'id' => $issue_id ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}esk_books SET available_quantity = available_quantity + 1 WHERE id = %d",
					$issue->book_id
				)
			);
		}
	}
	esk_flash( 'success', __( 'Book returned.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

$books   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_books WHERE deleted_at IS NULL ORDER BY title" );
$issues  = $wpdb->get_results(
	"SELECT i.*, b.title AS book_title, u.display_name AS student_name
	FROM {$wpdb->prefix}esk_book_issues i
	JOIN {$wpdb->prefix}esk_books b ON i.book_id = b.id
	LEFT JOIN {$wpdb->prefix}esk_students st ON i.student_id = st.id
	LEFT JOIN {$wpdb->prefix}users u ON st.user_id = u.ID
	WHERE i.deleted_at IS NULL
	ORDER BY i.issue_date DESC
	LIMIT 100"
);
$students = $wpdb->get_results(
	"SELECT s.id, u.display_name FROM {$wpdb->prefix}esk_students s JOIN {$wpdb->prefix}users u ON s.user_id = u.ID WHERE s.status = 'active' AND s.deleted_at IS NULL ORDER BY u.display_name"
);
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Library', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="esk-dashboard-columns">
		<div class="esk-dashboard-column esk-col-wide">
			<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
				<h2><?php esc_html_e( 'Add Book', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_book_form' ); ?>
					<div class="esk-form-row">
						<div class="esk-form-group"><label><?php esc_html_e( 'Title', 'eskoofy' ); ?> *</label><input type="text" name="title" required></div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Author', 'eskoofy' ); ?></label><input type="text" name="author"></div>
					</div>
					<div class="esk-form-row">
						<div class="esk-form-group"><label><?php esc_html_e( 'ISBN', 'eskoofy' ); ?></label><input type="text" name="isbn"></div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Quantity', 'eskoofy' ); ?></label><input type="number" name="quantity" value="1" min="1" style="width:80px;"></div>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><textarea name="description" rows="2" class="large-text"></textarea></div>
					<button type="submit" name="esk_book_save" class="button button-primary"><?php esc_html_e( 'Add Book', 'eskoofy' ); ?></button>
				</form>
			</div>

			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Title', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Author', 'eskoofy' ); ?></th><th><?php esc_html_e( 'ISBN', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Qty', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Available', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $books ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No books found.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $books as $b ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $b->title ); ?></strong></td>
								<td><?php echo esc_html( $b->author ); ?></td>
								<td><?php echo esc_html( $b->isbn ); ?></td>
								<td><?php echo esc_html( $b->quantity ); ?></td>
								<td><?php echo esc_html( $b->available_quantity ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="esk-dashboard-column esk-col-narrow">
			<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
				<h2><?php esc_html_e( 'Issue Book', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form">
					<?php wp_nonce_field( 'esk_book_issue_form' ); ?>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Book', 'eskoofy' ); ?> *</label>
						<select name="book_id" required>
							<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
							<?php foreach ( $books as $b ) : ?>
								<?php if ( $b->available_quantity > 0 ) : ?>
									<option value="<?php echo esc_attr( $b->id ); ?>"><?php echo esc_html( $b->title ); ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group">
						<label><?php esc_html_e( 'Student', 'eskoofy' ); ?> *</label>
						<select name="student_id" required>
							<option value=""><?php esc_html_e( 'Select', 'eskoofy' ); ?></option>
							<?php foreach ( $students as $st ) : ?>
								<option value="<?php echo esc_attr( $st->id ); ?>"><?php echo esc_html( $st->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Issue Date', 'eskoofy' ); ?></label><input type="date" name="issue_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></div>
					<div class="esk-form-group"><label><?php esc_html_e( 'Due Date', 'eskoofy' ); ?> *</label><input type="date" name="due_date" required></div>
					<button type="submit" name="esk_book_issue" class="button button-primary"><?php esc_html_e( 'Issue Book', 'eskoofy' ); ?></button>
				</form>
			</div>

			<table class="wp-list-table widefat striped esk-table">
				<thead><tr><th><?php esc_html_e( 'Book', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Due', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th><th><?php esc_html_e( '', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $issues ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No issues.', 'eskoofy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $issues as $i ) : ?>
							<tr>
								<td><?php echo esc_html( $i->book_title ); ?></td>
								<td><?php echo esc_html( $i->student_name ); ?></td>
								<td><?php echo esc_html( esk_date_format( $i->due_date ) ); ?></td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $i->status ); ?>"><?php echo esc_html( ucfirst( $i->status ) ); ?></span></td>
								<td>
									<?php if ( 'issued' === $i->status ) : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'esk_book_return_' . $i->id ); ?>
											<input type="hidden" name="issue_id" value="<?php echo esc_attr( $i->id ); ?>">
											<button type="submit" name="esk_book_return" class="button button-small"><?php esc_html_e( 'Return', 'eskoofy' ); ?></button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
