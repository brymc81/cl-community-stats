<?php
/**
 * Plugin Name: CL Community Stats
 * Description: Bricks presentation adapter for cl-reso-link community statistics.
 * Version: 1.0.0
 * Author: Charleston Livability
 */

use CL_Community_Stats\Community_Stats_Presenter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CL_COMMUNITY_STATS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

$presenter_file = CL_COMMUNITY_STATS_PLUGIN_DIR . 'includes/class-community-stats-presenter.php';
if ( file_exists( $presenter_file ) ) {
    require_once $presenter_file;
}

add_action( 'init', 'cl_community_stats_register_bricks_element', 11 );
add_action( 'init', 'cl_community_stats_register_shortcodes', 12 );

/**
 * Register the Bricks element when Bricks is available.
 */
function cl_community_stats_register_bricks_element(): void {
    if ( ! class_exists( '\\Bricks\\Elements' ) ) {
        return;
    }

    $element_file = CL_COMMUNITY_STATS_PLUGIN_DIR . 'includes/bricks/class-community-stats-element.php';
    if ( ! file_exists( $element_file ) ) {
        return;
    }

    require_once $element_file;

    if ( ! class_exists( '\\CL_Community_Stats\\Bricks\\Community_Stats_Element' ) ) {
        return;
    }

    \Bricks\Elements::register_element( $element_file );
}

/**
 * Register shortcodes for grouped and single metric output.
 */
function cl_community_stats_register_shortcodes(): void {
    add_shortcode( 'cl_community_stats', 'cl_community_stats_shortcode_grouped' );
    add_shortcode( 'cl_community_stat', 'cl_community_stats_shortcode_single' );
}

/**
 * Render grouped community stats.
 *
 * @param array<string,mixed> $atts
 */
function cl_community_stats_shortcode_grouped( array $atts = array() ): string {
    if ( ! class_exists( Community_Stats_Presenter::class ) ) {
        return '';
    }

    $presenter = new Community_Stats_Presenter();

    $atts = shortcode_atts(
        array(
            'geo_shape_id' => '',
            'community' => '',
            'community_key' => '',
            'months' => '12',
            'metrics' => '',
            'display_mode' => 'cards',
            'empty_state' => 'hide',
            'metric' => '',
            'show_label' => 'true',
            'number_format' => 'default',
            'output' => 'full',
        ),
        $atts,
        'cl_community_stats'
    );

    $months = $presenter->resolve_months( $atts['months'] );
    $display_mode = $presenter->resolve_display_mode( $atts['display_mode'] );
    $empty_state = $presenter->resolve_empty_state( $atts['empty_state'] );
    $number_format = $presenter->resolve_number_format( $atts['number_format'] );
    $metrics = $presenter->resolve_metrics( $atts['metrics'] );

    $context_inputs = cl_community_stats_build_context_inputs( $atts );
    $result = $presenter->fetch_market_stats( $context_inputs, $months );

    if ( 'single' === $display_mode ) {
        $single_metric = $presenter->resolve_metric( $atts['metric'], $metrics[0] ?? '' );
        $show_label = $presenter->resolve_boolean( $atts['show_label'], true );
        $output = ( 'value' === strtolower( trim( (string) $atts['output'] ) ) ) ? 'value' : 'full';
        $market = 'ok' === $result['state'] ? $result['market'] : array();

        return $presenter->render_single_stat( $market, $single_metric, $show_label, $empty_state, $output, $number_format );
    }

    if ( 'ok' !== $result['state'] ) {
        return $presenter->render_empty_state( $display_mode, $empty_state, __( 'Statistics unavailable for this context.', 'cl-community-stats' ) );
    }

    return $presenter->render_market_stats( $result['market'], $metrics, $display_mode, $empty_state, $number_format );
}

/**
 * Render a single metric for community stats.
 *
 * @param array<string,mixed> $atts
 */
function cl_community_stats_shortcode_single( array $atts = array() ): string {
    if ( ! class_exists( Community_Stats_Presenter::class ) ) {
        return '';
    }

    $presenter = new Community_Stats_Presenter();

    $atts = shortcode_atts(
        array(
            'geo_shape_id' => '',
            'community' => '',
            'community_key' => '',
            'months' => '12',
            'metrics' => '',
            'metric' => 'median_sale_price',
            'display_mode' => 'single',
            'show_label' => 'true',
            'empty_state' => 'hide',
            'number_format' => 'default',
            'output' => 'full',
        ),
        $atts,
        'cl_community_stat'
    );

    $months = $presenter->resolve_months( $atts['months'] );
    $metrics = $presenter->resolve_metrics( $atts['metrics'] );
    $metric = $presenter->resolve_metric( $atts['metric'], $metrics[0] ?? '' );
    $show_label = $presenter->resolve_boolean( $atts['show_label'], true );
    $empty_state = $presenter->resolve_empty_state( $atts['empty_state'] );
    $number_format = $presenter->resolve_number_format( $atts['number_format'] );
    $output = ( 'value' === strtolower( trim( (string) $atts['output'] ) ) ) ? 'value' : 'full';

    $context_inputs = cl_community_stats_build_context_inputs( $atts );
    $result = $presenter->fetch_market_stats( $context_inputs, $months );

    $market = 'ok' === $result['state'] ? $result['market'] : array();

    return $presenter->render_single_stat( $market, $metric, $show_label, $empty_state, $output, $number_format );
}

/**
 * Map shortcode attrs into presenter context inputs with strict precedence handling in presenter.
 *
 * @param array<string,mixed> $atts
 * @return array<string,mixed>
 */
function cl_community_stats_build_context_inputs( array $atts ): array {
    return array(
        'geo_shape_id_input' => $atts['geo_shape_id'] ?? ( $atts['geo_shape_id_input'] ?? '' ),
        'community_input' => $atts['community'] ?? ( $atts['community_input'] ?? '' ),
        'community_key_input' => $atts['community_key'] ?? ( $atts['community_key_input'] ?? '' ),
        'community_key' => $atts['community_key'] ?? '',
    );
}
