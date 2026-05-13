<?php

namespace CL_Community_Stats\Bricks;

use Bricks\Element;
use CL_Community_Stats\Community_Stats_Presenter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Community_Stats_Element extends Element {

    public $name = 'cl-community-stats';
    public $category = 'general';
    public $icon = 'ti-stats-up';

    public function get_label() {
        return __( 'Community Stats', 'cl-community-stats' );
    }

    public function get_keywords() {
        return array( 'community', 'stats', 'market', 'cl' );
    }

    public function set_control_groups() {
        $this->control_groups['query'] = array(
            'title' => __( 'Query', 'cl-community-stats' ),
        );

        $this->control_groups['display'] = array(
            'title' => __( 'Display', 'cl-community-stats' ),
        );
    }

    public function set_controls() {
        $this->controls['geo_shape_id_input'] = array(
            'tab' => 'content',
            'group' => 'query',
            'label' => __( 'Geo Shape ID', 'cl-community-stats' ),
            'type' => 'text',
            'placeholder' => 'mount_pleasant',
            'hasDynamicData' => true,
        );

        $this->controls['community_input'] = array(
            'tab' => 'content',
            'group' => 'query',
            'label' => __( 'Community', 'cl-community-stats' ),
            'type' => 'text',
            'placeholder' => 'mount_pleasant',
            'hasDynamicData' => true,
        );

        $this->controls['community_key_input'] = array(
            'tab' => 'content',
            'group' => 'query',
            'label' => __( 'Community Key', 'cl-community-stats' ),
            'type' => 'text',
            'placeholder' => 'mount_pleasant',
            'hasDynamicData' => true,
        );

        $this->controls['months'] = array(
            'tab' => 'content',
            'group' => 'query',
            'label' => __( 'Months', 'cl-community-stats' ),
            'type' => 'number',
            'default' => 12,
            'min' => 1,
            'max' => 60,
            'hasDynamicData' => true,
        );

        $this->controls['metrics'] = array(
            'tab' => 'content',
            'group' => 'display',
            'label' => __( 'Metrics', 'cl-community-stats' ),
            'type' => 'select',
            'multiple' => true,
            'placeholder' => __( 'Select metrics', 'cl-community-stats' ),
            'default' => array(
                'median_sale_price',
                'months_of_inventory',
                'sale_to_list_ratio',
            ),
            'options' => array(
                'median_list_price'    => __( 'Median List Price', 'cl-community-stats' ),
                'median_sale_price'    => __( 'Median Sale Price', 'cl-community-stats' ),
                'sale_to_list_ratio'   => __( 'Sale-to-List Ratio', 'cl-community-stats' ),
                'months_of_inventory'  => __( 'Months of Inventory', 'cl-community-stats' ),
                'active_listing_count' => __( 'Active Listings', 'cl-community-stats' ),
                'closed_sales_count'   => __( 'Closed Sales', 'cl-community-stats' ),
            ),
        );

        $this->controls['display_mode'] = array(
            'tab' => 'content',
            'group' => 'display',
            'label' => __( 'Display Mode', 'cl-community-stats' ),
            'type' => 'select',
            'options' => array(
                'cards' => __( 'Cards', 'cl-community-stats' ),
                'inline' => __( 'Inline', 'cl-community-stats' ),
                'list' => __( 'List', 'cl-community-stats' ),
            ),
            'default' => 'cards',
        );

        $this->controls['empty_state'] = array(
            'tab' => 'content',
            'group' => 'display',
            'label' => __( 'Empty State', 'cl-community-stats' ),
            'type' => 'select',
            'options' => array(
                'hide' => __( 'Hide', 'cl-community-stats' ),
                'message' => __( 'Message', 'cl-community-stats' ),
            ),
            'default' => 'hide',
        );
    }

    public function render() {
        $presenter = new Community_Stats_Presenter();
        $post_id = (int) get_the_ID();

        $context_inputs = array(
            'geo_shape_id_input' => $this->resolve_dynamic_text_setting( 'geo_shape_id_input', $post_id ),
            'community_input' => $this->resolve_dynamic_text_setting( 'community_input', $post_id ),
            'community_key_input' => $this->resolve_dynamic_text_setting( 'community_key_input', $post_id ),
            'community_key' => $this->resolve_dynamic_text_setting( 'community_key', $post_id ),
        );

        $months = $presenter->resolve_months( $this->resolve_dynamic_text_setting( 'months', $post_id, '12' ) );
        $metrics = $presenter->resolve_metrics( $this->settings['metrics'] ?? array() );
        $display_mode = $presenter->resolve_display_mode( $this->settings['display_mode'] ?? 'cards' );
        $empty_state = $presenter->resolve_empty_state( $this->settings['empty_state'] ?? 'hide' );

        $result = $presenter->fetch_market_stats( $context_inputs, $months );

        if ( 'ok' === $result['state'] ) {
            $markup = $presenter->render_market_stats( $result['market'], $metrics, $display_mode, $empty_state );
        } else {
            $markup = $presenter->render_empty_state( $display_mode, $empty_state, __( 'Statistics unavailable for this context.', 'cl-community-stats' ) );
        }

        if ( '' === $markup ) {
            return;
        }

        echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function resolve_dynamic_text_setting( string $key, int $post_id, string $default = '' ): string {
        $raw = $this->settings[ $key ] ?? $default;

        if ( ! is_scalar( $raw ) ) {
            return '';
        }

        $resolved = (string) $raw;

        if ( method_exists( $this, 'render_dynamic_data' ) ) {
            $dynamic = $this->render_dynamic_data( $resolved, $post_id );
            if ( is_scalar( $dynamic ) ) {
                $resolved = (string) $dynamic;
            }
        } elseif ( function_exists( 'bricks_render_dynamic_data' ) ) {
            $dynamic = bricks_render_dynamic_data( $resolved, $post_id );
            if ( is_scalar( $dynamic ) ) {
                $resolved = (string) $dynamic;
            }
        }

        $resolved = trim( $resolved );
        if ( $this->is_unresolved_dynamic_placeholder( $resolved ) ) {
            return '';
        }

        return $resolved;
    }

    private function is_unresolved_dynamic_placeholder( string $value ): bool {
        return 1 === preg_match( '/^\{[a-z0-9_:-]+\}$/i', trim( $value ) );
    }
}
