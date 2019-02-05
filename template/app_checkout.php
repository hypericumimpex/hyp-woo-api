<?php
$input = file_get_contents("php://input");
$request = json_decode($input,true);

$cart_items = $request['cart_items'];
if(isset($request['os']) && $request['os'] == 'android'){
if(isset($cart_items)){
    if(!empty($cart_items)){
        // add cart contents
        WC()->cart->empty_cart();
        
        if(isset($request['user_id']) && !empty($request['user_id'])){
            $user_id = $request['user_id']; 
            $current_user_id = null;
            if(is_user_logged_in()){
                $current_user_id = get_current_user_id();    
            }            
            if($current_user_id != $user_id){
                //wp_destroy_current_session();
                //wp_clear_auth_cookie();
                $user = get_user_by( 'id', $user_id);            
                if( $user ) {
                    wp_set_current_user( $user_id, $user->data->user_login );
                    wp_set_auth_cookie( $user_id );
                    do_action( 'wp_login', $user->data->user_login,10);
                }
            }
        } else {
            if(!is_user_logged_in()){
                //wp_destroy_current_session();
                wp_clear_auth_cookie();    
            }
        }
        $cart_item_key = '';$device_type = 'Android';
        foreach ( $cart_items as $values ){
            $product_id = $values['product_id'];
            $quantity = $values['quantity'];
            $variation_id = 0; $variations = array();
            if(isset($values['variation_id']) && !empty($values['variation_id'])){
                $variation_id = $values['variation_id'];
                if(isset($values['variation']) && !empty($values['variation'])){
                    $variations = $values['variation'];
                }
            }
            $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations );            
        }        
    }
}}
pgs_woo_api_remove_admin_bar();?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr(get_bloginfo( 'charset' )); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="format-detection" content="telephone=no" />
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php echo esc_url(get_bloginfo( 'pingback_url' )); ?>">
<?php $app_color = pgs_woo_api_get_app_color();?>
<style>
.pgs-woo-api-app-checkout .woocommerce .woocommerce-checkout-review-order-table .order-total td{ color: <?php echo esc_attr($app_color['primary_color'])?> !important; }
.pgs-woo-api-app-checkout .woocommerce button, input[type="button"], input[type="submit"]{ background-color: <?php echo esc_attr($app_color['primary_color'])?> !important; }
.pgs-woo-api-app-checkout .woocommerce .input-text:focus { border-width: 2px; border-color: <?php echo esc_attr($app_color['primary_color'])?> !important; }
.pgs-woo-api-app-checkout .select2-container--default.select2-container--open li:hover{ background-color: <?php echo esc_attr($app_color['primary_color'])?> !important; }
.pgs-woo-api-app-checkout .select2-container--default.select2-container--open .select2-results__option--highlighted{ background-color: <?php echo esc_attr($app_color['primary_color'])?> !important; }
.pgs-woo-api-app-checkout .woocs_auto_switcher{ display: none;}
</style>
<?php wp_head(); ?>
</head>
<body <?php body_class('pgs-woo-api-app-checkout');?>>
<?php remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
add_filter( 'woocommerce_checkout_registration_enabled', function( $data ) { return $data = 0; });
/**
 * Hook: pgs_woo_api_app_checkout_before_main_content.
 */
do_action( 'pgs_woo_api_app_checkout_before_main_content' );
    /**
    * Hook: pgs_woo_api_app_checkout_content_wrapper_start.
    * @hooked pgs_woo_api_app_checkout_output_content_wrapper_start - 10
    */
    do_action( 'pgs_woo_api_app_checkout_content_wrapper_start' );
    
    
        /**
         * Hook: pgs_woo_api_app_checkout_.
         */
        do_action( 'pgs_woo_api_app_checkout_before_content_loop' );
        
        if ( have_posts() ) :
            if( !WC()->cart->is_empty() ){
                while ( have_posts() ) : the_post();
        			the_content();
        		endwhile; // End of the loop.
        	}else{
        		esc_html_e( 'Your cart is empty.', 'pgs-woo-api' );
        	}
        endif;
        
        /**
         * Hook: pgs_woo_api_app_checkout_after_content_loop.
         */
        do_action( 'pgs_woo_api_app_checkout_after_content_loop' );
    
    /**
    * Hook: pgs_woo_api_app_checkout_content_wrapper_end.
    * @hooked pgs_woo_api_app_checkout_output_content_wrapper_end - 10
    */
    do_action( 'pgs_woo_api_app_checkout_content_wrapper_end' );
    
/**
 * Hook: pgs_woo_api_app_checkout_after_main_content. 
 */
do_action( 'pgs_woo_api_app_checkout_after_main_content' );
wp_footer(); ?>
</body>
</html>