<?php
/**
 * Onboarding/setup checklist.
 *
 * @package Eskoofy
 */

defined('ABSPATH') || exit;
global $wpdb;

$checks = array(
	array(
		'label' => __( 'School name set', 'eskoofy' ),
		'done'  => (bool) get_option( 'esk_school_name', '' ),
	),
	array(
		'label' => __( 'School phone set', 'eskoofy' ),
		'done'  => (bool) get_option( 'esk_school_phone', '' ),
	),
	array(
		'label' => __( 'School email set', 'eskoofy' ),
		'done'  => (bool) get_option( 'esk_school_email', '' ),
	),
	array(
		'label' => __( 'Currency set', 'eskoofy' ),
		'done'  => (bool) get_option( 'esk_currency', '' ),
	),
	array(
		'label' => __( 'At least one class created', 'eskoofy' ),
		'done'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_classes" ) > 0,
	),
	array(
		'label' => __( 'At least one section created', 'eskoofy' ),
		'done'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_sections" ) > 0,
	),
	array(
		'label' => __( 'At least one subject created', 'eskoofy' ),
		'done'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_subjects" ) > 0,
	),
	array(
		'label' => __( 'Active academic session set', 'eskoofy' ),
		'done'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_academic_sessions WHERE is_active = 1" ) > 0,
	),
	array(
		'label' => __( 'At least one payment gateway active', 'eskoofy' ),
		'done'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}esk_payment_gateways WHERE is_active = 1 AND deleted_at IS NULL" ) > 0,
	),
	array(
		'label' => __( 'Site logo set', 'eskoofy' ),
		'done'  => has_custom_logo() || (bool) get_option( 'esk_school_logo', '' ),
	),
);

$total     = count( $checks );
$completed = count( array_filter( $checks, static fn( $c ) => $c['done'] ) );
$percent   = $total > 0 ? (int) round( ( $completed / $total ) * 100 ) : 0;
?>
<div class="wrap esk-admin-wrap">
	<h1><?php esc_html_e( 'Onboarding', 'eskoofy' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Track your school setup progress.', 'eskoofy' ); ?></p>

	<div class="esk-card esk-form-card" style="margin-bottom:1.5rem;">
		<h2><?php printf( esc_html__( 'Progress: %d%%', 'eskoofy' ), $percent ); ?></h2>
		<div style="background:#e5e7eb;border-radius:6px;overflow:hidden;height:18px;">
			<div style="background:#2563eb;height:100%;width:<?php echo esc_attr( $percent ); ?>%;"></div>
		</div>
		<p><?php printf( esc_html__( '%1$d of %2$d setup steps complete.', 'eskoofy' ), $completed, $total ); ?></p>
	</div>

	<div class="esk-card">
		<h2><?php esc_html_e( 'Setup Checklist', 'eskoofy' ); ?></h2>
		<table class="wp-list-table widefat striped esk-table">
			<thead><tr><th><?php esc_html_e( 'Step', 'eskoofy' ); ?></th><th><?php esc_html_e( 'Status', 'eskoofy' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $checks as $c ) : ?>
					<tr>
						<td><?php echo esc_html( $c['label'] ); ?></td>
						<td>
							<?php if ( $c['done'] ) : ?>
								<span class="esk-badge esk-badge-completed"><?php esc_html_e( 'Done', 'eskoofy' ); ?></span>
							<?php else : ?>
								<span class="esk-badge esk-badge-pending"><?php esc_html_e( 'Pending', 'eskoofy' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>