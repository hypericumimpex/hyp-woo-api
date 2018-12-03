<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PGS_WOO_API_SellerController extends PGS_WOO_API_Controller{
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pgs-woo-api/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'seller';
    	
	public function __construct() {
		$this->register_routes();        	
	}
	public function register_routes() {		
		add_action( 'rest_api_init', array( $this, 'pgs_woo_api_register_route'));
	}
	
	
	public function pgs_woo_api_register_route() {        
        
        register_rest_route( $this->namespace, $this->rest_base, array(
    		'methods' => WP_REST_Server::CREATABLE,//'POST',
    		'callback' => array( $this, 'pgs_woo_api_get_seller_store_details'),
            'permission_callback' => array($this, 'pgs_woo_api_permission_callback'),
    	) );
        
        register_rest_route( $this->namespace, 'contact_seller', array(
    		'methods' => WP_REST_Server::CREATABLE,//'POST',
    		'callback' => array( $this, 'pgs_woo_api_seller_contact_form'),
            'permission_callback' => array($this, 'pgs_woo_api_permission_callback'),
    	) );
        
    }
    
    
    /**
    * URL : http://yourdomain.com/wp-json/pgs-woo-api/v1/seller    
    * @param seller_id : ####
    */
    public function pgs_woo_api_get_seller_store_details(){
        
        $input = file_get_contents("php://input");
        $request = json_decode($input,true);
        
        $data = array(); $producs = array();        
        
        $seller_id = $request['seller_id'];
                
        $per_page = 10;
        if(isset($request['product-per-page'])) {
    		$per_page = $request['product-per-page'];
    	}
        
        $args = array(        
            'post_type' 			=> 'product',
    		'post_status' 			=> array( 'publish', 'private' ),
    		'ignore_sticky_posts'   => 1,        
    		'posts_per_page'		=> $per_page,            
            'author'                => $seller_id 
        );
        $page = 1;
        if(isset($request['page'])) {
    		$page = $request['page'];
            $args['paged'] = $page;
    	}
                
        $is_vendor = pgs_woo_api_is_vendor_plugin_active();
        
        
        if($is_vendor['vendor_count'] > 0){
            if($is_vendor['vendor_for'] == 'dokan'){
                $data['seller_info'] = $this->pgs_woo_api_get_dokan_vender_info($seller_id);
            } else {                
                $data['seller_info'] = $this->pgs_woo_api_get_wc_marketplace_vender_info($seller_id);    
            }            
        }
        
        
        $loop = new WP_Query( $args );
        $productsObj = new PGS_WOO_API_ProductsController;
        if($loop->have_posts()):
            while ( $loop->have_posts() ) : $loop->the_post();
                $product_id = $loop->post->ID;                
                $product_data = $productsObj->get_products_data($product_id);                
                $producs[] = $product_data;
            endwhile; 
            $data['products'] = $producs; 
            wp_reset_postdata();
        else :        
            $data['products'] = array();                
        endif;        
        return $data;
    }
    
    
    /**
     * Get Dokan seller revire list   
     */
    public function pgs_woo_api_get_dokan_seller_review_list ($seller_id){
        
        $is_vendor = pgs_woo_api_is_dokan_pro_active();
        if($is_vendor){
            $dokan_template_reviews = Dokan_Pro_Reviews::init();
            $id                     = $seller_id;
            $post_type              = 'product';
            $limit                  = 20;
            $status                 = '1';        
            $comments               = $dokan_template_reviews->comment_query( $id, $post_type, $limit, $status );
            
            $data = $this->pgs_woo_api_dokan_seller_tab_reviews_list($comments);    
        } else {
            $data = array();    
        }
        return $data;        
    }
    
    function pgs_woo_api_dokan_seller_tab_reviews_list($comments){
        
        if ( count( $comments ) == 0 ) {
            $html_data =  array();
        } else {
            foreach ( $comments as $single_comment ) {
                if ( $single_comment->comment_approved ) {
                    $GLOBALS['comment'] = $single_comment;
                    $comment_date       = get_comment_date( 'l, F jS, Y \a\t g:i a', $single_comment->comment_ID );
                    $comment_author_img = get_avatar( $single_comment->comment_author_email, 180 );
                    $permalink          = get_comment_link( $single_comment );
                    $get_rating = '';
                    
                    if ( get_option( 'woocommerce_enable_review_rating' ) == 'yes' ) :
                        $rating = intval( get_comment_meta( $single_comment->comment_ID, 'rating', true ) );
                        $get_rating = $rating;
                        
                    endif;
                    $verified = $single_comment->user_id == 0 ? '(Guest)' : '';                    
                    $html_data[] = array(
                        'permalink' => $permalink,
                        'rating' => $get_rating,
                        'comment_author' => $single_comment->comment_author,
                        'verified' => $verified,
                        'comment_date' => $comment_date,
                        'comment_content' => $single_comment->comment_content 
                    );
                }
            }
        }
        $review_list = $html_data;
        return $review_list;        
    }
    
    /**
    * Dokan plugin vendor info
    */    
    public function pgs_woo_api_get_dokan_vender_info($seller_id){        
        
        $info       = get_user_meta( $seller_id, 'dokan_profile_settings', true );        
        $data = array();
        if(isset($info) && !empty($info)){
            $store_info = dokan_get_store_info( $seller_id );             
            $banner_id  = isset( $store_info['banner'] ) ? $store_info['banner'] : 0;        
            $image_size = 'medium';
            $banner_url = ( $banner_id ) ? wp_get_attachment_url( $banner_id ) : DOKAN_PLUGIN_ASSEST . '/images/default-store-banner.png';
            //$featured_seller = get_user_meta( $seller->ID, 'dokan_feature_seller', true );
           
                    
            $avatar = esc_url( $this->pgs_woo_api_get_dokan_avtar( $seller_id ) );
            
            //check support status on off
            $contact_seller = $this->pgs_woo_api_contact_seller_status('dokan');
            
            $seller_address = dokan_get_seller_address( $seller_id );
            $seller_rating = dokan_get_seller_rating( $seller_id );
            $store_tnc = (isset($store_info['store_tnc']))?nl2br($store_info['store_tnc']):'';
            $data = array(
                'is_seller' => true, 
                'seller_id' => $seller_id,
                'store_name' => $store_info['store_name'],
                'address' => $store_info['address'],
                'seller_address' => $seller_address,
                'seller_rating' => $seller_rating,            
                'avatar' => $avatar,
                'banner_url' => $banner_url,
                'store_tnc' => $store_tnc,
                'contact_seller' => $contact_seller, 
                'review_list' => $this->pgs_woo_api_get_dokan_seller_review_list($seller_id)
            );
        } else {
            $data = array(
                'is_seller' => false                                 
            );    
        }
        return $data;
    }
    
    
    public function pgs_woo_api_get_dokan_avtar($seller_id){
        // see if there is a user_avatar meta field
        $avatar = get_avatar_url( $seller_id );
        $user_avatar = get_user_meta( $seller_id, 'dokan_profile_settings', true );
        $gravatar_id = isset( $user_avatar['gravatar'] ) ? $user_avatar['gravatar'] : 0;
        if ( empty( $gravatar_id ) ) {
            return $avatar;
        }    
        $avater_url = wp_get_attachment_thumb_url( $gravatar_id );
        return $avater_url;
    }
    
    /**
    * WC Marketplace plugin vendor info
    */    
    public function pgs_woo_api_get_wc_marketplace_vender_info($author_id){
        global $WCMp;
        $html = '';$data = array();
        $vendor = get_wcmp_vendor($author_id);
        
        if ($vendor) {            
            $contact_seller = $this->pgs_woo_api_contact_seller_status('wc_marketplace');
            $data['is_seller'] = true;
            $data['contact_seller'] = $contact_seller;
            $data['seller_id'] = $author_id;
            $data['store_name'] = $vendor->user_data->display_name;                
            $data['address'] = '';                
            $data['store_tnc'] = '';
            
            if ($term_id = get_user_meta($author_id, '_vendor_term_id', true)) {
                $term_vendor = term_exists(absint($term_id), $WCMp->taxonomy->taxonomy_name);                
            } else {
                $term_vendor =  false;
            } 
            $rating_result_array = wcmp_get_vendor_review_info($term_vendor['term_id']);
            
            
            if(!empty($term_vendor)){
                $data['seller_rating']['rating'] = $rating_result_array['avg_rating'];
                $data['seller_rating']['count'] = $rating_result_array['total_rating'];                        
            }
            
            $vender_meta = $this->wc_marketplace_vender_meta($author_id);
            $data['review_list'] = $this->pgs_woo_api_get_wc_marketplace_seller_review_list($author_id);
            
            $data = array_merge($data,$vender_meta);
                                
                                   
        } else {
            $data = array(
                'is_seller' => false                
            );    
        }        
        return $data;
    }
    
    /**
    * WC Marketplace plugin vendor info meta
    */
    public function wc_marketplace_vender_meta($vendor_id){
        
        global $WCMp;
        $vendor = get_wcmp_vendor($vendor_id);
        $vendor_hide_address = get_user_meta($vendor_id, '_vendor_hide_address', true);
        $vendor_hide_phone = get_user_meta($vendor_id, '_vendor_hide_phone', true);
        $vendor_hide_email = get_user_meta($vendor_id, '_vendor_hide_email', true);
        
            
        $address = '';
        if ($vendor_hide_address != 'Enable') {                
            $address = '';    
            if ($vendor->city) {
                $address = $vendor->city . ', ';
            }
            if ($vendor->state) {
                $address .= $vendor->state . ', ';
            }
            if ($vendor->country) {
                $address .= $vendor->country;
            }
        }
        if (!empty($mobile) && $vendor_hide_phone != 'Enable') { $data['mobile'] = apply_filters('vendor_shop_page_contact', $mobile, $vendor_id); }
        if (!empty($email) && $vendor_hide_email != 'Enable') { $data['email'] = apply_filters('vendor_shop_page_email', $email, $vendor_id); }
        
        $is_vendor_add_external_url_field = apply_filters('is_vendor_add_external_url_field', true);
        if ($WCMp->vendor_caps->vendor_capabilities_settings('is_vendor_add_external_url') && $is_vendor_add_external_url_field) {            
            $external_url = '';
            $external_store_url = get_user_meta($vendor_id, '_vendor_external_store_url', true);
            $external_store_label = get_user_meta($vendor_id, '_vendor_external_store_label', true);
            if (empty($external_store_label))
                $external_store_label = esc_html__('External Store URL', 'pgs-woo-api');
            if (isset($external_store_url) && !empty($external_store_url)) {
                $external_url = apply_filters('vendor_shop_page_external_store', esc_url_raw($external_store_url), $vendor_id);
            }
            if(!empty($external_url)){
                $data['external_store_label'] = $external_store_label;  
                $data['external_url'] = $external_url;    
            }
        }
        
        
        $vendor_fb_profile = get_user_meta($vendor_id, '_vendor_fb_profile', true);
        $vendor_twitter_profile = get_user_meta($vendor_id, '_vendor_twitter_profile', true);
        $vendor_linkdin_profile = get_user_meta($vendor_id, '_vendor_linkdin_profile', true);
        $vendor_google_plus_profile = get_user_meta($vendor_id, '_vendor_google_plus_profile', true);
        $vendor_youtube = get_user_meta($vendor_id, '_vendor_youtube', true);
        $vendor_instagram = get_user_meta($vendor_id, '_vendor_instagram', true);
        
        if ($vendor_fb_profile) { $data['social_profile']['facebook'] = esc_url($vendor_fb_profile);}
        if ($vendor_twitter_profile) { $data['social_profile']['twitter'] = esc_url($vendor_twitter_profile); }        
        if ($vendor_linkdin_profile) { $data['social_profile']['linkdin'] = esc_url($vendor_linkdin_profile); }
        if ($vendor_google_plus_profile) { $data['social_profile']['google_plus'] = esc_url($vendor_google_plus_profile); }
        if ($vendor_youtube) { $data['social_profile']['youtube'] = esc_url($vendor_youtube); }
        if ($vendor_instagram) { $data['social_profile']['instagram'] = esc_url($vendor_instagram); }
        
        $vendor_hide_description = get_user_meta($vendor_id, '_vendor_hide_description', true);
        $string = '';
        if (!$vendor_hide_description) {            
            $description = $vendor->description;
            $string = stripslashes($description);                
        }
        $data['store_description'] = $string; 
        
        $image = $vendor->image ? $vendor->image : $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
        $banner_url = $vendor->banner;
        $data['avatar'] = $image;
        $data['banner_url'] = $banner_url;        
        $data['seller_address'] = $address;                   
        return $data;    
    }
    
    
    /**
     * Get WC Marketplace seller revire list   
     */
    public function pgs_woo_api_get_wc_marketplace_seller_review_list ($vendor_id){
        
        global $WCMp, $wpdb;        
        $posts_per_page = get_option('posts_per_page');
        if (empty($vendor_id) || $vendor_id == '' || $vendor_id == 0) {
            $data = array();
        } else {
            $args_default = array(
                'status' => 'approve',
                'type' => 'wcmp_vendor_rating',
                'count' => false,
                //'number' => $posts_per_page,
                //'offset' => $offset,
                'posts_per_page' => -1,
                'meta_key' => 'vendor_rating_id',
                'meta_value' => $vendor_id,
            );
            $args = apply_filters('wcmp_vendor_review_rating_args_to_fetch', $args_default);
            $comments = get_comments($args);            
            $data = $this->pgs_woo_api_wc_marketplace_reviews_list($comments,$vendor_id);
        }
        return $data;
        
    }
    
    
    public function pgs_woo_api_wc_marketplace_reviews_list($comments,$vendor_term_id){
        
        if ( count( $comments ) == 0 ) {
            $html_data =  array();
        } else {
            $is_verified = false;
            foreach($comments as $comment) {
		          
        		$rating   = intval( get_comment_meta( $comment->comment_ID, 'vendor_rating', true ) );
                $verified = wcmp_review_is_from_verified_owner( $comment, $vendor_term_id );
                $get_rating = '';
                
                if ( $rating && get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) : 
    				$get_rating = $rating;
    			endif;
                
                
                if ( $comment->comment_approved == '0' ) : 
    				$comment_date = esc_html__( 'Your comment is awaiting approval', 'pgs-woo-api' );
    			else :
    				
    					$comment->comment_ID;
    
						if ( get_option( 'woocommerce_review_rating_verification_label' ) === 'yes' ){
						  if ( $verified ){
						      $is_verified = true;
						  }    								
						}
                        $comment_date = get_comment_date( wc_date_format(), $comment->comment_ID );    
    			endif;    
    			                
                
                $html_data[] = array(
                    //'permalink' => $permalink,
                    'rating' => $get_rating,
                    'comment_author' => $comment->comment_author,
                    'verified' => $is_verified,
                    'comment_date' => $comment_date,
                    'comment_content' => $comment->comment_content,
                    'avatar' => get_avatar_url ($comment->comment_author_email )  
                );
        	}
        }
        $review_list = $html_data;
        return $review_list;        
    }
    
    /**
    * URL : http://yourdomain.com/wp-json/pgs-woo-api/v1/contact_seller
    * @param name: #####
    * @param email: #####
    * @param message : ####
    * @param seller_id : ####
    */
    public function pgs_woo_api_seller_contact_form(){
        
        $input = file_get_contents("php://input");
        $request = json_decode($input,true);
        $required = array( 'name','email','message' );
        
        $validation = $this->pgs_woo_api_param_validation( $required, $request );
        if($validation){
           return $validation; 
        }
    	   
        
        $is_vendeo = pgs_woo_api_is_vendor_plugin_active();
        if($is_vendeo['vendor_count'] > 0){
            if($is_vendeo['vendor_for'] == 'dokan'){
                $data = $this->get_dokan_vendor_contact_form($request);
            } else {
                $data = $this->get_wc_marketplace_vendor_contact_form($request);    
            }            
        }
        return $data;    
    }
    
    
    
    /**
     * Send contact request to dokan 
     */ 
    public function get_dokan_vendor_contact_form($posted){
                 
        $contact_name    = sanitize_text_field( $posted['name'] );
        $contact_email   = sanitize_text_field( $posted['email'] );
        $contact_message = strip_tags( $posted['message'] );
        
        $error = array( "status" => "error" );
        if ( empty( $contact_name ) ) {
            $error['message'] = esc_html__( 'Please provide your name.', 'pgs-woo-api' );
            return $error;
        }

        if ( empty( $contact_name ) ) {
            $error['message'] = esc_html__( 'Please provide your name.', 'pgs-woo-api' );
            return $error;
        }
        $seller = array();
        $seller = get_user_by( 'id', (int) $posted['seller_id'] );
        
        
        if ( !$seller ) {
            $error['message'] = esc_html__( 'Something went wrong!', 'pgs-woo-apie' );
            return $error;
        }
        
        do_action( 'dokan_trigger_contact_seller_mail', $seller->user_email, $contact_name, $contact_email, $contact_message );

        $output =  array(
            "status" => "success",
            "message" => esc_html__( 'Email sent successfully!', 'pgs-woo-api') 
        );
        return $output;       
    }
    
    /**
     * Send contact request to wc_marketplace 
     */ 
    public function get_wc_marketplace_vendor_contact_form($posted){
        $error = array( "status" => "error" );
        
        $contact_name    = sanitize_text_field( $posted['name'] );
        $contact_email   = sanitize_text_field( $posted['email'] );
        $contact_message = strip_tags( $posted['message'] );
        
        $error = array( "status" => "error" );
        if ( empty( $contact_name ) ) {
            $error['message'] = esc_html__( 'Please provide your name.', 'pgs-woo-api' );
            return $error;
        }

        if ( empty( $contact_name ) ) {
            $error['message'] = esc_html__( 'Please provide your name.', 'pgs-woo-api' );
            return $error;
        }

        $seller = get_user_by( 'id', (int) $posted['seller_id'] );

        if ( !$seller ) {
            $error['message'] = esc_html__( 'Something went wrong!', 'pgs-woo-api' );
            return $error;
        }
        
        $vendor_id = $posted['seller_id']; 
        $to_email = '';
		$capability_settings = get_option('wcmp_general_customer_support_details_settings_name');
        if( isset( $capability_settings['can_vendor_add_customer_support_details'] ) ) {			 
			$vendor_meta = get_user_meta( $vendor_id );				
			if( isset($vendor_meta['_vendor_customer_email'][0])) {
                $vendor_meta['nickname'][0];                    
                if(isset($vendor_meta['_vendor_customer_email'][0])) { 
                    $to_email = $vendor_meta['_vendor_customer_email'][0];
                }                    
			}			
		} else {
            if(isset($capability_settings['csd_email'])) {
                $to_email = $capability_settings['csd_email']; 					
            }			
		}
        $to = $to_email;
        $reply_to = $contact_email; 
        $from_name = $contact_name;
        $from_email = $contact_email; 
        if($to != ''){
            
            $vendor_contact_mail_options_data = pgs_woo_api_get_vendor_contact_mail_options_data();
            $vendor_contact_subject = $vendor_contact_mail_options_data['vendor_contact_subject'];
            $vendor_contact_from_name = $vendor_contact_mail_options_data['vendor_contact_from_name'];
            $vendor_contact_from_email = $vendor_contact_mail_options_data['vendor_contact_from_email'];
            
            
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'From: '.$vendor_contact_from_name.' <'.$vendor_contact_from_email.'>';
            $headers[] = 'Reply-To: '.$from_name.' <'.$reply_to.'>';        
            
            $subject = sprintf( esc_html__( 'Customer support alert form %s', 'pgs-woo-api' ), $from_name );
            
            if(isset($vendor_contact_subject) && !empty($vendor_contact_subject)){
                $subject = $vendor_contact_subject;     
            }
            
            $subject = apply_filters('pgs_woo_api_wc_marketplace_vendor_contact_subject', $subject,$from_name);    
            $contact_message = apply_filters('pgs_woo_api_wc_marketplace_vendor_contact_subject_message_body', $contact_message);
            
            if ( !wp_mail($to, $subject, $contact_message,$headers) ){                       
                $error['error'] = esc_html__("The e-mail could not be sent.Please try after some time.");
                return $error;                        
            }
        } else {
            $error['error'] = esc_html__( 'Something went wrong. Please try after some time.', 'pgs-woo-api' );
            return $error;    
        }
                      
        $output =  array(
            "status" => "success",
            "message" => esc_html__( 'Email sent successfully!', 'pgs-woo-api') 
        );
        return $output;       
    }
 }
 new PGS_WOO_API_SellerController; 
 ?>