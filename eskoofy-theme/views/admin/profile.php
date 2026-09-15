<?php
/**
 * Admin profile — mirrors the app's profile edit page.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;

$user      = wp_get_current_user();
$saved     = false;
$error     = '';

if ( isset( $_POST['esk_profile_save'] ) && current_user_can( 'edit_user', $user->ID ) ) {
	check_admin_referer( 'esk_profile_nonce' );
	$show_name = sanitize_text_field( wp_unslash( $_POST['esk_display_name'] ?? '' ) );
	$email     = sanitize_email( wp_unslash( $_POST['esk_email'] ?? '' ) );
	$bio       = sanitize_textarea_field( wp_unslash( $_POST['esk_bio'] ?? '' ) );

	$ug = new WP_User( $user->ID );
	$ug->display_name = $show_name;
	$ug->description  = $bio;
	wp_update_user( $ug );

	if ( $email && is_email( $email ) ) {
		$check = get_user_by( 'email', $email );
		if ( $check && (int) $check->ID !== (int) $user->ID ) {
			$error = __( 'That email is already in use by another account.', 'eskoofy' );
		} elseif ( wp_update_user( array( 'ID' => $user->ID, 'user_email' => $email ) ) ) {
			$saved = true;
		}
	} else {
		$saved = true;
	}

	if ( ! empty( $_POST['esk_new_pass'] ) && empty( $error ) ) {
		$pass = (string) wp_unslash( $_POST['esk_new_pass'] );
		wp_set_password( $pass, $user->ID );
		// Re-auth so the new password sticks immediately.
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );
		$saved = true;
	}

	$user = wp_get_current_user();
}

$roles = $user->roles;
$role_labels = array();
foreach ( (array) $roles as $r ) {
	$role_labels[] = translate_user_role( ucwords( str_replace( array( '-', '_' ), ' ', $r ) ) );
}
?>
<div class="wrap esk-admin-wrap">

	<div class="mb-6 flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?php esc_html_e( 'My Profile', 'eskoofy' ); ?></h1>
			<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo esc_html( sprintf( __( 'Manage your account for %s.', 'eskoofy' ), wp_specialchars_decode( get_bloginfo( 'name' ) ) ) ); ?></p>
		</div>
	</div>

	<?php do_action( 'admin_notices' ); ?>
	<?php if ( $saved && '' === $error ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr__( 'Profile updated successfully.', 'eskoofy' ); ?>" data-type="success"></div>
	<?php elseif ( '' !== $error ) : ?>
		<div data-esk-flash-toast data-message="<?php echo esc_attr( $error ); ?>" data-type="error"></div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-3">
		<div class="admin-card">
			<div class="flex flex-col items-center p-6 text-center">
				<div class="mb-4 flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-3xl font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-400">
					<?php
					$avatar = get_avatar( $user->ID, 96 );
					if ( $avatar ) {
						echo wp_kses_post( $avatar );
					} else {
						echo esc_html( esk_initials( $user->display_name ) );
					}
					?>
				</div>
				<h2 class="text-lg font-semibold text-slate-900 dark:text-white"><?php echo esc_html( $user->display_name ); ?></h2>
				<p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo esc_html( (string) $user->user_email ); ?></p>
				<?php if ( $role_labels ) : ?>
					<div class="mt-3 flex flex-wrap justify-center gap-2">
						<?php foreach ( $role_labels as $rl ) : ?>
							<span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-400"><?php echo esc_html( $rl ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="mt-6 text-xs text-slate-400 dark:text-slate-500">
					<?php echo esc_html( sprintf( __( 'Member since %s', 'eskoofy' ), gmdate( 'M Y', strtotime( $user->user_registered ) ) ) ); ?>
				</p>
			</div>
		</div>

		<div class="admin-card lg:col-span-2">
			<div class="admin-card-header">
				<h2 class="text-base font-semibold text-slate-900 dark:text-white"><?php esc_html_e( 'Account Settings', 'eskoofy' ); ?></h2>
			</div>
			<div class="admin-card-body">
				<form method="post" action="">
					<?php wp_nonce_field( 'esk_profile_nonce' ); ?>
					<div class="space-y-5">
						<div class="grid gap-4 sm:grid-cols-2">
							<div>
								<label for="esk-display-name" class="block text-sm font-medium text-slate-700 dark:text-slate-300"><?php esc_html_e( 'Display name', 'eskoofy' ); ?></label>
								<input id="esk-display-name" name="esk_display_name" type="text" value="<?php echo esc_attr( $user->display_name ); ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
							</div>
							<div>
								<label for="esk-email" class="block text-sm font-medium text-slate-700 dark:text-slate-300"><?php esc_html_e( 'Email address', 'eskoofy' ); ?></label>
								<input id="esk-email" name="esk_email" type="email" value="<?php echo esc_attr( (string) $user->user_email ); ?>" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
							</div>
						</div>
						<div>
							<label for="esk-bio" class="block text-sm font-medium text-slate-700 dark:text-slate-300"><?php esc_html_e( 'Short bio', 'eskoofy' ); ?></label>
							<textarea id="esk-bio" name="esk_bio" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"><?php echo esc_textarea( (string) $user->description ); ?></textarea>
						</div>
						<div>
							<label for="esk-new-pass" class="block text-sm font-medium text-slate-700 dark:text-slate-300"><?php esc_html_e( 'New password', 'eskoofy' ); ?></label>
							<input id="esk-new-pass" name="esk_new_pass" type="password" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" autocomplete="new-password">
							<p class="mt-1 text-xs text-slate-400 dark:text-slate-500"><?php esc_html_e( 'Leave blank to keep your current password.', 'eskoofy' ); ?></p>
						</div>
						<div class="flex items-center gap-3">
							<button type="submit" name="esk_profile_save" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"><?php esc_html_e( 'Save changes', 'eskoofy' ); ?></button>
							<a href="<?php echo esc_url( get_edit_user_link() ); ?>" class="text-sm font-medium text-slate-500 hover:text-brand-600 dark:text-slate-400"><?php esc_html_e( 'Advanced WordPress profile settings', 'eskoofy' ); ?></a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>