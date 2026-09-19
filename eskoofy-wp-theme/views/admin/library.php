<?php
/**
 * Library management — books and issue/return.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

if ( isset( $_POST['esk_library_category_save'] ) ) {
	check_admin_referer( 'esk_library_category_form' );
	$wpdb->insert( $wpdb->prefix . 'esk_book_categories', array(
		'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
		'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
	) );
	esk_flash( 'success', __( 'Book category added.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

if ( isset( $_POST['esk_library_category_delete'] ) ) {
	check_admin_referer( 'esk_library_category_delete_' . absint( $_POST['cat_id'] ?? 0 ) );
	$wpdb->delete( $wpdb->prefix . 'esk_book_categories', array( 'id' => absint( $_POST['cat_id'] ?? 0 ) ) );
	esk_flash( 'success', __( 'Book category deleted.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

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
			$update   = array(
				'return_date' => gmdate( 'Y-m-d' ),
				'status'      => 'returned',
			);
			$due      = strtotime( (string) $issue->due_date );
			$today    = strtotime( gmdate( 'Y-m-d' ) );
			if ( $due && $today > $due ) {
				$days_late      = (int) floor( ( $today - $due ) / DAY_IN_SECONDS );
				$fine_per_day   = (float) get_option( 'esk_fine_per_day', 5 );
				$update['late_fee'] = round( max( 0, $days_late * $fine_per_day ), 2 );
			}
			$wpdb->update( $wpdb->prefix . 'esk_book_issues', $update, array( 'id' => $issue_id ) );
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

if ( isset( $_POST['esk_book_mark_lost'] ) ) {
	check_admin_referer( 'esk_book_mark_lost_' . absint( $_POST['issue_id'] ?? 0 ) );
	$issue_id = absint( $_POST['issue_id'] ?? 0 );
	if ( $issue_id ) {
		$issue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}esk_book_issues WHERE id = %d", $issue_id ) );
		if ( $issue ) {
			$wpdb->update( $wpdb->prefix . 'esk_book_issues', array(
				'status'   => 'lost',
				'late_fee' => isset( $_POST['lost_fee'] ) ? round( max( 0, (float) $_POST['lost_fee'] ), 2 ) : $issue->late_fee,
				'notes'    => sanitize_text_field( $_POST['notes'] ?? '' ),
			), array( 'id' => $issue_id ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}esk_books SET available_quantity = available_quantity - 1 WHERE id = %d AND available_quantity > 0",
					$issue->book_id
				)
			);
		}
	}
	esk_flash( 'success', __( 'Book marked as lost.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

if ( isset( $_POST['esk_fine_paid'] ) ) {
	check_admin_referer( 'esk_fine_paid_' . absint( $_POST['issue_id'] ?? 0 ) );
	$issue_id = absint( $_POST['issue_id'] ?? 0 );
	if ( $issue_id ) {
		$wpdb->update( $wpdb->prefix . 'esk_book_issues', array( 'fine_paid' => 1 ), array( 'id' => $issue_id ) );
	}
	esk_flash( 'success', __( 'Fine marked as paid.', 'eskoofy' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=esk-library' ) );
	exit;
}

$books   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_books WHERE deleted_at IS NULL ORDER BY title" );
$categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}esk_book_categories ORDER BY name" );
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

			<div class="esk-card esk-form-card" style="margin-top:1.5rem;">
				<h2><?php esc_html_e( 'Book Categories', 'eskoofy' ); ?></h2>
				<form method="post" class="esk-form esk-form-horizontal" style="margin-bottom:1rem;">
					<?php wp_nonce_field( 'esk_library_category_form' ); ?>
					<div class="esk-form-row">
						<div class="esk-form-group"><label><?php esc_html_e( 'Name', 'eskoofy' ); ?> *</label><input type="text" name="name" required></div>
						<div class="esk-form-group"><label><?php esc_html_e( 'Description', 'eskoofy' ); ?></label><input type="text" name="description" class="regular-text"></div>
						<button type="submit" name="esk_library_category_save" class="button button-primary"><?php esc_html_e( 'Add Category', 'eskoofy' ); ?></button>
					</div>
				</form>
				<table class="wp-list-table widefat striped esk-table">
					<thead><tr><th><?php esc_html_e( 'Name', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Description', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
					<tbody>
						<?php if ( empty( $categories ) ) : ?>
							<tr><td colspan="3"><?php esc_html_e( 'No categories yet.', 'eskoofy' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $categories as $cat ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $cat->name ); ?></strong></td>
									<td><?php echo esc_html( $cat->description ); ?></td>
									<td>
										<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this category?', 'eskoofy' ); ?>');">
											<?php wp_nonce_field( 'esk_library_category_delete_' . $cat->id ); ?>
											<input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat->id ); ?>">
											<button type="submit" name="esk_library_category_delete" class="button button-small"><?php esc_html_e( 'Delete', 'eskoofy' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
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
				<thead><tr><th><?php esc_html_e( 'Book', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Student', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Due', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Fine', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Actions', 'eskoofy' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $issues ) ) : ?>
						<tr><td colspan="6">
							<div class="esk-empty-state">
								<div class="esk-empty-state-icon"><span class="dashicons dashicons-book-alt"></span></div>
								<p class="esk-empty-state-title"><?php esc_html_e( 'No book issues', 'eskoofy' ); ?></p>
								<p class="esk-empty-state-message"><?php esc_html_e( 'Issue a book to a student to start tracking.', 'eskoofy' ); ?></p>
							</div>
						</td></tr>
					<?php else : ?>
						<?php foreach ( $issues as $i ) : ?>
							<tr>
								<td><?php echo esc_html( $i->book_title ); ?></td>
								<td><?php echo esc_html( $i->student_name ); ?></td>
								<td><?php echo esc_html( esk_date_format( $i->due_date ) ); ?></td>
								<td>
									<?php if ( null !== $i->late_fee && (float) $i->late_fee > 0 ) : ?>
										<?php echo esc_html( esk_format_currency( $i->late_fee ) ); ?>
										<?php if ( ! $i->fine_paid ) : ?>
											<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Mark this fine as paid?', 'eskoofy' ); ?>', 'brand')">
												<?php wp_nonce_field( 'esk_fine_paid_' . $i->id ); ?>
												<input type="hidden" name="issue_id" value="<?php echo esc_attr( $i->id ); ?>">
												<button type="submit" name="esk_fine_paid" class="button button-small"><?php esc_html_e( 'Mark Paid', 'eskoofy' ); ?></button>
											</form>
										<?php else : ?>
											<span class="esk-text-success"><?php esc_html_e( 'Paid', 'eskoofy' ); ?></span>
										<?php endif; ?>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
								<td><span class="esk-badge esk-badge-<?php echo esc_attr( $i->status ); ?>"><?php echo esc_html( ucfirst( $i->status ) ); ?></span></td>
								<td>
									<?php if ( 'issued' === $i->status ) : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'esk_book_return_' . $i->id ); ?>
											<input type="hidden" name="issue_id" value="<?php echo esc_attr( $i->id ); ?>">
											<button type="submit" name="esk_book_return" class="button button-small"><?php esc_html_e( 'Return', 'eskoofy' ); ?></button>
										</form>
										<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Mark this book as lost? A replacement fee can be recorded.', 'eskoofy' ); ?>')">
											<?php wp_nonce_field( 'esk_book_mark_lost_' . $i->id ); ?>
											<input type="hidden" name="issue_id" value="<?php echo esc_attr( $i->id ); ?>">
											<input type="number" name="lost_fee" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Lost fee', 'eskoofy' ); ?>" style="width:80px;">
											<button type="submit" name="esk_book_mark_lost" class="button button-small"><?php esc_html_e( 'Mark Lost', 'eskoofy' ); ?></button>
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
