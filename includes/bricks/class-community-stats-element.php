<?php

namespace CL_Community_Stats\Bricks;

use Bricks\Element;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Community_Stats_Element extends Element {

    public $name = 'cl-community-stats';
    public $category = 'general';
    public $icon = 'ti-stats-up';
    private static $missing_community_key_logged = false;
    private static $invalid_response_logged = false;

    public function get_label() {
        return __( 'Community Stats', 'cl-community-stats' );
    }

    public function get_keywords() {
        return array( 'community', 'stats', 'market', 'cl' );
    }

    public function set_controls() {
        $this->controls['community_key'] = [
            'tab' => 'content',
            'label' => 'Community Key',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'hasDynamicData' => true,
        ];

        $this->controls['months'] = array(
            'label' => __( 'Months', 'cl-community-stats' ),
            'type' => 'number',
            'default' => 12,
            'min' => 1,
            'hasDynamicData' => true,
        );
    }

    public function render() {
        $community_key = $this->settings['community_key'] ?? '';
        $community_key = $this->render_dynamic_value( $community_key );
        $community_key = sanitize_text_field( $community_key );

        $months_raw = isset( $this->settings['months'] ) ? $this->settings['months'] : 12;
        $months = intval( $this->render_dynamic_value( $months_raw ) );
        $months = max( 1, min( 60, $months ) );

        $stats = [
            'median_sale_price' => [
                'label' => 'Median Sale Price',
                'formatter' => 'format_currency_compact',
            ],
            'months_of_inventory' => [
                'label' => 'Months of Inventory',
                'formatter' => 'format_number_1dp',
            ],
            'sale_to_list_ratio' => [
                'label' => 'Sale-to-List Ratio',
                'formatter' => 'format_percent_1dp',
            ],
        ];

        if ( '' === $community_key ) {
            $this->log_missing_community_key();
            $this->render_stats_grid( array(), $stats );
            return;
        }

        $market = $this->fetch_market_data( $community_key, $months );
        $this->render_stats_grid( $market, $stats );
    }

    private function render_stats_grid( array $market, array $stats ): void {
        echo '<div class="cl-stats-grid">';
        foreach ( $stats as $key => $config ) {
            $formatter = $config['formatter'] ?? '';
            $raw = array_key_exists( $key, $market ) ? $market[ $key ] : null;
            $value = method_exists( __CLASS__, $formatter ) ? self::{$formatter}( $raw ) : '';
            $this->render_stat_card( (string) $config['label'], $value );
        }
        echo '</div>';
    }

    private function render_stat_card( string $label, string $value ): void {
        echo '<div class="cl-stat">';
        echo '<div class="cl-stat__label">' . esc_html( $label ) . '</div>';
        echo '<div class="cl-stat__value">' . esc_html( $value ) . '</div>';
        echo '</div>';
    }

    private function fetch_market_data( string $community_key, int $months ): array {
        if ( '' === $community_key ) {
            return array();
        }

        $endpoint = rest_url( 'cl-reso-link/v1/stats/community' );
        $url = add_query_arg(
            array(
                'community_key' => $community_key,
                'months' => $months,
            ),
            $endpoint
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 10,
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->log_invalid_response( 'request_error' );
            return array();
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            $this->log_invalid_response( 'http_' . $status );
            return array();
        }

        $body = wp_remote_retrieve_body( $response );
        if ( ! is_string( $body ) || '' === $body ) {
            $this->log_invalid_response( 'empty_body' );
            return array();
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) ) {
            $this->log_invalid_response( 'invalid_json' );
            return array();
        }

        if ( $this->is_soft_failure_payload( $decoded ) ) {
            return array();
        }

        if ( ! isset( $decoded['data'] ) || ! is_array( $decoded['data'] ) || ! array_key_exists( 'market', $decoded['data'] ) ) {
            $this->log_invalid_response( 'invalid_shape' );
            return array();
        }

        $market = $decoded['data']['market'];
        if ( ! is_array( $market ) ) {
            $this->log_invalid_response( 'invalid_shape' );
            return array();
        }

        return $market;
    }

    private function render_dynamic_value( $value ): string {
        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $resolved = (string) $value;

        if ( function_exists( 'bricks_render_dynamic_data' ) ) {
            $dynamic = bricks_render_dynamic_data( $resolved, get_the_ID() );
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

    public static function format_currency_compact( $value ): string {
        if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
            return '';
        }

        $number = (float) $value;
        $sign = $number < 0 ? '-' : '';
        $abs = abs( $number );

        if ( $abs >= 1000000 ) {
            $compact = self::trim_trailing_zero( number_format( $abs / 1000000, 1, '.', '' ) );
            return $sign . '$' . $compact . 'M';
        }

        if ( $abs >= 1000 ) {
            $compact = self::trim_trailing_zero( number_format( $abs / 1000, 1, '.', '' ) );
            return $sign . '$' . $compact . 'K';
        }

        return $sign . '$' . number_format( $abs, 0, '.', ',' );
    }

    public static function format_percent_1dp( $value ): string {
        if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
            return '';
        }

        return number_format( (float) $value * 100, 1, '.', '' ) . '%';
    }

    public static function format_number_1dp( $value ): string {
        if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
            return '';
        }

        return number_format( (float) $value, 1, '.', '' );
    }

    private static function trim_trailing_zero( string $value ): string {
        if ( false === strpos( $value, '.' ) ) {
            return $value;
        }

        $value = rtrim( $value, '0' );
        return rtrim( $value, '.' );
    }

    private function is_soft_failure_payload( array $payload ): bool {
        if ( ! isset( $payload['state'] ) || ! is_string( $payload['state'] ) ) {
            return false;
        }

        $state = strtolower( trim( $payload['state'] ) );
        return in_array( $state, array( 'no_context', 'invalid_context', 'engine_error' ), true );
    }

    private function is_unresolved_dynamic_placeholder( string $value ): bool {
        return 1 === preg_match( '/^\{[a-z0-9_:-]+\}$/i', trim( $value ) );
    }

    private function log_missing_community_key(): void {
        if ( self::$missing_community_key_logged ) {
            return;
        }

        error_log( '[CL Community Stats] Missing required community_key; rendering empty state.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        self::$missing_community_key_logged = true;
    }

    private function log_invalid_response( string $reason ): void {
        if ( self::$invalid_response_logged ) {
            return;
        }

        error_log( '[CL Community Stats] Invalid stats response (' . $reason . '); rendering empty state.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        self::$invalid_response_logged = true;
    }
}
