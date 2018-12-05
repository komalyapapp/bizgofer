<?php

require get_stylesheet_directory() . '/api-functions.php';
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_filter( 'rest_url_prefix', 'biz_api_slug');
function biz_api_slug( $slug ) {
	return 'wp_json';
}


function my_login_logo_one() { 
	?> 
	<style type="text/css"> 
	body.login div#login h1 a {
	background-image: url(http://bizgofer.mastishakmitr.com/wp-content/uploads/2018/10/biz-gofer-logo-stacked.jpg); 
	padding-bottom: 30px; 
	} 
	</style>
	<?php 
} 
add_action( 'login_enqueue_scripts', 'my_login_logo_one' );
add_filter( 'woocommerce_rest_prepare_shop_order_object', 'custom_change_shop_order_response', 10, 3 );
function custom_change_shop_order_response( $response, $object, $request ) {
 $order = wc_get_order( $response->data['id'] );
 $discount_tax=0;
	$used_coupons = $request->get_param( 'coupon_lines' );	
    $coupon_amount = 0;
    if( !empty( $used_coupons ) ):
        foreach ($used_coupons as $coupon ){
            $coupon_id = $coupon['id'];
			$coupon_amount = $coupon['discount'];
        }
    endif;
	$order_coupons = $response->data['coupon_lines'];
    if( !empty( $order_coupons ) ) :
        foreach ( $order_coupons as $coupon ) {
            wc_update_order_item_meta( $coupon['id'], 'discount_amount', $coupon['discount'] );
            wc_update_order_item_meta( $coupon['id'], 'discount_total', $coupon['discount'] );		
        }
    endif;
	  $order_total = get_post_meta( $response->data['id'], '_order_total', true );
	  $order_total = $order_total - $coupon_amount ;

    update_post_meta( $order->ID, '_order_total', $order_total );
    update_post_meta( $order->ID, '_order_discount', $coupon_amount ); 
    $response->data['total']  = (string)$order_total;
    $response->data['discount_total']  = (string)$coupon_amount;
    return $response;
}


/* Adds the taxonomy page in the admin. */
add_action( 'admin_menu', 'my_add_profession_admin_page' );
function register_new_order_status() {
	register_post_status( 'wc-rejected', array(
        'label'                     => 'Rejected',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Rejected<span class="count">(%s)</span>', 'Rejected<span class="count">(%s)</span>' )
	) );
	register_post_status( 'wc-payment-pending', array(
        'label'                     => 'Payment Pending',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Payment Pending<span class="count">(%s)</span>', 'Payment Pending<span class="count">(%s)</span>' )
	) );
	register_post_status( 'wc-payment-paid', array(
        'label'                     => 'Payment Paid',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Payment Paid<span class="count">(%s)</span>', 'Payment Paid<span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_new_order_status' );
// Add to list of WC Order statuses
function add_shipped_to_order_statuses( $order_statuses ) {  
    $new_order_statuses = array();  
    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {  
        $new_order_statuses[ $key ] = $status;  
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-payment-paid'] = 'Payment Paid';           
            $new_order_statuses['wc-payment-pending'] = 'Payment Pending';           
        }
		if('wc-cancelled'== $key){
            $new_order_statuses['wc-rejected'] = 'Rejected';
		}
    }  
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_shipped_to_order_statuses' );
function wc_renaming_order_status( $order_statuses ) {
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-on-hold' === $key ) {
            $order_statuses['wc-on-hold'] = _x( 'Payment not completed', 'Order status', 'woocommerce' );
        }
		if ( 'wc-failed' === $key ) {
            $order_statuses['wc-failed'] = _x( 'Payment failed', 'Order status', 'woocommerce' );
        }
		if ( 'wc-rejected' === $key ) {
            $order_statuses['wc-rejected'] = _x( 'Rejected', 'Order status', 'woocommerce' );
        }
		if ( 'wc-shipped' === $key ) {
            $order_statuses['wc-shipped'] = _x( 'Shipped', 'Order status', 'woocommerce' );
        }
		if ( 'wc-payment-paid' === $key ) {
            $order_statuses['wc-payment-paid'] = _x( 'Payment Paid', 'Order status', 'woocommerce' );
        }
		if ( 'wc-refund-initiated' === $key ) {
            $order_statuses['wc-refund-initiated'] = _x( 'Refund Initiated', 'Order status', 'woocommerce' );
        }
    }
    return $order_statuses;
}
add_filter( 'wc_order_statuses', 'wc_renaming_order_status' );
function biz_dokan_get_order_status_translated( $status ) {
    switch ($status) {
      case 'completed':
        case 'wc-completed':
            return __( 'Completed', 'dokan-lite' );
            break;

        case 'pending':
        case 'wc-pending':
            return __( 'Pending Payment', 'dokan-lite' );
            break; 

		case 'payment-paid':
        case 'wc-payment-paid':
            return __( 'Payment Paid', 'dokan-lite' );
            break;

        case 'on-hold':
        case 'wc-on-hold':
            return __( 'Payment not completed', 'dokan-lite' );
            break;

        case 'processing':
        case 'wc-processing':
            return __( 'Processing', 'dokan-lite' );
            break;

        case 'refunded':
        case 'wc-refunded':
            return __( 'Refunded', 'dokan-lite' );
            break;

		case 'rejected':
        case 'wc-rejected':
            return __( 'Rejected', 'dokan-lite' );
            break;

		case 'shipped':
        case 'wc-shipped':
            return __( 'Shipped', 'dokan-lite' );
            break;

        case 'cancelled':
        case 'wc-cancelled':
            return __( 'Cancelled', 'dokan-lite' );
            break;

		case 'refund-initiated':
        case 'wc-refund-initiated':
            return __( 'Refund Initiated', 'dokan-lite' );
            break;
			
        case 'failed':
        case 'wc-failed':
            return __( 'Payment Failed', 'dokan-lite' );
            break;

        default:
            return apply_filters( 'dokan_get_order_status_translated', '', $status );
            break;
    }
}
function jwt_function($arg,$action){ 
	require get_stylesheet_directory() . '/inc/php-jwt/Authentication/JWT.php';
	require get_stylesheet_directory() . '/inc/php-jwt/Exceptions/ExpiredException.php';
	require get_stylesheet_directory() . '/inc/php-jwt/Exceptions/BeforeValidException.php';
	require get_stylesheet_directory() . '/inc/php-jwt/Exceptions/SignatureInvalidException.php';
	if($action=="encode"){
		return JWT::encode($arg, JWT_KEY, 'HS512');
	}
	elseif($action=="decode"){
		try{
			$decode=JWT::decode($arg, JWT_KEY, array('HS512'));
				if(($decode->aud=="http://bizgofer.mastishakmitr.com")&&($decode->iss=="http://bizgofer.mastishakmitr.com")){
					return $decode;
				}
				else{
					return "invalid domain";
				}
			}		
		catch(Exception $e){
			return $e->getMessage();	
		}
	}
	else{
		return "";
	}
}



/***komal functions ****/
function category_has_parent($catid){
    $category = get_category($catid);
    if ($category->category_parent > 0){
        return true;
    }
    return false;
}


function sendPushNotificationToFCMSever($deviceToken, $data, $deviceType=1, $user_id) {
	if($deviceType==0){
		$json_data=array(	
			"to" => $deviceToken,
			"data" =>$data
			);
	}
	else{
	$json_data=array(	
		"to" => $deviceToken,
		"notification"=>$data,
		"data" =>$data
		);
	}
	$querydata = json_encode($json_data);
	$server_key = "AAAASxNE7k0:APA91bFMKctHIfzMCGFDNp-GWRH4suqq_OxaXT7n2yWmdxy28-LgQrvwZqW7m8LK6yd2RVSl7-W4bIlVDgDHmxvm2w0ft8i4iumqL0FZJAwXP058V8Ebk1J3HTl6mA5z_Uftk8-dpzFG";
	//FCM API end-point
	$url = 'https://fcm.googleapis.com/fcm/send';
	//api_key in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key

	//header with content_type api key
	$headers = array(
		'Content-Type:application/json',
		'Authorization:key='.$server_key
	);
	//CURL request to route notification to FCM connection server (provided by Google)
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $querydata);
	$result = curl_exec($ch);
	if ($result === FALSE) {
		die('Oops! FCM Send Error: ' . curl_error($ch));
	}else{
		global $wpdb;
		$wpdb->insert( 
			'wp_notification', 
			array( 
				'message'=>$data['message'], 'order_id'=>$data['order_id'], 'user_id'=>$user_id, 'data'=>$querydata,
			)			
		);		
	}
	curl_close($ch);
}

/**
 * Register a Agent menu page.
 */
function register_coupon_menu_page() {
    add_menu_page(
        __( 'Coupons', 'textdomain' ),
        'Coupons',
        'manage_woocommerce',
        'coupons',
        'add_coupons_admin',
       '',
        6
	);
	add_submenu_page(
        'coupons',
        __( 'List Coupons', 'textdomain' ),
        __( 'list coupons', 'textdomain' ),
        'manage_woocommerce',
        'edit.php?post_type=shop_coupon'
    );
}


add_action( 'admin_menu', 'my_plugin_menu' );
function my_plugin_menu(){
	global $menu;
	foreach ( $menu as $k => $props ) {
		if($props[0]=="Dokan"){  $menu[$k][0]= "Manage Vendors";}
    }
	echo "<style>.copyButton {
		background: #008ec2;
		padding: 5px;
		color: #fff;
		margin: 0 5px;
		border-radius: 3px;
		cursor:pointer;
	}.copyButton:hover {
		color: #eee;	
	} .bzfcode{width: 0;
		height: 0;		
		border: none;
		box-shadow: none;
	}.error:first-of-type {

		display: none;
	
	}</style>";
$current_user = wp_get_current_user();
if ( in_array( 'bizagent', (array) $current_user->roles ) ) {  
echo "<style>#toplevel_page_woocommerce,.page-title-action{display:none;}</style>";
}
}
function get_coupon_expiry_date($expiry_date, $as_timestamp = false)
{
    if ('' != $expiry_date) {
        if ($as_timestamp) {
            return strtotime($expiry_date);
        }
        
        return date('Y-m-d', strtotime($expiry_date));
    }
    
    return '';
}
add_action( 'admin_menu', 'register_coupon_menu_page' );
wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );
function add_coupons_admin(){
	if(isset($_REQUEST['create_coupon'])){
		$request=$_REQUEST;
		$auth = get_post($request['product_ids'][0]); 
	$authid = $auth->post_author; 
		$args = array(
			'author' => $authid,
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'desc',
			'post_type' => 'shop_coupon',
			'post_status' => 'publish'
		);
		
		
		/**
		 * get the particular author coupons data
		 */
		$coupons = get_posts($args);		
		// check if this coupon already exist with the same name
		if (count($coupons) > 0) {
			foreach ($coupons as $coupon) {
				
				if ($coupon->post_title === $request['coupon_code']) {
					$coupon_post_id = $coupon->ID;
					$couponExist    = true;
					break;
				} else {
					$couponExist = false;
				}
				
			}
		} else {			
			$couponExist = false;			
		}
		
		if ($couponExist) {			
			$new_coupon = array(
				'ID' => $coupon_post_id,
				'post_author' => $authid,
				'post_status' => 'publish',
				'post_type' => 'shop_coupon'
			);
			$new_coupon_id = wp_update_post($new_coupon);			
			$data = $request['coupon_code']." Coupon Added Successfully";			
		} else {			
			$new_coupon    = array(
				'post_title' => $request['coupon_code'],
				'post_author' => $authid,
				'post_content' => '',
				'post_status' => 'publish',
				'post_type' => 'shop_coupon'
			);
			$new_coupon_id = wp_insert_post($new_coupon, true);			
			$data = "'".$request['coupon_code']."' Coupon Added Successfully";
			
		}
		
		// Add meta
		update_post_meta($new_coupon_id, 'discount_type', $request['discount_type']);
		update_post_meta($new_coupon_id, 'coupon_amount', wc_format_decimal($request['coupon_amount']));
		update_post_meta($new_coupon_id, 'individual_use', $request['individual_use']);		
		update_post_meta($new_coupon_id, 'product_ids', implode(',', array_filter(array_map('intval', $request['product_ids']))));
		update_post_meta($new_coupon_id, 'expiry_date', get_coupon_expiry_date(wc_clean($request['expiry_date'])));
		update_post_meta($new_coupon_id, 'date_expires',get_coupon_expiry_date(wc_clean($request['expiry_date']), true));		
		update_post_meta($new_coupon_id, 'product_categories', array_filter(array_map('intval', $request['product_categories'])));		
		echo "<div class='Success'><h3 style='display: inline;'>".$data."</h3>";
		echo '<input type="text" id="bzfcode" value="'.$request['coupon_code'].'" style="box-shadow: none;width:0; height:0; border:none;"/><a id="copyButton" class="copyButton" ><b>Click To Copy Coupon Code</b></a></div>';
	?>
	<script>
		document.getElementById("copyButton").addEventListener("click", function() {
			console.log(document.getElementById("bzfcode").value);
			copyToClipboard();
		});

		function copyToClipboard() {	
		/* Get the text field */
		var copyText = document.getElementById("bzfcode");
		/* Select the text field */
		copyText.select();
		/* Copy the text inside the text field */
		document.execCommand("copy");
		/* Alert the copied text */
		alert(copyText.value + ": Copied" );
		}
	</script>
	<?php }
	wp_enqueue_script('admin-widgets');
	wp_enqueue_script('suggest'); 
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script('dpic', '/wp-content/plugins/woocommerce/assets/js/admin/meta-boxes-coupon.min.js');
	wp_enqueue_script(
		'wc-enhanced-select',
		'wc_enhanced_select_params',
		array(
		  'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
		  'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
		  'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
		  'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
		  'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
		  'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
		  'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
		  'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
		  'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
		  'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
		  'ajax_url'                  => admin_url( 'admin-ajax.php' ),
		  'search_products_nonce'     => wp_create_nonce( 'search-products' ),
		  'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
		)
	  );
	  wp_enqueue_style( 'woocommerce_admin_styles', '/wp-content/plugins/woocommerce/assets/css/admin.css', array(), WC_VERSION );
	echo "<h1>Add Coupons</h1>";
	echo "<div id='coupon_options' class='panel-wrap coupon_data'>
	<form name='' method='post'>
	<div id='titlewrap'>
		<label class='' id='title-prompt-text' for='title'>Coupon code</label>
		<input name='coupon_code' size='30' value='' id='title' spellcheck='true' autocomplete='off' type='text' required>
	</div>
	<div class='wc-tabs-back'></div>

	<div id='general_coupon_data' class='panel' style='display: block;'>
		<p class='form-field discount_type_field'>
			<label for='discount_type'>Discount type</label>
			<select style='' id='discount_type' name='discount_type' class='select short'>
				<option value='fixed_product'>Fixed Product discount</option>		
			</select>
		</p>";		
		woocommerce_wp_text_input(
			array(
				'id'          => 'coupon_amount',
				'label'       => __( 'Coupon amount', 'woocommerce' ),
				'placeholder' => wc_format_localized_price( 0 ),
				'description' => __( 'Value of the coupon.', 'woocommerce' ),
				'data_type'   => 'price',
				'desc_tip'    => true,
				'value'       => '',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => 'expiry_date',
				'value'             => '',
				'label'             => __( 'Coupon expiry date', 'woocommerce' ),
				'placeholder'       => 'YYYY-MM-DD',
				'description'       => '',
				'class'             => 'date-picker',
				'custom_attributes' => array(
					'pattern' => apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ),
				),
			)
		);?>

		<p class="form-field">
		<label><?php _e( 'Products', 'woocommerce' ); ?></label>
		<select class="wc-product-search" multiple="multiple" style="width: 50%;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">			
		</select>
		<?php echo wc_help_tip( __( 'Products that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.', 'woocommerce' ) ); ?>
	</p>
	<p class="form-field">
		<label for="product_categories"><?php _e( 'Product categories', 'woocommerce' ); ?></label>
		<select id="product_categories" name="product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'woocommerce' ); ?>">
			<?php						
			$categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

			if ( $categories ) {
				foreach ( $categories as $cat ) {
					echo '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
				}
			}
			?>
		</select> <?php echo wc_help_tip( __( 'Product categories that the coupon will be applied to, or that need to be in the cart in order for the "Fixed cart discount" to be applied.', 'woocommerce' ) ); ?>
	</p>

	<?php 	echo "
	<p><input type='submit' name='create_coupon' value='Create'/></p>
	</div>
	</form>
</div>";?>
	 <script>	
	  var se_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
	 jQuery( '.selectfield_type' ).change(function() {
		jQuery('.stylenone').hide();
		jQuery("." + jQuery(this).val() + "field").show();
		jQuery('#se_search_element_id').suggest(se_ajax_url + '?action=product_lookup');
	  });
		
	</script>
<?php }
add_action('wp_enqueue_scripts', 'se_wp_enqueue_scripts');
function se_wp_enqueue_scripts() {
    wp_enqueue_script('suggest');
}
add_action('wp_ajax_product_lookup', 'product_lookup');
add_action('wp_ajax_nopriv_product_lookup', 'product_lookup');
    
    function product_lookup() {
        global $wpdb;
    
        $search = like_escape($_REQUEST['q']);
    
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish', 
            'posts_per_page'        => -1,
            'search_prod_title'=>$search

        );
        add_filter( 'posts_where', 'title_filters', 10, 2 );
        $products = new WP_Query($args);
        remove_filter( 'posts_where', 'title_filters', 10, 2 );
        
        foreach($products->posts as $product){
            echo $product->post_title."<span class='none'>(ID".$product->ID.")</span>" ."\n";
        }
        wp_die();
	}
	
	function title_filters( $where, &$wp_query ){
        global $wpdb;
        if ( $search_term = $wp_query->get( 'search_prod_title' ) ) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $search_term ) ) . '%\'';
        }
        return $where;
    } 
	add_filter( 'woocommerce_product_data_panels', 'add_meta_box_content', 10, 1 );
	add_filter( 'woocommerce_variable_product_before_variations', 'add_meta_box_kk', 10, 1 );
	function add_meta_box_kk(){ ?>
		<script>	
			jQuery( ".variations_tab").click(function() {			
				setTimeout(function(){				
					jQuery.each(jQuery('.woocommerce_variation'), function (index,value) { 
						var postid=jQuery("#post_ID").val();
						var attrid = jQuery(value).find(".remove_variation").attr("rel");
					if(jQuery(value).find(".bzfcode").length==0){
						jQuery(value).find(".remove_variation").before('<input type="text" class="bzfcode" value="BZF_'+postid+'_'+attrid+'" style="width:0; height:0; border:none;"/><a class="copyButton" style="cursor:pointer" ><b>CLICK TO COPY CODE</b></a>');
					}					
					});
					jQuery(".copyButton").on("click",function(){
						var copyText=jQuery(this).prev('.bzfcode');					
							copyText.select();
						document.execCommand("copy");					
						alert(copyText.val() + ": Copied" );
						});

				},2000);
			});
			

		</script>
	<?php }

	function add_meta_box_content()
	{
		$id=get_the_ID();
		$product = wc_get_product( $id );
		$type=$product->get_type();
		$status=$product->get_status();
		if($type=="simple"&&$status=="publish"){
		echo '<input type="text" id="bzfcode" value="BZF_'.$id .'" style="width:0; height:0; border:none;"/><a id="copyButton" class="copyButton" ><b>Click To Copy Product URL</b></a>';
		?>
		<script>
			document.getElementById("copyButton").addEventListener("click", function() {
				console.log(document.getElementById("bzfcode").value);
				copyToClipboard();
			});

			function copyToClipboard() {
				var copyText = document.getElementById("bzfcode");
				copyText.select();
				document.execCommand("copy");
				alert( copyText.value + ": Copied" );
			}
		</script>
		<?php
		}
	}
?>