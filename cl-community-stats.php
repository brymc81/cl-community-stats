<?php
/**
 * Plugin Name: CL Community Stats
 * Description: Bricks presentation adapter for cl-reso-link community statistics.
 * Version: 1.0.0
 * Author: Charleston Livability
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CL_COMMUNITY_STATS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'init', 'cl_community_stats_register_bricks_element', 11 );

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
