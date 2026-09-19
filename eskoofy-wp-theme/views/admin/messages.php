<?php
/**
 * Internal messages.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$tab = sanitize_text_field( $_GET['tab'] ?? 'inbox' );
$me  = get_current_user_id();

if ( isset( $_POST['esk_message_send'] ) ) {
	check_admin_referer( 'esk_message_form' );
	$receiver = absint( $_POST['receiver_id'] ?? 0 );
	$subject  = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
	$body     = sanitize_textarea_field( wp_unslash( $_POST['body'] ?? '' ) );
	if ( $receiver && $body ) {
		$wpdb->insert( $wpdb->prefix . 'esk_messages', array(
			'sender_id'   => $me,
			'receiver_id'  => $receiver,
			'subject'     => $subject,
			'body'        => $body,
		) );
		esk_flash( 'success', __( 'Message sent.', 'eskoofy' ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=esk-messages' ) );
	exit;
}

if ( 'inbox' === $tab ) {
	$messages = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT m.*, u.display_name AS sender_name
			FROM {$wpdb->prefix}esk_messages m
			JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
			WHERE m.receiver_id = %d
			ORDER BY m.created_at DESC LIMIT 100",
			$me
		)
	);
} else {
	$messages = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT m.*, u.display_name AS receiver_name
			FROM {$wpdb->prefix}esk_messages m
			JOIN {$wpdb->prefix}users u ON m.receiver_id = u.ID
			WHERE m.sender_id = %d
			ORDER BY m.created_at DESC LIMIT 100",
			$me
		)
	);
}

$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID, display_name FROM {$wpdb->prefix}users WHERE ID != %d ORDER BY display_name", $me ) );
$flash = esk_get_flash( 'success' );
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Messages', 'eskoofy' ); ?></h1>

	<?php if ( $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper esk-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-messages&tab=inbox' ) ); ?>" class="nav-tab <?php echo 'inbox' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Inbox', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-messages&tab=sent' ) ); ?>" class="nav-tab <?php echo 'sent' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Sent', 'eskoofy' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=esk-messages&tab=compose' ) ); ?>" class="nav-tab <?php echo 'compose' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Compose', 'eskoofy' ); ?></a>
	</nav>

	<?php if ( 'compose' === $tab ) : ?>
		<div class="esk-card esk-form-card">
			<h2><?php esc_html_e( 'Compose Message', 'eskoofy' ); ?></h2>
			<form method="post" class="esk-form">
				<?php wp_nonce_field( 'esk_message_form' ); ?>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'To', 'eskoofy' ); ?></label>
					<select name="receiver_id" required>
						<option value=""><?php esc_html_e( 'Select recipient', 'eskoofy' ); ?></option>
						<?php foreach ( $users as $u ) : ?>
							<option value="<?php echo esc_attr( $u->ID ); ?>"><?php echo esc_html( $u->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Subject', 'eskoofy' ); ?></label>
					<input type="text" name="subject" class="regular-text">
				</div>
				<div class="esk-form-group">
					<label><?php esc_html_e( 'Message', 'eskoofy' ); ?></label>
					<textarea name="body" rows="6" class="large-text" required></textarea>
				</div>
				<button type="submit" name="esk_message_send" class="button button-primary"><?php esc_html_e( 'Send', 'eskoofy' ); ?></button>
			</form>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr>
				<th><?php echo 'inbox' === $tab ? esc_html__( 'From', 'eskoofy' ) : esc_html__( 'To', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Subject', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Preview', 'eskoofy' ); ?></th>
				<th><?php esc_html_e( 'Date', 'eskoofy' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( empty( $messages ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No messages.', 'eskoofy' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $messages as $m ) : ?>
						<tr>
							<td><?php echo 'inbox' === $tab ? esc_html( $m->sender_name ) : esc_html( $m->receiver_name ); ?></td>
							<td><strong><?php echo esc_html( $m->subject ); ?></strong></td>
							<td><?php echo esc_html( wp_trim_words( $m->body, 10 ) ); ?></td>
							<td><?php echo esc_html( esk_date_format( $m->created_at ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>