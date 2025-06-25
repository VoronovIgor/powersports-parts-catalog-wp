<?php
/**
 * Plugin Name: ZCatalog – Powersports Spare‑Parts E-Catalog
 * Plugin URI: https://zapscript.net
 * Description: Multi-level spare parts e-catalog (Groups → Brands → Years → Models → Bodies → Parts). Includes diagrams of spare parts for: ATVs, FL Models, Motorcycles, Personal Watercraft, Scooters, Side x Side, Snowmobiles, Utilities, Watercrafts, GEM® Electric, Slingshot, Lawn Tractor, Multi-Purpose Engine, Outdoor Power Equipment, Race Kart, Sport Boat, WaveRunner, Roadster, Boats.
 * Version: 2.1.0
 * Author: ZapscriptNet
 * Author URI: https://zapscript.net
 * Text Domain: zcatalog
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ZCATALOG_API_BASE           = 'https://techcat.hitd.ru/api/tech/';
const ZCATALOG_OPTION_KEY         = 'zcatalog_api_key';
const ZCATALOG_PLACEHOLDER_KEY    = 'zcatalog_placeholder_url';
const ZCATALOG_PART_URL_TEMPLATE  = 'zcatalog_part_url';

class ZCatalog {
    private static ?self $inst = null;

    public static function instance(): self {
        return self::$inst ??= new self();
    }

    public static function activate(): void {
        add_option( ZCATALOG_PLACEHOLDER_KEY, plugins_url( 'assets/model.png', __FILE__ ) );
        add_option( ZCATALOG_PART_URL_TEMPLATE, '/search?@article' );
    }

    public static function deactivate(): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_zcatalog%' ) );
    }

    public static function uninstall(): void {
        delete_option( ZCATALOG_OPTION_KEY );
        delete_option( ZCATALOG_PLACEHOLDER_KEY );
        delete_option( ZCATALOG_PART_URL_TEMPLATE );
    }

    private function __construct() {
        add_action( 'plugins_loaded', fn() => load_plugin_textdomain( 'zcatalog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ) );
        add_action( 'admin_menu',  [ $this, 'settings_page' ] );
        add_action( 'admin_init',  [ $this, 'register_settings' ] );
        add_shortcode( 'zcatalog', [ $this, 'shortcode' ] );
        add_action( 'wp_enqueue_scripts', fn() => wp_enqueue_style(
            'zcatalog-style',
            plugins_url( 'assets/zcatalog.css', __FILE__ ),
            [],
            '2.1.0'
        ));
    }

    public function settings_page(): void {
        add_options_page(
            'ZCatalog',
            'ZCatalog',
            'manage_options',
            'zcatalog',
            function() {
                ?>
                <div class="wrap">
                    <h1><?php _e('ZCatalog', 'zcatalog'); ?></h1>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'zcatalog_settings' );
                        do_settings_sections( 'zcatalog' );
                        submit_button();
                        ?>
                    </form>
                </div>
                <?php
            }
        );
    }

    public function register_settings(): void {
        register_setting( 'zcatalog_settings', ZCATALOG_OPTION_KEY, [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('zcatalog_settings', 'zcatalog_part_url', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_section(
            'zcatalog_sec',
            __('Settings', 'zcatalog'),
            '__return_false',
            'zcatalog'
        );

        add_settings_section(
            'zcatalog_info',
            __('Information', 'zcatalog'),
            function() {
                echo '<p>';
                echo __('To obtain an API key, please visit ', 'zcatalog');
                echo '<a href="https://zapscript.net" target="_blank" rel="noopener noreferrer">https://zapscript.net</a> ';
                echo __('or contact Telegram ', 'zcatalog');
                echo '<a href="https://t.me/zplandev" target="_blank" rel="noopener noreferrer">@zplandev</a>.';
                echo '</p>';
            },
            'zcatalog'
        );


        add_settings_field(
            ZCATALOG_OPTION_KEY,
            __('API Key', 'zcatalog'),
            function() {
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text"/>',
                    esc_attr( ZCATALOG_OPTION_KEY ),
                    esc_attr( get_option( ZCATALOG_OPTION_KEY, '' ) )
                );
            },
            'zcatalog',
            'zcatalog_sec'
        );

        add_settings_field(
            'zcatalog_part_url',
            __('Part URL (use @article)', 'zcatalog'),
            function() {
                $value = get_option('zcatalog_part_url', '/search?@article');
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text" placeholder="/search?@article" />',
                    esc_attr('zcatalog_part_url'),
                    esc_attr($value)
                );
                echo '<p class="description">' . __('Enter the URL where the part article should redirect to. Example: /search?query=@article', 'zcatalog') . '</p>';
            },
            'zcatalog',
            'zcatalog_sec'
        );
    }

    public function shortcode(): string {
        $route = [
            'group_id' => absint( $_GET['group_id'] ?? 0 ),
            'brand_id' => absint( $_GET['brand_id'] ?? 0 ),
            'year_id'  => absint( $_GET['year_id'] ?? 0 ),
            'model_id' => absint( $_GET['model_id'] ?? 0 ),
            'body_id'  => absint( $_GET['body_id'] ?? 0 ),
        ];

        ob_start();
        echo '<div class="zcatalog-wrapper">';

        if ( $route['body_id'] ) {
            $this->render_parts( $route['body_id'] );
        } elseif ( $route['model_id'] ) {
            $this->render_bodies( $route['model_id'] );
        } elseif ( $route['year_id'] ) {
            $this->render_models( $route['year_id'] );
        } elseif ( $route['brand_id'] ) {
            $this->render_years( $route['brand_id'] );
        } elseif ( $route['group_id'] ) {
            $this->render_brands( $route['group_id'] );
        } else {
            $this->render_groups();
        }

        echo '</div>';
        return ob_get_clean();
    }

    private function render_groups(): void {
        $response = $this->api_post('get-all-groups');
        $groups = $response['groups'] ?? [];

        echo '<h2>' . esc_html__('Vehicle Groups', 'zcatalog') . '</h2>';

        if (empty($groups)) {
            echo '<p>' . esc_html__('No data available.', 'zcatalog') . '</p>';
            return;
        }

        echo '<div class="zcatalog-grid">';
        foreach ($groups as $group) {
            $url = add_query_arg('group_id', $group['id']);
            $name = esc_html($group['group_name']);

            printf(
                '<a href="%s" class="zcatalog-tile">%s</a>',
                esc_url($url),
                $name
            );
        }
        echo '</div>';
    }

    private function render_brands(int $group_id): void {
        $data = $this->api_post('get-brands-for-group', ['groupId' => $group_id]);

        echo '<div class="zcatalog-step"><h2>' . esc_html__('Brands', 'zcatalog') . '</h2>';
        echo '<div class="zcatalog-grid">';

        foreach ($data['brands'] ?? [] as $brand) {
            $url = add_query_arg(['group_id' => $group_id, 'brand_id' => $brand['id']]);
            $name = esc_html($brand['brand_name']);

            printf(
                '<a href="%s" class="zcatalog-tile">%s</a>',
                esc_url($url),
                $name
            );
        }

        echo '</div></div>';
    }

    private function render_years(int $brand_id): void {
        $data = $this->api_post('get-years-for-brand', ['brandId' => $brand_id]);
        $years = $data['years'] ?? [];
        usort($years, fn($a, $b) => intval($b['year']) <=> intval($a['year']));

        echo '<div class="zcatalog-step"><h2>' . esc_html__('Production Years', 'zcatalog') . '</h2>';
        echo '<div class="zcatalog-grid">';

        foreach ($years as $year) {
            $url = add_query_arg(['brand_id' => $brand_id, 'year_id' => $year['id']]);
            $yearText = esc_html($year['year']);

            printf(
                '<a href="%s" class="zcatalog-tile">%s</a>',
                esc_url($url),
                $yearText
            );
        }

        echo '</div></div>';
    }

    private function render_models(int $year_id): void {
        $data = $this->api_post('get-models-for-year', ['yearId' => $year_id]);

        echo '<div class="zcatalog-step"><h2>' . esc_html__('Models', 'zcatalog') . '</h2>';
        echo '<div class="zcatalog-grid">';

        $fallback_img = esc_url(plugins_url('assets/model.png', __FILE__));

        foreach ($data['models'] ?? [] as $model) {
            $url  = add_query_arg(['year_id' => $year_id, 'model_id' => $model['id']]);
            $name = esc_html($model['model_name']);

            printf(
                '<a href="%s" class="zcatalog-tile">
                <img src="%s" alt="%s" loading="lazy" class="zcatalog-thumb" style="max-width:50px; height:auto;" />
                <div class="zcatalog-tile-title">%s</div>
            </a>',
                esc_url($url),
                $fallback_img,
                $name,
                $name
            );
        }

        echo '</div></div>';
    }

    private function render_bodies(int $model_id): void {
        $data = $this->api_post('get-body-for-model', ['modelId' => $model_id]);

        echo '<div class="zcatalog-step"><h2>' . esc_html__('Assemblies / Schemes', 'zcatalog') . '</h2>';
        echo '<div class="zcatalog-grid">';

        foreach ($data['model_bodies'] ?? [] as $body) {
            $url     = add_query_arg(['model_id' => $model_id, 'body_id' => $body['id']]);
            $name    = esc_html($body['body_name']);
            $preview = esc_url($body['body_image_preview'] ?? '');

            printf(
                '<a href="%s" class="zcatalog-tile">
                <img src="%s" alt="%s" loading="lazy" class="zcatalog-thumb" />
                <div class="zcatalog-tile-title">%s</div>
            </a>',
                esc_url($url),
                $preview,
                $name,
                $name
            );
        }

        echo '</div></div>';
    }

    private function render_parts(int $body_id): void {
        $data = $this->api_post('get-parts-for-body', ['bodyId' => $body_id]);
        echo '<div class="zcatalog-step"><h2>' . esc_html__('Parts', 'zcatalog') . '</h2>';

        if (empty($data['parts'])) {
            echo '<p>' . esc_html__('No parts available.', 'zcatalog') . '</p></div>';
            return;
        }

        $part_url_template = get_option('zcatalog_part_url', '/search?@article');

        echo '<div class="zcatalog-parts-wrapper">';
        echo '<div class="zcatalog-parts-table"><table class="zcatalog-table">
    <thead><tr>
        <th>#</th>
        <th>' . esc_html__('Name', 'zcatalog') . '</th>
        <th>' . esc_html__('Article', 'zcatalog') . '</th>
        <th>' . esc_html__('Qty', 'zcatalog') . '</th>
        <th>' . esc_html__('Action', 'zcatalog') . '</th>
    </tr></thead><tbody>';

        foreach ($data['parts'] as $p) {
            $article = esc_html($p['part_article']);
            $part_url = str_replace('@article', rawurlencode($p['part_article']), $part_url_template);
            $part_url_esc = esc_url($part_url);

            printf(
                '<tr>
                <td>%s</td>
                <td>%s</td>
                <td><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></td>
                <td>%s</td>
                <td><a href="%s" class="btn-order" target="_blank" rel="noopener noreferrer">%s</a></td>
            </tr>',
                esc_html($p['part_position']),
                esc_html($p['part_name']),
                $part_url_esc,
                $article,
                esc_html($p['part_count']),
                $part_url_esc,
                esc_html__('Order', 'zcatalog')
            );
        }

        echo '</tbody></table></div>';
        echo '<div class="zcatalog-parts-images">';

        if (!empty($data['image_body'])) {
            printf(
                '<div class="single_body_image"><img src="%s" alt="%s"></div>',
                esc_url($data['image_body']),
                esc_attr__('Diagram', 'zcatalog')
            );
        } else {
            echo '<p>' . esc_html__('No image available.', 'zcatalog') . '</p>';
        }

        echo '</div></div></div>';
    }


    private function api_post( string $endpoint, array $body = [] ): array {
        $key = get_option( ZCATALOG_OPTION_KEY, '' );
        if ( ! $key ) {
            return [];
        }

        $body['key'] = $key;

        $max_attempts  = 3;
        $delay_seconds = 30;

        for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
            $response = wp_remote_post( ZCATALOG_API_BASE . $endpoint, [
                'body'    => $body,
                'timeout' => 15,
            ]);

            if ( ! is_wp_error( $response ) ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $data ) ) {
                    return $data;
                }
            }

            if ( $attempt < $max_attempts ) {
                sleep( $delay_seconds );
            }
        }

        return [];
    }
}

add_action( 'init', [ 'ZCatalog', 'instance' ] );

function zcatalog_display(): void {
    echo do_shortcode( '[zcatalog]' );
}

register_activation_hook( __FILE__,   [ 'ZCatalog', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'ZCatalog', 'deactivate' ] );
register_uninstall_hook( __FILE__,    [ 'ZCatalog', 'uninstall' ] );
