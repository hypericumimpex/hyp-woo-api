<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PGS_WOO_API_HomeController extends PGS_WOO_API_Controller{
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
	protected $rest_base = 'home';
    	
	public function __construct() {
		$this->register_routes();	
	}
	public function register_routes() {
		
		add_action( 'rest_api_init', array( $this, 'pgs_woo_api_register_route'));
	}
	
	
	public function pgs_woo_api_register_route() {
        
        register_rest_route( $this->namespace, $this->rest_base, array(
    		'methods' => WP_REST_Server::CREATABLE,//'POST',
    		'callback' => array($this, 'pgs_woo_api_app_home'),
            'permission_callback' => array($this, 'pgs_woo_api_permission_callback'),
    	) );           
    }
    /**
    * URL : http://yourdomain.com/wp-json/pgs-woo-api/v1/home    
    */   
    public function pgs_woo_api_app_home( WP_REST_Request $request){    
        
        $input = file_get_contents("php://input");
        $request = json_decode($input,true);
        $app_ver = '';
        if(isset($request['app-ver']) && !empty($request['app-ver'])) {
    		$app_ver = $request['app-ver'];
    	}        
        $lang='';
        $is_wpml_active = pgs_woo_api_is_wpml_active();        
        if($is_wpml_active){            
            $lang = pgs_woo_api_wpml_get_lang();
            if(!empty($lang)){                
                $lang_prifix = '_'.$lang;                                
                $pgs_woo_api_wpml_home_option = get_option('pgs_woo_api_home_option'.$lang_prifix);
            }
        }
                
        $pgs_woo_api_home_option = array();$pgs_woo_api_home_option['app_logo'] = '';$pgs_woo_api_home_option['app_logo_light'] = '';        
        $pgs_woo_api_home_option = get_option('pgs_woo_api_home_option');
        
        $price_formate_option = get_woo_price_formate_option_array();        
        
        if(!empty($lang)){
            $pgs_woo_api_home_option['app_logo'] = $this->pgs_woo_api_get_app_logo($pgs_woo_api_wpml_home_option,$pgs_woo_api_home_option);
            $pgs_woo_api_home_option['app_logo_light'] = $this->pgs_woo_api_get_app_logo_light($pgs_woo_api_wpml_home_option,$pgs_woo_api_home_option);
            $pgs_woo_api_home_option['main_category'] = $this->pgs_woo_api_get_main_category($pgs_woo_api_home_option,$lang);
            $pgs_woo_api_home_option['main_slider'] = $this->pgs_woo_api_get_main_slider($pgs_woo_api_wpml_home_option);
            $pgs_woo_api_home_option['category_banners'] = $this->pgs_woo_api_get_category_banners($pgs_woo_api_wpml_home_option);
            $pgs_woo_api_home_option['banner_ad'] = $this->pgs_woo_api_get_banner_ads($pgs_woo_api_wpml_home_option);
            
            $feature_box_data = $this->pgs_woo_api_get_feature_box($pgs_woo_api_wpml_home_option);
            $pgs_woo_api_home_option['feature_box_heading'] = $feature_box_data['feature_box_heading'];
            $pgs_woo_api_home_option['feature_box_status'] = $feature_box_data['feature_box_status'];
            $pgs_woo_api_home_option['feature_box'] = $feature_box_data['feature_box'];
            $carousel_data =  $this->pgs_woo_api_get_home_products_carousel_data($pgs_woo_api_wpml_home_option,$app_ver);
        
        } else {
            $pgs_woo_api_home_option['app_logo'] = $this->pgs_woo_api_get_app_logo($pgs_woo_api_home_option);
            $pgs_woo_api_home_option['app_logo_light'] = $this->pgs_woo_api_get_app_logo_light($pgs_woo_api_home_option);
            $pgs_woo_api_home_option['main_category'] = $this->pgs_woo_api_get_main_category($pgs_woo_api_home_option);
            $pgs_woo_api_home_option['main_slider'] = $this->pgs_woo_api_get_main_slider($pgs_woo_api_home_option);
            $pgs_woo_api_home_option['category_banners'] = $this->pgs_woo_api_get_category_banners($pgs_woo_api_home_option);
            $pgs_woo_api_home_option['banner_ad'] = $this->pgs_woo_api_get_banner_ads($pgs_woo_api_home_option);
            $feature_box_data = $this->pgs_woo_api_get_feature_box($pgs_woo_api_home_option);
            $pgs_woo_api_home_option['feature_box_heading'] = $feature_box_data['feature_box_heading'];
            $pgs_woo_api_home_option['feature_box_status'] = $feature_box_data['feature_box_status'];
            $pgs_woo_api_home_option['feature_box'] = $feature_box_data['feature_box'];                        
            $carousel_data =  $this->pgs_woo_api_get_home_products_carousel_data($pgs_woo_api_home_option,$app_ver);            
        }
        
        if($app_ver !== ''){
            $products_view_orders = array();
            if(isset($carousel_data['products_carousel'])){                
                foreach($carousel_data['products_carousel'] as $key => $val){
                    $products_view_orders[]=array(
                        "name"=>$key
                    );        
                }
            }
            $pgs_woo_api_home_option['products_view_orders'] = $products_view_orders;
            $pgs_woo_api_home_option['products_carousel'] = $carousel_data['products_carousel']; 
        } else {
            unset($pgs_woo_api_home_option['products_carousel']);    
            $pgs_woo_api_home_option['popular_products'] = $carousel_data['popular_products'];
            $pgs_woo_api_home_option['scheduled_sale_products'] = $carousel_data['scheduled_sale_products'];
        }
        $pgs_woo_api_home_option['static_page'] = $this->pgs_woo_api_get_static_pages($pgs_woo_api_home_option,$lang); 
        $pgs_woo_api_home_option['info_pages'] = $this->pgs_woo_api_get_info_pages($pgs_woo_api_home_option,$lang);
        $pgs_woo_api_home_option['all_categories'] = $this->pgs_woo_api_cat_list(); 
        $pgs_woo_api_home_option['is_wishlist_active'] = pgs_woo_api_is_wishlist_active();
        $pgs_woo_api_home_option['is_currency_switcher_active'] = pgs_woo_api_is_currency_switcher_active();
        $pgs_woo_api_home_option['is_order_tracking_active'] = pgs_woo_api_is_order_tracking_active();
        $pgs_woo_api_home_option['is_reward_points_active'] = pgs_woo_api_is_reward_points_active();        
        $pgs_woo_api_home_option['is_guest_checkout_active'] = pgs_woo_api_is_guest_checkout();
        $is_wpml_active = pgs_woo_api_is_wpml_active();
        if($is_wpml_active){
            $pgs_api_is_wpml_status = (isset($pgs_woo_api_home_option['pgs_api_is_wpml']))?$pgs_woo_api_home_option['pgs_api_is_wpml']:'enable';
            if($pgs_api_is_wpml_status == "enable"){
                $is_wpml_active = true;    
            } else {
                $is_wpml_active = false;
            }
        }
        $pgs_woo_api_home_option['is_wpml_active'] = $is_wpml_active;
        $pgs_woo_api_home_option['price_formate_options'] = $price_formate_option;
        
        $pgsiosappurl = get_option('pgs_ios_app_url');
        $pgs_ios_app_url = (isset($pgsiosappurl))?$pgsiosappurl:'';
        $pgs_woo_api_home_option['ios_app_url'] = $pgs_ios_app_url;
        $site_language = get_bloginfo('language');
        $pgs_woo_api_home_option['site_language'] = $site_language;
        $pgs_woo_api_home_option['wpml_languages'] = $this->pgs_woo_api_get_all_wpml_langs($is_wpml_active);
        $checkout_redirect_urls  = $this->pgs_woo_api_get_checkout_redirect_url();
        $pgs_woo_api_home_option['checkout_redirect_url'] = $checkout_redirect_urls;

        /**
         *  Get App Assets app color
         */ 
        $app_color = array( 
            'header_color' => '',
            'primary_color' => '#60A727',
            'secondary_color' => ''
        );
        $app_assets = get_option('pgs_woo_api_app_assets_options');
        if(isset($app_assets) && !empty($app_assets)){
            if(isset($app_assets['app_assets']['app_color']) && !empty($app_assets['app_assets']['app_color'])){                
                $app_color = $app_assets['app_assets']['app_color'];
            }
        }
        $pgs_woo_api_home_option['app_color'] = $app_color;
        
        $is_rtl = false;
        if ( is_rtl() ) {
            $is_rtl = true;
        }
        $pgs_woo_api_home_option['is_rtl'] = $is_rtl;
        $cs_status = pgs_woo_api_is_currency_switcher_active();
        if($cs_status){
            $currency_data = get_option('woocs');
            if(isset($currency_data) && !empty($currency_data)){
                global $WOOCS;
                $currencies = $WOOCS->get_currencies();
                if(isset($currencies) && !empty($currencies)){
                    $pgs_woo_api_home_option['currency_switcher'] = $currencies;                        
                }    
            }
        }
        return $pgs_woo_api_home_option;
    }
    
    
    public function pgs_woo_api_get_app_logo($pgs_woo_api_option,$default_lang_app_logo=array()){
        $app_logo_id = (isset($pgs_woo_api_option['app_logo']))?$pgs_woo_api_option['app_logo']:'';
        $app_logo_url = '';
        if(!empty($app_logo_id)){    	  
            $src = wp_get_attachment_image_src($app_logo_id, apply_filters( 'pgs_woo_api_app_logo_image', 'full' ) );
            if(!empty($src)){
                $app_logo_url = $src[0];
            }   
    	} else {
            $app_logo_id = (isset($default_lang_app_logo['app_logo_light']))?$default_lang_app_logo['app_logo_light']:'';
            if(!empty($app_logo_id)){
                $src = wp_get_attachment_image_src($app_logo_id, apply_filters( 'pgs_woo_api_app_logo_light_image', 'full' ) );
                if(!empty($src)){
                    $app_logo_url = $src[0];
                }
            }            
        }
        return $app_logo_url;        
    }
    
    public function pgs_woo_api_get_app_logo_light($pgs_woo_api_option,$default_lang_logo_light=array()){
        $app_logo_light_id = (isset($pgs_woo_api_option['app_logo_light']))?$pgs_woo_api_option['app_logo_light']:'';
        $app_logo_light_url = '';
        if(!empty($app_logo_light_id)){
            $src = wp_get_attachment_image_src($app_logo_light_id, apply_filters( 'pgs_woo_api_app_logo_light_image', 'full' ) );
            if(!empty($src)){
                $app_logo_light_url = $src[0];
            }
        } else {
            $app_logo_light_id = (isset($default_lang_logo_light['app_logo_light']))?$default_lang_logo_light['app_logo_light']:'';
            if(!empty($app_logo_light_id)){
                $src = wp_get_attachment_image_src($app_logo_light_id, apply_filters( 'pgs_woo_api_app_logo_light_image', 'full' ) );
                if(!empty($src)){
                    $app_logo_light_url = $src[0];
                }
            }            
        }
        return $app_logo_light_url;        
    }
    
    
    public function pgs_woo_api_get_main_category($pgs_woo_api_option,$lang=''){
        $main_category_arr = array();        
        if(isset($pgs_woo_api_option['main_category']) && !empty($pgs_woo_api_option['main_category'])){        
            $p = 0;            
            foreach($pgs_woo_api_option['main_category'] as $key => $val){
                
                if(isset($val['main_cat_id']) && !empty($val['main_cat_id']) ){                    
                    $cat_data = get_term_by( 'id',$val['main_cat_id'],'product_cat' );
                    if(!empty($lang)){
                        if( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'icl_object_id' ) ) {
                            $original_id = icl_object_id( $val['main_cat_id'], 'product_cat', true, $lang );
                            $val['main_cat_id'] = $original_id;                            
                        }
                    }
                    $main_category_arr[$p]['main_cat_id'] = $val['main_cat_id'];
                    $main_category_arr[$p]['main_cat_name'] = html_entity_decode($cat_data->name);
                    
                    $attch_id = get_term_meta( $val['main_cat_id'], 'product_app_cat_thumbnail_id', true );                 
                    $vsrc = wp_get_attachment_image_src($attch_id, apply_filters( 'pgs_woo_api_main_category_image', 'thumbnail' ) );                                
                    if(!empty($vsrc)){                    
                        $main_category_arr[$p]['main_cat_image'] = $vsrc[0];                                                
                    } else {
                        $main_category_arr[$p]['main_cat_image'] = ''; 
                    }                
                    $p++;
                }                
            }            
        } 
        return $main_category_arr;
    }
    
    public function pgs_woo_api_get_static_pages($pgs_woo_api_option,$lang=''){
        $static_pagey_arr = array(
            "about_us"=> "",
            "terms_of_use"=> "",
            "privacy_policy"=> ""            
        );
        foreach($pgs_woo_api_option['static_page'] as $key => $static_page_id){            
            if(isset($static_page_id) && !empty($static_page_id) ){
                if(!empty($lang)){
                    if( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'icl_object_id' ) ) {
                        $static_page_id = icl_object_id( $static_page_id, 'post', true, $lang );
                    }
                }
            }
            $static_pagey_arr[$key] = $static_page_id;
        }
        return $static_pagey_arr;
    }
    
    public function pgs_woo_api_get_info_pages($pgs_woo_api_option,$lang=''){
        $info_pages_arr[] = array( "info_pages_page_id"=> "" );
        foreach($pgs_woo_api_option['info_pages'] as $key => $info_page){              
            if(isset($info_page['info_pages_page_id']) && !empty($info_page['info_pages_page_id']) ){                 
                if(!empty($lang)){
                    if( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'icl_object_id' ) ) {
                        $info_page['info_pages_page_id'] = icl_object_id( $info_page['info_pages_page_id'], 'post', true, $lang );
                    }
                }
            }
            $info_pages_arr[$key]['info_pages_page_id'] = $info_page['info_pages_page_id'];
        }
        return $info_pages_arr;
    }
    
    public function pgs_woo_api_get_main_slider($pgs_woo_api_option){
        $main_slider_arr = array();
        if(isset($pgs_woo_api_option['main_slider']) && !empty($pgs_woo_api_option['main_slider'])){
            $t = 0;
            foreach($pgs_woo_api_option['main_slider'] as $k => $v){                    
                if(isset($v['upload_image_id']) && !empty($v['upload_image_id']) ){
                    
                    $main_slider_arr[$t]['upload_image_id'] = $v['upload_image_id']; 
                    $main_slider_arr[$t]['slider_cat_id'] = $v['slider_cat_id'];
                    $vsrc = wp_get_attachment_image_src($v['upload_image_id'], apply_filters( 'pgs_woo_api_slider_image', 'large' ));                    
                    if(!empty($vsrc)){                            
                        $main_slider_arr[$t]['upload_image_url'] = esc_url($vsrc[0]);                            
                    } else {
                        $main_slider_arr[$t]['upload_image_url'] = ''; 
                    }
                    $t++;
                }
                                                            
            }
        }                
        return $main_slider_arr;
    }
    
    public function pgs_woo_api_get_category_banners($pgs_woo_api_option){
        $category_banners_arr = array();
        if(isset($pgs_woo_api_option['category_banners']) && !empty($pgs_woo_api_option['category_banners'])){
            $p = 0;            
            foreach($pgs_woo_api_option['category_banners'] as $k => $v){                    
                if( !empty($v['cat_banners_image_id']) || !empty($v['cat_banners_title']) || !empty($v['cat_banners_cat_id']) ){
                    if( !empty($v['cat_banners_image_id']) ){                    
                        $category_banners_arr[$p]['cat_banners_image_id'] = $v['cat_banners_image_id'];                        
                        $vsrc = wp_get_attachment_image_src($v['cat_banners_image_id'], apply_filters( 'pgs_woo_api_cat_banners_image', 'app_thumbnail' ));
                        if(!empty($vsrc)){                            
                            $category_banners_arr[$p]['cat_banners_image_url'] = esc_url($vsrc[0]);                            
                        } else {
                            $category_banners_arr[$p]['cat_banners_image_url'] = ''; 
                        }
                    } 
                    $category_banners_arr[$p]['cat_banners_cat_id'] = (isset($v['cat_banners_cat_id']))?$v['cat_banners_cat_id']:'';
                    if( !empty($v['cat_banners_title']) ){                                                
                        $category_banners_arr[$p]['cat_banners_title'] = stripslashes($v['cat_banners_title']);                    
                    } else {
                        $category_banners_arr[$p]['cat_banners_title'] = '';
                    }                                          
                }
                $p++;                                                           
            }                   
        }        
        return $category_banners_arr;
    }
    
    public function pgs_woo_api_get_banner_ads($pgs_woo_api_option){
    
        $banner_ad_arr = array();
        if(isset($pgs_woo_api_option['banner_ad']) && !empty($pgs_woo_api_option['banner_ad'])){
            $b = 0;
            foreach($pgs_woo_api_option['banner_ad'] as $k => $v){                    
                if(isset($v['banner_ad_image_id']) && !empty($v['banner_ad_image_id']) ){
                    $banner_ad_image_id = $v['banner_ad_image_id'];                     
                    $vsrc = wp_get_attachment_image_src($banner_ad_image_id, apply_filters( 'pgs_woo_api_banner_ad_image', 'large' ) );
                    if(!empty($vsrc)){                            
                        $banner_ad_arr[$b]['banner_ad_image_url'] = $vsrc[0];                            
                    } else {
                        $banner_ad_arr[$b]['banner_ad_image_url'] = ''; 
                    }
                    $banner_ad_arr[$b]['banner_ad_image_id'] = $banner_ad_image_id;
                
                    $banner_ad_arr[$b]['banner_ad_cat_id'] = $v['banner_ad_cat_id'];
                    $b++;
                }
            }
        }
        return $banner_ad_arr;
    }
    
    
    public function pgs_woo_api_get_feature_box($pgs_woo_api_option){        
        if(isset($pgs_woo_api_option['feature_box_heading'])){
            $pgs_woo_api_home_option['feature_box_heading'] = stripslashes($pgs_woo_api_option['feature_box_heading']);                    
        } else {
            $pgs_woo_api_home_option['feature_box_heading'] = '';    
        }
        
        $feature_box_status = (isset($pgs_woo_api_option['feature_box_status']) && !empty($pgs_woo_api_option['feature_box_status']))?$pgs_woo_api_option['feature_box_status']:'enable';
        $pgs_woo_api_home_option['feature_box_status'] = $feature_box_status;        
        if($feature_box_status == "enable"){            
            $f = 0;
            if(isset($pgs_woo_api_option['feature_box'])&& !empty($pgs_woo_api_option['feature_box'])){
                foreach($pgs_woo_api_option['feature_box'] as $key => $val){
                    $pgs_woo_api_home_option['feature_box'][$f]['feature_title'] = (isset($val['feature_title']))?$val['feature_title']:'';
                    $pgs_woo_api_home_option['feature_box'][$f]['feature_content'] = (isset($val['feature_content']))?$val['feature_content']:'';
                    if(isset($val['feature_image_id']) && !empty($val['feature_image_id']) ){            
                        $attch_id = $val['feature_image_id'];                 
                        $vsrc = wp_get_attachment_image_src($attch_id, apply_filters( 'pgs_woo_api_feature_image', 'thumbnail' ) );                                
                        if(!empty($vsrc)){                    
                            $pgs_woo_api_home_option['feature_box'][$f]['feature_image'] = $vsrc[0];                                                
                        } else {
                            $pgs_woo_api_home_option['feature_box'][$f]['feature_image'] = ''; 
                        }                
                    }                
                    $f++;
                }
            } else {
                $pgs_woo_api_home_option['feature_box'] = array();
            }
        } else {
            $pgs_woo_api_home_option['feature_box'] = array();
        }        
        return $pgs_woo_api_home_option;     
    }
    
    
    public function pgs_woo_api_get_home_products_carousel_data($pgs_woo_api_option,$app_ver=''){        
        $i=0; $orderby='date'; $order='desc';$no_of_items=4;
        if($app_ver !== ''){
            if(isset($pgs_woo_api_option['products_carousel'])){
                foreach($pgs_woo_api_option['products_carousel'] as $k => $v){
                    if( $k == 'feature_products' ){
                        $pgs_woo_api_home_option['products_carousel'][$k]['status'] = $v['status'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['title'] = $v['title'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['screen_order'] = $i;
                        $pgs_woo_api_home_option['products_carousel'][$k]['products'] = $this->pgs_woo_api_get_feature_products_list($no_of_items,$show='featured',$orderby,$order);
                    } elseif( $k == 'recent_products' ){
                        $pgs_woo_api_home_option['products_carousel'][$k]['status'] = $v['status'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['title'] = $v['title'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['screen_order'] = $i;
                        $pgs_woo_api_home_option['products_carousel'][$k]['products'] = $this->pgs_woo_api_get_recent_products_list($no_of_items,$show='recent',$orderby,$order);
                    } elseif( $k == 'special_deal_products' ){
                        $pgs_woo_api_home_option['products_carousel'][$k]['status'] = $v['status'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['title'] = $v['title'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['screen_order'] = $i;
                        $pgs_woo_api_home_option['products_carousel'][$k]['products'] = $this->pgs_woo_api_scheduled_sale_products($no_of_items,$app_ver);
                    } elseif( $k == 'popular_products' ){
                        $pgs_woo_api_home_option['products_carousel'][$k]['status'] = $v['status'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['title'] = $v['title'];
                        $pgs_woo_api_home_option['products_carousel'][$k]['screen_order'] = $i;
                        $pgs_woo_api_home_option['products_carousel'][$k]['products'] = $this->pgs_woo_api_get_popular_products($no_of_items,$show='popular',$orderby,$order,$app_ver);
                    }
                    $i++;
                }
            }
        } else {
            unset($pgs_woo_api_option['products_carousel']);
            $pgs_woo_api_home_option['popular_products'] = $this->pgs_woo_api_get_popular_products($no_of_items,$show='popular',$orderby,$order,$app_ver);
            $pgs_woo_api_home_option['scheduled_sale_products'] = $this->pgs_woo_api_scheduled_sale_products($no_of_items,$app_ver);
        }
        return $pgs_woo_api_home_option;    
    }
    
    public function pgs_woo_api_get_all_wpml_langs($is_wpml_active){        
        $lang_data = array();$pgs_woo_api_icl_get_languages=array();
        if($is_wpml_active){
            global $wpdb,$sitepress;            
            $ls_settings = get_option('icl_sitepress_settings');		 
            $icl_get_languages = icl_get_languages();
            if(!empty($icl_get_languages)){                
                foreach($icl_get_languages as $key => $lan){
                    $site_language = (isset($lan['default_locale']))?str_replace( '_', '-', $lan['default_locale'] ):'';
                    if(isset($ls_settings['icl_lso_flags']) && $ls_settings['icl_lso_flags']==1){
						 $disp_language = icl_disp_language($lan['native_name'], $lan['translated_name']);
					}else{
						 $disp_language = icl_disp_language($lan['native_name']);
					}                    
                    $pgs_woo_api_icl_get_languages[] = array(
                        "code" => $icl_get_languages[$key]['code'],
                        "id" => $icl_get_languages[$key]['id'],
                        "native_name" => $icl_get_languages[$key]['native_name'],
                        //"major" => $icl_get_languages[$key]['major'],
                        "active" => $icl_get_languages[$key]['active'],
                        "default_locale" => $icl_get_languages[$key]['default_locale'],
                        //"encode_url" => $icl_get_languages[$key]['encode_url'],
                        //"tag" => $icl_get_languages[$key]['tag'],
                        "translated_name" => $icl_get_languages[$key]['translated_name'],
                        //"url" => $icl_get_languages[$key]['url'],
                        "language_code" => $icl_get_languages[$key]['language_code'],
                        "disp_language" => $disp_language,
                        "site_language" => $site_language,
                        "is_rtl" => $sitepress->is_rtl( $key )
                    );
                }
            }            
            $lang_data = $pgs_woo_api_icl_get_languages;
        }
        return $lang_data;
    }
    
    public function pgs_woo_api_cat_list(){
        $taxonomy     = 'product_cat';
        $orderby      = 'name';  
        $show_count   = 1;      // 1 for yes, 0 for no
        $pad_counts   = 1;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no  
        $title        = '';  
        $empty        = 0;
        
        $args = array(
             'taxonomy'     => $taxonomy,
             'orderby'      => $orderby,
             'show_count'   => $show_count,
             'pad_counts'   => $pad_counts,
             'hierarchical' => $hierarchical,
             'title_li'     => $title,
             'hide_empty'   => $empty
        );
        $all_categories = get_categories( $args );
            
        $data = array();
        if(isset($all_categories) && !empty($all_categories)){
            foreach ($all_categories as $cat) {
                
                $product_app_cat_thumbnail_id = get_term_meta($cat->term_id, 'product_app_cat_thumbnail_id', true); 
                $vsrc = wp_get_attachment_image_src($product_app_cat_thumbnail_id, apply_filters( 'pgs_woo_api_app_cat_thumbnail_image', 'thumbnail' ) );
                if(!empty($vsrc)){                            
                    $main_cat_id_image = $vsrc[0];                            
                } else {
                    $main_cat_id_image = ''; 
                }
                
                $data[] = array(
                    'description' => $cat->category_description,            
                    'id' => $cat->term_id,
                    'image' => array(                
                        'src' => $main_cat_id_image,                
                    ),        
                    'name' => html_entity_decode($cat->name),
                    'parent' => $cat->category_parent,
                    'slug' => $cat->slug,
                );       
            }
        }
        return $data;    
    }
    
    function pgs_woo_api_get_recent_products_list($no_of_items,$show,$orderby,$order){
        $query = $this->pgs_woo_api_get_product_crousel_query($no_of_items,$show,$orderby,$order);
        $result = $this->pgs_woo_api_get_product_crousel_data($query);
        return $result;
    }

    function pgs_woo_api_get_feature_products_list($no_of_items,$show,$orderby,$order){        
        $query = $this->pgs_woo_api_get_product_crousel_query($no_of_items,$show,$orderby,$order);        
        $result = $this->pgs_woo_api_get_product_crousel_data($query);
        return $result;
    }
         
    /**
     * Get All Popular Products     
     */ 
    function pgs_woo_api_get_popular_products($no_of_items,$show,$orderby,$order,$app_ver=''){        
        
        $productdata = array();
        $product_visibility_term_ids = wc_get_product_visibility_term_ids();
        $query_args = array(
			'posts_per_page' => 4,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
			'order'          => 'desc',
			'meta_query'     => array(),
			'tax_query'      => array(
				'relation' => 'AND',
			),
		); // WPCS: slow query ok.

		
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => is_search() ? $product_visibility_term_ids['exclude-from-search'] : $product_visibility_term_ids['exclude-from-catalog'],
			'operator' => 'NOT IN',
		);
		$query_args['post_parent'] = 0;		
        $query_args['meta_query'] = array(
    		array(
    			'key'     => 'total_sales',
    			'value'   => 0,
                'type'    => 'numeric',
    			'compare' => '>',
    		)
    	);
		$query_args['orderby']  = 'meta_value_num';
        $popular_products = new WP_Query( $query_args );
        if ( $popular_products && $popular_products->have_posts() ) {
            while ( $popular_products->have_posts() ) {
				$popular_products->the_post();
				$product_id = get_the_ID();
                if (has_post_thumbnail( $product_id )){
                    $image = '';
                    $image = get_the_post_thumbnail_url($product_id, apply_filters( 'pgs_woo_api_app_thumbnail_image', 'app_thumbnail' ));
                    if(empty($image)){
                        $image = woocommerce_placeholder_img_src();    
                    } 
                } else {
                  $image = woocommerce_placeholder_img_src();  
                }
                
                pgs_woo_api_hook_remove_tax_in_price_html();//Remove include tax in price html
                $product = wc_get_product( $product_id );
                $price_html = $product->get_price_html();
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $get_price = $product->get_price();
                
                $is_currency_switcher_active = pgs_woo_api_is_currency_switcher_active();
                if($is_currency_switcher_active){
                    $regular_price = $this->pgs_woo_api_update_currency_rate($regular_price);
                    $sale_price = $this->pgs_woo_api_update_currency_rate($sale_price);
                    $get_price = $this->pgs_woo_api_update_currency_rate($get_price);
                }
                
                $price = array(
                    'regular_price' => $regular_price,
                    'sale_price' => $sale_price,
                    'price' => $get_price
                );
                $average = $product->get_average_rating();
                
                $productdata[] = array(
                    'id' => $product_id,
                    'title' => html_entity_decode(get_the_title($product_id)),
                    'image' => $image,
                    'price_html' => $price_html,
                    'price' => $price,
                    'rating' => $average
                );
			}
            wp_reset_postdata();
            return $productdata;
        } else {
            if($app_ver == ''){                
                return $error = array();
            } else {
                return $productdata;
            }
        }
    }
    
    
    /**
     * Gey All Scheduled Sale Products OR special_deal_products     
     */ 
    public function pgs_woo_api_scheduled_sale_products($no_of_items=10,$app_ver=''){
        
        global $wpdb,$woocommerce;
        $error = array( "status" => "error" );
        $productdata = array();     
        $qur = "SELECT posts.ID, posts.post_parent
        FROM $wpdb->posts posts
        INNER JOIN $wpdb->postmeta ON (posts.ID = $wpdb->postmeta.post_id)
        INNER JOIN $wpdb->postmeta AS mt1 ON (posts.ID = mt1.post_id)
        WHERE
            posts.post_status = 'publish'
            AND  (mt1.meta_key = '_sale_price_dates_to' AND mt1.meta_value >= ".time().")
            
            GROUP BY posts.ID 
            ORDER BY posts.post_title ASC";
        $product_ids_raw = $wpdb->get_results( $qur );        
        $product_ids_on_sale = array();
        $image = '';
        foreach ( $product_ids_raw as $product_raw ) 
        {
            if(!empty($product_raw->post_parent))
            {
                $product_ids_on_sale[] = $product_raw->post_parent;
            }
            else
            {
                $product_ids_on_sale[] = $product_raw->ID;              
            }
        }
        $product_ids_on_sale = array_unique($product_ids_on_sale);
            
        if ( !empty( $product_ids_on_sale ) ) {        
            
                foreach($product_ids_on_sale as $val){
                         
                    $product_id = $val;
                
                    if (has_post_thumbnail( $product_id )){
                        $image = get_the_post_thumbnail_url($product_id);    
                    } else {
                        $image = woocommerce_placeholder_img_src();               
                    }
                    $from = get_post_meta($product_id,'_sale_price_dates_from',true);                    
                        
                    $now = new DateTime();
                    $future_date = new DateTime(date('Y-m-d').' 24:00:00');
                    
                    $product = wc_get_product( $product_id );
                    $price_html = $product->get_price_html();
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $get_price = $product->get_price();
                    
                    /**
                     * Update currency rale if currency switcher plugin is active 
                     */ 
                    $is_currency_switcher_active = pgs_woo_api_is_currency_switcher_active();
                    if($is_currency_switcher_active){                        
                        $regular_price = $this->pgs_woo_api_update_currency_rate($regular_price);
                        $sale_price = $this->pgs_woo_api_update_currency_rate($sale_price);
                        $get_price = $this->pgs_woo_api_update_currency_rate($get_price);
                    }
                    
                    
                    $price = array(
                        'regular_price' => $regular_price,
                        'sale_price' => $sale_price,
                        'price' => $get_price,
                    );
                    $per = 0;
                    if( $product->is_type( 'simple' ) ){
                        if($regular_price > 0 && $sale_price > 0){
                            $per = round((($regular_price - $sale_price) / ($regular_price)) * 100);
                        }
                    } elseif( $product->is_type( 'variable' ) ){

            			$available_variations = $product->get_available_variations();
            
            			if($available_variations){
            
            				$percents = array();
            				foreach($available_variations as $variations){
            
            					$regular_price = $variations['display_regular_price'];
            					$sale_price = $variations['display_price'];
            
            					if ($regular_price){
            						$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
            						$percents[] =  $percentage;
            					}
            				}
            
            				$max_discount = min($percents);
            				$per = $max_discount;
            			}
            		}
                    
                    $interval = $future_date->diff($now);                            
                    $deal_life = array(                        
                        'hours' => $interval->format('%h'),
                        'minutes' => $interval->format('%i'),
                        'seconds' => $interval->format('%s')
                    );  
                    
                    
                    $average = $product->get_average_rating();
                              
                    if($from <= time()){
                        $productdata[] = array(
                            'id' => $product_id,
                            'title' => html_entity_decode(get_the_title($product_id)),
                            'image' => $image,
                            'deal_life' => $deal_life,
                            'price_html' => $price_html,
                            'price' => $price,
                            'percentage' => $per,                     
                            'rating' => $average
                        );
                    }                
                }
                if($app_ver == ''){
                    $sts = array( 
                        "status" => "success",
                        "products" => $productdata                    
                    );
                    return $sts;        
                } else {
                    return $productdata;          
                }
        } else {            
            if($app_ver == ''){
                $error['status'] = "error";
                $error['message'] = esc_html__("No product found","pgs-woo-api");
                return $error;
            } else {
                return $productdata;
            }
        }         
    }
    
    
    function pgs_woo_api_get_product_crousel_data($loop){                
        if($loop->have_posts()){
            while ( $loop->have_posts() ) : $loop->the_post();global $product; 
                //                             
                $product_id = get_the_ID();
            
                if (has_post_thumbnail( $product_id )){
                    $image = get_the_post_thumbnail_url($product_id);    
                } else {
                    $image = woocommerce_placeholder_img_src();               
                }
                
                $product = wc_get_product( $product_id );
                $price_html = $product->get_price_html();
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $get_price = $product->get_price();
                
                /**
                 * Update currency rale if currency switcher plugin is active 
                 */ 
                $is_currency_switcher_active = pgs_woo_api_is_currency_switcher_active();
                if($is_currency_switcher_active){
                    $regular_price = $this->pgs_woo_api_update_currency_rate($regular_price);
                    $sale_price = $this->pgs_woo_api_update_currency_rate($sale_price);
                    $get_price = $this->pgs_woo_api_update_currency_rate($get_price);
                       
                }
                
                
                $price = array(
                    'regular_price' => $regular_price,
                    'sale_price' => $sale_price,
                    'price' => $get_price,
                );
                $per = 0;
                if( $product->is_type( 'simple' ) ){
                    if($regular_price > 0 && $sale_price > 0){
                        $per = round((($regular_price - $sale_price) / ($regular_price)) * 100);
                    }
                } elseif( $product->is_type( 'variable' ) ){

        			$available_variations = $product->get_available_variations();
        
        			if($available_variations){
        
        				$percents = array();
        				foreach($available_variations as $variations){
        
        					$regular_price = $variations['display_regular_price'];
        					$sale_price = $variations['display_price'];
        
        					if ($regular_price){
        						$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
        						$percents[] =  $percentage;
        					}
        				}
        
        				$max_discount = min($percents);
        				$per = $max_discount;
        			}
        		}                          
                
                $average = $product->get_average_rating();
                $productdata[] = array(
                    'id' => $product_id,
                    'title' => html_entity_decode(get_the_title($product_id)),
                    'image' => $image,                    
                    'price_html' => $price_html,
                    'price' => $price,
                    'percentage' => $per,                     
                    'rating' => $average,                      
                );             
            endwhile;
        } else {            
            $productdata = array();
        }
        wp_reset_query();
        return $productdata;
    }
    
    function pgs_woo_api_get_product_crousel_query($number,$show,$orderby,$order){		 
        $product_visibility_term_ids = wc_get_product_visibility_term_ids();
		$query_args = array(
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
			//'order'          => $order,
			'meta_query'     => array(),
			'tax_query'      => array(
				'relation' => 'AND',
			),
		); // WPCS: slow query ok.		

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['outofstock'],
					'operator' => 'NOT IN',
				),
			); // WPCS: slow query ok.
		}

		switch ( $show ) {
			case 'featured':
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['featured'],
				);
				break;
			case 'recent':				
				break;                            
		}

		switch ( $orderby ) {
			case 'price':
				$query_args['meta_key'] = '_price'; // WPCS: slow query ok.
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'rand':
				$query_args['orderby'] = 'rand';
				break;
			case 'sales':
				$query_args['meta_key'] = 'total_sales'; // WPCS: slow query ok.
				$query_args['orderby']  = 'meta_value_num';
				break;
			default:
				$query_args['orderby'] = 'date';
		}        
		return new WP_Query( apply_filters( 'pgs_woo_api_get_product_crousel_query', $query_args ) );    
    }
    
    
    function pgs_woo_api_get_checkout_redirect_url(){
        //checkout redirect url
        $pgs_woo_api_checkout_custom_redirect_urls = get_option('pgs_woo_api_checkout_custom_redirect_urls');
        $redirect_urls=array();
        if(!empty($pgs_woo_api_checkout_custom_redirect_urls)){
            $urls=explode( "\n", $pgs_woo_api_checkout_custom_redirect_urls );
            foreach($urls as $url){
                if(!empty($url)){
                    $url = str_replace("\r","",$url);
                    if(!empty($url)){
                        $redirect_urls[]= $url;
                    }
                }
            }
        }
        return $redirect_urls;
    }
 }
new PGS_WOO_API_HomeController;