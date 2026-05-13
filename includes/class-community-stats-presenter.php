<?php

namespace CL_Community_Stats;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Community_Stats_Presenter {

    private const METRIC_DEFINITIONS = array(
        'median_list_price'    => 'Median List Price',
        'median_sale_price'    => 'Median Sale Price',
        'sale_to_list_ratio'   => 'Sale-to-List Ratio',
        'months_of_inventory'  => 'Months of Inventory',
        'active_listing_count' => 'Active Listings',
        'closed_sales_count'   => 'Closed Sales',
    );

    private const DEFAULT_METRICS = array(
        'median_sale_price',
        'months_of_inventory',
        'sale_to_list_ratio',
    );

    private const DISPLAY_MODES = array( 'cards', 'inline', 'list' );

    private const EMPTY_STATES = array( 'hide', 'message' );

    private static $missing_context_logged = false;

    private static $invalid_response_logged = false;

    public function get_metric_definitions(): array {
        return self::METRIC_DEFINITIONS;
    }

    public function resolve_months( $raw_months ): int {
        $months = is_scalar( $raw_months ) ? (int) $raw_months : 12;
        return max( 1, min( 60, $months ) );
    }

    public function resolve_metrics( $raw_metrics ): array {
        $requested = array();

        if ( is_string( $raw_metrics ) ) {
            $requested = array_map( 'trim', explode( ',', $raw_metrics ) );
        } elseif ( is_array( $raw_metrics ) ) {
            foreach ( $raw_metrics as $metric ) {
                if ( is_scalar( $metric ) ) {
                    $requested[] = trim( (string) $metric );
                }
            }
        }

        if ( empty( $requested ) ) {
            return self::DEFAULT_METRICS;
        }

        $allowed = array();
        foreach ( $requested as $metric ) {
            if ( isset( self::METRIC_DEFINITIONS[ $metric ] ) ) {
                $allowed[] = $metric;
            }
        }

        if ( empty( $allowed ) ) {
            return self::DEFAULT_METRICS;
        }

        return array_values( array_unique( $allowed ) );
    }

    public function resolve_display_mode( $raw_mode ): string {
        $mode = is_scalar( $raw_mode ) ? strtolower( trim( (string) $raw_mode ) ) : '';
        return in_array( $mode, self::DISPLAY_MODES, true ) ? $mode : 'cards';
    }

    public function resolve_empty_state( $raw_empty_state ): string {
        $empty_state = is_scalar( $raw_empty_state ) ? strtolower( trim( (string) $raw_empty_state ) ) : '';
        return in_array( $empty_state, self::EMPTY_STATES, true ) ? $empty_state : 'hide';
    }

    public function fetch_market_stats( array $context_inputs, int $months ): array {
        $query_args = $this->build_query_args( $context_inputs, $months );
        if ( empty( $query_args ) ) {
            $this->log_missing_context();

            return array(
                'state' => 'empty',
                'market' => array(),
                'reason' => 'missing_context',
            );
        }

        $endpoint = rest_url( 'cl-reso-link/v1/stats/community' );
        $url = add_query_arg( $query_args, $endpoint );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 10,
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->log_invalid_response( 'request_error' );
            return array(
                'state' => 'error',
                'market' => array(),
                'reason' => 'request_error',
            );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            $this->log_invalid_response( 'http_' . $status );
            return array(
                'state' => 'error',
                'market' => array(),
                'reason' => 'http_error',
            );
        }

        $body = wp_remote_retrieve_body( $response );
        if ( ! is_string( $body ) || '' === $body ) {
            $this->log_invalid_response( 'empty_body' );
            return array(
                'state' => 'error',
                'market' => array(),
                'reason' => 'empty_body',
            );
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) ) {
            $this->log_invalid_response( 'invalid_json' );
            return array(
                'state' => 'error',
                'market' => array(),
                'reason' => 'invalid_json',
            );
        }

        if ( $this->is_soft_failure_payload( $decoded ) ) {
            return array(
                'state' => 'empty',
                'market' => array(),
                'reason' => 'soft_failure',
            );
        }

        $market = array();
        if ( isset( $decoded['market'] ) && is_array( $decoded['market'] ) ) {
            $market = $decoded['market'];
        } elseif ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) && isset( $decoded['data']['market'] ) && is_array( $decoded['data']['market'] ) ) {
            $market = $decoded['data']['market'];
        }

        if ( empty( $market ) ) {
            $this->log_invalid_response( 'invalid_shape' );
            return array(
                'state' => 'error',
                'market' => array(),
                'reason' => 'invalid_shape',
            );
        }

        return array(
            'state' => 'ok',
            'market' => $market,
            'reason' => '',
        );
    }

    public function render_market_stats( array $market, array $metrics, string $display_mode, string $empty_state ): string {
        $mode = $this->resolve_display_mode( $display_mode );
        $items_markup = '';

        foreach ( $metrics as $metric ) {
            if ( ! isset( self::METRIC_DEFINITIONS[ $metric ] ) ) {
                continue;
            }

            if ( ! array_key_exists( $metric, $market ) ) {
                continue;
            }

            $formatted = $this->format_metric_value( $metric, $market[ $metric ] );
            if ( '' === $formatted ) {
                continue;
            }

            $items_markup .= $this->render_metric_item( self::METRIC_DEFINITIONS[ $metric ], $formatted, $mode );
        }

        if ( '' === $items_markup ) {
            return $this->render_empty_state( $mode, $empty_state, __( 'No statistics available.', 'cl-community-stats' ) );
        }

        return $this->render_wrapper( $mode, $items_markup );
    }

    public function render_empty_state( string $display_mode, string $empty_state, string $message ): string {
        $mode = $this->resolve_display_mode( $display_mode );
        $state = $this->resolve_empty_state( $empty_state );

        if ( 'message' !== $state ) {
            return '';
        }

        $empty_markup = '<div class="cl-community-stats__empty">' . esc_html( $message ) . '</div>';
        return $this->render_wrapper( $mode, $empty_markup );
    }

    private function build_query_args( array $context_inputs, int $months ): array {
        $query = array(
            'months' => max( 1, min( 60, $months ) ),
        );

        $geo_shape_id = $this->sanitize_geo_shape_id( $context_inputs['geo_shape_id_input'] ?? '' );
        if ( '' !== $geo_shape_id ) {
            $query['geo_shape_id'] = $geo_shape_id;
            return $query;
        }

        $community = $this->sanitize_context_token( $context_inputs['community_input'] ?? '' );
        if ( '' !== $community ) {
            $query['community'] = $community;
            return $query;
        }

        $community_key = $this->sanitize_context_token( $context_inputs['community_key_input'] ?? '' );
        if ( '' === $community_key ) {
            $community_key = $this->sanitize_context_token( $context_inputs['community_key'] ?? '' );
        }

        if ( '' !== $community_key ) {
            $query['community_key'] = $community_key;
            return $query;
        }

        return array();
    }

    private function sanitize_geo_shape_id( $value ): string {
        $token = $this->sanitize_context_token( $value );

        if ( '' === $token ) {
            return '';
        }

        if ( strlen( $token ) > 64 ) {
            return '';
        }

        return $token;
    }

    private function sanitize_context_token( $value ): string {
        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $token = strtolower( trim( sanitize_text_field( (string) $value ) ) );
        if ( '' === $token ) {
            return '';
        }

        if ( 1 !== preg_match( '/^[a-z0-9_-]+$/', $token ) ) {
            return '';
        }

        return $token;
    }

    private function format_metric_value( string $metric, $value ): string {
        if ( null === $value ) {
            return '';
        }

        if ( is_string( $value ) && '' === trim( $value ) ) {
            return '';
        }

        if ( ! is_numeric( $value ) ) {
            return is_scalar( $value ) ? trim( (string) $value ) : '';
        }

        $numeric = (float) $value;

        switch ( $metric ) {
            case 'median_list_price':
            case 'median_sale_price':
                return '$' . number_format( (int) round( $numeric ) );

            case 'sale_to_list_ratio':
                return number_format( $numeric * 100, 1, '.', '' ) . '%';

            case 'months_of_inventory':
                return $this->format_trimmed_decimal( $numeric, 1 );

            case 'active_listing_count':
            case 'closed_sales_count':
                return number_format( (int) round( $numeric ) );
        }

        return (string) $value;
    }

    private function render_wrapper( string $display_mode, string $inner_markup ): string {
        $mode = $this->resolve_display_mode( $display_mode );
        $class = 'cl-community-stats cl-community-stats--' . $mode;

        if ( 'list' === $mode ) {
            return '<ul class="' . esc_attr( $class ) . '">' . $inner_markup . '</ul>';
        }

        return '<div class="' . esc_attr( $class ) . '">' . $inner_markup . '</div>';
    }

    private function render_metric_item( string $label, string $value, string $display_mode ): string {
        $item_markup = '<div class="cl-community-stats__value">' . esc_html( $value ) . '</div>'
            . '<div class="cl-community-stats__label">' . esc_html( $label ) . '</div>';

        if ( 'list' === $display_mode ) {
            return '<li class="cl-community-stats__item">' . $item_markup . '</li>';
        }

        return '<div class="cl-community-stats__item">' . $item_markup . '</div>';
    }

    private function format_trimmed_decimal( float $value, int $precision ): string {
        $formatted = number_format( $value, $precision, '.', '' );
        return rtrim( rtrim( $formatted, '0' ), '.' );
    }

    private function is_soft_failure_payload( array $payload ): bool {
        if ( ! isset( $payload['state'] ) || ! is_string( $payload['state'] ) ) {
            return false;
        }

        $state = strtolower( trim( $payload['state'] ) );
        return in_array( $state, array( 'no_context', 'invalid_context', 'engine_error' ), true );
    }

    private function log_missing_context(): void {
        if ( self::$missing_context_logged ) {
            return;
        }

        error_log( '[CL Community Stats] Missing required context; rendering empty state.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        self::$missing_context_logged = true;
    }

    private function log_invalid_response( string $reason ): void {
        if ( self::$invalid_response_logged ) {
            return;
        }

        error_log( '[CL Community Stats] Invalid stats response (' . $reason . '); rendering empty state.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        self::$invalid_response_logged = true;
    }
}
