<?php
/**
 * Admin Tools — install the app's demo content and repair the site URL.
 *
 * @package Eskoofy
 */

defined( 'ABSPATH' ) || exit;

$message = (string) ( $tools_message ?? '' );
$already = (bool) get_option( 'esk_demo_seeded' );
?>

<div class="max-w-2xl space-y-4">
	<?php if ( '' !== $message ) : ?>
		<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-900/30 dark:text-green-300"><?php echo esc_html( $message ); ?></div>
	<?php endif; ?>

	<div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
		<h2 class="text-lg font-bold text-slate-900 dark:text-slate-100"><?php esc_html_e( 'Install demo content', 'eskoofy' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
			<?php esc_html_e( 'Seeds the same "Example School" demo content as the Laravel app: school settings, homepage CMS rows, 30 teachers, 5 notices, 15 events, 8 galleries, 14 classes, students, announcements, fees, subjects, exams, testimonials, payments, expenses and attendances.', 'eskoofy' ); ?>
		</p>
		<p class="mt-2 text-xs text-slate-400"><?php echo $already ? esc_html__( 'Status: demo content is already installed.', 'eskoofy' ) : esc_html__( 'Status: not installed yet.', 'eskoofy' ); ?></p>
		<form method="post" class="mt-4">
			<?php wp_nonce_field( 'esk_tools_nonce' ); ?>
			<button type="submit" name="esk_install_demo" value="1" class="inline-flex items-center rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-500">
				<?php esc_html_e( 'Install demo content', 'eskoofy' ); ?>
			</button>
		</form>
	</div>

	<div class="rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
		<h2 class="text-lg font-bold text-slate-900 dark:text-slate-100"><?php esc_html_e( 'Repair site URL', 'eskoofy' ); ?></h2>
		<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
			<?php esc_html_e( 'If the site ever redirects to a /client/ path, this pins the WordPress home/siteurl options back to the current site root.', 'eskoofy' ); ?>
		</p>
		<form method="post" class="mt-4">
			<?php wp_nonce_field( 'esk_tools_nonce' ); ?>
			<button type="submit" name="esk_repair_url" value="1" class="inline-flex items-center rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
				<?php esc_html_e( 'Repair site URL', 'eskoofy' ); ?>
			</button>
		</form>
	</div>
</div>