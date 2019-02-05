<?php
/**
 * Plugin Name: HYP Woo Api
 * Plugin URI: https://github.com/hypericumimpex/hyp-woo-api/
 * Description: This plugin contains important functions and features for "WooCommerce API".
 * Version: 3.1.2
 * Author: Romeo C.
 * Author URI: https://github.com/hypericumimpex/
 * Text Domain: pgs-woo-api
 * WC requires at least: 3.0
 * WC tested up to: 3.5.0 
 */
if ( ! function_exists( 'is_plugin_active' ) ) {    
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
global $plugin_version;

$plugin_version = '3.1.2';

if( ! defined( 'PGS_API_PATH' ) ) define( 'PGS_API_PATH', plugin_dir_path( __FILE__ ) );
if( ! defined( 'PGS_API_URL' ) ) define( 'PGS_API_URL', plugin_dir_url( __FILE__ ) );
if( ! defined( 'PGS_ENVATO_API' ) ) define('PGS_ENVATO_API',  'https://envatoapi.potenzaglobalsolutions.com/');
if( ! defined( 'PGS_WOO_API' ) ) define('PGS_WOO_API', 'pgs-woo-api');
if (!is_plugin_active('woocommerce/woocommerce.php')) {    
    add_action('admin_notices', 'pgs_woo_api_admin_notice');
    return;
} else {
    // Plugin activation/deactivation hooks
    register_activation_hook( __FILE__, 'pgs_woo_api_activate' );
    register_deactivation_hook( __FILE__, 'pgs_woo_api_deactivate' );       
}

if (!is_plugin_active('rest-api/plugin.php')) {
    add_action('admin_notices', 'pgs_woo_api_admin_notice');
}

if (!is_plugin_active('rest-api-oauth1/oauth-server.php')) {
    add_action('admin_notices', 'pgs_woo_api_admin_notice');
}

add_action("admin_init", "pgs_woo_api_create_custom_tabels");
function pgs_woo_api_create_custom_tabels(){
    global $wpdb;
    if ( is_multisite() && $network_wide ) {
        // Get all blogs in the network and activate plugin on each one
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            //Create push-notification tabale for manage notifications
            pgs_woo_api_add_push_notification_tabel_schema();
            restore_current_blog();
        }
    } else {
        //Create push-notification tabale for manage notifications
        pgs_woo_api_add_push_notification_tabel_schema();
    }    
    $is_wpml_active = pgs_woo_api_is_wpml_active();           
    if($is_wpml_active){
        $initial_language = get_option( 'pgs_woo_api_wpml_initial_language_array' );                
        if(isset($initial_language) && !empty($initial_language)){
            return;    
        } else {                        
            $icl_get_languages = icl_get_languages();
            if(!empty($icl_get_languages)){                
                foreach($icl_get_languages as $key => $lan){
                    if($lan['active'] == '1'){                        
                        update_option( 'pgs_woo_api_wpml_initial_language_array',$icl_get_languages[$key] );
                        $initial_language = get_option( 'WPLANG' );
                        update_option( 'pgs_woo_api_wpml_initial_language',$initial_language );
                        break;
                    }
                }
            }            
        }
    }        
}



function pgs_woo_api_admin_notice() {
    
    $html  = '<div id="message" class="error fade"><p style="line-height: 150%">';
    $html .= '<strong>'.esc_html__("PGS Woo Api","pgs-woo-api").'</strong><p>';
    $html .= esc_html__("Requires the following plugins to be activated","pgs-woo-api").'<br />';
    
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        $html .= esc_html__("Please","pgs-woo-api").' <a href="https://wordpress.org/plugins/woocommerce/" target="_blank"> '.esc_html__("install / activate","pgs-woo-api").' </a> WooCommerce '.esc_html__("plugin","pgs-woo-api").'.'.'<br />';    
    }
    $restapi = pgs_woo_api_is_plugin_installed('rest-api');
    if(!$restapi){
        $html .= esc_html__("Please","pgs-woo-api").' <a href="'.admin_url('themes.php?page=theme-plugins').'"> '.esc_html__("install","pgs-woo-api").' </a> WordPress REST API (Version 2) '.esc_html__("plugin","pgs-woo-api").'.'.'<br />';        
    } else {
        if(!is_plugin_active('rest-api/plugin.php')){
            $html .= esc_html__("Please","pgs-woo-api").' <a href="'.admin_url('themes.php?page=theme-plugins').'"> '.esc_html__("activate","pgs-woo-api").' </a> WordPress REST API (Version 2) '.esc_html__("plugin","pgs-woo-api").'.'.'<br />';    
        }    
    }
    $restapi = pgs_woo_api_is_plugin_installed('rest-api-oauth1');
    if(!$restapi){
        $html .= esc_html__("Please","pgs-woo-api").' <a href="'.admin_url('themes.php?page=theme-plugins').'"> '.esc_html__("install","pgs-woo-api").' </a> WP REST API - OAuth 1.0a Server '.esc_html__("plugin","pgs-woo-api").'.';    
    } else {
        if(!is_plugin_active('rest-api-oauth1/oauth-server.php')){
            $html .= esc_html__("Please","pgs-woo-api").' <a href="'.admin_url('themes.php?page=theme-plugins').'"> '.esc_html__("activate","pgs-woo-api").' </a> WP REST API - OAuth 1.0a Server '.esc_html__("plugin","pgs-woo-api").'.';    
        }        
    }
    $html .= '</p></div>';
    echo $html;
}


/**
 * The code that runs during plugin activation.
 */
function pgs_woo_api_activate() {
    $checkout_page = '';
    $checkout_page = get_option('pgs_checkout_page');
    if(empty($checkout_page)){
        $pgs_page = array(
            'post_type' => 'page',
            'post_title'    => 'App Checkout',
            'post_content'  => '[woocommerce_checkout]',
            'post_status'   => 'publish',
        );
        // Insert the page into the database.
        $pageid = wp_insert_post( $pgs_page );
        if($pageid){
            update_option('pgs_checkout_page', $pageid);
        }
    }
}

/**
 * Check plugin is installed
 */ 
function pgs_woo_api_is_plugin_installed($search){
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugins = get_plugins();
	$plugins = array_filter( array_keys($plugins), function($k){
		if( strpos($k, '/') !== false ) return true;
	});
	$plugins_stat = function($plugins, $search){
		$new_plugins = array();
		foreach($plugins as $plugin){
			$new_plugins_data = explode('/', $plugin);
			$new_plugins[] = $new_plugins_data[0];
		}        
        return in_array($search, $new_plugins);
	};
	return $plugins_stat($plugins, $search);
}

/**
 * The code that runs during plugin deactivation.
 */
function pgs_woo_api_deactivate() {
	// TODO: Add settings for plugin deactivation
}
add_action('plugins_loaded', 'wan_load_textdomain');
function wan_load_textdomain() {
    load_plugin_textdomain( 'pgs-woo-api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Includes for Admin
if ( is_admin() ) {
	require_once PGS_API_PATH.'/inc/tgm-plugin-activation/tgm-init.php';// Load TGM Plugin compatibility file.
}

require_once( PGS_API_PATH . 'inc/woo-api-functions.php' );
require_once( PGS_API_PATH . 'inc/sample_data/sample_data_functions.php' );
require_once( PGS_API_PATH . 'inc/classes/class-pgs-woo-api-support.php' );

require_once( PGS_API_PATH . 'inc/meta-box/pgs_custom_cat_thumbmail.php' );
require_once( PGS_API_PATH . 'inc/meta-box/pgs_custom_colorpiker.php' );
require_once( PGS_API_PATH . 'inc/classes/class-pgs-woo-api-controller.php' );
require_once( PGS_API_PATH . 'inc/meta-box/add_user_meta.php' );
require_once( PGS_API_PATH . 'inc/options-pages/option_page.php' );
require_once( PGS_API_PATH . 'inc/options-pages/setting_page.php' );
require_once( PGS_API_PATH . 'inc/options-pages/geo-fencing.php' );
require_once( PGS_API_PATH . 'inc/support/templates/support.php' );
require_once( PGS_API_PATH . 'inc/setup-wizard/wizard.php' );

require_once( PGS_API_PATH . 'inc/meta-box/add_coupon_meta.php' );
require_once( PGS_API_PATH . 'inc/meta-box/add_order_status_meta.php' );
require_once( PGS_API_PATH . 'inc/classes/class-pgs-woo-api-test-controller.php' );
require_once( PGS_API_PATH . 'inc/classes/class-pgs-woo-api-token-generations-controller.php' );
require_once( PGS_API_PATH . 'inc/classes/class-pgs-woo-api-rewards-controller.php' );

require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-home-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-login-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-logout-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-password-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-products-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-live-search-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-reviews-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-cart-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-attributes-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-deactiveuser-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-orders-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-coupons-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-wishlist-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-user-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-info-pages-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-static-pages-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-contactus-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-push-notification-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-seller-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-geofencing-controller.php' );
require_once( PGS_API_PATH . 'endpoints/class-pgs-woo-api-rewards-points-controller.php' );
require_once( PGS_API_PATH . 'test_api_shortcode.php' );