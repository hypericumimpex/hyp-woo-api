<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PGS_WOO_API_LiveSearchController extends PGS_WOO_API_Controller{
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
	protected $rest_base = 'live_search';
    	
	public function __construct() {
		$this->register_routes();        	
	}
	public function register_routes() {
		add_action( 'rest_api_init', array( $this, 'pgs_woo_api_register_route'));
	}
	
	
	public function pgs_woo_api_register_route() {        
        
        
        register_rest_route( $this->namespace, $this->rest_base, array(
    		'methods' => WP_REST_Server::CREATABLE,//'POST',
    		'callback' => array( $this, 'pgs_woo_api_get_live_search_products'),
            'permission_callback' => array($this, 'pgs_woo_api_permission_callback'),
    	) );    
    }
    
    
    /**
    * URL : http://yourdomain.com/wp-json/pgs-woo-api/v1/live_search
    * @param search: ####      
    */
    function pgs_woo_api_get_live_search_products(WP_REST_Request $request){
        
        $input = file_get_contents("php://input");
        $request = json_decode($input,true);
        
        $per_page = 10;
        if(isset($request['product-per-page'])) {
    		$per_page = $request['product-per-page'];
    	}
               
        $args = array(        
            'post_type' 			=> 'product',
    		'post_status' 			=> 'publish',
    		'ignore_sticky_posts'   => 1,        
    		'posts_per_page'		=> $per_page            
        );
        
        $search = isset($request['search']) ? $request['search'] : false;
        if(!empty($search) && $search != null){
			$args['s'] = $search;
		}
        
        $loop = new WP_Query( $args );    
        $error['status'] = 'error';
        if($loop->have_posts()):
            while ( $loop->have_posts() ) : $loop->the_post();
                $product_id = $loop->post->ID;
                $product_title = get_the_title($product_id);
                if ( has_post_thumbnail( $product_id ) ) {
                    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $loop->post->ID ), 'thumbnail' );
                    $product_image = $image[0]; 
                } else {
                    $product_image = wc_placeholder_img_src();        
                }                
                $data[] = array(
                    'id' => $product_id, 
                    'name' => $product_title,
                    'image' => $product_image
                );
             endwhile; 
            wp_reset_postdata();
        else :        
            $error['message'] = esc_html__("No product found","pgs-woo-api");
            return $error;    
        endif;    
        return $data;                    
    }
 }
 new  PGS_WOO_API_LiveSearchController;?>