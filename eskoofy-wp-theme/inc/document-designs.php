<?php
/**
 * Configurable document designs / watermarks for the Eskoofy WordPress theme.
 *
 * Parity port of eskoofy-laravel-app/app/Services/DocumentDesignService.php.
 * A `document_designs` table stores per-type themes + watermarks; the helpers
 * below resolve the active design, normalise/clamp values, and emit a dompdf-
 * safe CSS block that the certificate/admit-card/id-card print views inject.
 *
 * @package Eskoofy
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * @return array<int,string>
 */
function esk_document_types(): array {
	return array( 'certificate', 'testimonial', 'marksheet', 'admit_card', 'id_card' );
}

/**
 * @return array<int,string>
 */
function esk_document_templates(): array {
	return array( 'classic', 'modern', 'minimal', 'bordered' );
}

function esk_document_is_type( ?string $type ): bool {
	return is_string( $type ) && in_array( $type, esk_document_types(), true );
}

function esk_document_is_template( ?string $template ): bool {
	return is_string( $template ) && in_array( $template, esk_document_templates(), true );
}

/**
 * The active design row for a document type, or null.
 *
 * @return object|null
 */
function esk_document_active_design( string $type ) {
	global $wpdb;
	$table = $wpdb->prefix . 'esk_document_designs';

	$row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . $table . ' WHERE document_type = %s AND is_default = 1 AND is_active = 1 ORDER BY updated_at DESC LIMIT 1',
			$type
		),
		OBJECT
	); // phpcs:ignore

	return is_object( $row ) ? $row : null;
}

/**
 * Normalised theme for a document type.
 *
 * @return array<string,mixed>
 */
function esk_document_theme( string $type ): array {
	$defaults = esk_document_defaults( $type );
	$design   = esk_document_active_design( $type );
	$settings = array();
	if ( $design && ! empty( $design->settings ) ) {
		$decoded  = json_decode( (string) $design->settings, true );
		$settings = is_array( $decoded ) ? $decoded : array();
	}

	$theme                      = esk_document_sanitize_theme( array_merge( $defaults, $settings ) );
	$template                   = ( $design && ! empty( $design->template ) )
		? $design->template
		: ( $settings['template'] ?? $defaults['template'] ?? 'classic' );
	$theme['template']          = esk_document_is_template( $template ) ? $template : 'classic';
	$theme['document_type']     = $type;
	$theme['name']              = (string) ( $design->name ?? ucwords( str_replace( '_', ' ', $type ) ) );
	$theme['has_custom_design'] = (bool) $design;

	return $theme;
}

/**
 * Normalised watermark for a document type.
 *
 * @return array<string,mixed>
 */
function esk_document_watermark( string $type ): array {
	$config = array(
		'enabled'     => (bool) get_option( 'esk_document_watermark_enabled', false ),
		'type'        => 'text',
		'text'        => get_option( 'esk_document_watermark_text', get_bloginfo( 'name' ) ),
		'image_path'  => null,
		'opacity'     => 0.18,
		'rotation'    => 45,
		'position'    => 'diagonal',
		'font_size'   => 48,
		'color'       => '#0f172a',
		'font_family' => 'inherit',
		'documents'   => array_fill_keys( esk_document_types(), true ),
	);

	$design   = esk_document_active_design( $type );
	$override = array();
	if ( $design && ! empty( $design->watermark ) ) {
		$decoded  = json_decode( (string) $design->watermark, true );
		$override = is_array( $decoded ) ? $decoded : array();
	}

	$merged            = array_merge( $config, $override );
	$global            = array_key_exists( 'enabled', $override )
		? (bool) $override['enabled']
		: (bool) $config['enabled'];
	$merged['enabled'] = $global;

	return esk_document_sanitize_watermark( $merged, $type );
}

/**
 * Per-type config defaults (the shipped bd-profile look).
 *
 * @return array<string,mixed>
 */
function esk_document_defaults( string $type ): array {
	$shared = array(
		'font_family'      => 'Georgia, serif',
		'base_font_size'   => 16,
		'title_font_size'  => 24,
		'border_style'     => 'double',
		'border_width'     => 3,
		'border_radius'    => 0,
		'page_size'        => 'a4',
		'orientation'      => 'landscape',
		'padding'          => 48,
		'accent_bar'       => 'none',
		'primary_color'    => '#1e40af',
		'secondary_color'  => '#1e3a8a',
		'accent_color'     => '#2563eb',
		'muted_color'      => '#64748b',
		'background_color' => '#ffffff',
		'border_color'     => '#2563eb',
		'text_color'       => '#1f2937',
	);

	$per_type = array(
		'certificate' => array_merge( $shared, array( 'template' => 'classic' ) ),
		'testimonial' => array_merge(
			$shared,
			array(
				'template'        => 'classic',
				'primary_color'   => '#16a34a',
				'secondary_color' => '#166534',
				'accent_color'    => '#16a34a',
				'border_color'    => '#16a34a',
			)
		),
		'marksheet'   => array(
			'template'         => 'modern',
			'primary_color'    => '#1f2937',
			'secondary_color'  => '#4b5563',
			'accent_color'     => '#2563eb',
			'muted_color'      => '#6b7280',
			'background_color' => '#ffffff',
			'font_family'      => 'Helvetica, Arial, sans-serif',
			'base_font_size'   => 13,
			'title_font_size'  => 22,
			'border_style'     => 'solid',
			'border_width'     => 1,
			'border_color'     => '#d1d5db',
			'border_radius'    => 8,
			'page_size'        => 'a4',
			'orientation'      => 'portrait',
			'padding'          => 40,
			'accent_bar'       => 'bottom',
			'text_color'       => '#1f2937',
		),
		'admit_card'  => array(
			'template'         => 'modern',
			'primary_color'    => '#1e40af',
			'secondary_color'  => '#1e3a8a',
			'accent_color'     => '#1e40af',
			'muted_color'      => '#6b7280',
			'background_color' => '#ffffff',
			'font_family'      => 'Arial, Helvetica, sans-serif',
			'base_font_size'   => 14,
			'title_font_size'  => 24,
			'border_style'     => 'solid',
			'border_width'     => 3,
			'border_color'     => '#1e40af',
			'border_radius'    => 12,
			'page_size'        => 'a4',
			'orientation'      => 'portrait',
			'padding'          => 30,
			'accent_bar'       => 'top',
			'text_color'       => '#1f2937',
		),
		'id_card'     => array(
			'template'         => 'modern',
			'primary_color'    => '#1e40af',
			'secondary_color'  => '#1e3a8a',
			'accent_color'     => '#1e40af',
			'muted_color'      => '#6b7280',
			'background_color' => '#ffffff',
			'font_family'      => 'Arial, Helvetica, sans-serif',
			'base_font_size'   => 13,
			'title_font_size'  => 18,
			'border_style'     => 'solid',
			'border_width'     => 3,
			'border_color'     => '#1e40af',
			'border_radius'    => 12,
			'page_size'        => 'credit-card',
			'orientation'      => 'landscape',
			'padding'          => 20,
			'accent_bar'       => 'none',
			'text_color'       => '#1f2937',
		),
	);

	return $per_type[ $type ] ?? $shared;
}

/**
 * Clamp/sanitise a submitted settings payload.
 *
 * @param array<string,mixed> $input
 * @return array<string,mixed>
 */
function esk_document_sanitize_theme( array $input ): array {
	$input = wp_parse_args(
		$input,
		array(
			'primary_color'    => '#1e40af',
			'secondary_color'  => '#1e3a8a',
			'accent_color'     => '#2563eb',
			'text_color'       => '#1f2937',
			'muted_color'      => '#64748b',
			'background_color' => '#ffffff',
			'border_color'     => '#1e40af',
			'font_family'      => 'inherit',
			'base_font_size'   => 14,
			'title_font_size'  => 20,
			'border_style'     => 'solid',
			'border_width'     => 2,
			'border_radius'    => 0,
			'padding'          => 32,
			'page_size'        => 'a4',
			'orientation'      => 'portrait',
			'accent_bar'       => 'none',
			'show_header'      => true,
			'show_footer'      => true,
			'show_logo'        => true,
			'show_number'      => true,
			'show_issue_date'  => true,
			'show_signature'   => true,
			'show_notes'       => true,
			'logo_position'    => 'top-center',
			'header_text'      => null,
			'footer_text'      => null,
			'signature_label'  => null,
		)
	);

	$fonts = array(
		'Georgia, serif',
		'Helvetica, Arial, sans-serif',
		'Arial, Helvetica, sans-serif',
		'Times New Roman, serif',
		'Verdana, sans-serif',
		'inherit',
	);

	return array(
		'primary_color'    => esk_document_color( $input['primary_color'], '#1e40af' ),
		'secondary_color'  => esk_document_color( $input['secondary_color'], '#1e3a8a' ),
		'accent_color'     => esk_document_color( $input['accent_color'], '#2563eb' ),
		'text_color'       => esk_document_color( $input['text_color'], '#1f2937' ),
		'muted_color'      => esk_document_color( $input['muted_color'], '#64748b' ),
		'background_color' => esk_document_color( $input['background_color'], '#ffffff' ),
		'border_color'     => esk_document_color( $input['border_color'], '#1e40af' ),
		'font_family'      => in_array( (string) ( $input['font_family'] ?? '' ), $fonts, true ) ? (string) $input['font_family'] : 'inherit',
		'base_font_size'   => esk_document_clamp_int( $input['base_font_size'], 8, 32, 14 ),
		'title_font_size'  => esk_document_clamp_int( $input['title_font_size'], 10, 72, 20 ),
		'border_style'     => in_array( (string) ( $input['border_style'] ?? '' ), array( 'none', 'solid', 'double', 'dashed', 'dotted' ), true )
			? (string) $input['border_style'] : 'solid',
		'border_width'     => esk_document_clamp_int( $input['border_width'], 0, 12, 2 ),
		'border_radius'    => esk_document_clamp_int( $input['border_radius'], 0, 40, 0 ),
		'padding'          => esk_document_clamp_int( $input['padding'], 0, 160, 32 ),
		'page_size'        => in_array( (string) ( $input['page_size'] ?? '' ), array( 'a4', 'letter', 'legal', 'credit-card' ), true )
			? (string) $input['page_size'] : 'a4',
		'orientation'      => in_array( (string) ( $input['orientation'] ?? '' ), array( 'portrait', 'landscape' ), true )
			? (string) $input['orientation'] : 'portrait',
		'accent_bar'       => in_array( (string) ( $input['accent_bar'] ?? '' ), array( 'none', 'top', 'bottom', 'left', 'right' ), true )
			? (string) $input['accent_bar'] : 'none',
		'show_header'      => esk_document_flag( $input['show_header'] ?? true, true ),
		'show_footer'      => esk_document_flag( $input['show_footer'] ?? true, true ),
		'show_logo'        => esk_document_flag( $input['show_logo'] ?? true, true ),
		'show_number'      => esk_document_flag( $input['show_number'] ?? true, true ),
		'show_issue_date'  => esk_document_flag( $input['show_issue_date'] ?? true, true ),
		'show_signature'   => esk_document_flag( $input['show_signature'] ?? true, true ),
		'show_notes'       => esk_document_flag( $input['show_notes'] ?? true, true ),
		'logo_position'    => in_array( (string) ( $input['logo_position'] ?? '' ), array( 'top-left', 'top-center' ), true )
			? (string) $input['logo_position'] : 'top-center',
		'header_text'      => esk_document_line( $input['header_text'] ?? null, 255 ),
		'footer_text'      => esk_document_line( $input['footer_text'] ?? null, 255 ),
		'signature_label'  => esk_document_line( $input['signature_label'] ?? null, 120 ),
	);
}

/**
 * Clamp/sanitise a submitted watermark payload.
 *
 * @param array<string,mixed> $input
 * @return array<string,mixed>
 */
function esk_document_sanitize_watermark( array $input, ?string $type = null ): array {
	$type_value = in_array( (string) ( $input['type'] ?? '' ), array( 'text', 'image', 'logo' ), true )
		? (string) $input['type'] : 'text';
	$text       = esk_document_line( $input['text'] ?? null, 120 );

	$documents = array();
	foreach ( esk_document_types() as $doc_type ) {
		$documents[ $doc_type ] = isset( $input['documents'][ $doc_type ] )
			? (bool) $input['documents'][ $doc_type ]
			: true;
	}
	if ( $type !== null && isset( $input['documents'] ) ) {
		$documents[ $type ] = (bool) $input['documents'][ $type ];
	}

	return array(
		'enabled'     => (bool) ( $input['enabled'] ?? false ),
		'type'        => $type_value,
		'text'        => $text ?: ( 'text' === $type_value ? (string) get_bloginfo( 'name' ) : null ),
		'image_path'  => esk_document_upload_path( $input['image_path'] ?? null ),
		'opacity'     => round( max( 0.05, min( 1.0, (float) ( $input['opacity'] ?? 0.18 ) ) ), 3 ),
		'rotation'    => esk_document_clamp_int( $input['rotation'] ?? 45, -180, 180, 45 ),
		'position'    => in_array( (string) ( $input['position'] ?? '' ), array( 'center', 'diagonal', 'tile', 'top', 'bottom' ), true )
			? (string) $input['position'] : 'diagonal',
		'font_size'   => esk_document_clamp_int( $input['font_size'] ?? 48, 8, 120, 48 ),
		'color'       => esk_document_color( $input['color'] ?? '#0f172a', '#0f172a' ),
		'font_family' => (string) ( esk_document_line( $input['font_family'] ?? null, 120 ) ?? 'inherit' ),
		'documents'   => $documents,
	);
}

/**
 * The dompdf-safe CSS block for a document type (theme + watermark + custom CSS).
 *
 * @param array<string,mixed> $theme
 * @param array<string,mixed> $watermark
 */
function esk_document_css( array $theme, array $watermark, string $custom_css = '' ): string {
	$root = '.doc-root.doc-' . (string) preg_replace( '/[^a-z0-9_-]/i', '', (string) $theme['document_type'] );

	$declarations = '';
	$vars         = array(
		'--doc-primary'      => $theme['primary_color'],
		'--doc-secondary'    => $theme['secondary_color'],
		'--doc-accent'       => $theme['accent_color'],
		'--doc-text'         => $theme['text_color'],
		'--doc-muted'        => $theme['muted_color'],
		'--doc-bg'           => $theme['background_color'],
		'--doc-border'       => $theme['border_color'],
		'--doc-font'         => $theme['font_family'],
		'--doc-base-size'    => $theme['base_font_size'] . 'px',
		'--doc-title-size'   => $theme['title_font_size'] . 'px',
		'--doc-border-style' => $theme['border_style'],
		'--doc-border-width' => $theme['border_width'] . 'px',
		'--doc-radius'       => $theme['border_radius'] . 'px',
		'--doc-padding'      => $theme['padding'] . 'px',
		'--doc-tint'         => esk_document_tint( $theme['accent_color'], 0.08 ),
		'--doc-tint-strong'  => esk_document_tint( $theme['primary_color'], 0.10 ),
	);
	foreach ( $vars as $name => $value ) {
		$declarations .= $name . ':' . $value . ';';
	}

	$css  = $root . '{' . $declarations . '}';
	$css .= esk_document_template_css( $theme );
	$css .= esk_document_accent_bar_css( $theme );
	$css .= esk_document_page_css( $theme );
	$css .= esk_document_watermark_css( $watermark, $root );

	if ( '' !== $custom_css ) {
		$css .= "\n" . esk_document_sanitize_css( $custom_css );
	}

	return $css;
}

/**
 * @param array<string,mixed> $theme
 */
function esk_document_template_css( array $theme ): string {
	$root = '.doc-root.doc-' . (string) preg_replace( '/[^a-z0-9_-]/i', '', (string) $theme['document_type'] );

	$base        = $root . '{'
		. 'font-family:' . $theme['font_family'] . ';font-size:' . $theme['base_font_size'] . 'px;color:' . $theme['text_color'] . ';'
		. 'background:' . $theme['background_color'] . ';'
		. 'border:' . $theme['border_width'] . 'px ' . $theme['border_style'] . ' ' . $theme['border_color'] . ';'
		. 'border-radius:' . $theme['border_radius'] . 'px;padding:' . $theme['padding'] . 'px;'
		. 'position:relative;width:100%;box-sizing:border-box;'
		. '}';
	$content     = $root . ' > *{position:relative;z-index:1;}';
	$headings    = $root . ' .doc-title{font-size:' . $theme['title_font_size'] . 'px;color:' . $theme['secondary_color'] . ';font-weight:700;margin:0 0 .4em;}';
	$muted       = $root . ' .doc-muted{color:' . $theme['muted_color'] . ';}';
	$name        = $root . ' .doc-name{color:' . $theme['primary_color'] . ';font-weight:700;text-decoration:underline;}';
	$number      = $root . ' .doc-number{color:' . $theme['muted_color'] . ';font-size:.85em;}';
	$notes       = $root . ' .doc-notes{background:' . esk_document_tint( $theme['accent_color'], 0.08 ) . ';'
		. 'border-left:3px solid ' . $theme['accent_color'] . ';padding:10px 12px;text-align:left;'
		. 'color:' . $theme['text_color'] . ';}';
	$signature   = $root . ' .doc-signature{border-top:1px solid ' . $theme['text_color'] . ';padding-top:6px;text-align:center;color:' . $theme['text_color'] . ';}';
	$table       = $root . ' table.doc-table{width:100%;border-collapse:collapse;}';
	$table_cells = $root . ' .doc-table th,.doc-table td{border:1px solid ' . $theme['border_color'] . ';padding:8px 10px;text-align:left;}';
	$table_head  = $root . ' .doc-table th{background:' . esk_document_tint( $theme['primary_color'], 0.10 ) . ';color:' . $theme['secondary_color'] . ';}';
	$logo        = $root . ' .doc-logo{max-height:60px;object-fit:contain;margin:0 auto 8px;}';
	$logo_left   = $root . ' .doc-logo-left{margin:0 12px 8px 0;float:left;}';
	$panel       = $root . ' .doc-panel{background:' . esk_document_tint( $theme['accent_color'], 0.06 ) . ';'
		. 'border:1px solid ' . $theme['border_color'] . ';border-radius:' . $theme['border_radius'] . 'px;padding:12px 16px;}';
	$panel_muted = $root . ' .doc-panel-muted{background:' . esk_document_tint( $theme['muted_color'], 0.08 ) . ';}';
	$pass        = $root . ' .doc-pass{color:' . esk_document_tint( $theme['primary_color'], 1.0 ) . ';font-weight:700;}';
	$fail        = $root . ' .doc-fail{color:#dc2626;font-weight:700;}';

	$templates = array(
		'classic'  => $root . '{text-align:center;}' . $root . ' .doc-body{text-align:left;}',
		'modern'   => $root . '{box-shadow:0 1px 3px rgba(15,23,42,.12);}'
			. $root . ' .doc-title{letter-spacing:1px;text-transform:uppercase;}'
			. $root . ' .doc-body{text-align:left;line-height:1.7;}',
		'minimal'  => $root . '{border-width:0;border-radius:0;background:transparent;}'
			. $root . ' .doc-title{font-weight:400;letter-spacing:2px;}'
			. $root . ' .doc-name{text-decoration:none;}',
		'bordered' => $root . '{border-style:double;}'
			. '.doc-inner-frame{position:absolute;top:8px;right:8px;bottom:8px;left:8px;'
			. 'border:1px solid ' . $theme['accent_color'] . ';border-radius:' . $theme['border_radius'] . 'px;'
			. 'pointer-events:none;opacity:.6;}',
	);

	return $base . $content . $headings . $muted . $name . $number . $notes . $signature
		. $table . $table_cells . $table_head . $logo . $logo_left . $panel . $panel_muted . $pass . $fail
		. ( $templates[ $theme['template'] ] ?? $templates['classic'] );
}

/**
 * @param array<string,mixed> $theme
 */
function esk_document_accent_bar_css( array $theme ): string {
	if ( 'none' === $theme['accent_bar'] ) {
		return '.doc-accent-bar{display:none;}';
	}
	$positions = array(
		'top'    => 'top:0;left:0;right:0;',
		'bottom' => 'bottom:0;left:0;right:0;',
		'left'   => 'top:0;bottom:0;left:0;width:6px;',
		'right'  => 'top:0;bottom:0;right:0;width:6px;',
	);
	$position  = $positions[ $theme['accent_bar'] ] ?? 'top:0;left:0;right:0;';
	$height    = in_array( $theme['accent_bar'], array( 'left', 'right' ), true ) ? '100%' : '6px';

	return '.doc-accent-bar{display:block;position:absolute;' . $position . 'height:' . $height . ';z-index:2;'
		. 'background:' . $theme['accent_color'] . ';}';
}

/**
 * @param array<string,mixed> $theme
 */
function esk_document_page_css( array $theme ): string {
	$sizes = array(
		'a4'          => '210mm',
		'letter'      => '215.9mm',
		'legal'       => '215.9mm',
		'credit-card' => '85.6mm',
	);
	$width = $sizes[ $theme['page_size'] ] ?? '210mm';

	return '@page{size:' . $theme['orientation'] . ' ' . $width . ';margin:10mm;}';
}

/**
 * @param array<string,mixed> $watermark
 */
function esk_document_watermark_css( array $watermark, string $root ): string {
	if ( empty( $watermark['enabled'] ) ) {
		return '';
	}

	$layer = $root . ' > .doc-watermark{position:absolute;z-index:0;top:0;right:0;bottom:0;left:0;'
		. 'display:flex;align-items:center;justify-content:center;'
		. 'pointer-events:none;overflow:hidden;}';
	$item  = '.doc-watermark-item{opacity:' . $watermark['opacity'] . ';color:' . $watermark['color'] . ';'
		. 'font-size:' . $watermark['font_size'] . 'px;font-family:' . $watermark['font_family'] . ';'
		. 'font-weight:700;white-space:nowrap;text-align:center;line-height:1.1;}';

	$position_css = '';
	switch ( $watermark['position'] ) {
		case 'center':
			$position_css = '.doc-watermark-item{transform:rotate(0deg);}';
			break;
		case 'top':
			$position_css = '.doc-watermark{align-items:flex-start;padding-top:8%;}.doc-watermark-item{transform:rotate(' . $watermark['rotation'] . 'deg);}';
			break;
		case 'bottom':
			$position_css = '.doc-watermark{align-items:flex-end;padding-bottom:8%;}.doc-watermark-item{transform:rotate(' . $watermark['rotation'] . 'deg);}';
			break;
		case 'tile':
			$position_css = '.doc-watermark{flex-wrap:wrap;align-content:center;gap:6% 8%;}.doc-watermark-item{'
				. 'transform:rotate(' . $watermark['rotation'] . 'deg);font-size:' . max( 10, (int) ( $watermark['font_size'] * 0.5 ) ) . 'px;}';
			break;
		default:
			$position_css = '.doc-watermark-item{transform:rotate(' . $watermark['rotation'] . 'deg);}';
	}

	return $layer . $item . $position_css;
}

/**
 * Strip anything executable from custom CSS (parity with the app's sanitiser).
 */
function esk_document_sanitize_css( string $css ): string {
	$css = wp_strip_all_tags( $css );
	$css = str_ireplace( array( 'javascript:', 'vbscript:', 'expression(', '@import', '-moz-binding', 'behavior:', '@charset' ), '', $css );
	$css = (string) preg_replace_callback(
		'#url\(([^)]*)\)#i',
		static function ( array $m ): string {
			$value = trim( $m[1], " \t\"'" );
			if ( '' === $value || (bool) preg_match( '#^(https?:|data:|//|\.\./)#i', $value ) ) {
				return 'none';
			}

			return 'url(' . $value . ')';
		},
		$css
	);
	$css = str_replace( array( '<', '>' ), '', $css );
	$css = str_replace( array( '/*', '*/' ), '', $css );

	if ( (bool) preg_match( '/[{}]/', $css ) && substr_count( $css, '{' ) !== substr_count( $css, '}' ) ) {
		return '';
	}

	return trim( $css );
}

// ------------------------------------------------------------- primitives --

function esk_document_color( mixed $value, string $fallback ): string {
	$value = is_string( $value ) ? trim( $value ) : '';

	return (bool) preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ? strtolower( $value ) : $fallback;
}

function esk_document_tint( string $hex, float $alpha ): string {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) && 8 !== strlen( $hex ) ) {
		return 'rgba(37,99,235,' . esk_document_alpha( $alpha ) . ')';
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . esk_document_alpha( $alpha ) . ')';
}

function esk_document_alpha( float $alpha ): string {
	return rtrim( rtrim( number_format( $alpha, 3, '.', '' ), '0' ), '.' );
}

function esk_document_clamp_int( mixed $value, int $min, int $max, int $default ): int {
	if ( ! is_numeric( $value ) ) {
		return $default;
	}

	return max( $min, min( $max, (int) $value ) );
}

function esk_document_flag( mixed $value, bool $default ): bool {
	if ( null === $value || '' === $value ) {
		return $default;
	}

	return filter_var( $value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE ) ?? $default;
}

function esk_document_line( mixed $value, int $max ): ?string {
	if ( ! is_string( $value ) ) {
		return null;
	}
	$value = trim( wp_strip_all_tags( $value ) );

	return '' === $value ? null : mb_substr( $value, 0, $max );
}

function esk_document_upload_path( mixed $value ): ?string {
	if ( ! is_string( $value ) ) {
		return null;
	}
	$value = trim( $value );
	if ( '' === $value || (bool) preg_match( '#^(https?:|//|data:)#i', $value ) || str_contains( $value, '..' ) ) {
		return null;
	}

	return mb_substr( ltrim( $value, '/' ), 0, 255 );
}

/**
 * The CSS block for a print view, keyed by document type.
 */
function esk_document_style( string $type ): string {
	if ( ! esk_document_is_type( $type ) ) {
		return '';
	}

	$theme     = esk_document_theme( $type );
	$watermark = esk_document_watermark( $type );
	$design    = esk_document_active_design( $type );
	$custom    = $design && ! empty( $design->custom_css ) ? (string) $design->custom_css : '';

	return esk_document_css( $theme, $watermark, $custom );
}

/**
 * The watermark markup for a print view, keyed by document type.
 */
function esk_document_watermark_html( string $type ): string {
	if ( ! esk_document_is_type( $type ) ) {
		return '';
	}
	$wm = esk_document_watermark( $type );
	if ( empty( $wm['enabled'] ) ) {
		return '';
	}

	$inner = '';
	if ( 'text' === $wm['type'] ) {
		$inner = esc_html( (string) ( $wm['text'] ?? '' ) );
	} elseif ( ! empty( $wm['image_path'] ) ) {
		$inner = '<img src="' . esc_url( $wm['image_path'] ) . '" alt="">';
	}

	if ( '' === $inner ) {
		return '';
	}

	return '<div class="doc-watermark"><span class="doc-watermark-item">' . $inner . '</span></div>';
}
