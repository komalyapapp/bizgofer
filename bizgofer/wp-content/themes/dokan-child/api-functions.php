<?php 
   function get_gps_distance($lat1,$long1,$d,$angle)
{
    # Earth Radious in KM
    $R = 6378.14;
    # Degree to Radian
    $latitude1 = $lat1 * (M_PI/180);
    $longitude1 = $long1 * (M_PI/180);
    $brng = $angle * (M_PI/180);
    $latitude2 = asin(sin($latitude1)*cos($d/$R) + cos($latitude1)*sin($d/$R)*cos($brng));
    $longitude2 = $longitude1 + atan2(sin($brng)*sin($d/$R)*cos($latitude1),cos($d/$R)-sin($latitude1)*sin($latitude2));

    # back to degrees
    $latitude2 = $latitude2 * (180/M_PI);
    $longitude2 = $longitude2 * (180/M_PI);

    # 6 decimal for Leaflet and other system compatibility
   $lat2 = round ($latitude2,6);
   $long2 = round ($longitude2,6);

   // Push in array and get back
   $tab[0] = $lat2;
   $tab[1] = $long2;
   return $tab;
 }
    function bizgofer_categories($request)   {  
        $request['radius']=50000;
        global $wpdb;
        if(isset($request['latitude']) && isset($request['longitude']) && isset($request['radius'])){
            $meta = get_post_meta( 18 ,'slide_detail' );  
            $prepared_args['role'] = "seller";
            if($request['radius'] && $request['latitude'])
            {
                
                $lat1 = number_format($request['latitude'], 5);
                $lon1 = number_format($request['longitude'], 5);
                $d 	  = $request['radius']; 
                //earth's radius in miles
                $r = 3959;

                //compute max and min latitudes / longitudes for search square
                $latN = rad2deg(asin(sin(deg2rad($lat1)) * cos($d / $r) + cos(deg2rad($lat1)) * sin($d / $r) * cos(deg2rad(0))));
                $latS = rad2deg(asin(sin(deg2rad($lat1)) * cos($d / $r) + cos(deg2rad($lat1)) * sin($d / $r) * cos(deg2rad(180))));
                $lonE = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(90)) * sin($d / $r) * cos(deg2rad($lat1)), cos($d / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));
                $lonW = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(270)) * sin($d / $r) * cos(deg2rad($lat1)), cos($d / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));

                //display information about starting point
                $hor1=get_gps_distance($lat1,$lon1,$request['radius'],0);
                $hor2=get_gps_distance($lat1,$lon1,$request['radius'],180);
                
                $prepared_args['meta_query'] = array('relation' => 'AND',
                    array('key'     => 'dokan_enable_selling',	'value' => 'yes','compare' => 'LIKE'),
                    array('key'     => 'latitude', 'value' => $hor1[0], 'compare' => '<='),
                    array('key'     => 'latitude', 'value' => $hor2[0], 'compare' => '>='),
                    
                    array('key'     => 'longitude', 'value' => $hor1[1], 'compare' => '<='),
                    array('key'     => 'longitude', 'value' => $hor2[1], 'compare' => '>=')
                );              
            }
            $query = new WP_User_Query( $prepared_args );
            foreach($query->results as $vendor){
                $store_ids[]=$vendor->ID;
            }
            $storeid=implode(",",$store_ids);
            
            $sql="SELECT DISTINCT(terms.term_id) as ID, terms.name, tax.parent FROM {$wpdb->posts} as posts LEFT JOIN {$wpdb->term_relationships} as relationships ON posts.ID = relationships.object_ID LEFT JOIN {$wpdb->term_taxonomy} as tax ON relationships.term_taxonomy_id = tax.term_taxonomy_id LEFT JOIN {$wpdb->terms} as terms ON tax.term_id = terms.term_id WHERE 1=1 AND ( posts.post_status = 'publish' AND tax.taxonomy = 'product_cat' and posts.post_author in (". $storeid .") ) ORDER BY terms.name ASC";
            $allcat=$wpdb->get_results($sql, OBJECT);                
            $data = array();               
            foreach($allcat as $r){
                $subcat=array();
                if($r->parent==0){                                              
                    $thumbnail_id = get_woocommerce_term_meta($r->ID, 'thumbnail_id', true);
                    if($thumbnail_id){
                    // get the image URL for parent category
                        $image = wp_get_attachment_url($thumbnail_id);
                    }else{
                        $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
                    } 
                    $data[]=array(				
                        "id"=>$r->ID,
                        "name"=>html_entity_decode($r->name),
                        "thumb"=> $image                  
                    );
                }					
            }
            $result=array(
            "statuscode"=>200,
            "message"=> "Success",
            "data"=>array("categories"=>$data,
            "banner"=>$meta
                )
            );		
        }
        else{
            $result=array(
                "statuscode"=>401,
                "message"=> "invalid Details",
                "errors"=>"Missing Some details!"
            );
        }	
    
        return $result;
    }   
    function bizgofer_categories_without_location($request){ 
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){  
             global $wpdb;
             $sql="SELECT DISTINCT(terms.term_id) as ID, terms.name, tax.parent FROM {$wpdb->posts} as posts LEFT JOIN {$wpdb->term_relationships} as relationships ON posts.ID = relationships.object_ID LEFT JOIN {$wpdb->term_taxonomy} as tax ON relationships.term_taxonomy_id = tax.term_taxonomy_id LEFT JOIN {$wpdb->terms} as terms ON tax.term_id = terms.term_id WHERE 1=1 AND ( posts.post_status = 'publish' AND tax.taxonomy = 'product_cat' ) ORDER BY terms.name ASC";
             $allcat=$wpdb->get_results($sql, OBJECT);                
             $data = array();               
             foreach($allcat as $r){
                 $subcat=array();
                 if($r->parent==0){                                              
                     $thumbnail_id = get_woocommerce_term_meta($r->ID, 'thumbnail_id', true);
                     if($thumbnail_id){
                     // get the image URL for parent category
                         $image = wp_get_attachment_url($thumbnail_id);
                     }else{
                         $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
                     } 
                     $data[]=array(				
                         "id"=>$r->ID,
                         "name"=>html_entity_decode($r->name),
                         "thumb"=> $image                  
                     );
                 }					
             }
             $result=array(
             "statuscode"=>200,
             "message"=> "Success",
             "data"=>$data  
             );
        }
        else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
         return $result;
     }     
    function bizgofer_subcategorieswithproducts($request){
        global $wpdb;
        $catid=$request['catid'];
        $subcat=get_categories( array( 'child_of' => $catid,'parent' => $catid,'type' => 'post', 'taxonomy' => 'product_cat', 'order' => 'ASC', 'orderby' => 'name' ) );
        foreach ($subcat as $r){
            $children = get_term_children($r->term_id, 'product_cat');
            $thumbnail_id = get_woocommerce_term_meta($r->term_id, 'thumbnail_id', true);
            if($thumbnail_id){            
                $image = wp_get_attachment_url($thumbnail_id);
            }else{
                $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
            }
            $data[]=array(				
                "id"=>$r->term_id,             
                "name"=>html_entity_decode($r->name),
                "thumb"=> $image,
                "type"=>"subcategory",
                "extandable"=> empty( $children ) ? false : true               
            );
            $tax_q[]=array(
                'taxonomy'      => 'product_cat',
                'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                'terms'         => $r->term_id,
                'operator'      => 'NOT IN' // Possible values are 'IN', 'NOT IN', 'AND'.
            );
        }
        $tax_q[]=array(
            'taxonomy'      => 'product_cat',
            'field' => 'term_id', //This is optional, as it defaults to 'term_id'
            'terms'         => $catid,
            'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
        );       
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',         
            'posts_per_page'        => '12',
            'tax_query'             => array(
                'relation' => 'AND',   
                $tax_q

            )
        );
        $products = new WP_Query($args);
        foreach($products->posts as $product){
            $thumbnail_id = get_post_meta($product->ID, '_thumbnail_id', true);
            if($thumbnail_id){            
                $image = wp_get_attachment_url($thumbnail_id);
            }else{
                $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
            }
            $data[]=array(
                "id"=>$product->ID,
                "name"=>$product->post_title,
                "thumb"=>$image,
                "type"=>"product",   
                "extandable"=>false
            );
        }
        $result=array(
				"statuscode"=>200,
                "message"=> "Success",
                "data"=>$data
				);
        return $result;
    }
    function bizgofer_subcategorieswithoutproduct($request){
        global $wpdb;
      //  $request = $request->get_json_params();
        
        $catid=$request['catid'];
        $subcat=get_categories( array( 'child_of' => $catid, 'type' => 'post', 'taxonomy' => 'product_cat', 'order' => 'ASC', 'orderby' => 'name' ) );
        foreach ($subcat as $r){
            $thumbnail_id = get_woocommerce_term_meta($r->term_id, 'thumbnail_id', true);
            if($thumbnail_id){
            // get the image URL for parent category
                $image = wp_get_attachment_url($thumbnail_id);
            }else{
                $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
            }
            $data[]=array(				
                "id"=>$r->term_id,
                "name"=>$r->name,
                "thumb"=> $image                  
            );
        }
        $result=array(
				"statuscode"=>200,
                "message"=> "Success",
                "data"=>$data
				);
        return $result;
    }
    function bizgofer_product_list_admin($request){
        global $wpdb;
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish', 
            'author'                => 7,        
            'posts_per_page'        => $request['per_page'],
            'paged'                 => $request['page'],
            'tax_query'             => array(
                'relation' => 'AND',   
                array(
                    'taxonomy'      => 'product_cat',
                    'field' => 'term_id',
                    'terms'         => $request['category'],
                    'operator'      => 'IN' 
                )    
            )
        );
        $products = new WP_Query($args);
        $total_posts = $products->found_posts;
        foreach($products->posts as $product){
            $catname=array();
            $thumbnail_id = get_post_meta($product->ID, '_thumbnail_id', true);
            if($thumbnail_id){            
                $image = wp_get_attachment_url($thumbnail_id);
            }else{
                $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
            }
            foreach ( wc_get_object_terms( $product->ID, 'product_cat') as $term ) {
                $catname[] =  html_entity_decode($term->name);
        }
            $data[]=array(
                "id"=>$product->ID,
                "name"=>$product->post_title,
                "thumb"=>$image,
                "categories"=>$catname
            );
        }
        $result=array(
				"statuscode"=>200,
                "message"=> "Success",
                "data"=>$data
                );
        $max_pages = ceil( $total_posts / (int)$request['per_page'] );
        $response = rest_ensure_response( $result );
		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );
        return $response;
    } 
    function bizgofer_product_list_vendor($request){
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
            global $wpdb;
            $args = array(
                'post_type'             => 'product',
                'post_status'           => 'publish', 
                'author'                => $user_id,
                'posts_per_page'        => $request['per_page'],
                'paged'                 => $request['page'],

            );
            $products = new WP_Query($args);
            $total_posts = $products->found_posts;
            foreach($products->posts as $product){
                $catname=array();
                $thumbnail_id = get_post_meta($product->ID, '_thumbnail_id', true);
                if($thumbnail_id){            
                    $image = wp_get_attachment_url($thumbnail_id);
                }else{
                    $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
                }
                foreach ( wc_get_object_terms( $product->ID, 'product_cat') as $term ) {
                        $catname[] =  html_entity_decode($term->name);
                }
                $data[]=array(
                    "id"=>$product->ID,
                    "name"=>$product->post_title,
                    "terms"=>$product->post_excerpt, 
                    "thumb"=>$image, 
                    "catgories"=>$catname
                    
                );
            }
            $result=array(
                    "statuscode"=>200,
                    "message"=> "Success",
                    "data"=>$data
                    );
                    $max_pages = ceil( $total_posts / (int)$request['per_page'] );
                    $response = rest_ensure_response( $result );
                    $response->header( 'X-WP-Total', (int) $total_posts );
                    $response->header( 'X-WP-TotalPages', (int) $max_pages );
                    return $response;
        }
        else{
            $response = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
        return $response;
    } 

    function bizgo_get_taxonomy_terms( $product, $taxonomy = 'cat' ) {
        $terms = array();
        foreach ( wc_get_object_terms( $product->get_id(), 'product_' . $taxonomy ) as $term ) {
            $terms[] = array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }

        return $terms;
    }
    
    function bizgo_get_attributes( $product ) {
        $attributes = array();

        if ( $product->is_type( 'variation' ) ) {
          // Variation attributes.
          foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
            $name = str_replace( 'attribute_', '', $attribute_name );
    
            if ( ! $attribute ) {
              continue;
            }
    
            // Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
            if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
              $option_term = get_term_by( 'slug', $attribute, $name );
              $attributes[] = array(
                'id'     => wc_attribute_taxonomy_id_by_name( $name ),
                'name'   => bizgo_get_attribute_taxonomy_name( $name ),
                'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
              );
            } else {
              $attributes[] = array(
                'id'     => 0,
                'name'   => $name,
                'option' => $attribute,
              );
            }
          }
        } else {
          foreach ( $product->get_attributes() as $attribute ) {
            if ( $attribute['is_taxonomy'] ) {
              $attributes[] = array(
                'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
                'name'      => bizgo_get_attribute_taxonomy_name( $attribute['name'] ),
                'position'  => (int) $attribute['position'],
                'visible'   => (bool) $attribute['is_visible'],
                'variation' => (bool) $attribute['is_variation'],
                'options'   => bizgo_get_attribute_options( $product->get_id(), $attribute ),
              );
            } else {
              $attributes[] = array(
                'id'        => 0,
                'name'      => $attribute['name'],
                'position'  => (int) $attribute['position'],
                'visible'   => (bool) $attribute['is_visible'],
                'variation' => (bool) $attribute['is_variation'],
                'options'   => bizgo_get_attribute_options( $product->get_id(), $attribute ),
              );
            }
          }
        }
    
        return $attributes;
      }

  
    function bizgo_get_attribute_options( $product_id, $attribute ) {
        if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
            return wc_get_product_terms( $product_id, $attribute['name'], array(
                'fields' => 'names',
            ) );
        } elseif ( isset( $attribute['value'] ) ) {
            return array_map( 'trim', explode( '|', $attribute['value'] ) );
        }

        return array();
    }

    function bizgofer_get_variation_data( $product ) {
        $variations = array();
        foreach ( $product->get_children() as $child_id ) {
            $variation = wc_get_product( $child_id );
            if ( ! $variation || ! $variation->exists() ) {
                continue;
            }
            $variations[] = array(
                'id'                 => $variation->get_id(),                
                'sku'                => $variation->get_sku(),
                'price'              => $variation->get_price(),
                'regular_price'      => $variation->get_regular_price(),
                'sale_price'         => $variation->get_sale_price(),
                'date_on_sale_from'  => $variation->get_date_on_sale_from() ? date( 'Y-m-d', $variation->get_date_on_sale_from()->getTimestamp() ) : '',
                'date_on_sale_to'    => $variation->get_date_on_sale_to() ? date( 'Y-m-d', $variation->get_date_on_sale_to()->getTimestamp() ) : '',
                'on_sale'            => $variation->is_on_sale(),               
                'tax_status'         => $variation->get_tax_status(),
                'tax_class'          => $variation->get_tax_class(),
                'manage_stock'       => $variation->managing_stock(),
                'stock_quantity'     => $variation->get_stock_quantity(),
                'in_stock'           => $variation->is_in_stock(),
                'shipping_class'     => $variation->get_shipping_class(),
                'shipping_class_id'  => $variation->get_shipping_class_id(),                
                'attributes'         => bizgo_get_attributes( $variation ),
            );
        }
        return $variations;
    }
    function bizgofer_get_particular_variation_data( $variationid ) {        
            $variation = wc_get_product( $variationid );
            $variations = array(
                'id'                 => $variation->get_id(),                
                'sku'                => $variation->get_sku(),
                'price'              => $variation->get_price(),
                'regular_price'      => $variation->get_regular_price(),
                'sale_price'         => $variation->get_sale_price(),
                'date_on_sale_from'  => $variation->get_date_on_sale_from() ? date( 'Y-m-d', $variation->get_date_on_sale_from()->getTimestamp() ) : '',
                'date_on_sale_to'    => $variation->get_date_on_sale_to() ? date( 'Y-m-d', $variation->get_date_on_sale_to()->getTimestamp() ) : '',
                'on_sale'            => $variation->is_on_sale(),               
                'tax_status'         => $variation->get_tax_status(),
                'tax_class'          => $variation->get_tax_class(),
                'manage_stock'       => $variation->managing_stock(),
                'stock_quantity'     => $variation->get_stock_quantity(),
                'in_stock'           => $variation->is_in_stock(),
                'shipping_class'     => $variation->get_shipping_class(),
                'shipping_class_id'  => $variation->get_shipping_class_id(),                
                'attributes'         => bizgo_get_attributes( $variation ),
            );
        return $variations;
    }

   
    function bizgo_get_attribute_taxonomy_name($name) {
        $tax    = get_taxonomy( $name );
        $labels = get_taxonomy_labels( $tax );

        return $labels->singular_name;
    }
    function check_user_exist($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
        $user=get_users(
            array(
             'meta_key' => 'mobile',
             'meta_value' => $data['mobile'],
             'number' => 1,
             'count_total' => false
        ) );
        if (email_exists($data['user_email'])){
            $result=array(
                "statuscode"=>401,
                "error"=> "user already exists",
                "message"=>"Sorry, that user already exists as a customer/vendor!"
            );
        }
        elseif(count($user)>0){
            $result=array(
                "statuscode"=>401,
                "error"=> "user already exists",
                "message"=>"Sorry, that user already exists as a customer/vendor!"
            );
        }
        else{
            $result=array(
                "statuscode"=>200,               
                "message"=>"Success!",
                "data"=>true
            );
        }
        return $result;
    }
    function signup_any_user($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
         if (email_exists($data['user_email'])){
            $result=array(
                "statuscode"=>401,
                "error"=> "user already exists",
                "message"=>"Sorry, that user already exists!"
            );
         }	else{
            $allowed_params = array('user_login', 'user_email', 'user_pass', 'display_name', 'user_nicename', 'user_url', 'nickname', 'first_name','last_name', 'description', 'rich_editing', 'user_registered', 'role', 'jabber', 'aim', 'yim', 'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front');
            $user=array();
            foreach($data as $field => $value){   

                if( in_array($field, $allowed_params) ){
                  $user[$field] = trim(sanitize_text_field($value)); 
                }
                else{
                  $usermeta[$field] = trim(sanitize_text_field($value)); 
                }                  
            } 
            if($data["role"]==""){
                $user['role'] = get_option('default_role');
            } 
           $user_id = wp_insert_user( $user );
           if($user_id)	{
            if($user['role'] == "seller"){
                    $dokan_settings = array(
                        'store_id'     => $user_id,
                        'store_name'     => strip_tags( $data['store_name'] ),
                        'store_title'     => strip_tags( $data['store_name'] ),
                        'social'         => array(),
                        'payment'        => array(),
                        'phone'          => $data['mobile'],
                        'address'        => array(
                                            "street_1"=> $data['address'],
                                            "street_2"=> "",
                                            "city"=> $data['city'],
                                            "zip"=> $data['pin_code'],
                                            "country"=> "IN",
                                            "state"=> $data['state'],
                                            ),
                        'logo'        => $data['profile_image'],
                        'show_email'     => 'no',
                        'location'       => strtoupper($data['city']),
                        'find_address'   => '',
                        'banner'         => 0,
                        'enable_tnc'	=> "on",
                        'store_tnc'     => ""
                    );
                    update_user_meta($user_id,'dokan_profile_settings', $dokan_settings );             
                    update_user_meta($user_id,"dokan_enable_selling","yes");
                    update_user_meta($user_id,"dokan_store_name",$data['store_name']);
                    update_user_meta($user_id,"dokan_publishing","yes");
                }
        
                foreach($usermeta as $f=>$v){
                    update_user_meta( $user_id, $f, $v);
                }
                $users = new WP_User($user_id);
                $r=$users->data; 
                $r->{"role"} =$users->roles[0]; 
                $meta=get_user_meta( $users->ID );
                $r->{"first_name"} =$meta['first_name'][0];            
                $r->{"last_name"} =$meta['last_name'][0];            
                $r->{"mobile"} =$meta['mobile'][0];            
                $r->{"source"} =$meta['source'][0];            
                $r->{"profile_image"} =isset($meta['profile_image'][0])?$meta['profile_image'][0]:"";
                $arg=array(
                    "user_login" => $r->user_login,
                    "user_id"=>$users->ID,	
                    "iat" => time(),
                    "exp" => time() + 1.577e+7, // time in the future 
                    "aud" =>"http://bizgofer.mastishakmitr.com",
                    "iss" =>"http://bizgofer.mastishakmitr.com"			
                );                
                $jwt_token=jwt_function($arg,"encode");    
                $r->{"auth_token"}=$jwt_token;
                if($users->roles[0]=="seller"){
                    $r->{"store_name"}=$meta['dokan_store_name'][0];
                    $r->{"address"}=$meta['address'][0];
                    $r->{"city"}=$meta['city'][0];
                    $r->{"state"}=$meta['state'][0];
                    $r->{"pin_code"}=$meta['pin_code'][0];
                    $r->{"gstin"}=$meta['gstin'][0];
                    $r->{"longitude"}=$meta['longitude'][0];
                    $r->{"latitude"}=$meta['latitude'][0];
                    $r->{"custom_address"}=$meta['custom_address'][0];
                }
                $result=array(
                    "statuscode"=>200,
                    "message"=> "Success",
                    "data"=>$r
                );
            }
            else{
                $result=array(
                    "statuscode"=>400,
                    "message"=> "error",
                    "data"=>$user_id
                );
            }
        }
       return $result;
    }
    function update_user($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id; 
            $allowed_params = array('user_pass', 'display_name', 'user_nicename','user_email',  'nickname', 'first_name','last_name', 'description', 'rich_editing', 'user_registered', 'role', 'jabber', 'aim', 'yim', 'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front');
            $user=array(); 
            $user['ID'] = $user_id;
            foreach($data as $field => $value){   

                if( in_array($field, $allowed_params) ){
                  $user[$field] = trim(sanitize_text_field($value)); 
                }
                else{
                  $usermeta[$field] = trim(sanitize_text_field($value)); 
                }                  
            } 
            
            $updateuserid =  wp_update_user(  $user  );    
            if ( is_wp_error( $user_id ) ) {
                $result = array(
                    "statuscode"=>402,
                    "message"=> "error",
                    "data"=>"Profile can not be updated"
                );
            }
            else{
                foreach($usermeta as $f=>$v){
                    update_user_meta( $user_id, $f, $v);
                }
                $users = new WP_User($user_id);
                $r=$users->data; 
                $r->{"role"} =$users->roles[0]; 
                if($users->roles[0] == "seller"){
                    $userprofiledata=get_user_meta($user_id,'dokan_profile_settings',true);
                    if(isset($data['store_title'])&&($data['store_title']!="")&&($data['store_title']!="undefined")){
                        $userprofiledata['store_title']=$data['store_title'];
                    }
                    if(isset($data['mobile'])&&($data['mobile']!="")&&($data['mobile']!="undefined")){
                        $userprofiledata['phone']="+".$data['mobile'];
                    }
                    if(isset($data['profile_image'])&&($data['profile_image']!="")&&($data['profile_image']!="undefined")){
                        $userprofiledata['logo']=$data['profile_image'];
                    }
                    if(isset($data['city'])&&($data['city']!="")&&($data['city']!="undefined")){
                        $userprofiledata['location']=strtoupper($data['city']);
                        $userprofiledata['address']['city']=$data['city'];
                    }
                    if(isset($data['state'])&&($data['state']!="")&&($data['state']!="undefined")){
                       $userprofiledata['address']['state']=$data['state'];
                    }
                    if(isset($data['address'])&&($data['address']!="")&&($data['address']!="undefined")){
                        $userprofiledata['address']['street_1']=$data['address'];
                    }					
                    if(isset($data['pin_code'])&&($data['pin_code']!="")&&($data['pin_code']!="undefined")){
                        $userprofiledata['address']['zip']=$data['pin_code'];
                    }
                    if(isset($data['store_name'])&&($data['store_name']!="")&&($data['store_name']!="undefined")){
                        $userprofiledata['store_name']=strtoupper($data['store_name']);
                        
                        update_user_meta($user_id,"dokan_store_name",$data['store_name']);
                    }
                    update_user_meta($user_id,"dokan_profile_settings",$userprofiledata);
                }
                $meta=get_user_meta( $users->ID );
                $r->{"first_name"} =$meta['first_name'][0];            
                $r->{"last_name"} =$meta['last_name'][0];            
                $r->{"mobile"} =$meta['mobile'][0]; 
                $r->{"source"} =$meta['source'][0];               
                $r->{"profile_image"} =isset($meta['profile_image'][0])?$meta['profile_image'][0]:"";
                if($users->roles[0]=="seller"){
                    $r->{"store_name"}=$meta['dokan_store_name'][0];
                    $r->{"address"}=$meta['address'][0];
                    $r->{"city"}=$meta['city'][0];
                    $r->{"state"}=$meta['state'][0];
                    $r->{"pin_code"}=$meta['pin_code'][0];
                    $r->{"gstin"}=$meta['gstin'][0];
                    $r->{"longitude"}=$meta['longitude'][0];
                    $r->{"latitude"}=$meta['latitude'][0];
                    $r->{"custom_address"}=$meta['custom_address'][0];
                }
                $result=array(
                    "statuscode"=>200,
                    "message"=> "Success",
                    "data"=>$r
                );
            }
        }  else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
       return $result;
    }

    function change_password($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id; 
            $user_login = $jwt->user_login; 
            
            $login = wp_authenticate_username_password(NULL, $user_login , $data['old_password']);
            if($login->data!=null){
            $updateuserid =  wp_update_user( array( "ID"=>$user_id, "user_pass" => $data['new_password']) );    
            if ( is_wp_error( $updateuserid ) ) {
                $result = array(
                    "statuscode"=>402,
                    "message"=> "Profile can not be updated",
                    "errors"=>"Profile can not be updated"
                );
            }
            else{
                $result=array(
                    "statuscode"=>200,
                    "message"=>"Password Updated Successfully!",
                    "data"=>$user_id
                );
            }
          } else{ 
              $result = array(
                "statuscode"=>403,
                "message"=>"Invalid Details!",
                "errors"=>"Invalid Details!"
                );
          }
        }  else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
       return $result;
    }

    function bizgofer_change_lock_key($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
            $user=get_users(
                array(
                 'meta_key' => 'mobile',
                 'meta_value' => $data['mobile'],
                 'number' => 1,
                 'count_total' => false
            ) );           
           if(count($user)>0){
                $user_id=$user[0]->ID;
                $updateuserid =  wp_update_user( array( "ID"=>$user_id, "user_pass" => $data['new_password']) ); 
                $result=array(
                    "statuscode"=>200,
                    "message"=>"Password Updated Successfully!",
                    "data"=>$user_id
                );  
            }else{
                $result=array(
                        "statuscode"=>401,
                        "error"=> "user does not exists",
                        "message"=>"Sorry, Invalid user!"
                    );
            }           
     
       return $result;
    }
    function bizgofer_lost_lock_key($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
            $user=get_users(
                array(
                 'search_columns' => 'email',
                 'search' => $data['email'],
                 'number' => 1,
                 'count_total' => false
            ) );  
             
           if(count($user)>0){
                $user_login=$data['email'];
                $sent_mail=bizgofer_retrieve_password($user_login);
                if($sent_mail){
                    $result=array(
                        "statuscode"=>200,
                        "message"=>"Email sent to your email, please check your email!",
                        "data"=>$user_login
                    );
                } 
                else{
                    $result=array(
                        "statuscode"=>402,
                        "message"=>"Something went wrong!",
                        "error"=>"Something went wrong!",                       
                    ); 
                } 
            }else{
                $result=array(
                        "statuscode"=>401,
                        "error"=> "user does not exists",
                        "message"=>"Sorry, Invalid user!"
                    );
            }           
     
       return $result;
    }

    function bizgofer_login_user($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }        
        if(isset($data['source']) && isset($data['user_login']) && isset($data['user_pass'])){            
            if($data['source']=="email"){
                $input=$data['user_login'];
                if(!filter_var($input, FILTER_VALIDATE_EMAIL)){
                    $user=get_users(
                        array(
                        'meta_key' => 'mobile',
                        'meta_value' => $input,
                        'number' => 1,
                        'count_total' => false
                    ) );           
                }
                else{
                    if(strpos($input,"+")!== false){ 
                        $input=str_replace("+","",$input);
                    }
                    $user=get_users(
                        array(
                        'search_columns' => 'email',
                        'search' => $input,
                        'number' => 1,
                        'count_total' => false
                    ) );  
                }
                if(count($user)>0){
                    $user_login = $user[0]->user_login;
                    $login = wp_authenticate_username_password(NULL, $user_login , $data['user_pass']);
                    if($login->data!=null){                    
                        $meta=get_user_meta( $login->ID );
                        $r=$login->data; 
                        $r->{"role"} =$login->roles[0];            
                        $r->{"first_name"} =$meta['first_name'][0];            
                        $r->{"last_name"} =$meta['last_name'][0];            
                        $r->{"mobile"} =$meta['mobile'][0];  
                        $r->{"source"} =$meta['source'][0];              
                        $r->{"profile_image"} =isset($meta['profile_image'][0])?$meta['profile_image'][0]:""; 
                        $arg=array(
                            "user_login" => $r->user_login,
                            "user_id"=>$login->ID,	
                            "iat" => time(),
                            "exp" => time() + 1.577e+7 , //604800 time in the future 
                            "aud" =>"http://bizgofer.mastishakmitr.com",
                            "iss" =>"http://bizgofer.mastishakmitr.com"			
                        );                
                        $jwt_token=jwt_function($arg,"encode");    
                        $r->{"auth_token"}=$jwt_token;
                        if($login->roles[0]=="seller"){
                            $r->{"store_name"}=$meta['dokan_store_name'][0];
                            $r->{"address"}=$meta['address'][0];
                            $r->{"city"}=$meta['city'][0];
                            $r->{"state"}=$meta['state'][0];
                            $r->{"pin_code"}=$meta['pin_code'][0];
                            $r->{"gstin"}=$meta['gstin'][0];
                            $r->{"longitude"}=$meta['longitude'][0];
                            $r->{"latitude"}=$meta['latitude'][0];
                            $r->{"custom_address"}=$meta['custom_address'][0];
                        }
                        if(isset($data['device_token']) && ($data['device_token'] != "")  ){
                            update_user_meta($login->ID ,"device_token", $data['device_token']);
                        }
                        $result=array(
                            "statuscode"=>200,
                            "message"=> "Success",
                            "data"=>$r
                        );
                        
                    }else{
                        $result=array(
                            "statuscode"=>406,
                            "error"=> "Incorrect Credentials!",
                            "message"=>"Incorrect Credentials!"
                        );
                        return $result;
                    }
                }
                    else{
                        $result=array(
                            "statuscode"=>401,
                            "message"=> "Incorrect Details",
                            "errors"=>$login->errors
                        );
                    }             
            }elseif($data['source']=="facebook"||$data['source']=="google"){
                
                $user_login = $data['user_login'];
                $login = wp_authenticate_username_password(NULL, $user_login , $user_login);            
                if(!is_wp_error($login)){
                    if($login->roles[0]==$data["role"]){
                        $meta=get_user_meta( $login->ID );
                        $r=$login->data; 
                        $r->{"role"} =$login->roles[0];            
                        $r->{"first_name"} =$meta['first_name'][0];            
                        $r->{"last_name"} =$meta['last_name'][0];            
                        $r->{"mobile"} =$meta['mobile'][0];  
                        $r->{"source"} =$meta['source'][0];              
                        $r->{"profile_image"} =isset($meta['profile_image'][0])?$meta['profile_image'][0]:""; 
                        $arg=array(
                            "user_login" => $r->user_login,
                            "user_id"=>$login->ID,	
                            "iat" => time(),
                            "exp" => time() + 1.577e+7 , //604800 time in the future 
                            "aud" =>"http://bizgofer.mastishakmitr.com",
                            "iss" =>"http://bizgofer.mastishakmitr.com"			
                        );                
                        $jwt_token=jwt_function($arg,"encode");    
                        $r->{"auth_token"}=$jwt_token;
                        if($login->roles[0]=="seller"){
                            $r->{"store_name"}=$meta['dokan_store_name'][0];
                            $r->{"address"}=$meta['address'][0];
                            $r->{"city"}=$meta['city'][0];
                            $r->{"state"}=$meta['state'][0];
                            $r->{"pin_code"}=$meta['pin_code'][0];
                            $r->{"gstin"}=$meta['gstin'][0];
                            $r->{"longitude"}=$meta['longitude'][0];
                            $r->{"latitude"}=$meta['latitude'][0];
                            $r->{"custom_address"}=$meta['custom_address'][0];
                        }
                        if(isset($data['device_token']) && ($data['device_token'] != "")  ){
                            update_user_meta($login->ID ,"device_token", $data['device_token']);
                        }
                        $result=array(
                            "statuscode"=>200,
                            "message"=> "Success",
                            "data"=>$r
                        );
                    }
                    else{
                        $result=array(
                            "statuscode"=>406,
                            "error"=> "user does not exists",
                            "message"=>"Sorry, that user does not exists!"
                        );
                        return $result;
                    }
                }else{
                    $user=get_users(
                        array(
                        'search_columns' => 'email',
                        'search' => $data['user_email'],
                        'number' => 1,
                        'count_total' => false
                    ) ); 
                    if(count($user)>0){
                        $user_login = $user[0]->user_login;
                        $user_id=$user[0]->ID;
                        $login=new WP_User( $user_id );
                        $meta=get_user_meta($user_id);
                        if($login->roles[0]==$data["role"]){
                            $r=$login->data; 
                            $r->{"role"} =$user[0]->roles[0];            
                            $r->{"first_name"} =$meta['first_name'][0];            
                            $r->{"last_name"} =$meta['last_name'][0];            
                            $r->{"mobile"} =$meta['mobile'][0];  
                            $r->{"source"} =$meta['source'][0];              
                            $r->{"profile_image"} =isset($meta['profile_image'][0])?$meta['profile_image'][0]:""; 
                            $arg=array(
                                "user_login" => $user_login,
                                "user_id"=>$user_id,	
                                "iat" => time(),
                                "exp" => time() + 1.577e+7 , //604800 time in the future 
                                "aud" =>"http://bizgofer.mastishakmitr.com",
                                "iss" =>"http://bizgofer.mastishakmitr.com"			
                            );                
                            $jwt_token=jwt_function($arg,"encode");    
                            $r->{"auth_token"}=$jwt_token;
                            if($user[0]->roles[0]=="seller"){
                                $r->{"store_name"}=$meta['dokan_store_name'][0];
                                $r->{"address"}=$meta['address'][0];
                                $r->{"city"}=$meta['city'][0];
                                $r->{"state"}=$meta['state'][0];
                                $r->{"pin_code"}=$meta['pin_code'][0];
                                $r->{"gstin"}=$meta['gstin'][0];
                                $r->{"longitude"}=$meta['longitude'][0];
                                $r->{"latitude"}=$meta['latitude'][0];
                                $r->{"custom_address"}=$meta['custom_address'][0];
                            }
                            if(isset($data['device_token']) && ($data['device_token'] != "")  ){
                                update_user_meta($user_id ,"device_token", $data['device_token']);
                            }
                            $result=array(
                                "statuscode"=>200,
                                "message"=> "Success",
                                "data"=>$r
                            );
                        }
                        else{
                            $result=array(
                                "statuscode"=>406,
                                "error"=> "user does not exists",
                                "message"=>"Sorry, that user does not exists!"
                            );                
                            return $result;
                        }
                    }
                    else{
                        $result=array(
                            "statuscode"=>405,
                            "error"=> "user does not exists",
                            "message"=>"Sorry, that user does not exists!"
                        );                
                        return $result;
                    }
                }
            }       
            else{
                $result=array(
                    "statuscode"=>401,
                    "message"=> "Incorrect Details",
                    "errors"=>$login->errors
                );
            }
        }
        else{
            $result=array(
                "statuscode"=>402,
                "message"=> "Invalid Details",
                "errors"=>"Missing Some details!"
            );
        }
        return $result;
    }



    

    function bizgofer_category_search($request){
        global $wpdb;     
        if(isset($request['latitude']) && isset($request['search']) && isset($request['radius'])){
            $prepared_args['role'] = "seller";
            if($request['radius'] && $request['latitude'])
            {
                
                $lat1 = $request['latitude'];
                $lon1 = $request['longitude'];
                $d 	  = $request['radius']; 
                //earth's radius in miles
                $r = 3959;

                //compute max and min latitudes / longitudes for search square
                $latN = rad2deg(asin(sin(deg2rad($lat1)) * cos($d / $r) + cos(deg2rad($lat1)) * sin($d / $r) * cos(deg2rad(0))));
                $latS = rad2deg(asin(sin(deg2rad($lat1)) * cos($d / $r) + cos(deg2rad($lat1)) * sin($d / $r) * cos(deg2rad(180))));
                $lonE = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(90)) * sin($d / $r) * cos(deg2rad($lat1)), cos($d / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));
                $lonW = rad2deg(deg2rad($lon1) + atan2(sin(deg2rad(270)) * sin($d / $r) * cos(deg2rad($lat1)), cos($d / $r) - sin(deg2rad($lat1)) * sin(deg2rad($latN))));

                //display information about starting point  
                $prepared_args['meta_query'] = array('relation' => 'AND',
                    array('key'     => 'dokan_enable_selling',	'value' => 'yes','compare' => 'LIKE'),
                    array('key'     => 'latitude', 'value' => $latN, 'compare' => '<='),
                    array('key'     => 'latitude', 'value' => $latS, 'compare' => '>='),
                    
                    array('key'     => 'longitude', 'value' => $lonE, 'compare' => '<='),
                    array('key'     => 'longitude', 'value' => $lonW, 'compare' => '>=')
                );
            }
            $query = new WP_User_Query( $prepared_args );
            foreach($query->results as $vendor){
                $store_ids[]=$vendor->ID;
            }
            $storeid=implode(",",$store_ids);
            
            $sql="SELECT DISTINCT(terms.term_id) as ID, terms.name, tax.parent FROM {$wpdb->posts} as posts LEFT JOIN {$wpdb->term_relationships} as relationships ON posts.ID = relationships.object_ID LEFT JOIN {$wpdb->term_taxonomy} as tax ON relationships.term_taxonomy_id = tax.term_taxonomy_id LEFT JOIN {$wpdb->terms} as terms ON tax.term_id = terms.term_id WHERE 1=1 AND ( posts.post_status = 'publish' AND tax.taxonomy = 'product_cat' and posts.post_author in (". $storeid .") and  terms.name like '%".$request['search']."%') ORDER BY terms.name ASC";
            $allcat=$wpdb->get_results($sql, OBJECT);                
            $data = array(); 
            $catname=array();              
            foreach($allcat as $r){
                $parent="subcategory";
                if($r->parent==0){$parent="category";}
                $children = get_term_children($r->ID, 'product_cat');                                              
                $thumbnail_id = get_woocommerce_term_meta($r->ID, 'thumbnail_id', true);
                if($thumbnail_id){
                // get the image URL for parent category
                    $image = wp_get_attachment_url($thumbnail_id);
                }else{
                    $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
                } 
                $data[]=array(				
                    "id"=>(int)$r->ID,
                    "name"=>html_entity_decode($r->name),
                    "thumb"=> $image,
                    "type"=>$parent,
                    "extandable"=> empty( $children ) ? false : true ,
                    "categories"=>$catname              
                );            					
            }
            $args = array(
                'post_type'             => 'product',
                'post_status'           => 'publish',         
                's'                     => $request['search'],
                'author'                => $storeid
            );
            $products = new WP_Query($args);
            foreach($products->posts as $product){
                $thumbnail_id = get_post_meta($product->ID, '_thumbnail_id', true);
                if($thumbnail_id){            
                    $image = wp_get_attachment_url($thumbnail_id);
                }else{
                    $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
                }
                $catname=array();
                foreach (wc_get_object_terms($product->ID, 'product_cat') as $term ) {
                        $catname[] =  $term->name;
                }
                $data[]=array(
                    "id"=>(int)$product->ID,
                    "name"=>html_entity_decode($product->post_title),
                    "thumb"=>$image,
                    "type"=>"product",
                    "extandable"=> false,
                    "categories"=>$catname
                );
            }
            $result=array(
                "statuscode"=>200,
                "message"=> "Success",
                "data"=>$data
            );
        }else{
            $result=array(
                "statuscode"=>401,
                "message"=> "invalid Details",
                "errors"=>"Missing Some details!"
            );
        }		
        return $result;
    }

    function bizgofer_product_detail($request){
        if(isset( $request['product_id'] )){
            if(isset($request['variation_id'])){
                $variationid=$request['variation_id'];
            }
        global $woocommerce;
        $vendorproducts = array();
        $product = wc_get_product( $request['product_id'] );
        $productData = $product->get_data();
        $thumbnail_id = get_post_meta($productData['id'], '_thumbnail_id', true);
            if($thumbnail_id){            
                $image = wp_get_attachment_url($thumbnail_id);
            }else{
                $image= site_url() . "/wp-content/uploads/2018/08/list.png";						 
            }
            $parentcat="";
            foreach($productData['category_ids'] as $cats){                
                 $catname[]=get_the_category_by_ID( $cats );
                
                 if(get_term_parents_list( $cats, 'product_cat', array( 'separator'=>'', 'inclusive' => false )) == ""){                
                     $parentcat=$cats;
                 }
            }
       
        if(get_post_meta($productData['id'], '_wc_deposits_enable_deposit', true)=="yes"){
            $enable_deposit=true;
        }  
        $data=array(
            "id"=>$productData['id'],
            "name"=>$productData['name'],
            "thumb"=>$image,
            'description'        => apply_filters( 'the_content', $product->get_description() ),
            'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_short_description() ),
            'short_description_plain'  =>  $product->get_short_description(),           
            "maincategory"=>$parentcat,
            "categories"=>$catname,
            'attributes' => bizgo_get_attributes( $product ),
            "tokenenable" => isset($enable_deposit)? true : false,
            "tokenamount" =>get_post_meta($productData['id'], '_wc_deposits_deposit_amount', true),
            "tokentype" =>get_post_meta($productData['id'], '_wc_deposits_amount_type', true),
        );

        $args                         = array();
		$args['author__not_in']       = "1";
		$args['menu_order']           = $request['menu_order'];
		$args['offset']               = $request['offset'];
		$args['order']                = $request['order'];
		$args['orderby']              = $request['orderby'];
		$args['paged']                = $request['page'];
		$args['post__in']             = $request['include'];
		$args['post__not_in']         = $request['exclude'];
		$args['name']                 = $request['slug'];
		$args['post_parent__in']      = $request['parent'];
		$args['post_parent__not_in']  = $request['parent_exclude'];
		$args['post_status']          = $request['status'];
		$args['s']                    = $productData['name'];
		$args['title']                    = $productData['name'];
		
		if(isset($request['meta_key'])){
			$args['meta_key'] = $request['meta_key'];
		}
		$args['date_query'] = array();
		
		if ( is_array( $request['filter'] ) ) {
			$args = array_merge( $args, $request['filter'] );
			unset( $args['filter'] );
		}
		$args['posts_per_page'] = $request['per_page']; 
		$args['post_type'] = "product";
        foreach($productData['category_ids'] as $catid){
            $tax_q[]=array(
                'taxonomy'      => 'product_cat',
                'field' => 'term_id', 
                'terms'         => $catid,
                'operator'      => 'IN'
            );  
        }     
        $args['tax_query']= array( 'relation' => 'AND',
                $tax_q
        );
		$posts_query = new WP_Query();
		$query_result = $posts_query->query( $args );
        foreach($query_result as $prod){
            $product = wc_get_product( $prod->ID );
            $authordata=get_userdata( $prod->post_author );
            if ($product->is_sold_individually()){
                $max_limit =  1 ;
            }
            else{
                $max_limit=10;
            }
            if(isset($request['variation_id'])){
                $variation[]=bizgofer_get_particular_variation_data($variationid);
            }else{
                $variation=array();
            }
            $vendorproducts[] = array(
                'product_id'        => $product->get_id(),
                'vendor_id'         =>$prod->post_author,
                'vendor_name'        =>$authordata->first_name ." ". $authordata->last_name,
                'vendor_address'         =>$authordata->address,
                'product_name'      => html_entity_decode($product->get_name()),
                'type'               => $product->get_type(),
                'status'             => $product->get_status(),
                'sku'                => $product->get_sku(),
                'price'              => $product->get_price(),
                'regular_price'      =>  $product->get_regular_price(),
                'sale_price'         => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), 2 ) : null,
                'price_html'         => $product->get_price_html(),
                'taxable'            => $product->is_taxable(),
                'tax_status'         => $product->get_tax_status(),
                'tax_class'          => $product->get_tax_class(),
                'managing_stock'     => $product->managing_stock(),
                'stock_quantity'     => $product->get_stock_quantity(),
                'in_stock'           => $product->is_in_stock(),                
                'purchaseable'       => $product->is_purchasable(),
                'featured'           => $product->is_featured(),
                'shipping_required'  => $product->needs_shipping(),
                'shipping_taxable'   => $product->is_shipping_taxable(),
                'shipping_class'     => $product->get_shipping_class(),
                'shipping_class_id'  => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
                'description'        => apply_filters( 'the_content', $product->get_description() ),
                'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_short_description() ),
                'short_description_plain'  =>  $product->get_short_description(),
                'reviews_allowed'    => $product->get_reviews_allowed(),
                'average_rating'     => wc_format_decimal( $product->get_average_rating(), 2 ),
                'sold_individually' => $product->is_sold_individually(),
                'max_limit'=>$max_limit,
                'rating_count'       => $product->get_rating_count(),   
                'categories'=>$catname,            
                'variations'    => $variation
            ); 
        }
        $data['products']=$vendorproducts;
        $result=array(
            "statuscode"=>200,
            "message"=> "Success",
            "data"=>$data
        );
        }
        else{
            $result=array(
                "statuscode"=>401,
                "message"=> "invalid Details",
                "errors"=>"Missing Some details!"
            );
        }	
        return $result;    
    }

    function bizgofer_get_product_detail_without_vendor($request){
        if(isset( $request['product_id'] )){
            global $woocommerce;
            $vendorproducts = array();
            $product = wc_get_product( $request['product_id'] );
            $productData = $product->get_data();
            
            $thumbnail_id = get_post_meta($productData['id'], '_thumbnail_id', true);
            if(get_post_meta($productData['id'], '_wc_deposits_enable_deposit', true)=="yes"){
                $enable_deposit=true;
            }
            $data=array(
                'product_id'         => $product->get_id(),           
                "thumb"=>$thumbnail_id,
                'description'        => apply_filters( 'the_content', $product->get_description() ),
                'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_short_description() ),
                'short_description_plain'  =>  $product->get_short_description(),
                "price"              =>$productData['price'],
                "regular_price"      =>$productData['regular_price'],
                "sale_price"         =>$productData['sale_price'],
                'product_id'         => $product->get_id(),
                'product_name'       => html_entity_decode($product->get_name()),
                'type'               => $product->get_type(),
                'status'             => $product->get_status(),
                'sku'                => $product->get_sku(),
                'price'              => $product->get_price(),
                'regular_price'      => $product->get_regular_price(),
                'sale_price'         => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), 2 ) : null,
                'price_html'         => $product->get_price_html(),
                'taxable'            => $product->is_taxable(),
                'tax_status'         => $product->get_tax_status(),
                'tax_class'          => $product->get_tax_class(),
                'managing_stock'     => $product->managing_stock(),
                'stock_quantity'     => $product->get_stock_quantity(),
                'in_stock'           => $product->is_in_stock(),                
                'purchaseable'       => $product->is_purchasable(),
                'featured'           => $product->is_featured(),
                'shipping_required'  => $product->needs_shipping(),
                'shipping_taxable'   => $product->is_shipping_taxable(),
                'shipping_class'     => $product->get_shipping_class(),
                'shipping_class_id'  => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
                'description'        => apply_filters( 'the_content', $product->get_description() ),
                'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_short_description() ),
                'short_description_plain'  =>  $product->get_short_description(),
                'reviews_allowed'    => $product->get_reviews_allowed(),
                'average_rating'     => wc_format_decimal( $product->get_average_rating(), 2 ),
                'sold_individually'  => $product->is_sold_individually(),            
                'rating_count'       => $product->get_rating_count(),
                "categories"         => $productData['category_ids'],
                'attributes'         => bizgo_get_attributes( $product ),               
                'variations'         => bizgofer_get_variation_data($product),
                'tokenenable'       => isset($enable_deposit) ? true:false ,
                'tokenamount'     => get_post_meta($productData['id'], '_wc_deposits_deposit_amount', true),
                'tokentype'     => get_post_meta($productData['id'], '_wc_deposits_amount_type', true)
                
            );

            $result=array(
                "statuscode"=>200,
                "message"=> "Success",
                "data"=>$data
            );
        }
        else{
            $result=array(
                "statuscode"=>401,
                "message"=> "invalid Details",
                "errors"=>"Missing Some details!"
            );
        }	
        return $result;    
    }
    function bizgofer_add_user_address($request) {
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
    
            $addresses  = array();
            $is_default = false;
            $useraddress = get_user_meta( $user_id, 'wc_multiple_shipping_addresses' ,true);
           if($useraddress){array_map('serialize', $useraddress);	}	else{$useraddress=array();}
            $newaddress['type'] = $request['address']['type'];
            $newaddress['shipping_first_name'] = $request['address']['first_name'];
            $newaddress['shipping_last_name']  = $request['address']['last_name'];
            $newaddress['shipping_company'] = $request['address']['company'];
            $newaddress['shipping_address_1'] = $request['address']['address_1'];          
            $newaddress['shipping_city'] = $request['address']['city'];
            $newaddress['shipping_state'] = $request['address']['state'];
            $newaddress['shipping_country'] = $request['address']['country'];
            $newaddress['shipping_phone'] = $request['address']['phone'];
            $newaddress['shipping_email'] = $request['address']['email'];
            $newaddress['shipping_postcode'] = $request['address']['postcode'];			
            $newaddress['shipping_address_is_default'] = $request['address']['is_default'];				
            
            if($request['address']['is_default'] == 1 || $request['address']['is_default'] == true){				
                foreach($useraddress as $k => $val){
                    $useraddress[$k]['shipping_address_is_default'] = 0; 
                }			
            }
            $useraddress[] = $newaddress;
            $counter = 0;
            foreach($useraddress as $k => $val)
            {
                $useraddress[$k]['id'] = $k+1; 
                $counter++;
            } 		
            
            update_user_meta( $user_id, 'wc_multiple_shipping_addresses', $useraddress  );    
            $useraddressfinal = get_user_meta($user_id, 'wc_multiple_shipping_addresses');            
            $keys = count($useraddressfinal[0]) - 1;
            $label = ($useraddressfinal[0][$keys]['type'])?$useraddressfinal[0][$keys]['type']:"";
            $first_name = ($useraddressfinal[0][$keys]['shipping_first_name'])?$useraddressfinal[0][$keys]['shipping_first_name']:"";
            $last_name  = ($useraddressfinal[0][$keys]['shipping_last_name'])?$useraddressfinal[0][$keys]['shipping_last_name']:"";
            $company 	= ($useraddressfinal[0][$keys]['shipping_company'])?$useraddressfinal[0][$keys]['shipping_company']:"";
            $shipping_phone = ($useraddressfinal[0][$keys]['shipping_phone'])?$useraddressfinal[0][$keys]['shipping_phone']:"";
            $shipping_email = ($useraddressfinal[0][$keys]['shipping_email'])?$useraddressfinal[0][$keys]['shipping_email']:"";
            $address_1  = ($useraddressfinal[0][$keys]['shipping_address_1'])?$useraddressfinal[0][$keys]['shipping_address_1']:"";            
            $city  		= ($useraddressfinal[0][$keys]['shipping_city'])?$useraddressfinal[0][$keys]['shipping_city']:"";
            $state  	= ($useraddressfinal[0][$keys]['shipping_state'])?$useraddressfinal[0][$keys]['shipping_state']:"";
            $country  	= ($useraddressfinal[0][$keys]['shipping_country'])?$useraddressfinal[0][$keys]['shipping_country']:"";
            $postcode  	= ($useraddressfinal[0][$keys]['shipping_postcode'])?$useraddressfinal[0][$keys]['shipping_postcode']:"";
            $is_default = ($useraddressfinal[0][$keys]['shipping_address_is_default'])?$useraddressfinal[0][$keys]['shipping_address_is_default']:false;
            
            $modify = array(
                'id'		=> $keys + 1,                 
                'type'      => $label,
                'first_name'=> $request['address']['first_name'],
                'last_name'	=> $request['address']['last_name'],							
                'phone'		=> $request['address']['phone'], 
                'email'		=> $request['address']['email'],
                'company'	=> $request['address']['company'],
                'address_1'	=> $request['address']['address_1'],
                'city'		=> $request['address']['city'],
                'state'		=> $request['address']['state'],
                'country'	=> $request['address']['country'],
                'postcode'	=> $request['address']['postcode'],						
                'is_default'=> $request['address']['is_default']
            );
            $result=array(
                        "statuscode"=>200,
                        "message"=> "Success",			
                        "data"=> $modify
                    );
        }
        else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
        return $result;
        
    }

    function bizgofer_update_user_address($request) {
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;    
            $addresses  = array();
            $is_default = false;
            $useraddress = get_user_meta( $user_id, 'wc_multiple_shipping_addresses' ,true);
            array_map('serialize', $useraddress);
            $newaddress['id'] = $request['address']['id'];
            $newaddress['type'] = $request['address']['type'];
            $newaddress['shipping_first_name'] = $request['address']['first_name'];
            $newaddress['shipping_last_name']  = $request['address']['last_name'];
            $newaddress['shipping_company'] = $request['address']['company'];
            $newaddress['shipping_address_1'] = $request['address']['address_1'];
            $newaddress['shipping_city'] = $request['address']['city'];
            $newaddress['shipping_state'] = $request['address']['state'];
            $newaddress['shipping_country'] = $request['address']['country'];
            $newaddress['shipping_phone'] = $request['address']['phone'];
            $newaddress['shipping_email'] = $request['address']['email'];
            $newaddress['shipping_postcode'] = $request['address']['postcode'];			
            $newaddress['shipping_address_is_default'] = $request['address']['is_default'];				
            
            if($request['address']['is_default'] == 1 || $request['address']['is_default'] == true){				
                foreach($useraddress as $k => $val){
                    $useraddress[$k]['shipping_address_is_default'] = false; 
                }			
            }
            $useraddress[$request['address']['id']-1] = $newaddress;   
            update_user_meta( $user_id, 'wc_multiple_shipping_addresses', $useraddress  );
            $final['id'] = $request['address']['id'];
            $final['type'] = $request['address']['type'];
            $final['first_name'] = $request['address']['first_name'];
            $final['last_name']  = $request['address']['last_name'];
            $final['company'] = $request['address']['company'];
            $final['address_1'] = $request['address']['address_1'];
            $final['city'] = $request['address']['city'];
            $final['state'] = $request['address']['state'];
            $final['country'] = $request['address']['country'];
            $final['phone'] = $request['address']['phone'];
            $final['email'] = $request['address']['email'];
            $final['postcode'] = $request['address']['postcode'];			
            $final['is_default'] = $request['address']['is_default'];	            
            $result=array(
                        "statuscode"=>200,
                        "message"=> "Success",			
                        "data"=>$final
                    );
            }
            else{
                $result = array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                );
            }
        return $result;        
    }

    function bizgofer_all_user_address($request){        
            global $wpdb;
            $result=array();
            $final=array();
            $auth_token=$request->get_header('Auth-Token');        
            $jwt=jwt_function($auth_token,"decode");        
            if(is_object($jwt)){
                $user_id = $jwt->user_id;
                $useraddress = get_user_meta( $user_id, 'wc_multiple_shipping_addresses');
                $final = array();
                if ( ! empty( $useraddress ) ) {                    
                    foreach($useraddress[0] as $k => $value){                        
                        if($value['shipping_address_is_default'])
                        { 
                            $is_default = $value['shipping_address_is_default'];
                        } else{
                            $is_default = false;	
                        }
                        $label = isset($value['type']) ? $value['type']:"";
                        $first_name = ($value['shipping_first_name'])?$value['shipping_first_name']:"";
                        $last_name  = ($value['shipping_last_name'])?$value['shipping_last_name']:"";
                        $company 	= ($value['shipping_company'])?$value['shipping_company']:"";
                        $shipping_phone = ($value['shipping_phone'])?$value['shipping_phone']:"";
                        $shipping_email = ($value['shipping_email'])?$value['shipping_email']:"";
                        $address_1  = ($value['shipping_address_1'])?$value['shipping_address_1']:"";
                        $city  		= ($value['shipping_city'])?$value['shipping_city']:"";
                        $state  	= ($value['shipping_state'])?$value['shipping_state']:"";
                        $country  	= ($value['shipping_country'])?$value['shipping_country']:"";
                        $postcode  	= ($value['shipping_postcode'])?$value['shipping_postcode']:"";
                        $is_default = ($value['shipping_address_is_default'])?$value['shipping_address_is_default']:false;
                       $key= ($k + 1);
                        $modify = array(
                            'id'		=>"$key", 
                            'type'		=> $label,
                            'first_name'=> $first_name,
                            'last_name'	=> $last_name,							
                            'phone'		=> $shipping_phone, 
                            'email'		=> $shipping_email,
                            'company'	=> $company,
                            'address_1'	=> $address_1,
                            'city'		=> $city,
                            'state'		=> $state,
                            'country'	=> $country,
                            'postcode'	=> $postcode ,						
                            'is_default'=> $is_default
                        ); 
                        
                        $final[] = $modify;
                    }			
        
                    
                }    $result=array(
                        "statuscode"=>200,
                        "message"=> "Success",			
                        "data"=>$final
                    );       
            }else{
                $result=array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                 );
            }            
        return $result;    
    }

    function bizgofer_delete_user_address($request) { 

        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");        
            if(is_object($jwt)){
                $user_id = $jwt->user_id;  
                $addresses  = array();
                $is_default = false;
                $useraddress = get_user_meta( $user_id, 'wc_multiple_shipping_addresses' ,true);
                array_map('serialize', $useraddress); 
                $key = $request['id'] -1;
                if($useraddress[$key]['shipping_address_is_default']==1)	{
                    unset($useraddress[$key]);
                    if(count($useraddress)>0){
                    $new=key($useraddress);
                    $useraddress[$new]['shipping_address_is_default']=1;
                    }
                }
                else{	
                    unset($useraddress[$key]);
                }             
                update_user_meta( $user_id, 'wc_multiple_shipping_addresses', $useraddress  );               
                $result=array(
                            "statuscode"=>200,
                            "message"=> "Success",			
                            "data"=> array('id'=>$request['id']) 
                        );
            }
            else{
                $result = array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                );
            }    
        return $result;        
    }
    function bizgo_upload_from_file( $files, $headers ) {
        if ( empty( $files ) ) {
            return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.' ), array( 'status' => 400 ) );
        }
        if ( ! empty( $headers['content_md5'] ) ) {
            $content_md5 = array_shift( $headers['content_md5'] );
            $expected    = trim( $content_md5 );
            $actual      = md5_file( $files['file']['tmp_name'] );
     
            if ( $expected !== $actual ) {
                return new WP_Error( 'rest_upload_hash_mismatch', __( 'Content hash did not match expected.' ), array( 'status' => 412 ) );
            }
        }     
        // Pass off to WP to handle the actual upload.
        $overrides = array(
            'test_form'   => false,
        );
        // Bypasses is_uploaded_file() when running unit tests.
        if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
            $overrides['action'] = 'wp_handle_mock_upload';
        }
        /** Include admin functions to get access to wp_handle_upload() */
        require_once ABSPATH . 'wp-admin/includes/admin.php';     
        $file = wp_handle_upload( $files['file'], $overrides );     
        if ( isset( $file['error'] ) ) {
            return new WP_Error( 'rest_upload_unknown_error', $file['error'], array( 'status' => 500 ) );
        }
     
        return $file;
    }
    function bizgo_upload_from_data( $data, $headers ) {
        if ( empty( $data ) ) {
            return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.' ), array( 'status' => 400 ) );
        }
     
        if ( empty( $headers['content_type'] ) ) {
            return new WP_Error( 'rest_upload_no_content_type', __( 'No Content-Type supplied.' ), array( 'status' => 400 ) );
        }
     
        if ( empty( $headers['content_disposition'] ) ) {
            return new WP_Error( 'rest_upload_no_content_disposition', __( 'No Content-Disposition supplied.' ), array( 'status' => 400 ) );
        }
     
        $filename = bizgo_get_filename_from_disposition( $headers['content_disposition'] );
     
        if ( empty( $filename ) ) {
            return new WP_Error( 'rest_upload_invalid_disposition', __( 'Invalid Content-Disposition supplied. Content-Disposition needs to be formatted as `attachment; filename="image.png"` or similar.' ), array( 'status' => 400 ) );
        }
     
        if ( ! empty( $headers['content_md5'] ) ) {
            $content_md5 = array_shift( $headers['content_md5'] );
            $expected    = trim( $content_md5 );
            $actual      = md5( $data );
     
            if ( $expected !== $actual ) {
                return new WP_Error( 'rest_upload_hash_mismatch', __( 'Content hash did not match expected.' ), array( 'status' => 412 ) );
            }
        }
     
        // Get the content-type.
        $type = array_shift( $headers['content_type'] );
     
        /** Include admin functions to get access to wp_tempnam() and wp_handle_sideload() */
        require_once ABSPATH . 'wp-admin/includes/admin.php';
     
        // Save the file.
        $tmpfname = wp_tempnam( $filename );
     
        $fp = fopen( $tmpfname, 'w+' );
     
        if ( ! $fp ) {
            return new WP_Error( 'rest_upload_file_error', __( 'Could not open file handle.' ), array( 'status' => 500 ) );
        }
     
        fwrite( $fp, $data );
        fclose( $fp );
     
        // Now, sideload it in.
        $file_data = array(
            'error'    => null,
            'tmp_name' => $tmpfname,
            'name'     => $filename,
            'type'     => $type,
        );
     
        $overrides = array(
            'test_form' => false,
        );
     
        $sideloaded = wp_handle_sideload( $file_data, $overrides );
     
        if ( isset( $sideloaded['error'] ) ) {
            @unlink( $tmpfname );
     
            return new WP_Error( 'rest_upload_sideload_error', $sideloaded['error'], array( 'status' => 500 ) );
        }
     
        return $sideloaded;
    }
    function bizgo_get_filename_from_disposition( $disposition_header ) {
        // Get the filename.
        $filename = null;
     
        foreach ( $disposition_header as $value ) {
            $value = trim( $value );
     
            if ( strpos( $value, ';' ) === false ) {
                continue;
            }
     
            list( $type, $attr_parts ) = explode( ';', $value, 2 );
     
            $attr_parts = explode( ';', $attr_parts );
            $attributes = array();
     
            foreach ( $attr_parts as $part ) {
                if ( strpos( $part, '=' ) === false ) {
                    continue;
                }
     
                list( $key, $value ) = explode( '=', $part, 2 );
     
                $attributes[ trim( $key ) ] = trim( $value );
            }
     
            if ( empty( $attributes['filename'] ) ) {
                continue;
            }
     
            $filename = trim( $attributes['filename'] );
     
            // Unquote quoted filename, but after trimming.
            if ( substr( $filename, 0, 1 ) === '"' && substr( $filename, -1, 1 ) === '"' ) {
                $filename = substr( $filename, 1, -1 );
            }
        }
     
        return $filename;
    }
    function bizgofer_handle_status_param( $post_status, $post_type ) {
 
        switch ( $post_status ) {
            case 'draft':
            case 'pending':
                break;
            case 'private':
                if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
                    return new WP_Error( 'rest_cannot_publish', __( 'Sorry, you are not allowed to create private posts in this post type.' ), array( 'status' => rest_authorization_required_code() ) );
                }
                break;
            case 'publish':
            case 'future':
                if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
                    return new WP_Error( 'rest_cannot_publish', __( 'Sorry, you are not allowed to publish posts in this post type.' ), array( 'status' => rest_authorization_required_code() ) );
                }
                break;
            default:
                if ( ! get_post_status_object( $post_status ) ) {
                    $post_status = 'draft';
                }
                break;
        }
     
        return $post_status;
    }
    function bizgofer_get_additional_fields( $object_type = null ) {
 
        if ( ! $object_type ) {
            $object_type = bizgofer_get_object_type();
        }
     
        if ( ! $object_type ) {
            return array();
        }
     
        global $wp_rest_additional_fields;
     
        if ( ! $wp_rest_additional_fields || ! isset( $wp_rest_additional_fields[ $object_type ] ) ) {
            return array();
        }
     
        return $wp_rest_additional_fields[ $object_type ];
    }
    function bizgofer_get_object_type() {
        $schema = bizgofer_get_item_schema();
     
        if ( ! $schema || ! isset( $schema['title'] ) ) {
            return null;
        }
     
        return $schema['title'];
    }
    function bizgofer_add_additional_fields_to_object( $object, $request ) {
 
        $additional_fields = bizgofer_get_additional_fields();
     
        foreach ( $additional_fields as $field_name => $field_options ) {
     
            if ( ! $field_options['get_callback'] ) {
                continue;
            }
     
            $object[ $field_name ] = call_user_func( $field_options['get_callback'], $object, $field_name, $request, bizgofer_get_object_type() );
        }
     
        return $object;
    }
    function bizgofer_add_additional_fields_schema( $schema ) {
        if ( empty( $schema['title'] ) ) {
            return $schema;
        }
     
        // Can't use $this->get_object_type otherwise we cause an inf loop.
        $object_type = $schema['title'];
     
        $additional_fields = bizgofer_get_additional_fields( $object_type );
     
        foreach ( $additional_fields as $field_name => $field_options ) {
            if ( ! $field_options['schema'] ) {
                continue;
            }
     
            $schema['properties'][ $field_name ] = $field_options['schema'];
        }
     
        return $schema;
    }
    function bizgofer_get_item_schema() {
 
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'attachment',
            'type'       => 'object',
            // Base properties for every Post.
            'properties' => array(
                'date'            => array(
                    'description' => __( "The date the object was published, in the site's timezone." ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view', 'edit', 'embed' ),
                ),
                'date_gmt'        => array(
                    'description' => __( 'The date the object was published, as GMT.' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                ),
                'guid'            => array(
                    'description' => __( 'The globally unique identifier for the object.' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                    'properties'  => array(
                        'raw'      => array(
                            'description' => __( 'GUID for the object, as it exists in the database.' ),
                            'type'        => 'string',
                            'context'     => array( 'edit' ),
                            'readonly'    => true,
                        ),
                        'rendered' => array(
                            'description' => __( 'GUID for the object, transformed for display.' ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                    ),
                ),
                'id'              => array(
                    'description' => __( 'Unique identifier for the object.' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'link'            => array(
                    'description' => __( 'URL to the object.' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'modified'        => array(
                    'description' => __( "The date the object was last modified, in the site's timezone." ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'modified_gmt'    => array(
                    'description' => __( 'The date the object was last modified, as GMT.' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
               /* 'slug'            => array(
                    'description' => __( 'An alphanumeric identifier for the object unique to its type.' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'arg_options' => array(
                        'sanitize_callback' => array( $this, 'sanitize_slug' ),
                    ),
                ),*/
                'status'          => array(
                    'description' => __( 'A named status for the object.' ),
                    'type'        => 'string',
                    'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
                    'context'     => array( 'view', 'edit' ),
                ),
                'type'            => array(
                    'description' => __( 'Type of Post for the object.' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'password'        => array(
                    'description' => __( 'A password to protect access to the content and excerpt.' ),
                    'type'        => 'string',
                    'context'     => array( 'edit' ),
                ),
            ),
        );
 
         $post_type_obj = get_post_type_object( 'attachment' );
 
        if ( $post_type_obj->hierarchical ) {
            $schema['properties']['parent'] = array(
                'description' => __( 'The ID for the parent of the object.' ),
                'type'        => 'integer',
                'context'     => array( 'view', 'edit' ),
            );
        }
 
        $post_type_attributes = array(
            'title',
            'editor',
            'author',
            'excerpt',
            'thumbnail',
            'comments',
            'revisions',
            'page-attributes',
            'post-formats',
            'custom-fields',
        );
        $fixed_schemas = array(
            'post' => array(
                'title',
                'editor',
                'author',
                'excerpt',
                'thumbnail',
                'comments',
                'revisions',
                'post-formats',
                'custom-fields',
            ),
            'page' => array(
                'title',
                'editor',
                'author',
                'excerpt',
                'thumbnail',
                'comments',
                'revisions',
                'page-attributes',
                'custom-fields',
            ),
            'attachment' => array(
                'title',
                'author',
                'comments',
                'revisions',
                'custom-fields',
            ),
        );
        foreach ( $post_type_attributes as $attribute ) {
            if ( isset( $fixed_schemas[ 'attachment' ] ) && ! in_array( $attribute, $fixed_schemas[ 'attachment' ], true ) ) {
                continue;
            } elseif ( ! isset( $fixed_schemas[ 'attachment' ] ) && ! post_type_supports( 'attachment', $attribute ) ) {
                continue;
            }
    
            switch ( $attribute ) {
    
                case 'title':
                    $schema['properties']['title'] = array(
                        'description' => __( 'The title for the object.' ),
                        'type'        => 'object',
                        'context'     => array( 'view', 'edit', 'embed' ),
                        'arg_options' => array(
                            'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
                            'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database()
                        ),
                        'properties'  => array(
                            'raw' => array(
                                'description' => __( 'Title for the object, as it exists in the database.' ),
                                'type'        => 'string',
                                'context'     => array( 'edit' ),
                            ),
                            'rendered' => array(
                                'description' => __( 'HTML title for the object, transformed for display.' ),
                                'type'        => 'string',
                                'context'     => array( 'view', 'edit', 'embed' ),
                                'readonly'    => true,
                            ),
                        ),
                    );
                    break;
    
                case 'editor':
                    $schema['properties']['content'] = array(
                        'description' => __( 'The content for the object.' ),
                        'type'        => 'object',
                        'context'     => array( 'view', 'edit' ),
                        'arg_options' => array(
                            'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
                            'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database()
                        ),
                        'properties'  => array(
                            'raw' => array(
                                'description' => __( 'Content for the object, as it exists in the database.' ),
                                'type'        => 'string',
                                'context'     => array( 'edit' ),
                            ),
                            'rendered' => array(
                                'description' => __( 'HTML content for the object, transformed for display.' ),
                                'type'        => 'string',
                                'context'     => array( 'view', 'edit' ),
                                'readonly'    => true,
                            ),
                            'protected'       => array(
                                'description' => __( 'Whether the content is protected with a password.' ),
                                'type'        => 'boolean',
                                'context'     => array( 'view', 'edit', 'embed' ),
                                'readonly'    => true,
                            ),
                        ),
                    );
                    break;
    
                case 'author':
                    $schema['properties']['author'] = array(
                        'description' => __( 'The ID for the author of the object.' ),
                        'type'        => 'integer',
                        'context'     => array( 'view', 'edit', 'embed' ),
                    );
                    break;
    
                case 'excerpt':
                    $schema['properties']['excerpt'] = array(
                        'description' => __( 'The excerpt for the object.' ),
                        'type'        => 'object',
                        'context'     => array( 'view', 'edit', 'embed' ),
                        'arg_options' => array(
                            'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
                            'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database()
                        ),
                        'properties'  => array(
                            'raw' => array(
                                'description' => __( 'Excerpt for the object, as it exists in the database.' ),
                                'type'        => 'string',
                                'context'     => array( 'edit' ),
                            ),
                            'rendered' => array(
                                'description' => __( 'HTML excerpt for the object, transformed for display.' ),
                                'type'        => 'string',
                                'context'     => array( 'view', 'edit', 'embed' ),
                                'readonly'    => true,
                            ),
                            'protected'       => array(
                                'description' => __( 'Whether the excerpt is protected with a password.' ),
                                'type'        => 'boolean',
                                'context'     => array( 'view', 'edit', 'embed' ),
                                'readonly'    => true,
                            ),
                        ),
                    );
                    break;
    
                case 'thumbnail':
                    $schema['properties']['featured_media'] = array(
                        'description' => __( 'The ID of the featured media for the object.' ),
                        'type'        => 'integer',
                        'context'     => array( 'view', 'edit', 'embed' ),
                    );
                    break;
    
                case 'comments':
                    $schema['properties']['comment_status'] = array(
                        'description' => __( 'Whether or not comments are open on the object.' ),
                        'type'        => 'string',
                        'enum'        => array( 'open', 'closed' ),
                        'context'     => array( 'view', 'edit' ),
                    );
                    $schema['properties']['ping_status'] = array(
                        'description' => __( 'Whether or not the object can be pinged.' ),
                        'type'        => 'string',
                        'enum'        => array( 'open', 'closed' ),
                        'context'     => array( 'view', 'edit' ),
                    );
                    break;
    
                case 'page-attributes':
                    $schema['properties']['menu_order'] = array(
                        'description' => __( 'The order of the object in relation to other object of its type.' ),
                        'type'        => 'integer',
                        'context'     => array( 'view', 'edit' ),
                    );
                    break;
    
                case 'post-formats':
                    // Get the native post formats and remove the array keys.
                    $formats = array_values( get_post_format_slugs() );
    
                    $schema['properties']['format'] = array(
                        'description' => __( 'The format for the object.' ),
                        'type'        => 'string',
                        'enum'        => $formats,
                        'context'     => array( 'view', 'edit' ),
                    );
                    break;
    
               /* case 'custom-fields':
                    $schema['properties']['meta'] = $this->meta->get_field_schema();
                    break;
     */
            }
        }
    
        if ( 'post' === 'attachment' ) {
            $schema['properties']['sticky'] = array(
                'description' => __( 'Whether or not the object should be treated as sticky.' ),
                'type'        => 'boolean',
                'context'     => array( 'view', 'edit' ),
            );
        }
    
      /*  $schema['properties']['template'] = array(
            'description' => __( 'The theme file to use to display the object.' ),
            'type'        => 'string',
            'context'     => array( 'view', 'edit' ),
            'arg_options' => array(
                'validate_callback' => array( $this, 'check_template' ),
            ),
        );*/
    
        $taxonomies = wp_list_filter( get_object_taxonomies( 'attachment', 'objects' ), array( 'show_in_rest' => true ) );
        foreach ( $taxonomies as $taxonomy ) {
            $base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
            $schema['properties'][ $base ] = array(
                /* translators: %s: taxonomy name */
                'description' => sprintf( __( 'The terms assigned to the object in the %s taxonomy.' ), $taxonomy->name ),
                'type'        => 'array',
                'items'       => array(
                    'type'    => 'integer',
                ),
                'context'     => array( 'view', 'edit' ),
            );
        }
    
        return bizgofer_add_additional_fields_schema( $schema );
    }
    function bizgofer_prepare_item_for_database( $request ) {
        $prepared_post = new stdClass;
     
        // Post ID.
        if ( isset( $request['id'] ) ) {
            $existing_post = bizgofer_get_post( $request['id'] );
            if ( is_wp_error( $existing_post ) ) {
                return $existing_post;
            }
     
            $prepared_post->ID = $existing_post->ID;
        }
     
        $schema = bizgofer_get_item_schema();
     
        // Post title.
        if ( ! empty( $schema['properties']['title'] ) && isset( $request['title'] ) ) {
            if ( is_string( $request['title'] ) ) {
                $prepared_post->post_title = $request['title'];
            } elseif ( ! empty( $request['title']['raw'] ) ) {
                $prepared_post->post_title = $request['title']['raw'];
            }
        }
     
        // Post content.
        if ( ! empty( $schema['properties']['content'] ) && isset( $request['content'] ) ) {
            if ( is_string( $request['content'] ) ) {
                $prepared_post->post_content = $request['content'];
            } elseif ( isset( $request['content']['raw'] ) ) {
                $prepared_post->post_content = $request['content']['raw'];
            }
        }
     
        // Post excerpt.
        if ( ! empty( $schema['properties']['excerpt'] ) && isset( $request['excerpt'] ) ) {
            if ( is_string( $request['excerpt'] ) ) {
                $prepared_post->post_excerpt = $request['excerpt'];
            } elseif ( isset( $request['excerpt']['raw'] ) ) {
                $prepared_post->post_excerpt = $request['excerpt']['raw'];
            }
        }
     
        // Post type.
        if ( empty( $request['id'] ) ) {
            // Creating new post, use default type for the controller.
            $prepared_post->post_type = 'attachment';
        } else {
            // Updating a post, use previous type.
            $prepared_post->post_type = get_post_type( $request['id'] );
        }
     
        $post_type = get_post_type_object( $prepared_post->post_type );
     
        // Post status.
        if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {
            $status = bizgofer_handle_status_param( $request['status'], $post_type );
     
            if ( is_wp_error( $status ) ) {
                return $status;
            }
     
            $prepared_post->post_status = $status;
        }
     
        // Post date.
        if ( ! empty( $schema['properties']['date'] ) && ! empty( $request['date'] ) ) {
            $date_data = rest_get_date_with_gmt( $request['date'] );
     
            if ( ! empty( $date_data ) ) {
                list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
                $prepared_post->edit_date = true;
            }
        } elseif ( ! empty( $schema['properties']['date_gmt'] ) && ! empty( $request['date_gmt'] ) ) {
            $date_data = rest_get_date_with_gmt( $request['date_gmt'], true );
     
            if ( ! empty( $date_data ) ) {
                list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
                $prepared_post->edit_date = true;
            }
        }
     
        // Post slug.
        if ( ! empty( $schema['properties']['slug'] ) && isset( $request['slug'] ) ) {
            $prepared_post->post_name = $request['slug'];
        }
     
        // Author.
        if ( ! empty( $schema['properties']['author'] ) && ! empty( $request['author'] ) ) {
            $post_author = (int) $request['author'];
     
            if ( get_current_user_id() !== $post_author ) {
                $user_obj = get_userdata( $post_author );
     
                if ( ! $user_obj ) {
                    return new WP_Error( 'rest_invalid_author', __( 'Invalid author ID.' ), array( 'status' => 400 ) );
                }
            }
     
            $prepared_post->post_author = $post_author;
        }
     
        // Post password.
        if ( ! empty( $schema['properties']['password'] ) && isset( $request['password'] ) ) {
            $prepared_post->post_password = $request['password'];
     
            if ( '' !== $request['password'] ) {
                if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
                    return new WP_Error( 'rest_invalid_field', __( 'A post can not be sticky and have a password.' ), array( 'status' => 400 ) );
                }
     
                if ( ! empty( $prepared_post->ID ) && is_sticky( $prepared_post->ID ) ) {
                    return new WP_Error( 'rest_invalid_field', __( 'A sticky post can not be password protected.' ), array( 'status' => 400 ) );
                }
            }
        }
     
        if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
            if ( ! empty( $prepared_post->ID ) && post_password_required( $prepared_post->ID ) ) {
                return new WP_Error( 'rest_invalid_field', __( 'A password protected post can not be set to sticky.' ), array( 'status' => 400 ) );
            }
        }
     
        // Parent.
        if ( ! empty( $schema['properties']['parent'] ) && isset( $request['parent'] ) ) {
            if ( 0 === (int) $request['parent'] ) {
                $prepared_post->post_parent = 0;
            } else {
                $parent = get_post( (int) $request['parent'] );
                if ( empty( $parent ) ) {
                    return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post parent ID.' ), array( 'status' => 400 ) );
                }
                $prepared_post->post_parent = (int) $parent->ID;
            }
        }
     
        // Menu order.
        if ( ! empty( $schema['properties']['menu_order'] ) && isset( $request['menu_order'] ) ) {
            $prepared_post->menu_order = (int) $request['menu_order'];
        }
     
        // Comment status.
        if ( ! empty( $schema['properties']['comment_status'] ) && ! empty( $request['comment_status'] ) ) {
            $prepared_post->comment_status = $request['comment_status'];
        }
     
        // Ping status.
        if ( ! empty( $schema['properties']['ping_status'] ) && ! empty( $request['ping_status'] ) ) {
            $prepared_post->ping_status = $request['ping_status'];
        }
     
        if ( ! empty( $schema['properties']['template'] ) ) {
            // Force template to null so that it can be handled exclusively by the REST controller.
            $prepared_post->page_template = null;
        }
     
        /**
         * Filters a post before it is inserted via the REST API.
         *
         * The dynamic portion of the hook name, `'attachment'`, refers to the post type slug.
         *
         * @since 4.7.0
         *
         * @param stdClass        $prepared_post An object representing a single post prepared
         *                                       for inserting or updating the database.
         * @param WP_REST_Request $request       Request object.
         */
        return apply_filters( "rest_pre_insert_attachment", $prepared_post, $request );
     
    }
    function bizgofer_get_post( $id ) {
        $obpost=new WP_REST_Posts_Controller($id);
        $error = new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
        if ( (int) $id <= 0 ) {
            return $error;
        }
     
        $post = get_post( (int) $id );
        if ( empty( $post ) || empty( $post->ID ) || $obpost->post_type !== $post->post_type ) {
            return $error;
        }
     
        return $post;
    }
    function bizgofer_update_additional_fields_for_object( $object, $request ) {
        $additional_fields = bizgofer_get_additional_fields();
     
        foreach ( $additional_fields as $field_name => $field_options ) {
            if ( ! $field_options['update_callback'] ) {
                continue;
            }
     
            // Don't run the update callbacks if the data wasn't passed in the request.
            if ( ! isset( $request[ $field_name ] ) ) {
                continue;
            }
     
            $result = call_user_func( $field_options['update_callback'], $request[ $field_name ], $object, $field_name, $request, $this->get_object_type() );
     
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }
     
        return true;
    }
    function bizgofer_prepare_date_response( $date_gmt, $date = null ) {
        // Use the date if passed.
        if ( isset( $date ) ) {
            return mysql_to_rfc3339( $date );
        }
     
        // Return null if $date_gmt is empty/zeros.
        if ( '0000-00-00 00:00:00' === $date_gmt ) {
            return null;
        }
     
        // Return the formatted datetime.
        return mysql_to_rfc3339( $date_gmt );
    }
    function bizgofer_prepare_links( $post ) {
        $base = sprintf( '%s/%s', 'wp/v2' , 'media' );
     
        // Entity meta.
        $links = array(
            'self' => array(
                'href'   => rest_url( trailingslashit( $base ) . $post->ID ),
            ),
            'collection' => array(
                'href'   => rest_url( $base ),
            ),
            'about'      => array(
                'href'   => rest_url( 'wp/v2/types/' . 'attachment' ),
            ),
        );
     
        if ( ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'author' ) )
            && ! empty( $post->post_author ) ) {
            $links['author'] = array(
                'href'       => rest_url( 'wp/v2/users/' . $post->post_author ),
                'embeddable' => true,
            );
        }
     
        if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'comments' ) ) {
            $replies_url = rest_url( 'wp/v2/comments' );
            $replies_url = add_query_arg( 'post', $post->ID, $replies_url );
     
            $links['replies'] = array(
                'href'       => $replies_url,
                'embeddable' => true,
            );
        }
     
        if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'revisions' ) ) {
            $links['version-history'] = array(
                'href' => rest_url( trailingslashit( $base ) . $post->ID . '/revisions' ),
            );
        }
     
        $post_type_obj = get_post_type_object( $post->post_type );
     
        if ( $post_type_obj->hierarchical && ! empty( $post->post_parent ) ) {
            $links['up'] = array(
                'href'       => rest_url( trailingslashit( $base ) . (int) $post->post_parent ),
                'embeddable' => true,
            );
        }
     
        // If we have a featured media, add that.
        if ( $featured_media = get_post_thumbnail_id( $post->ID ) ) {
            $image_url = rest_url( 'wp/v2/media/' . $featured_media );
     
            $links['https://api.w.org/featuredmedia'] = array(
                'href'       => $image_url,
                'embeddable' => true,
            );
        }
     
        if ( ! in_array( $post->post_type, array( 'attachment', 'nav_menu_item', 'revision' ), true ) ) {
            $attachments_url = rest_url( 'wp/v2/media' );
            $attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );
     
            $links['https://api.w.org/attachment'] = array(
                'href' => $attachments_url,
            );
        }
     
        $taxonomies = get_object_taxonomies( $post->post_type );
     
        if ( ! empty( $taxonomies ) ) {
            $links['https://api.w.org/term'] = array();
     
            foreach ( $taxonomies as $tax ) {
                $taxonomy_obj = get_taxonomy( $tax );
     
                // Skip taxonomies that are not public.
                if ( empty( $taxonomy_obj->show_in_rest ) ) {
                    continue;
                }
     
                $tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;
     
                $terms_url = add_query_arg(
                    'post',
                    $post->ID,
                    rest_url( 'wp/v2/' . $tax_base )
                );
     
                $links['https://api.w.org/term'][] = array(
                    'href'       => $terms_url,
                    'taxonomy'   => $tax,
                    'embeddable' => true,
                );
            }
        }
     
        return $links;
    }
    function bizgofer_prepare_item_for_response( $post, $request ) {
        $GLOBALS['post'] = $post;
     
        setup_postdata( $post );
     
        $schema = bizgofer_get_item_schema();
     
        // Base fields for every post.
        $data = array();
     
        if ( ! empty( $schema['properties']['id'] ) ) {
            $data['id'] = $post->ID;
        }
     
        if ( ! empty( $schema['properties']['date'] ) ) {
            $data['date'] = bizgofer_prepare_date_response( $post->post_date_gmt, $post->post_date );
        }
     
        if ( ! empty( $schema['properties']['date_gmt'] ) ) {
            // For drafts, `post_date_gmt` may not be set, indicating that the
            // date of the draft should be updated each time it is saved (see
            // #38883).  In this case, shim the value based on the `post_date`
            // field with the site's timezone offset applied.
            if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
                $post_date_gmt = get_gmt_from_date( $post->post_date );
            } else {
                $post_date_gmt = $post->post_date_gmt;
            }
            $data['date_gmt'] = bizgofer_prepare_date_response( $post_date_gmt );
        }
     
        if ( ! empty( $schema['properties']['guid'] ) ) {
            $data['guid'] = array(
                /** This filter is documented in wp-includes/post-template.php */
                'rendered' => apply_filters( 'get_the_guid', $post->guid, $post->ID ),
                'raw'      => $post->guid,
            );
        }
     
        if ( ! empty( $schema['properties']['modified'] ) ) {
            $data['modified'] = bizgofer_prepare_date_response( $post->post_modified_gmt, $post->post_modified );
        }
     
        if ( ! empty( $schema['properties']['modified_gmt'] ) ) {
            // For drafts, `post_modified_gmt` may not be set (see
            // `post_date_gmt` comments above).  In this case, shim the value
            // based on the `post_modified` field with the site's timezone
            // offset applied.
            if ( '0000-00-00 00:00:00' === $post->post_modified_gmt ) {
                $post_modified_gmt = date( 'Y-m-d H:i:s', strtotime( $post->post_modified ) - ( get_option( 'gmt_offset' ) * 3600 ) );
            } else {
                $post_modified_gmt = $post->post_modified_gmt;
            }
            $data['modified_gmt'] = bizgofer_prepare_date_response( $post_modified_gmt );
        }
     
        if ( ! empty( $schema['properties']['password'] ) ) {
            $data['password'] = $post->post_password;
        }
     
        if ( ! empty( $schema['properties']['slug'] ) ) {
            $data['slug'] = $post->post_name;
        }
     
        if ( ! empty( $schema['properties']['status'] ) ) {
            $data['status'] = $post->post_status;
        }
     
        if ( ! empty( $schema['properties']['type'] ) ) {
            $data['type'] = $post->post_type;
        }
     
        if ( ! empty( $schema['properties']['link'] ) ) {
            $data['link'] = get_permalink( $post->ID );
        }
     
        if ( ! empty( $schema['properties']['title'] ) ) {
            add_filter( 'protected_title_format', 'protected_title_format'  );
     
            $data['title'] = array(
                'raw'      => $post->post_title,
                'rendered' => get_the_title( $post->ID ),
            );
     
            remove_filter( 'protected_title_format', 'protected_title_format' );
        }
     
        $has_password_filter = false;
     /*
        if ( $this->can_access_password_content( $post, $request ) ) {
            // Allow access to the post, permissions already checked before.
            add_filter( 'post_password_required', '__return_false' );
     
            $has_password_filter = true;
        }*/
     
        if ( ! empty( $schema['properties']['content'] ) ) {
            $data['content'] = array(
                'raw'       => $post->post_content,
                /** This filter is documented in wp-includes/post-template.php */
                'rendered'  => post_password_required( $post ) ? '' : apply_filters( 'the_content', $post->post_content ),
                'protected' => (bool) $post->post_password,
            );
        }
     
        if ( ! empty( $schema['properties']['excerpt'] ) ) {
            /** This filter is documented in wp-includes/post-template.php */
            $excerpt = apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $post ) );
            $data['excerpt'] = array(
                'raw'       => $post->post_excerpt,
                'rendered'  => post_password_required( $post ) ? '' : $excerpt,
                'protected' => (bool) $post->post_password,
            );
        }
     
        if ( $has_password_filter ) {
            // Reset filter.
            remove_filter( 'post_password_required', '__return_false' );
        }
     
        if ( ! empty( $schema['properties']['author'] ) ) {
            $data['author'] = (int) $post->post_author;
        }
     
        if ( ! empty( $schema['properties']['featured_media'] ) ) {
            $data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
        }
     
        if ( ! empty( $schema['properties']['parent'] ) ) {
            $data['parent'] = (int) $post->post_parent;
        }
     
        if ( ! empty( $schema['properties']['menu_order'] ) ) {
            $data['menu_order'] = (int) $post->menu_order;
        }
     
        if ( ! empty( $schema['properties']['comment_status'] ) ) {
            $data['comment_status'] = $post->comment_status;
        }
     
        if ( ! empty( $schema['properties']['ping_status'] ) ) {
            $data['ping_status'] = $post->ping_status;
        }
     
        if ( ! empty( $schema['properties']['sticky'] ) ) {
            $data['sticky'] = is_sticky( $post->ID );
        }
     
        if ( ! empty( $schema['properties']['template'] ) ) {
            if ( $template = get_page_template_slug( $post->ID ) ) {
                $data['template'] = $template;
            } else {
                $data['template'] = '';
            }
        }
     
        if ( ! empty( $schema['properties']['format'] ) ) {
            $data['format'] = get_post_format( $post->ID );
     
            // Fill in blank post format.
            if ( empty( $data['format'] ) ) {
                $data['format'] = 'standard';
            }
        }
     
        if ( ! empty( $schema['properties']['meta'] ) ) {
            $data['meta'] = $this->meta->get_value( $post->ID, $request );
        }
     
        $taxonomies = wp_list_filter( get_object_taxonomies( 'attachment', 'objects' ), array( 'show_in_rest' => true ) );
     
        foreach ( $taxonomies as $taxonomy ) {
            $base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
     
            if ( ! empty( $schema['properties'][ $base ] ) ) {
                $terms = get_the_terms( $post, $taxonomy->name );
                $data[ $base ] = $terms ? array_values( wp_list_pluck( $terms, 'term_id' ) ) : array();
            }
        }
     
        $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data    = bizgofer_add_additional_fields_to_object( $data, $request );
        $data    = bizgofer_filter_response_by_context( $data, $context );
     
        // Wrap the data in a response object.
        $response = rest_ensure_response( $data );
     
        $response->add_links( bizgofer_prepare_links( $post ) );
     
        /**
         * Filters the post data for a response.
         *
         * The dynamic portion of the hook name, `'attachment'`, refers to the post type slug.
         *
         * @since 4.7.0
         *
         * @param WP_REST_Response $response The response object.
         * @param WP_Post          $post     Post object.
         * @param WP_REST_Request  $request  Request object.
         */
        return apply_filters( "rest_prepare_attachment", $response, $post, $request );
    }
    function bizgofer_filter_response_by_context( $data, $context ) {
 
        $schema = bizgofer_get_item_schema();
     
        foreach ( $data as $key => $value ) {
            if ( empty( $schema['properties'][ $key ] ) || empty( $schema['properties'][ $key ]['context'] ) ) {
                continue;
            }
     
            if ( ! in_array( $context, $schema['properties'][ $key ]['context'], true ) ) {
                unset( $data[ $key ] );
                continue;
            }
     
            if ( 'object' === $schema['properties'][ $key ]['type'] && ! empty( $schema['properties'][ $key ]['properties'] ) ) {
                foreach ( $schema['properties'][ $key ]['properties'] as $attribute => $details ) {
                    if ( empty( $details['context'] ) ) {
                        continue;
                    }
     
                    if ( ! in_array( $context, $details['context'], true ) ) {
                        if ( isset( $data[ $key ][ $attribute ] ) ) {
                            unset( $data[ $key ][ $attribute ] );
                        }
                    }
                }
            }
        }
     
        return $data;
    }
    function bizgofer_upload_image($request){
        if ( ! empty( $request['post'] ) && in_array( get_post_type( $request['post'] ), array( 'revision', 'attachment' ), true ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid parent type.' ), array( 'status' => 400 ) );
		}
       // echo wpApiSettings.nonce; die();
		// Get the file via $_FILES or raw data.
		$files = $request->get_file_params();
		$headers = $request->get_headers();

		if ( ! empty( $files ) ) {
			$file = bizgo_upload_from_file( $files, $headers );
		} else {
			$file = bizgo_upload_from_data( $request->get_body(), $headers );
		}

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$name       = basename( $file['file'] );
		$name_parts = pathinfo( $name );
		$name       = trim( substr( $name, 0, -(1 + strlen( $name_parts['extension'] ) ) ) );

		$url     = $file['url'];
		$type    = $file['type'];
		$file    = $file['file'];

		// use image exif/iptc data for title and caption defaults if possible
		$image_meta = wp_read_image_metadata( $file );

		if ( ! empty( $image_meta ) ) {
			if ( empty( $request['title'] ) && trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
				$request['title'] = $image_meta['title'];
			}

			if ( empty( $request['caption'] ) && trim( $image_meta['caption'] ) ) {
				$request['caption'] = $image_meta['caption'];
			}
		}

		$attachment = bizgofer_prepare_item_for_database( $request );
		$attachment->post_mime_type = $type;
		$attachment->guid = $url;

		if ( empty( $attachment->post_title ) ) {
			$attachment->post_title = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
		}

		// $post_parent is inherited from $attachment['post_parent'].
		$id = wp_insert_attachment( wp_slash( (array) $attachment ), $file, 0, true );

		if ( is_wp_error( $id ) ) {
			if ( 'db_update_error' === $id->get_error_code() ) {
				$id->add_data( array( 'status' => 500 ) );
			} else {
				$id->add_data( array( 'status' => 400 ) );
			}
			return $id;
		}

		$attachment = get_post( $id );

		do_action( 'rest_insert_attachment', $attachment, $request, true );

		// Include admin functions to get access to wp_generate_attachment_metadata().
		require_once ABSPATH . 'wp-admin/includes/admin.php';

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

		if ( isset( $request['alt_text'] ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
		}

		$fields_update = bizgofer_update_additional_fields_for_object( $attachment, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );
		$response = bizgofer_prepare_item_for_response( $attachment, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );		
		return $response;
     }

    /***Cart APis */
    function bizgofer_validate_product_id( $product_id ) {
		if ( $product_id <= 0 ) {
			return new WP_Error( 'wc_cart_rest_product_id_required', __( 'Product ID number is required!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}

		if ( ! is_numeric( $product_id ) ) {
				return new WP_Error( 'wc_cart_rest_product_id_not_numeric', __( 'Product ID must be numeric!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
    }
    function bizgofer_validate_quantity( $quantity ) {
		if ( ! is_numeric( $quantity ) ) {
			return new WP_Error( 'wc_cart_rest_quantity_not_numeric', __( 'Quantity must be numeric!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} 
    function bizgofer_validate_product( $product_id = null, $quantity = 1 ) {
		bizgofer_validate_product_id( $product_id );

		bizgofer_validate_quantity( $quantity );
	}    

    function bizgofer_create_sub_orders($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
            $data['delivery_type']="delivery";           
            if(!isset($data['profilepic']) || $data['profilepic'] =="" ){
                $data['profilepic']="";
            }  
            $date=date('F d, Y',$data['date']);
            $time=date('H:i:s',$data['date']);
           
           $suborderdata['transaction_id']    = $data['transaction_id']; 
           $suborderdata['set_paid']    = $data['set_paid']; 
           $suborderdata['parent_order_id']    = $data['order_id']; 
           $suborderdata['delivery_date']      = $date; 
           $suborderdata['delivery_time'] 	    = $time; 
           $suborderdata['takeawaytimestamp']  =$data['date'];
           $suborderdata['delivery_type'] 	 = $data['delivery_type'];
           $suborderdata['ordernote'] 	 	 = $data['ordernote'];  
           $suborderdata['profilepic'] 	 = $data['profilepic']; 
           $suborderdata['merchantaddress'] 	 = $data['merchantaddress'];
           $suborderdata['merchantlogo'] 	 = $data['merchantlogo'];
           $coupon_code                       = $data['coupon_code'];
           if ( get_post_meta( $data['order_id'], 'has_sub_order' ) == true ) {
               $args = array(
                   'post_parent' => $data['order_id'],
                   'post_type'   => 'shop_order',
                   'numberposts' => -1,
                   'post_status' => 'any'
               );
               $child_orders = get_children( $args );
               foreach ( $child_orders as $child ) {
                   wp_delete_post( $child->ID );
               }
           }
           $parent_order = new WC_Order( $data['order_id'] );			
           if(isset($data['stripe_token'])&& $data['stripe_token']!=""){
            $notificationm= array("amount"=>$parent_order->get_total(),
            "currency"=>"usd",
            "source"=>$data['stripe_token'],
            "description"=>"Charge for main order id#".$data['order_id']);
            $authorization = "Authorization: Bearer sk_test_5JCBT67ZMEJL7fhfdOmvgHti";
            $notificationm= "amount=".(int) $parent_order->get_total()."&currency=usd&source=".$data['stripe_token']."&description=Charge for main order id ".$data['order_id'];
       
               $ch = curl_init();
               curl_setopt( $ch,CURLOPT_URL, "https://api.stripe.com/v1/charges" );
               curl_setopt( $ch,CURLOPT_USERPWD, "sk_test_5JCBT67ZMEJL7fhfdOmvgHti" . ":"."" );
               curl_setopt( $ch,CURLOPT_POST, true );
               curl_setopt( $ch,CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded')  );
               curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
               curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
               curl_setopt( $ch,CURLOPT_POSTFIELDS, $notificationm );
               $result = curl_exec($ch );
                $err = curl_error($ch);
                if (!$err)
                {
                    $rs=json_decode($result);
                    $suborderdata['transaction_id']=$rs->id;
                    $suborderdata['set_paid'] = true;
                    
                }else{
                  $result= array("statuscode"=>500,
                  "message"=>"Issue in Payment!",
                  "errors"=>$err);
                  return $result;
                }
           }  
               $order_items = $parent_order->get_items();
               $sellers=array();
               foreach ( $order_items as $item ) {
                   $seller_id             = get_post_field( 'post_author', $item['product_id'] );
                   $sellers[$seller_id][] = $item;
               } 
           
               update_post_meta( $data['order_id'], 'has_sub_order', true );
               $orderdetails=array();
               foreach ($sellers as $seller_id => $seller_products ) {
                 $orderdetails[]=bizgofer_create_seller_order( $parent_order, $seller_id, $seller_products, $suborderdata , $coupon_code);
               }
               global $woocommerce;
               $WC = WC();
               foreach ($WC->cart->cart_contents as $cart_item_key => $cart_item) {              
                   unset($WC->cart->cart_contents[$cart_item_key]);
                   $WC->cart->set_quantity($cart_item_key, 0, true);
                   $WC->cart->remove_cart_item($cart_item_key);
               }            
               global $wpdb;
               $table = $wpdb->prefix . 'woocommerce_sessions';     
               $wpdb->delete( $table, array(  'session_key' => $user_id ) );
               $status = delete_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id());   
              
               $responses=array(
                   "statuscode"=>200,
                   "message"=> "Success",
                   "data"=>array(
                       'main_order_id' =>$parent_order->get_id(), 
                       'payment_method'=>$parent_order->get_payment_method(),                      			
                       'main_order_total'=>$parent_order->get_total(),				
                       'suborders'=>$orderdetails                       
                       )
               );
        }
        else{
            $responses = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
           return $responses; 
     
    }   
    function bizgofer_get_order_item_meta_map() {
        return apply_filters( 'dokan_get_order_item_meta_keymap', array(
            'product_id'   => '_product_id',
            'variation_id' => '_variation_id',
            'quantity'     => '_qty',
            'tax_class'    => '_tax_class',
            'subtotal'     => '_line_subtotal',
            'subtotal_tax' => '_line_subtotal_tax',
            'total'        => '_line_total',
            'total_tax'    => '_line_tax',
            'taxes'        => '_line_tax_data',
            'wc_deposit_meta'=>"wc_deposit_meta"
        ) );
    }

    function biz_create_discounted_seller_order($order_id, $product_qty, $product_id, $order_quantity, $coupon_code)
    {

        
        $suborder       = new WC_Order($order_id);
        $product_to_add = get_product($product_id);
        $sale_price     = $product_to_add->get_regular_price();
        if ($product_to_add->is_on_sale()) {
            $sale_price = $product_to_add->get_sale_price();
        }       
        $couponObject   = new WC_Coupon($coupon_code);
        $discountCoupon = $couponObject->amount;
        $discountCode   = $couponObject->code;
        
        if ($couponObject->discount_type == 'percent') {            
            // Here we calculate the final price with the discount
            $final_price  = round(($sale_price * $product_qty) * ((100 - $discountCoupon) / 100), 2);
            $price_params = array(
                'totals' => array(
                    'subtotal' => ($sale_price * $product_qty),
                    'total' => $final_price
                )
            );
            $suborder->add_product(get_product($product_id), $product_qty, $price_params);
            $suborder->add_coupon($discountCode, ($discountCoupon / 100));
        } elseif ($couponObject->discount_type == 'fixed_cart') {
            
            // Here we calculate the final price with the discount
            $final_price  = round(($sale_price * $product_qty) - (($discountCoupon / $order_quantity) * $product_qty), 2);
            $price_params = array(
                'totals' => array(
                    'subtotal' => ($sale_price * $product_qty),
                    'total' => $final_price
                )
            );
            $suborder->add_product(get_product($product_id), $product_qty, $price_params);
            $suborder->add_coupon($discountCode, (($discountCoupon / $order_quantity) * $product_qty));
          
        } elseif ($couponObject->discount_type == 'fixed_product') {
            
            // Here we calculate the final price with the discount
            $final_price  = round(($sale_price * $product_qty) - ($discountCoupon * $product_qty), 2);
            $price_params = array(
                'totals' => array(
                    'subtotal' => ($sale_price * $product_qty),
                    'total' => $final_price
                )
            );
            $suborder->add_product(get_product($product_id), $product_qty, $price_params);
            $suborder->add_coupon($discountCode, ($discountCoupon * $product_qty));
            
        }
        $discount=$sale_price - $final_price;
        return array("order_total"=>$suborder->calculate_totals(), "discount"=>  $discount);
        
    }
	function bizgofer_create_seller_order( $parent_order, $seller_id, $seller_products, $suborderdata ,$coupon_code ) {
        $label="tax";
        $post_status="wc-pending";
        $transaction_id    = $suborderdata['transaction_id'];
        $set_paid          = $suborderdata['set_paid'];
        $delivery_date     = $suborderdata['delivery_date'];
        $delivery_time     = $suborderdata['delivery_time'];
        $delivery_type     = $suborderdata['delivery_type'];
        $takeawaytimestamp = $suborderdata['takeawaytimestamp'];
        $order_data        = apply_filters('woocommerce_new_order_data', array(
            'post_type'     => 'shop_order',
            'post_title'    => sprintf( __( 'Order &ndash; %s', 'dokan-lite' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'dokan-lite' ) ) ),
            'post_status'   => $post_status,
            'ping_status'   => 'closed',
            'post_excerpt'  => isset( $posted['order_comments'] ) ? $posted['order_comments'] : '',
            'post_author'   => $seller_id,
            'post_parent'   => $parent_order->get_id(),
            'post_password' => uniqid( 'order_' )  
        ) );

        $order_id = wp_insert_post( $order_data );
       	
        if($suborderdata['ordernote']!=""){           
            $suborder   = new WC_Order( $order_id );
            $suborder->add_order_note($suborderdata['ordernote']);         
            update_post_meta( $order_id, 'order_special_note', $suborderdata['ordernote'] );
        }
        $discount=0;
        $tokenmoney=0;
        if ( $order_id && !is_wp_error( $order_id ) ) {
            
            $order_total = $order_tax = 0;
            $product_ids = array();
            do_action( 'woocommerce_new_order', $order_id );
            foreach ( $seller_products as $item ) {
               
                $order_total   += (float) $item->get_total();
                $order_tax     += (float) $item->get_total_tax();
                $product_ids[] = $item->get_product_id();

                //apply discount coupon 
                //start coupon discount functionality
                $flagProduct  = false;
                $flagCategory = false;
                $couponValid  = true;
                $cu_coupon    = false;
                
                $couponObject       = new WC_Coupon($coupon_code);
               
                $discountCategories = $couponObject->product_categories;
                $discountProducts   = $couponObject->product_ids;
                $usage_left         = $couponObject->usage_limit - $couponObject->usage_count;
                
                
                $get_coupon_post = get_page_by_title($coupon_code, ARRAY_A, 'shop_coupon');
                
                // get role of coupon author
                if (!empty($get_coupon_post)) {
                    $id        = get_post_field('post_author', $get_coupon_post['ID']);
                    $user_info = get_userdata($id);
                    
                    // check if this coupon created by admin or not
                    if (in_array('administrator', $user_info->roles)) {
                        $cu_coupon = true;
                    }
                }
                
                // check if this coupon applicable for all merchants(created by cu admin)
                if (!$cu_coupon) {
                    $args = array(
                        'author' => $seller_id,
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'desc',
                        'post_type' => 'shop_coupon',
                        'post_status' => 'publish'
                    );
                    
                    /**
                     * get the particular author coupons data
                     */
                    $coupons = get_posts($args);
                    
                    if (count($coupons) > 0) {
                        foreach ($coupons as $coupon) {
                            
                            if ($coupon->post_title === $coupon_code) {
                                $couponValid = true;
                                $msg         = '';
                                break;
                            } else {
                                $couponValid = false;
                                $msg         = 'Coupon is not valid for this store';
                            }
                            
                        }
                    } else {
                        
                        $couponValid = false;
                        $msg         = 'Coupon is not valid for this store';
                        
                    }
                }
                
                if ($usage_left < 0) {
                    $couponValid = false;
                    $msg         = 'Entered Coupon Usage Limit has been Reached';
                    
                }
                if ($couponObject->amount <= 0) {
                    $couponValid = false;
                    $msg         = 'Invalid Coupon';
                }
                
                $couponObject->expiry_date;
                $datetime1 = new DateTime('now');
                $datetime2 = new DateTime($couponObject->expiry_date);
                
                if ($datetime1 > $datetime2) {
                    $couponValid = false;
                    $msg         = 'Entered Coupon has been expired';
                }
                
                if ($couponValid) {
                    //check if product discount enable for this product id
                    if (in_array($item->get_product_id(), $discountProducts)) {
                        $flagProduct = true;
                    }
                    elseif(in_array($item->get_variation_id(), $discountProducts)){
                        $flagProduct = true;
                    }
                    
                    // check if category discount enable for this product id
                    if (count($discountCategories) > 0) {
                        $terms       = get_the_terms($item->get_product_id(), 'product_cat');
                        $flagProduct = false;
                        foreach ($terms as $term) {
                            if (in_array($term->term_id, $discountCategories)) {
                                $flagCategory = true;
                                break;
                            }
                        }
                    }
                    
                    // check for category or product discount  coupon
                    if ($flagCategory || $flagProduct) {
                        
                        if($item->get_variation_id()!=0){$productid=$item->get_variation_id();}
                        else{$productid=$item->get_product_id();}
                        $coup= biz_create_discounted_seller_order($order_id, $item->get_quantity(), $productid, $order_quantity, $coupon_code);
                        $order_total = $coup['order_total'];
                        $order_tax   = 0;
                        $discount= $coup['discount'];
                       
                    }
                } else {
                    
                    $item_id = wc_add_order_item($order_id, array(
                        'order_item_name' => $item->get_name(),
                        'order_item_type' => 'line_item'
                    ));
                    
                }
               
                if ( $item_id ) {
                    $item_meta_data = $item->get_data();
                    $meta_key_map = bizgofer_get_order_item_meta_map();
                    foreach ( $item->get_extra_data_keys() as $meta_key ) {
                        wc_add_order_item_meta( $item_id, $meta_key_map[$meta_key], $item_meta_data[$meta_key] );
                    } 
                    
                      $itemid=$item->get_product_id(); 
              
                }
                /***tax***/
                $tax_class           = $item->get_tax_class();
                $tax_status          = $item->get_tax_status();

                if ( '0' !== $tax_class && 'taxable' === $tax_status && wc_tax_enabled() ) {
                    $tax_rates = WC_Tax::find_rates( array(
                        'country'   => 'IN',							
                        'tax_class' => $tax_class,
                    ) );
                    $k=key (  $tax_rates );
                    $stotal = $item->get_total();
                    $staxes = WC_Tax::calc_tax( $stotal, $tax_rates, false );
                    $label=$tax_rates[$k]["label"];
                    if ( $item->is_type( 'line_item' ) ) {
                        $subtotal       = $item->get_subtotal();
                        $subtotal_taxes = WC_Tax::calc_tax( $subtotal, $tax_rates, false );						
                        $item->set_taxes( array( 'total' => $staxes, 'subtotal' => $subtotal_taxes ) );
                    } else {						
                        $item->set_taxes( array( 'total' => $staxes ) );
                    }
                }
            }
            
            $bill_ship = array(
                '_billing_country', '_billing_first_name', '_billing_last_name', '_billing_company',
                '_billing_address_1', '_billing_address_2', '_billing_city', '_billing_state', '_billing_postcode',
                '_billing_email', '_billing_phone', '_shipping_country', '_shipping_first_name', '_shipping_last_name',
                '_shipping_company', '_shipping_address_1', '_shipping_address_2', '_shipping_city',
                '_shipping_state', '_shipping_postcode'
            );

            // save billing and shipping address
            foreach ( $bill_ship as $val ) {
                $order_key = 'get_' . ltrim( $val, '_' );
                update_post_meta( $order_id, $val, $parent_order->$order_key() );
            }

            // do shipping
            $shipping_values = bizgofer_create_sub_order_shipping($parent_order, $seller_products, $order_id);
            $shipping_cost   = $shipping_values['cost'];
            $shipping_tax    = $shipping_values['tax'];

          
            // calculate the total with shipping tax
            $order_in_total = $order_total + $shipping_cost + $order_tax + $shipping_tax  ;
            // calculate the total without shipping tax
            $order_in_total = $order_total + $shipping_cost + $order_tax ;
            //total tax with shipping tax
            $tax_total =  $order_tax + $shipping_tax;
            //total tax without shipping tax
            $tax_total =  $order_tax;
            update_post_meta( $order_id, 'byconsolewooodt_delivery_date',   $delivery_date );
            update_post_meta( $order_id, 'utc_delivery_datetime',   date('Y-m-d h:i:s',$takeawaytimestamp ));
            update_post_meta( $order_id, 'byconsolewooodt_delivery_time',   $delivery_time );
            update_post_meta( $order_id, 'byconsolewooodt_delivery_type',   $delivery_type ); 
            update_post_meta( $order_id, 'byconsolewooodt_delivery_timestamp',   $takeawaytimestamp );
            update_post_meta( $order_id, '_payment_method',         $parent_order->get_payment_method() );
            update_post_meta( $order_id, '_payment_method_title',   $parent_order->get_payment_method_title() );
            update_post_meta( $order_id, '_order_shipping',         wc_format_decimal( $shipping_cost ) );
            update_post_meta( $order_id, '_order_discount',         wc_format_decimal( $discount ) );
            update_post_meta( $order_id, '_cart_discount',          wc_format_decimal( $discount ) );
            update_post_meta( $order_id, '_order_tax',              wc_format_decimal( $order_tax ) );
            update_post_meta( $order_id, '_order_shipping_tax',     wc_format_decimal( $shipping_tax ) );
            update_post_meta( $order_id, '_order_total',            wc_format_decimal( $order_in_total ) );
            update_post_meta( $order_id, '_order_key',              apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
            update_post_meta( $order_id, '_customer_user',          $parent_order->get_customer_id() );
            update_post_meta( $order_id, '_order_currency',         get_post_meta( $parent_order->get_id(), '_order_currency', true ) );
            update_post_meta( $order_id, '_prices_include_tax',     $parent_order->get_prices_include_tax() );
            update_post_meta( $order_id, '_customer_ip_address',    get_post_meta( $parent_order->get_id(), '_customer_ip_address', true ) );
            update_post_meta( $order_id, '_customer_user_agent',    get_post_meta( $parent_order->get_id(), '_customer_user_agent', true ) );
            update_post_meta( $order_id, '_profile_pic',   $suborderdata['profilepic'] );	
          
            $orderitem_id = wc_add_order_item( $order_id, array(
                    'order_item_name' 		=> $label,
                    'order_item_type' 		=> 'tax',
                ) );
                // Add line item meta
                if ( $orderitem_id ) {						
                    wc_add_order_item_meta( $orderitem_id, 'tax_amount', wc_clean( $order_tax ) );
                    wc_add_order_item_meta( $orderitem_id, 'shipping_tax_amount', wc_clean( $shipping_tax ) );wc_add_order_item_meta( $orderitem_id, 'rate_id', absint( isset( $k ) ? $k : 0 ) );
                    wc_add_order_item_meta( $orderitem_id, 'label', wc_clean( $label ));
                    wc_add_order_item_meta( $orderitem_id, 'compound', absint( isset( $order_tax['compound'] ) ? $order_tax['compound'] : 0 ) );
                }
            do_action( 'dokan_checkout_update_order_meta', $order_id, $seller_id );
            $seller_info = get_userdata($seller_id);        
            $sellerinfo = $seller_info->user_login;
            $deviceToken = $seller_info->device_token;
            $deviceType = $seller_info->device_type;
            $sellerstorename = get_user_meta( $seller_id, 'dokan_store_name', true ); 
            $sellerjsoninfo      = get_user_meta($seller_id,'dokan_profile_settings',true);
            update_post_meta( $order_id, 'order_vendor_logo', $sellerjsoninfo['logo']);
            update_post_meta( $order_id, 'order_vendor_address', $sellerjsoninfo['address']['street_1']);
            update_post_meta( $order_id, 'order_vendor_name',   $sellerstorename );
            update_post_meta( $order_id, 'order_vendor_id',   $seller_id );
            
            if($parent_order->get_payment_method()=="cod" || $parent_order->get_payment_method()=="COD"){
                $msgtxt = 'You have received an Order No #'.$order_id.' of amount ₹ '.$order_in_total ;
                $msgtxtuser = 'Your order #'.$order_id.' of amount ₹ '.$order_in_total.' has been successfully placed';        if($enable_deposit=="yes"){
                    $msgtxt = 'You have received on inspection Order No #'.$order_id ;
                }
                $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$order_id, 'type'=> 'V','status'=>'Recieved');               	
                sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType , $seller_id );
            }else{
                $suborder   = new WC_Order( $order_id );
                if ( true === $set_paid || $set_paid == 1) {
                    $suborder->set_transaction_id( $transaction_id );
                    $suborder->set_date_paid( current_time( 'timestamp', true ) );
                    $suborder->set_status("payment-paid");
                    $suborder->save();
                    $post_status="wc-payment-paid";
                    $msgtxt = 'You have received Paid Order No #'.$order_id.' of amount ₹ '.$order_in_total ;                
                $msgtxtuser = 'Your order #'.$order_id.' of amount ₹ '.$order_in_total.' has been successfully placed';             
                $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$order_id, 'type'=> 'V', 'status'=>'Paid Recieved');               	
                sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $seller_id);
                }
            }				
        } 
        
        $dataarray=array(
            'merchant_name'=>$sellerstorename,           
            'sub_order_id'=>$order_id,
            'sub_order_total'=>$order_in_total,
            'sub_order_status'=>str_replace("wc-","",$post_status),
            );		
      
        return $dataarray;		
    	
    }
    function bizgofer_create_sub_order_shipping( $parent_order, $order_id, $seller_products ) {

        // Get all shipping methods for parent order
        $shipping_methods = $parent_order->get_shipping_methods();
        $order_seller_id = get_post_field( 'post_author', $order_id );
    
        $applied_shipping_method = '';
    
        if ( $shipping_methods ) {
            foreach ( $shipping_methods as $method_item_id => $shipping_object ) {
                $shipping_seller_id = wc_get_order_item_meta( $method_item_id, 'seller_id', true );
    
                if ( $order_seller_id == $shipping_seller_id ) {
                    $applied_shipping_method = $shipping_object;
                }
            }
        }
    
        $shipping_method = apply_filters( 'dokan_shipping_method', $applied_shipping_method, $order_id, $parent_order );
    
        // bail out if no shipping methods found
        if ( ! $shipping_method ) {
            return;
        }
    
        $shipping_products = array();
        $packages = array();
    
        // emulate shopping cart for calculating the shipping method
        foreach ( $seller_products as $product_item ) {
            $product = wc_get_product( $product_item->get_product_id() );
    
            if ( $product->needs_shipping() ) {
                $shipping_products[] = array(
                    'product_id'        => $product_item->get_product_id(),
                    'variation_id'      => $product_item->get_variation_id(),
                    'variation'         => '',
                    'quantity'          => $product_item->get_quantity(),
                    'data'              => $product,
                    'line_total'        => $product_item->get_total(),
                    'line_tax'          => $product_item->get_total_tax(),
                    'line_subtotal'     => $product_item->get_subtotal(),
                    'line_subtotal_tax' => $product_item->get_subtotal_tax(),
                );
            }
        }
    
        if ( $shipping_products ) {
            $package = array(
                'contents'        => $shipping_products,
                'contents_cost'   => array_sum( wp_list_pluck( $shipping_products, 'line_total' ) ),
                'applied_coupons' => array(),
                'seller_id'       => $order_seller_id,
                'destination'     => array(
                    'country'   => $parent_order->get_shipping_country(),
                    'state'     => $parent_order->get_shipping_state(),
                    'postcode'  => $parent_order->get_shipping_postcode(),
                    'city'      => $parent_order->get_shipping_city(),
                    'address'   => $parent_order->get_shipping_address_1(),
                    'address_2' => $parent_order->get_shipping_address_2()
                )
            );
    
            $wc_shipping = WC_Shipping::instance();
            $pack = $wc_shipping->calculate_shipping_for_package( $package );
    
            if ( array_key_exists( $shipping_method['method_id'], $pack['rates'] ) ) {
    
                $method   = $pack['rates'][$shipping_method['method_id']];
                $cost     = wc_format_decimal( $method->cost );
    
                $item_id = wc_add_order_item( $order_id, array(
                    'order_item_name'       => $method->label,
                    'order_item_type'       => 'shipping'
                ) );
    
                $formatted_tax = array_map( 'wc_format_decimal', $method->taxes );
    
                if ( $item_id ) {
                    $taxes =  array(
                        'total' => $formatted_tax,
                    );
                    wc_add_order_item_meta( $item_id, 'method_id', $method->id );
                    wc_add_order_item_meta( $item_id, 'cost', $cost );
                    wc_add_order_item_meta( $item_id, 'total_tax', $method->get_shipping_tax() );
                    wc_add_order_item_meta( $item_id, 'taxes', $taxes );
    
                    foreach ( $method->get_meta_data() as $key => $value ) {
                        wc_add_order_item_meta( $item_id, $key, $value );
                    }
                }
    
                return array( 'cost' => $cost, 'tax' => $method->get_shipping_tax() );
            };
        }
    
        return 0;
    }
    function komal_add_to_cart($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }

        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
            $api_cart_contents=array();
            $line_items        = $data['line_items'];       
            $cart_content=maybe_unserialize(get_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id()));
            if(count($cart_content)>0){$api_cart_contents=$cart_content[0]['cart'];}       
            foreach ($line_items as $cart_product) {                
                $product_id = $cart_product['product_id'];
                $quantity   = $cart_product['quantity'];
                $main_product_id   = !isset($cart_product['main_product_id']) ? 0 : absint($cart_product['main_product_id']);
                $variation_id   = !isset($cart_product['variation_id']) ? 0 : absint($cart_product['variation_id']);
                $variation      = !isset($cart_product['variation']) ? array() : $cart_product['variation'];
                $cart_item_data = !isset($data['cart_item_data']) ? array() : $data['cart_item_data'];
                  
                bizgofer_validate_product($product_id, $quantity);                
                $product_data = wc_get_product($variation_id ? $variation_id : $product_id);                
                if ($quantity <= 0 || !$product_data || 'trash' === $product_data->get_status()) {
                    return new WP_Error('product_does_not_exist', __('Warning: This product does not exist!', 'cart-rest-api-for-woocommerce'), array(
                        'status' => 500
                    ));
                }
                
                // Force quantity to 1 if sold individually and check for existing item in cart.
                if ($product_data->is_sold_individually()) {
                    $quantity = 1;                    
                    $cart_contents = WC()->cart->cart_contents;                    
                    $found_in_cart = apply_filters('woocommerce_add_to_cart_sold_individually_found_in_cart', $cart_item_key && $cart_contents[$cart_item_key]['quantity'] > 0, $product_id, $variation_id, $cart_item_data, $cart_id);
                    if ($found_in_cart) {
                        return new WP_Error('product_sold_individually', sprintf(__('You cannot add another "%s" to your cart.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array(
                            'status' => 500
                        ));
                    }
                }            
            
                // Stock check - only check if we're managing stock and backorders are not allowed.
                if (!$product_data->is_in_stock()) {
                    throw new WP_Error('product_out_of_stock', sprintf(__('You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array(
                        'status' => 500
                    ));
                }
                if (!$product_data->has_enough_stock($quantity)) {
                    /* translators: 1: product name 2: quantity in stock */
                    throw new WP_Error('not_enough_in_stock', sprintf(__('You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'cart-rest-api-for-woocommerce'), $product_data->get_name(), wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data)), array(
                        'status' => 500
                    ));
                }
                
                // Stock check - this time accounting for whats already in-cart.
                if ($product_data->managing_stock()) {
                    $products_qty_in_cart = WC()->cart->get_cart_item_quantities();                    
                    if (isset($products_qty_in_cart[$product_data->get_stock_managed_by_id()]) && !$product_data->has_enough_stock($products_qty_in_cart[$product_data->get_stock_managed_by_id()] + $quantity)) {
                        throw new WP_Error('not_enough_stock_remaining', sprintf(__('You cannot add that amount to the cart &mdash; we have %1$s in stock and you already have %2$s in your cart.', 'cart-rest-api-for-woocommerce'), wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data), wc_format_stock_quantity_for_display($products_qty_in_cart[$product_data->get_stock_managed_by_id()], $product_data)), array(
                            'status' => 500
                        ));
                    }
                }              
                            
                $item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);               
                WC()->cart->set_quantity($item_key, $quantity);
                
                if ($item_key) {
                    $data = WC()->cart->get_cart_item($item_key);                    
                    if (is_array($data)) {                        
                        $cart_contents = WC()->cart->cart_contents;
                        $api_cart_contents[$item_key] = $cart_contents[$item_key];                        
                    }
                }                
            }
        
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_sessions';
        
        $api_cart_contents = array(
            'cart' => $api_cart_contents
        );
        
        $wpdb->replace($table, array(
            'session_key' => $user_id,
            'session_value' => maybe_serialize($api_cart_contents)
        ), array(
            '%s',
            '%s'
        ));      
        $status = update_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), $api_cart_contents);
            if ($status) {   
                $aa=WC()->cart->cart_contents;
                foreach($aa as $cart){
                    $pdata=wc_get_product( $cart['product_id'] );
                    $catname=array();
                    foreach ( wc_get_object_terms( $cart['product_id'], 'product_cat') as $term ) {
                            $catname[] =  $term->name;
                    }
                    $cart['product_name'] = $pdata->get_name();
                    $cart['categories'] = $catname; 
                    if ($pdata->is_sold_individually()){
                        $cart['max_limit'] =  1 ;
                    }
                    else{
                        $cart['max_limit'] = 10;
                    }  

                    if(count($cart['variation'])==0){unset($cart['variation']);}
                    $cart_con[]=$cart;
                } 
                $result = array(
                    "statuscode" => 200,
                    "message" => "Success",
                    "data" => $cart_con,                    
                );                
            }
            else{
                $result = array(
                    "statuscode"=>407,
                    "message"=> "Something went wrong, Please try again!",
                    "errors"=>"persistent cart not updated!"
                );
            } 
        }
        else{
            $result = array(
                "statuscode"=>add_to_cart477,
                "message"=> $jadd_to_cartwt,
                "errors"=>$jwtadd_to_cart
            );
        }
        return $result;
    }

    function bizgofer_add_to_cart($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }

        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
            $api_cart_contents=array();
            $line_items        = $data['line_items'];       
            $cart_content=maybe_unserialize(get_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id()));
            if(count($cart_content)>0){$api_cart_contents=$cart_content[0]['cart'];}       
            foreach ($line_items as $cart_product) {                
                $product_id = $cart_product['product_id'];
                $quantity   = $cart_product['quantity'];
                $main_product_id   = !isset($cart_product['main_product_id']) ? 0 : absint($cart_product['main_product_id']);
                $variation_id   = !isset($cart_product['variation_id']) ? 0 : absint($cart_product['variation_id']);
                $variation      = !isset($cart_product['variation']) ? array() : $cart_product['variation'];
                $cart_item_data = !isset($data['cart_item_data']) ? array() : $data['cart_item_data'];
                  
                bizgofer_validate_product($product_id, $quantity);                
                $product_data = wc_get_product($variation_id ? $variation_id : $product_id);                
                if ($quantity <= 0 || !$product_data || 'trash' === $product_data->get_status()) {
                    return new WP_Error('product_does_not_exist', __('Warning: This product does not exist!', 'cart-rest-api-for-woocommerce'), array(
                        'status' => 500
                    ));
                }
                
                // Force quantity to 1 if sold individually and check for existing item in cart.
                if ($product_data->is_sold_individually()) {
                    $quantity = 1;                    
                    $cart_contents = WC()->cart->cart_contents;                    
                    $found_in_cart = apply_filters('woocommerce_add_to_cart_sold_individually_found_in_cart', $cart_item_key && $cart_contents[$cart_item_key]['quantity'] > 0, $product_id, $variation_id, $cart_item_data, $cart_id);
                    if ($found_in_cart) {
                        return new WP_Error('product_sold_individually', sprintf(__('You cannot add another "%s" to your cart.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array(
                            'status' => 500
                        ));
                    }
                }            
            
                // Stock check - only check if we're managing stock and backorders are not allowed.
                if (!$product_data->is_in_stock()) {
                    throw new WP_Error('product_out_of_stock', sprintf(__('You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array(
                        'status' => 500
                    ));
                }
                if (!$product_data->has_enough_stock($quantity)) {
                    /* translators: 1: product name 2: quantity in stock */
                    throw new WP_Error('not_enough_in_stock', sprintf(__('You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'cart-rest-api-for-woocommerce'), $product_data->get_name(), wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data)), array(
                        'status' => 500
                    ));
                }
                
                // Stock check - this time accounting for whats already in-cart.
                if ($product_data->managing_stock()) {
                    $products_qty_in_cart = WC()->cart->get_cart_item_quantities();                    
                    if (isset($products_qty_in_cart[$product_data->get_stock_managed_by_id()]) && !$product_data->has_enough_stock($products_qty_in_cart[$product_data->get_stock_managed_by_id()] + $quantity)) {
                        throw new WP_Error('not_enough_stock_remaining', sprintf(__('You cannot add that amount to the cart &mdash; we have %1$s in stock and you already have %2$s in your cart.', 'cart-rest-api-for-woocommerce'), wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data), wc_format_stock_quantity_for_display($products_qty_in_cart[$product_data->get_stock_managed_by_id()], $product_data)), array(
                            'status' => 500
                        ));
                    }
                }
                         
                $item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);               
                WC()->cart->set_quantity($item_key, $quantity);
                
                if ($item_key) {
                    $data = WC()->cart->get_cart_item($item_key);                    
                    if (is_array($data)) {                        
                        $cart_contents = WC()->cart->cart_contents;
                        $api_cart_contents[$item_key] = $cart_contents[$item_key];                        
                    }
                }                
            }
        
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_sessions';
        
        $api_cart_contents = array(
            'cart' => $api_cart_contents
        );
        
        $wpdb->replace($table, array(
            'session_key' => $user_id,
            'session_value' => maybe_serialize($api_cart_contents)
        ), array(
            '%s',
            '%s'
        ));      
        $status = update_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), $api_cart_contents);
            if ($status) {   
                $aa=WC()->cart->cart_contents;
              
                foreach($aa as $cart){
                    $pdata=wc_get_product( $cart['product_id'] );
                    $catname=array();
                    foreach ( wc_get_object_terms( $cart['product_id'], 'product_cat') as $term ) {
                            $catname[] =  $term->name;
                    }
                    $cart['product_name'] = $pdata->get_name();
                    $cart['categories'] = $catname; 
                    if ($pdata->is_sold_individually()){
                        $cart['max_limit'] =  1 ;
                    }
                    else{
                        $cart['max_limit'] = 10;
                    }  

                    if(count($cart['variation'])==0){unset($cart['variation']);}
                    $cart_con[]=$cart;
                } 
                $result = array(
                    "statuscode" => 200,
                    "message" => "Success",
                    "data" => $cart_con,                    
                );                
            }
            else{
                $result = array(
                    "statuscode"=>403,
                    "message"=> "Something went wrong, Please try again!",
                    "errors"=>"persistent cart not updated!"
                );
            } 
        }
        else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
        return $result;
    }
    function bizgofer_get_cart($request){ 
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }      
            $auth_token=$request->get_header('Auth-Token');
            $jwt=jwt_function($auth_token,"decode");
            
            if(is_object($jwt)){
                $user_id = $jwt->user_id;
                $api_cart_contents=array();
                $cart_con=array();
                $cart_content=maybe_unserialize(get_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id()));
                if(count($cart_content)>0){$api_cart_contents=$cart_content[0]['cart'];}
                foreach($api_cart_contents as $k=>$cart){
                    $pdata=wc_get_product( $cart['product_id'] );
                    $catname=array();
                    foreach ( wc_get_object_terms( $cart['product_id'], 'product_cat') as $term ) {
                            $catname[] =  $term->name;
                    }
                    $cart['product_name'] = html_entity_decode($pdata->get_name());
                    $cart['categories'] = $catname; 
                    if ($pdata->is_sold_individually()){
                        $cart['max_limit'] =  1 ;
                    }
                    else{
                        $cart['max_limit'] = 10;
                    }                   
                    if(count($cart['variation'])==0){unset($cart['variation']);}
                    $cart_con[]=$cart;  
                }
                $result = array(
                    "statuscode" => 200,
                    "message" => "Success",
                    "data" => $cart_con                
                );
            }
            else{
                $result = array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                );
            }
        return $result;
    }
    function bizgofer_remove_cart($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }     
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
            global $woocommerce;                
            $WC             = WC();
            $prod_to_remove = intval($data['product_id']); 
            if(isset($data['variation_id'])){           
              $variation_id_remove = intval($data['variation_id']); 
            }           
            // Cycle through each product in the cart
            $cart_content=maybe_unserialize(get_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id()));
            if(count($cart_content)>0){$api_cart_contents=$cart_content[0]['cart'];}
            foreach ($api_cart_contents as $cart_item_key => $cart_item) {  
                // Get the Variation or Product ID
                $prod_id = $cart_item['product_id'];                
                $variationid = $cart_item['variation_id'];            
                // Check to see if IDs match
                if ($prod_to_remove == $prod_id) {
                  
                    if(!isset($variation_id_remove) || ($variationid==$variation_id_remove)){                      
                        $result = array();
                        unset($WC->cart->cart_contents[$cart_item_key]);
                        unset($api_cart_contents[$cart_item_key]);
                        $WC->cart->set_quantity($cart_item_key, 0, true);
                        $WC->cart->remove_cart_item($cart_item_key);
                        break;
                    }                    
                } else {
                    $result = array(
                        "statuscode" => 200,
                        "message" => "Success",
                        "data" => "Product id #" . $data['product_id'] . " does not belong to this store or invalid product id"
                    );
                    
                }
            }
            
            if (!empty($result)) {                
                return $result;                
            }
            
            $api_cart_content = array();
            global $wpdb;
            $table = $wpdb->prefix . 'woocommerce_sessions';
            
            $api_cart_content = array(
                'cart' => $api_cart_contents
            );
            
            $wpdb->replace($table, array(
                'session_key' => $user_id,
                'session_value' => maybe_serialize($WC->cart->cart_contents)
            ), array(
                '%s',
                '%s'
            ));
            
            $status = update_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), $api_cart_content);
           
            
            if ($status) {
              
                $aa=$api_cart_contents;
                foreach($aa as $cart){
                    $pdata=wc_get_product( $cart['product_id'] );
                    $catname=array();
                    foreach ( wc_get_object_terms( $cart['product_id'], 'product_cat') as $term ) {
                            $catname[] =  $term->name;
                    }
                    $cart['product_name'] = $pdata->get_name();
                    $cart['categories'] = $catname;
                    if(count($cart['variation'])==0){unset($cart['variation']);}
                    $cart_con[]=$cart;
                }
                     $result = array(
                         "statuscode" => 200,
                         "message" => "Success",
                         "data" => $cart_con,
                     );                
                
            } else {
                
                $result = array(
                    "statuscode" => 500,
                    "message" => "Error",
                    "data" => "Product could not deleted from Cart"
                );
                
            }
        }
        else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
        return $result;
    }
    function bizgofer_clear_cart($request){
             
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;
            global $woocommerce;                
            $WC             = WC();
            foreach ($WC->cart->cart_contents as $cart_item_key => $cart_item) {              
                unset($WC->cart->cart_contents[$cart_item_key]);
                $WC->cart->set_quantity($cart_item_key, 0, true);
                $WC->cart->remove_cart_item($cart_item_key);
            }            
            global $wpdb;
            $table = $wpdb->prefix . 'woocommerce_sessions';     
            $wpdb->delete( $table, array(  'session_key' => $user_id ) );
            $status = delete_user_meta($user_id, '_woocommerce_persistent_cart_' . get_current_blog_id());            
            $result = array(
                "statuscode" => 200,
                "message" => "Success",
                "data" => "Cart cleared!"
            );               
        }
        else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
        return $result;
    }

    function bizgofer_add_product($request){
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;
            global $wpdb;
          
            $alreadyproduct = $wpdb->get_var( "select post_id from $wpdb->postmeta as postmeta join $wpdb->posts as post on post.ID= postmeta.post_id  where postmeta.meta_key = '_parent_product' and  postmeta.meta_value = '".$request['product_id']."' and post.post_author='".$user_id."'");
            if($alreadyproduct==""){
                get_post_meta('_parent_product',$request['product_id']);
                $product = wc_get_product( $request['product_id'] );
                $cat= wc_get_object_terms( $product->get_id(), 'product_cat');
                foreach($cat as $c){
                    $categories[]=$c->term_id;
                }
                $thumbnail_id = get_post_meta($product->get_id(), '_thumbnail_id', true);           
                $attributes = get_post_meta($product->get_id(), '_product_attributes', true);           
                global $wpdb;
                    $post_id = wp_insert_post(array(
                        'post_author' => $user_id,
                        'post_title' => $product->get_name(),
                        'post_content' => $product->get_description(),
                        'post_excerpt' => $request['short_description'],
                        'post_status' => 'publish',
                        'post_type' => "product"
                    ));
                wp_set_object_terms($post_id, $categories, 'product_cat');          
                wp_set_object_terms($post_id, $product->get_type(), 'product_type');
            
                update_post_meta($post_id, '_visibility', 'visible');
                update_post_meta($post_id, '_stock_status', 'instock');
                update_post_meta($post_id, 'total_sales', '0');
                update_post_meta($post_id, '_downloadable', 'no');
                update_post_meta($post_id, '_virtual', 'yes');
                update_post_meta($post_id, '_regular_price', $request['price']);
                update_post_meta($post_id, '_sale_price', $request['sale_price']);
                update_post_meta($post_id, '_purchase_note', '');
                update_post_meta($post_id, '_featured', 'no');
                update_post_meta($post_id, '_weight', $request['weight']);
                update_post_meta($post_id, '_length', $request['length']);
                update_post_meta($post_id, '_width', $request['width']);
                update_post_meta($post_id, '_height', $request['height']);
                update_post_meta($post_id, '_sku', $request['sku']);
                update_post_meta($post_id, '_product_attributes', array());
                update_post_meta($post_id, '_sale_price_dates_from', '');
                update_post_meta($post_id, '_sale_price_dates_to', '');
                update_post_meta($post_id, '_price', $request['price']);
                update_post_meta($post_id, '_sold_individually', '');
                update_post_meta($post_id, '_manage_stock', 'no');
                update_post_meta($post_id, '_backorders', 'no');
                update_post_meta($post_id, '_stock', '');
                update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);
                update_post_meta($post_id, '_parent_product',  $request['product_id']);
                
                if($request['type']=='variable')
                {
                    update_post_meta($post_id, '_product_attributes', $attributes);
                    
                    $att_variations=$request['variations'];
                    foreach ($att_variations as $variation) {                   
                    $post_title = 'Product ' . $post_id . ' Variation ' . $price;
                        $variation_post    = array( 
                            'post_title' => $post_title,
                            'post_author' => $user_id,
                            'post_status' => 'publish',
                            'post_parent' => $post_id,
                            'post_type' => 'product_variation'
                        );
                        $variation_post_id = wp_insert_post($variation_post); // Insert the variation    
                        
                            foreach($variation['attributes'] as $attributes){                        
                                update_post_meta($variation_post_id, 'attribute_' . trim(strtolower(preg_replace('#[ -]+#', '-',$attributes['name']))), trim($attributes['option']));
                            }
                            update_post_meta($variation_post_id, '_price', $variation['price']);
                            if ($variation['sale_price'] != '' && $variation['sale_price'] > 0) {
                                update_post_meta($variation_post_id, '_sale_price', $variation['sale_price']);
                                update_post_meta($variation_post_id, '_regular_price',$variation['price']);
                            } else {
                                update_post_meta($variation_post_id, '_regular_price', $variation['price']);
                            }
                        }              
            
                }  
                if($request['tokenenable']){
                    update_post_meta($post_id, '_wc_deposits_enable_deposit', get_post_meta($product->get_id(), '_wc_deposits_enable_deposit', true));
                    update_post_meta($post_id, '_wc_deposits_deposit_amount',  get_post_meta($product->get_id(), '_wc_deposits_deposit_amount', true));
                    update_post_meta($post_id, '_wc_deposits_amount_type',  get_post_meta($product->get_id(), '_wc_deposits_amount_type', true));
                    update_post_meta($post_id, '_wc_deposits_force_deposit', "no");
                    update_post_meta($post_id, '_price', get_post_meta($product->get_id(), '_wc_deposits_deposit_amount', true));
                    update_post_meta($post_id, '_regular_price', get_post_meta($product->get_id(), '_wc_deposits_deposit_amount', true));
                }
                $result = array(
                    "statuscode"=>200,
                    "message"=> "success",
                    "data"=>$post_id
                );
            }
            elseif(get_post_status( $alreadyproduct )=="trash"){
                $product = wc_get_product( $alreadyproduct );
                $thumbnail_id   = get_post_meta($product->get_id(), '_thumbnail_id', true);           
					$attributes 	= get_post_meta($product->get_id(), '_product_attributes', true);           
					global $wpdb;
					$price= get_post_meta($product->get_id(), '_price', true);
					$post_id = wp_update_post(array(
							'ID' =>  $product->get_id(),
							'post_author' => $user_id,
							'post_title'  => $product->get_name(),
							'post_content' => $request['description'],
							'post_excerpt' => $request['short_description'],
							'post_status' => 'publish',
							'post_type' => "product"
						));
					
					update_post_meta($post_id, '_visibility', 'visible');
					update_post_meta($post_id, '_stock_status', 'instock');
					update_post_meta($post_id, 'total_sales', '0');
					update_post_meta($post_id, '_downloadable', 'no');
					update_post_meta($post_id, '_virtual', 'yes');
					update_post_meta($post_id, '_regular_price', $request['price']);
					update_post_meta($post_id, '_sale_price', $request['sale_price']);
					update_post_meta($post_id, '_purchase_note', '');
					update_post_meta($post_id, '_featured', 'no');
					update_post_meta($post_id, '_weight', $request['weight']);
					update_post_meta($post_id, '_length', $request['length']);
					update_post_meta($post_id, '_width',  $request['width']);
					update_post_meta($post_id, '_height', $request['height']);
					update_post_meta($post_id, '_sku', 	$request['sku']);
					update_post_meta($post_id, '_product_attributes', array());
					update_post_meta($post_id, '_sale_price_dates_from', '');
					update_post_meta($post_id, '_sale_price_dates_to', '');
					update_post_meta($post_id, '_price', $request['price']);
					update_post_meta($post_id, '_sold_individually', '');
					update_post_meta($post_id, '_manage_stock', 'no');
					update_post_meta($post_id, '_backorders', 'no');
					update_post_meta($post_id, '_stock', '');
					update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);
					   
					 if($request['type']=='variable')
					 {
						update_post_meta($post_id, '_product_attributes', $attributes);
						
						$att_variations=$request['variations'];
						
						foreach ($att_variations as $variation) {                   
						    $post_title = 'Product ' . $post_id . ' Variation ' . $price;
							$variation_post    = array( 
							    'ID' =>  $variation['id'],
								'post_title' => $post_title,
								'post_author' => $user_id,
								'post_status' => 'publish',
								'post_parent' => $post_id,
								'post_type' => 'product_variation'
							); 
							
							 $variation_post_id = wp_update_post($variation_post);  
							
								foreach($variation['attributes'] as $attributes){                        
									update_post_meta($variation_post_id, 'attribute_' . trim(strtolower(preg_replace('#[ -]+#', '-',$attributes['name']))), trim($attributes['option']));
								}
								update_post_meta($variation_post_id, '_price', $variation['price']);
								if ($variation['sale_price'] != '' && $variation['sale_price'] > 0) {
									update_post_meta($variation_post_id, '_sale_price', $variation['sale_price']);
									update_post_meta($variation_post_id, '_regular_price',$variation['price']);
								} else {
									update_post_meta($variation_post_id, '_regular_price', $variation['price']);
								}
							}              
				   
					 }    
					if($request['tokenenable']){
						update_post_meta($post_id, '_price',$price);
						update_post_meta($post_id, '_regular_price',$price);
					}       
					$result = array(
						"statuscode"=>200,
						"message"=> "success",
						"data"=>$post_id
					);
            }else{
                $result = array(
                "statuscode"=>407,
                "message"=> "You already created this Service!",
                "errors"=>"Cannot Duplicate Services"
            );
          } 
        }
        else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }
        return $result;
        
    }
	function bizgofer_edit_product($request){
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
      
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;
            $product = wc_get_product( $request['product_id'] );
				
            if(!empty($product))
            { 
				$post_author_id = get_post_field( 'post_author', $product->get_id());
				if($post_author_id == $user_id)
				{
					$thumbnail_id   = get_post_meta($product->get_id(), '_thumbnail_id', true);           
					$attributes 	= get_post_meta($product->get_id(), '_product_attributes', true);           
					global $wpdb;
					$price= get_post_meta($product->get_id(), '_price', true);
					$post_id = wp_update_post(array(
							'ID' =>  $product->get_id(),
							'post_author' => $user_id,
							'post_title'  => $product->get_name(),
							'post_content' => $request['description'],
							'post_excerpt' => $request['short_description'],
							'post_status' => 'publish',
							'post_type' => "product"
						));
					
					 
					update_post_meta($post_id, '_visibility', 'visible');
					update_post_meta($post_id, '_stock_status', 'instock');
					update_post_meta($post_id, 'total_sales', '0');
					update_post_meta($post_id, '_downloadable', 'no');
					update_post_meta($post_id, '_virtual', 'yes');
					update_post_meta($post_id, '_regular_price', $request['price']);
					update_post_meta($post_id, '_sale_price', $request['sale_price']);
					update_post_meta($post_id, '_purchase_note', '');
					update_post_meta($post_id, '_featured', 'no');
					update_post_meta($post_id, '_weight', $request['weight']);
					update_post_meta($post_id, '_length', $request['length']);
					update_post_meta($post_id, '_width',  $request['width']);
					update_post_meta($post_id, '_height', $request['height']);
					update_post_meta($post_id, '_sku', 	$request['sku']);
					update_post_meta($post_id, '_product_attributes', array());
					update_post_meta($post_id, '_sale_price_dates_from', '');
					update_post_meta($post_id, '_sale_price_dates_to', '');
					update_post_meta($post_id, '_price', $request['price']);
					update_post_meta($post_id, '_sold_individually', '');
					update_post_meta($post_id, '_manage_stock', 'no');
					update_post_meta($post_id, '_backorders', 'no');
					update_post_meta($post_id, '_stock', '');
					update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);
					   
					 if($request['type']=='variable')
					 {
						update_post_meta($post_id, '_product_attributes', $attributes);
						
						$att_variations=$request['variations'];
						
						foreach ($att_variations as $variation) {                   
						    $post_title = 'Product ' . $post_id . ' Variation ' . $price;
							$variation_post    = array( 
							    'ID' =>  $variation['id'],
								'post_title' => $post_title,
								'post_author' => $user_id,
								'post_status' => 'publish',
								'post_parent' => $post_id,
								'post_type' => 'product_variation'
							); 
							
							 $variation_post_id = wp_update_post($variation_post);  
							
								foreach($variation['attributes'] as $attributes){                        
									update_post_meta($variation_post_id, 'attribute_' . trim(strtolower(preg_replace('#[ -]+#', '-',$attributes['name']))), trim($attributes['option']));
								}
								update_post_meta($variation_post_id, '_price', $variation['price']);
								if ($variation['sale_price'] != '' && $variation['sale_price'] > 0) {
									update_post_meta($variation_post_id, '_sale_price', $variation['sale_price']);
									update_post_meta($variation_post_id, '_regular_price',$variation['price']);
								} else {
									update_post_meta($variation_post_id, '_regular_price', $variation['price']);
								}
							}              
				   
					 }    
					if($request['tokenenable']){
						update_post_meta($post_id, '_price',$price);
						update_post_meta($post_id, '_regular_price',$price);
					}       
					$result = array(
						"statuscode"=>200,
						"message"=> "success",
						"data"=>$post_id
					);
				}else{ 
					$result = array(
					"statuscode"=>401,
					"message"=> "error",
					"errors"=>"Invalid Product Owner"
				);
				}				
			}else{ 
					$result = array(
					"statuscode"=>401,
					"message"=> "error",
					"errors"=>"Invalid Product"
				);
				} 				
        }else{
            $result = array(
                "statuscode"=>401,
                "message"=> "error",
                "errors"=>$jwt
            );
        }
        return $result;        
    }
    function bizgofer_vendor_order_list($request){  
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;           
                    global $wpdb;				
                    if(!isset($request['status'])||$request['status']==""){
                        $status = 'all';
                    }else{
                            $status=$request['status'];
                    }
                    if(!isset($request['order_date'])||$request['order_date']==""){
                        $order_date=NULL;
                    }
                    else{
                        $order_date=$request['order_date'];
                    }
                    if(!isset($request['search'])||$request['search']==""){
                        $searchvar=NULL;
                    }
                    else{
                        $searchvar=$request['search'];
                    }
                    
                    if(!isset($request['limit'])||$request['limit']==""){
                        $limit=100;
                    }else{
                        $limit=$request['limit'];
                    }
                    
                    if(!isset($request['page'])||$request['page']==""||$request['page']==0){
                        $offset=0;
                    }else{
                        $offset=($request['page']-1)*$limit;
                    }	
                    
                    

                    $status_where='';
                   if(( $status == 'all' ) ){
                     $nostatus=" AND ( order_status = 'wc-processing' or order_status = 'wc-pending' or order_status = 'wc-payment-pending' or order_status = 'wc-payment-paid') ";
                    }else{
                        $nostatus=" AND ( order_status = 'wc-completed' or order_status = 'wc-rejected' or order_status = 'wc-cancelled' ) ";
                    }
                    $date_query = ( $order_date ) ? $wpdb->prepare( ' AND DATE( p.post_date ) = %s', $order_date ) : '';
                    $search = ( $searchvar ) ? $wpdb->prepare( ' AND do.order_id = %s', $searchvar ) : '';
                    $filter='';
                    $joinq='';
                    
                    if(isset($request['payment']) ){
                        $joinq .= " LEFT Join $wpdb->postmeta pm on pm.post_id= p.ID ";
                        if(isset($request['payment']) &&  $request['payment'] == "cod"){
                            $filter .=" AND pm.meta_key = '_payment_method' and pm.meta_value = 'cod'";
                        }
                        else if(isset($request['payment']) && $request['payment'] != ""){
                            $filter .=" AND pm.meta_key = '_payment_method' and pm.meta_value != 'cod'";	
                        }
                    }
                    if( isset($request['deliveryMethod'])){
                        $joinq .= " LEFT Join $wpdb->postmeta mp on mp.post_id= p.ID ";
                        if(isset($request['deliveryMethod']) &&  $request['deliveryMethod'] == "delivery"){						
                            $filter .=" AND mp.meta_key = 'byconsolewooodt_delivery_type' and mp.meta_value = 'delivery'";					
                        }
                        else if(isset($request['deliveryMethod']) ){
                            
                            $filter .=" AND mp.meta_key = 'byconsolewooodt_delivery_type' and mp.meta_value IN ('take_away','levering')";
                        }					
                    } 
                    $sql = "SELECT do.order_id, p.post_date
                            FROM {$wpdb->prefix}dokan_orders AS do
                            LEFT JOIN $wpdb->posts p ON p.ID = do.order_id
                            $joinq
                            WHERE do.seller_id = ". $user_id ." AND p.post_status != 'trash'  and p.post_parent!=0	$date_query $search $filter $status_where $nostatus 						
                            ";
                    if(isset($request['pricesort']) ){
                            $sql ="SELECT do.order_id, p.post_date, CAST(ps.meta_value as decimal(5,2)) as total 
                            FROM {$wpdb->prefix}dokan_orders AS do
                            LEFT JOIN $wpdb->posts p ON p.ID = do.order_id
                            LEFT Join $wpdb->postmeta ps on ps.post_id= p.ID AND ps.meta_key = '_order_total'
                            $joinq
                            WHERE do.seller_id = ". $user_id ." AND p.post_status != 'trash'  and p.post_parent!=0	$date_query $search $filter $status_where $nostatus				
                            ";
                                            
                        }
                    $orders = $wpdb->get_results( $sql );
                    $total_order=$wpdb->num_rows;
                    
                    $max_pages = ceil( $total_order / $limit);
                    
                if(isset($request['pricesort']) ){
                    $sql .= " ORDER BY total ". $request['pricesort']." LIMIT $offset, $limit"; 
                }
                else{
                    $sql .= " GROUP BY do.order_id ORDER BY p.post_date DESC LIMIT $offset, $limit"; 
                }
                
                    $orders = $wpdb->get_results( $sql );                   
                    $data=array();
                    foreach ( $orders as $order ) {
                        $the_order = new WC_Order( $order->order_id );				 
                        $customer_name  = get_post_meta( $order->order_id, '_billing_first_name', true ). ' '. get_post_meta( $order->order_id, '_billing_last_name', true );
                        $customer_add  = get_post_meta( $order->order_id, '_billing_address_1', true );
                        $customer_phone = esc_html( get_post_meta( $order->order_id, '_billing_phone', true ) );
                        $customer_note = esc_html( get_post_meta( $order->order_id, 'order_special_note', true ) );
                        if(get_post_meta( $order->order_id, '_payment_method', true )!="cod" && get_post_meta( $order->order_id, '_payment_method', true )!="" ){
                            $payment="Paid";						
                        }else{
                            $payment="COD";
                        }
                        $item=array();
                        foreach($the_order->get_items() as $key=>$vals){
                            
                            $pid= wc_get_order_item_meta($key,'_product_id');
                            
                            $catname=array();
                            foreach ( wc_get_object_terms( $pid, 'product_cat') as $term ) {
                                    $catname[] =  html_entity_decode($term->name);
                            }
                            if(get_post_meta($pid, '_wc_deposits_enable_deposit', true)=="yes"){
                                $enable_deposit=true;
                                $tokenmoney=get_post_meta($pid, '_wc_deposits_deposit_amount', true);
                            }
                            
                            $pdata=wc_get_product( $pid );
                            if( $pdata ){
                                $item[]=array(
                                    'product_id'=>$pid,
                                    'product_name'=>html_entity_decode($pdata->get_name()),
                                    'price'=> $vals->get_total(),
                                    'tokenenable'=>isset($enable_deposit)? true : false,
                                    'tokenamount'=>isset($tokenmoney)? $tokenmoney : 0,
                                    'categories'=>$catname,                                
                                    'quantity'=>wc_get_order_item_meta($key,'_qty')
                                );
                            }
                            unset($enable_deposit);
                            unset($tokenmoney);
                        }
                       $order_status = dokan_get_prop( $the_order, 'status' );
                       $statuskey= $order_status;
                       if( $order_status == "payment-pending"){
                           $statuskey="processing";
                       }
                       if( $order_status == "rejected" || $order_status == "cancelled" ){
                        $statuskey="completed";
                    }
                       $tax_rates = WC_Tax::find_rates( array(
                        'country'   => 'IN',							
                        'tax_class' => '',
                        ) );
                        $k=key (  $tax_rates );
                        $data[$statuskey][]=array(
                            'orderid'			   =>$order->order_id,
                            'tax_rate'             => $tax_rates[$k]["rate"] , 
                            'order_total'		   => $the_order->get_total(),
                            'date_time'		       => $the_order->get_meta("utc_delivery_datetime"),   
                            'order_status'         => $order_status,
                            'payment_method'        =>$the_order->get_payment_method(),
                            'payment_method_title'  =>$the_order->get_payment_method_title(),
                            'customer_name'        => $customer_name,
                            'customer_address'      =>$customer_add,
                            'customer_phone'       => $customer_phone,
                            'customer_note'        => $customer_note,
                            'products'             => $item,  
                                                    
                        );
                     }
                     if(count($data)==0){$data=(object)$data;}
                $results=array(
                                "statuscode"=>200,
                                "message"=> "Success",                               
                                "data"=>$data
                            );
                $result = rest_ensure_response( $results );
                $result->header( 'X-WP-Total', (int) $total_order );  
                $result->header( 'X-WP-TotalPages', (int) $max_pages );			
            } else{
                $result = array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                );
            }            
          
            return $result; 
          
    }
    function bizgofer_vendor_particular_order($request){  
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;
            $order_id= $request['order_id'];
                global $wpdb;
                $the_order = new WC_Order( $order_id );				 
                $customer_name  = get_post_meta( $order_id, '_billing_first_name', true ). ' '. get_post_meta( $order_id, '_billing_last_name', true );
                $customer_add  = get_post_meta( $order_id, '_billing_address_1', true );
                $customer_phone = esc_html( get_post_meta( $order_id, '_billing_phone', true ) );
                $customer_note = esc_html( get_post_meta( $order_id, 'order_special_note', true ) );
                if(get_post_meta( $order_id, '_payment_method', true )!="cod" && get_post_meta( $order_id, '_payment_method', true )!="" ){
                    $payment="Paid";						
                }else{
                    $payment="COD";
                }
                $item=array();
                foreach($the_order->get_items() as $key=>$vals){
                    
                    $pid= wc_get_order_item_meta($key,'_product_id');
                    $pdata=wc_get_product( $pid );
                    $catname=array();
                    foreach ( wc_get_object_terms( $pid, 'product_cat') as $term ) {
                            $catname[] =  html_entity_decode($term->name);
                    }
                    if(get_post_meta($pid, '_wc_deposits_enable_deposit', true)=="yes"){
                        $enable_deposit=true;
                        $tokenmoney=get_post_meta($pid, '_wc_deposits_deposit_amount', true);
                    } 
                    $item[]=array(
                        'product_id'=>$pid,
                        'product_name'=>html_entity_decode($pdata->get_name()),
                        'price'=> $vals->get_total(),
                        'tokenenable'=>isset($enable_deposit)? true : false,
                        'tokenamount'=>isset($tokenmoney)? $tokenmoney : 0,
                        'categories'=>$catname,                       
                        'quantity'=>wc_get_order_item_meta($key,'_qty')
                    );
                    unset($enable_deposit);
                    unset($tokenmoney);
                }
                $order_status = dokan_get_prop( $the_order, 'status' );
                $tax_rates = WC_Tax::find_rates( array(
                'country'   => 'IN',							
                'tax_class' => '',
                ) );
                $k=key (  $tax_rates );
                $data=array(
                    'orderid'			   =>$order_id,
                    'tax_rate'             => $tax_rates[$k]["rate"] , 
                    'order_total'		   => $the_order->get_total(),
                    'date_time'		       => $the_order->get_meta("utc_delivery_datetime"),   
                    'order_status'         => $order_status,
                    'payment_method'        =>$the_order->get_payment_method(),
                    'payment_method_title'  =>$the_order->get_payment_method_title(),
                    'customer_name'        => $customer_name,
                    'customer_address'      =>$customer_add,
                    'customer_phone'       => $customer_phone,
                    'customer_note'        => $customer_note,
                    'products'             => $item,  
                                            
                );
            $result=array(
                            "statuscode"=>200,
                            "message"=> "Success",                               
                            "data"=>$data
                        );             		
            } else{
                $result = array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                );
            }            
          
            return $result; 
          
    }
    
    
    function get_particular_variation($vid){ 
        $variations = array();
        $variation = wc_get_product( $vid );
        if ( ! $variation || ! $variation->exists() ) {
            return (object)$variations;
        }
        $variations = array(
            'id'                 => $vid,                
            'sku'                => $variation->get_sku(),
            'price'              => $variation->get_price(),
            'regular_price'      => $variation->get_regular_price(),
            'sale_price'         => $variation->get_sale_price(),
            'date_on_sale_from'  => $variation->get_date_on_sale_from() ? date( 'Y-m-d', $variation->get_date_on_sale_from()->getTimestamp() ) : '',
            'date_on_sale_to'    => $variation->get_date_on_sale_to() ? date( 'Y-m-d', $variation->get_date_on_sale_to()->getTimestamp() ) : '',
            'on_sale'            => $variation->is_on_sale(),               
            'tax_status'         => $variation->get_tax_status(),
            'tax_class'          => $variation->get_tax_class(),
            'manage_stock'       => $variation->managing_stock(),
            'stock_quantity'     => $variation->get_stock_quantity(),
            'in_stock'           => $variation->is_in_stock(),
            'shipping_class'     => $variation->get_shipping_class(),
            'shipping_class_id'  => $variation->get_shipping_class_id(),                
            'attributes'         => bizgo_get_attributes( $variation ),
        );
        return $variations;
    }


	function bizgofer_update_status($request){ 
	    global $wpdb;
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode"); 
         
           if(is_object($jwt)){
               
               $datavalue 	= json_decode($request, true);          
               $orderid 	= $request['order_id']; 
               $status 	= $request['status']; 
               $note 		= $request['note']; 	
               $order_id   = wc_get_order($orderid);
               
               if($order_id){ 
                       
                       $order      = new WC_Order( $orderid );
                       $mainorder=$order->get_parent_id();
                       
                       if($order->post->post_status == 'wc-pending' || $order->post->post_status == 'wc-processing' || $order->post->post_status == 'wc-on-hold' || $order->post->post_status == 'wc-reject' || $order->post->post_status == 'wc-cancelled' || $order->post->post_status == 'wc-payment-paid' || $order->post->post_status == 'wc-completed' || $order->post->post_status == 'wc-refunded'){ 
                          $transaction_id=$order->get_transaction_id();
                          $total=$order->get_total();
                      
                          if($status=="wc-cancelled"){                               
                                $seller_id = $wpdb->get_var("SELECT `seller_id` FROM `wp_dokan_orders` WHERE `order_id`=".$orderid);
                                $seller_info = get_userdata($seller_id);        
                                $sellerinfo = $seller_info->user_login;
                                $deviceToken = $seller_info->device_token;
                                $deviceType = $seller_info->device_type;

                                $msgtxt = 'Your order #'.$orderid.' has been Cancelled by customer';
                                $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'V', 'status'=>'Cancelled');  
                               
                                sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $seller_id);
                          }
                          $order->update_status( $status );    
                          $comntid = $order->add_order_note($note); 

                         if($status=="wc-processing"){                            
                            $customer_id=get_post_meta($orderid,'_customer_user',true);
                            $customer_info = get_userdata($customer_id);        
                            $customerinfo = $customer_info->user_login;
                            $deviceToken = $customer_info->device_token;
                            $deviceType = $customer_info->device_type;
                            $msgtxt = 'Your order #'.$orderid.' has been accepted';
                            $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'C', 'status'=>'accepted');                             
                            sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $customer_id);
                        }

                        if($status=="wc-rejected"){                                
                            $customer_id=get_post_meta($orderid,'_customer_user',true);
                            $customer_info = get_userdata($customer_id);        
                            $sellerinfo = $customer_info->user_login;
                            $deviceToken = $customer_info->device_token;
                            $deviceType = $customer_info->device_type;   
                            $msgtxt = 'Your order #'.$orderid.' has been rejected';
                            $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'C', 'status'=>'rejected'); 
                            sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $customer_id);
                        }	
                           $result = array( 
                               "statuscode"=>200, 
                               "message"=> $status,
                               "data"=> "Success"
                           );
                           
                       }
                       else
                       {
                           $result = array(
                               "statuscode"=>300,
                               "message"=> "This order can't be changed",
                               "data"=> "This order status can't be changed" 
                           );			
                       }
                   }
                   else
                   {	
                       $result = array(
                           "statuscode"=>401,
                           "message"=> "This order not exist",
                           "data"=> "This order status can't be changed" 
                       );			
                   }
           }else{
               $result = array(
                   "statuscode"=>401,
                   "message"=> "error",
                   "errors"=>$jwt
               );
           }		
                       
           return $result;		
       }

    function bizgofer_order_detail($request){
        $order = new WC_Order( $request['order_id'] );
        $item=array();
        foreach($order->get_items() as $key=>$vals){
            $enable_deposit=false;
            $pid= wc_get_order_item_meta($key,'_product_id');
            $pdata=wc_get_product( $pid );
            $catname=array();
            foreach ( wc_get_object_terms( $pid, 'product_cat') as $term ) {
                    $catname[] =  html_entity_decode($term->name);
            }
            $vid=wc_get_order_item_meta($key,'_variation_id');
            $authorid=get_post_field( 'post_author',$pid);
            if(get_post_meta($pid, '_wc_deposits_enable_deposit', true)=="yes"){
                $enable_deposit=true;
                $tokenmoney=get_post_meta($pid, '_wc_deposits_deposit_amount', true);
            } 
            $authordata=get_user_meta( $authorid );
            $item[]=array(
                'product_id'=>$pid,
                'product_name'=>html_entity_decode($pdata->get_name()),                
                'quantity'=>(int) wc_get_order_item_meta($key,'_qty'),
                'tokenenable'=>$enable_deposit,
                'tokenamount'=>isset($tokenmoney)? $tokenmoney : 0,
                'subtotal'=>$vals->get_total(),
                'vendor_id'         =>$authorid,
                'first_name'        =>$authordata['first_name'][0],
                'last_name'         =>$authordata['last_name'][0],
                'vendor_address'    =>$authordata['address'][0],
                'categories'=>$catname,
                'variation_id'      =>$vid,
                'variations'        => get_particular_variation( $vid ), 
            );
        }
       $order_status = dokan_get_prop( $order, 'status' );
       
       
       $data=array(
            'orderid'			   => $request['order_id'],
            'payment_method'       =>$order->get_payment_method(),
            'payment_method_title' =>$order->get_payment_method_title(),
            'order_subtotal'	   => (string)$order->get_subtotal(),
            'order_total_tax'	   => $order->get_total_tax(),
            'order_total'		   => $order->get_total(),
            'discount_total'		   => $order->get_discount_total(),
            'date_time'		       => $order->get_meta("utc_delivery_datetime"),   
            'order_status'         => $order_status,
            'customer_name'        =>  $order->get_billing_first_name() ." ".  $order->get_billing_last_name(),
            'customer_address'     => $order->get_billing_address_1(),
            'customer_phone'       => $order->get_billing_phone(),
            'customer_email'       => $order->get_billing_email(),
            'customer_city'        => $order->get_billing_city(),
            'customer_state'       => $order->get_billing_state(),
            'customer_postcode'    => $order->get_billing_postcode(),
            'customer_note'        =>  get_post_meta( $request['order_id'], 'order_special_note',true),
            'products'             => $item,                            
        );      
       $results=array(
        "statuscode"=>200,
        "message"=> "Success",                               
        "data"=>$data
        );
            return $results;		
    }
  
    function bizgofer_update_particular_order($request){
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
        $auth_token=$request->get_header('auth-token');
        $jwt=jwt_function($auth_token,"decode");
       
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;     
            $orderid= $data['order_id'];
            $order   = new WC_Order( $orderid );
            $token=$data['tokenenable'];
            $total=$data['amount'];
            $tax=$data['tax'];
            $line_items=$data['line_items'];
            $payment_method = $data['payment_method'];
            $payment_method_title = $data['payment_method_title'];
            if($token==true || $token == 1){
                foreach($order->get_items() as $key=>$vals){
                foreach($line_items as $item){
                    if(wc_get_order_item_meta($key,'_product_id')==$item['product_id']){
                        wc_update_order_item_meta($key,'_line_subtotal',$item['subtotal']);
                        wc_update_order_item_meta($key,'_line_subtotal_tax',$item['subtotal_tax']);
                        wc_update_order_item_meta($key,'_line_total',$item['total']);
                        wc_update_order_item_meta($key,'_line_tax',$item['total_tax']);
                    }
                }
                }
                $order->set_payment_method($payment_method);
                $order->set_payment_method_title($payment_method_title);
                $order->set_shipping_total(0.00);           
                $order->set_cart_tax($tax);
                $order->set_shipping_tax(0.00);
                $order->set_total($total);
                if($payment_method == "cod" || $payment_method == "COD"){
                    $order->set_status("completed");
                    $customer_id=get_post_meta($orderid,'_customer_user',true);
                    $customer_info = get_userdata($customer_id);        
                    $customerinfo = $customer_info->user_login;
                    $deviceToken = $customer_info->device_token;
                    $deviceType = $customer_info->device_type;
                    $msgtxt = 'Your order #'.$orderid.' has been completed with amount ₹'.$total;
                    $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'C', 'status'=>'completed');  
                    sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $customer_id);
                }else{
                    $order->set_status("payment-pending");
                    $customer_id=get_post_meta($orderid,'_customer_user',true);
                    $customer_info = get_userdata($customer_id);        
                    $customerinfo = $customer_info->user_login;
                    $deviceToken = $customer_info->device_token;
                    $deviceType = $customer_info->device_type;
                    $msgtxt = 'Your order #'.$orderid.' has been updated with amount ₹'.$total.' Please Pay';
                    $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'C', 'status'=>'updated');  
                    sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $customer_id);
                }
            }
            else{
                $customer_id=get_post_meta($orderid,'_customer_user',true);
                $customer_info = get_userdata($customer_id);        
                $customerinfo = $customer_info->user_login;
                $deviceToken = $customer_info->device_token;
                $deviceType = $customer_info->device_type;
                $msgtxt = 'Your order #'.$orderid.' has been completed ';
                $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'C', 'status'=>'completed');  
                sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $customer_id);
                $order->set_status("completed");
            }
            $order->save();

            $result=array(
                "statuscode"=>200,
                "message"=> "Success",                               
                "data"=>$orderid
            );       	
        } else{
            $result = array(
            "statuscode"=>477,
            "message"=> $jwt,
            "errors"=>$jwt
            );
        }            

        return $result; 

    }

    function bizgofer_update_particular_order_payment_details($request){        
        $data = $request->get_json_params();
        if(empty($data)){
        $data = $request->get_body_params();
        }
        $auth_token=$request->get_header('auth-token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){   
            global $wpdb;         
            $user_id = $jwt->user_id;     
            $orderid= $data['order_id'];
            $order   = new WC_Order( $orderid );          
            $transaction_id=$data['transaction_id'];
            if ( ! empty( $transaction_id ) ) {
                $order->set_transaction_id( $transaction_id );
                $order->set_date_paid( current_time( 'timestamp', true ) );
                $order->set_status('completed');
                $seller_id = $wpdb->get_var("SELECT `seller_id` FROM `wp_dokan_orders` WHERE `order_id`=".$orderid);
                $seller_info = get_userdata($seller_id);        
                $sellerinfo = $seller_info->user_login;
                $deviceToken = $seller_info->device_token;
                $deviceType = $seller_info->device_type;

                $msgtxt = "Your order # $orderid has been paid by customer";
                $notification=array("message"=>$msgtxt, "title"=>$msgtxt, "order_id"=>$orderid, 'type'=> 'V', 'status'=>'Paid');  
                
                sendPushNotificationToFCMSever($deviceToken, $notification , $deviceType, $seller_id);
            }
            $order->save();
            $result=array(
                "statuscode"=>200,
                "message"=> "Updated Successfully!",                               
                "data"=>$orderid
            );
        } else{
            $result = array(
            "statuscode"=>477,
            "message"=> $jwt,
            "errors"=>$jwt
            );
        }            

        return $result; 

    }

    function bizgofer_customer_order_list($request){  
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;           
                    global $wpdb;
                    $orders = get_posts( array(                        
                        'numberposts' => -1,
                        'meta_key'    => '_customer_user',
                        'meta_value'  => $user_id,
                        'post_type'   => wc_get_order_types(),
                        'post_status' => array_keys( wc_get_order_statuses() ),
                    ) );                    
                                   
                    $data=array();
                    foreach ( $orders as $order ) {
                        if($order->post_parent != 0){
                        $the_order = new WC_Order( $order->ID );				 
                        $vendor_id  = get_post_meta( $order->ID, 'order_vendor_id', true );

                        $vendordata=get_user_meta( $vendor_id );
                      
                        if(get_post_meta( $order->ID, '_payment_method', true )!="cod" && get_post_meta( $order->ID, '_payment_method', true )!="" ){
                            $payment="Paid";						
                        }else{
                            $payment="COD";
                        }
                        $item=array();
                        foreach($the_order->get_items() as $key=>$vals){
                            
                            $pid= wc_get_order_item_meta($key,'_product_id');
                            $pdata=wc_get_product( $pid );
                            $catname=array();
                            foreach ( wc_get_object_terms( $pid, 'product_cat') as $term ) {
                                    $catname[] =  $term->name;
                            }
                            if(get_post_meta($pid, '_wc_deposits_enable_deposit', true)=="yes"){
                                $enable_deposit=true;
                                $tokenmoney=get_post_meta($pid, '_wc_deposits_deposit_amount', true);
                            } 
                            $item[]=array(
                                'product_id'=>$pid,
                                'product_name'=>html_entity_decode($pdata->get_name()),
                                'price'=> $vals->get_total(),
                                'tokenenable'=>isset($enable_deposit)? true : false,
                                'tokenamount'=>isset($tokenmoney)? $tokenmoney : 0,
                                'categories'=>$catname,                              
                                'quantity'=>wc_get_order_item_meta($key,'_qty')
                            );
                            unset($enable_deposit);
                            unset($tokenmoney);
                        }
                       $order_status = dokan_get_prop( $the_order, 'status' );
                       $statuskey= $order_status;
                       if( $order_status == "payment-pending"){
                           $statuskey="processing";
                       }
                       $tax_rates = WC_Tax::find_rates( array(
                        'country'   => 'IN',							
                        'tax_class' => '',
                        ) );
                        $k=key (  $tax_rates );
                        $data[]=array(
                            'orderid'			   =>$order->ID,
                            'tax_rate'             => $tax_rates[$k]["rate"] , 
                            'order_total'		   => $the_order->get_total(),
                            'date_time'		       => $the_order->get_meta("utc_delivery_datetime"),   
                            'order_status'         => $order_status,
                            'payment_method'        =>$the_order->get_payment_method(),
                            'payment_method_title'  =>$the_order->get_payment_method_title(),
                            'first_name'        =>$vendordata['first_name'][0],
                            'last_name'         =>$vendordata['last_name'][0],
                            'vendor_address'    =>$vendordata['address'][0],
                            'products'             => $item,  
                                                    
                        );
                      }
                    }                 
                $results=array(
                                "statuscode"=>200,
                                "message"=> "Success",                               
                                "data"=>$data
                            );
                $result = rest_ensure_response( $results );
                $result->header( 'X-WP-Total', (int) $total_order );  
                $result->header( 'X-WP-TotalPages', (int) $max_pages );			
            } else{
                $result = array(
                    "statuscode"=>477,
                    "message"=> $jwt,
                    "errors"=>$jwt
                );
            }            
          
            return $result; 
          
    }

    function bizgofer_update_device_token($request){
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            
            $user_id = $jwt->user_id;  
            if(isset($request['device_token']) && ($request['device_token'] != "")  ){
                update_user_meta($user_id ,"device_token", $request['device_token']);
            }
            $result=array(
                "statuscode" => 200,
                "message" => "Success!",
                "data" =>$user_id
            );
        } else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }            
  
        return $result; 
    }

    function bizgofer_logout_all_users($request){
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;  
            delete_user_meta($user_id ,"device_token");
            $result=array(
                "statuscode" => 200,
                "message" => "Logout Successfully!",
                "data" =>$user_id
            );
        } else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }            
  
        return $result; 
    }
    function  bizgofer_all_notification($request){
        $auth_token=$request->get_header('Auth-Token');
        $jwt=jwt_function($auth_token,"decode");
        
        if(is_object($jwt)){
            $user_id = $jwt->user_id;  
            global $wpdb;
            $rs=$wpdb->get_results("SELECT * FROM `wp_notification` WHERE `user_id` = $user_id order by id DESC");
            foreach ($rs as $r){
                $data[]=array(
                    "order_id"=>$r->order_id,
                    "recieved_at"=>$r->added_at,
                    "message"=>str_replace("?","₹", $r->message)
                );
            }
            $result=array(
                "statuscode" => 200,
                "message" => "Success!",
                "data" =>$data
            );
        } else{
            $result = array(
                "statuscode"=>477,
                "message"=> $jwt,
                "errors"=>$jwt
            );
        }            
  
        return $result; 
    }
    function bizgofer_get_page_content($request){
        if($request['page']=="about"){
            $page=18;
        } else if($request['page']=="terms"){
            $page=1481;
        }else if($request['page']=="privacy"){
            $page=3;
        }
        if(isset($page)){
            $post = get_post($page); 
            $post_title = $post->post_title;
            $content = $post->post_content;
            $results = array("statuscode"=>200,
                        "message"=>"success!",
                        "data"=>array("title"=>$post_title,
                        "content"=>$content
                        ));
        }
        else{
            $results = array(
                "statuscode"=>401,
                "message"=>"Page does not exist!",
                "error"=>"Page does not exist!"
            );
        }
        return $results;
    }

    function bizgofer_retrieve_password($user_login) {      
        global $wpdb, $current_site, $wp_hasher;
    
        if ( empty( $user_login) ) {
            return false;
        } else if ( strpos( $user_login, '@' ) ) {
            $user_data = get_user_by( 'email', trim( $user_login ) );
            if ( empty( $user_data ) )
               return false;
        } else {
            $login = trim($user_login);
            $user_data = get_user_by('login', $login);
        }
    
        do_action('lostpassword_post');
    
    
        if ( !$user_data ) return false;
    
        // redefining user_login ensures we return the right case in the email
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
    
        do_action('retreive_password', $user_login);  // Misspelled and deprecated
        do_action('retrieve_password', $user_login);
    
        $allow = apply_filters('allow_password_reset', true, $user_data->ID);
    
        if ( ! $allow )
            return false;
        else if ( is_wp_error($allow) )
            return false;
    
        
             $key = wp_generate_password(20, false);
            do_action('retrieve_password_key', $user_login, $key);
            
            if ( empty( $wp_hasher ) ) {
                require_once ABSPATH . WPINC . '/class-phpass.php';
                $wp_hasher = new PasswordHash( 8, true );
            }
            $hashed = time() . ':' . $wp_hasher->HashPassword( $key );


            $wpdb->update($wpdb->users, array('user_activation_key' => $hashed), array('ID' => $user_data->ID));
       
        $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
        $message .= network_home_url( '/' ) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= '<' . network_site_url("my-account/lost-password/?key=$key&id=" . rawurlencode($user_data->ID), 'login') . ">\r\n";
        if ( is_multisite() )
            $blogname = $GLOBALS['current_site']->site_name;
        else            
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    
        $title = sprintf( __('[%s] Password Reset'), $blogname );
    
        $title = apply_filters('retrieve_password_title', $title);
        $message = apply_filters('retrieve_password_message', $message, $key);
    
        if ( $message && !wp_mail($user_email, $title, $message) )
            wp_die( __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...') );
    
        return true;
    }
    function iconic_reset_password_redirect( $user ) {
       echo '<h1>Your password has been reset successfully.Please go to mobile app and Login now</h1>';exit;
        die();
        wp_redirect( add_query_arg( 'password-reset', 'true', wc_get_page_permalink( 'myaccount' ) ) );
        
    }
    add_action( 'woocommerce_customer_reset_password', 'iconic_reset_password_redirect', 10 );
    add_action( 'login_form_resetpass',  'do_password_reset'  );
    /**
 * Resets the user's password if the password reset form was submitted.
 */
 function do_password_reset() {
     die();
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
        $rp_key = $_REQUEST['rp_key'];
        $rp_login = $_REQUEST['rp_login'];
 
        $user = check_password_reset_key( $rp_key, $rp_login );
 
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                wp_redirect( home_url( 'member-login?login=expiredkey' ) );
            } else {
                wp_redirect( home_url( 'member-login?login=invalidkey' ) );
            }
            exit;
        }
 
        if ( isset( $_POST['pass1'] ) ) {
            if ( $_POST['pass1'] != $_POST['pass2'] ) {
                // Passwords don't match
                $redirect_url = home_url( 'member-password-reset' );
 
                $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                $redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
 
                wp_redirect( $redirect_url );
                exit;
            }
 
            if ( empty( $_POST['pass1'] ) ) {
                // Password is empty
                $redirect_url = home_url( 'member-password-reset' );
 
                $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                $redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
 
                wp_redirect( $redirect_url );
                exit;
            }
 
            // Parameter checks OK, reset password
            reset_password( $user, $_POST['pass1'] );
            wp_redirect( home_url( 'member-login?password=changed' ) );
        } else {
            echo "Invalid request.";
        }
 
        exit;
    }
}
function biz_create_discounted_cart_order_price($product_qty, $productid,  $order_quantity, $coupon_code,$request,$variation_id)
{
    if($variation_id!=0){
        $product_id=$variation_id;
    }else{
        $product_id=$productid;
    }
    $product_to_add = get_product($product_id);
    $sale_price     = $product_to_add->get_regular_price();
    if ($product_to_add->is_on_sale()) {
        $sale_price = $product_to_add->get_sale_price();
    }
    $without_discount = $sale_price;
    $final_price = $sale_price;
    $couponObject   = new WC_Coupon($coupon_code);
    $discountCoupon = $couponObject->amount;
    $discountCode   = $couponObject->code;
    
    if ($couponObject->discount_type == 'percent') {
        
        // Here we calculate the final price with the discount 
        $final_price      = round(($sale_price * $product_qty) * ((100 - $discountCoupon) / 100), 2);
        $discount_applied = round(($sale_price * $product_qty) - $final_price, 2);
        $discounts        = array(
            "product_id" => (string)$product_id,
            "discounted_price" => (string)$final_price,
            "discount" => (string)$discount_applied
        );
        
    } elseif ($couponObject->discount_type == 'fixed_cart') {
        
        // Here we calculate the final price with the discount
        $final_price      = round(($sale_price * $product_qty) - (($discountCoupon / $order_quantity) * $product_qty), 2);
        $discount_applied = round(($sale_price * $product_qty) - $final_price, 2);
        $discounts        = array(
            "product_id" => (string)$product_id,
            "discounted_price" => (string)$final_price,
            "discount" => (string)$discount_applied
        );
        
        
    } elseif ($couponObject->discount_type == 'fixed_product') {
        
        // Here we calculate the final price with the discount
        $final_price      = round(($sale_price * $product_qty) - ($discountCoupon * $product_qty), 2);
        $discount_applied = round(($sale_price * $product_qty) - $final_price, 2);
        $discounts        = array(
            "product_id" => (string)$product_id,
            "discounted_price" => (string)$final_price,
            "discount" => (string)$discount_applied
        );
    }  
        $discounts['code']=(string)$coupon_code;
   

    return $discounts;
    
}
function bizgofer_pre_checkout($request){     
    $auth_token=$request->get_header('Auth-Token');
    $jwt=jwt_function($auth_token,"decode");
    $request = $request->get_json_params();
    if(is_object($jwt)){
            $user_id = $jwt->user_id;  
            global $woocommerce;
            $line_items  = $request['line_items']; 
            //get cart total
            $total = $request['total'];            
            $vendortotal   = array();
            $quantitytotal = '';
            $author        = array();
            $taxes         = array();
           
            foreach ($line_items as $order_products) {
                
                $stock     = get_post_meta($order_products['product_id'], '_stock', true);
                $reg_price = get_post_meta($order_products['product_id'], '_regular_price', true);
                
                if (($stock!="") && ($stock < $order_products['quantity'])) {
                    
                    $data['remaining_items'][] = array(
                        "product_id" => $order_products['product_id'],
                        "new_quantity" => $stock
                    );
                    
                } else {
                    $vendor_id = get_post_field('post_author', $order_products['product_id']);
                    
                    
                    
                    if (in_array($vendor_id, $author)) {
                        
                        $vendortotal[$vendor_id] += $order_products['totalPrice'];
                        $order_quantity += $order_products['quantity'];
                        
                    } else {
                        
                        $author[]                = $vendor_id;
                        $vendortotal[$vendor_id] = $order_products['totalPrice'];
                        $order_quantity += $order_products['quantity'];
                        
                    }
                    // Calculate discount coupons for line items
                    // start coupon discount functionality
                    $flagProduct  = false;
                    $flagCategory = false;
                    $couponValid  = true;
                    $cu_coupon    = false;
                    $msg="";
                    if (isset($request['coupon_code']) || $request['coupon_code'] != "") {
                        
                        $coupon_code = $request['coupon_code'];
                        
                        
                        $couponObject       = new WC_Coupon($coupon_code);
                        $discountCategories = $couponObject->product_categories;
                        $discountProducts   = $couponObject->product_ids;
                        $usage_left         = $couponObject->usage_limit - $couponObject->usage_count;
                        
                        $get_coupon_post = get_page_by_title($coupon_code, ARRAY_A, 'shop_coupon');
                        
                        // get role of coupon author
                        if (!empty($get_coupon_post)) {
                            $id        = get_post_field('post_author', $get_coupon_post['ID']);
                            $user_info = get_userdata($id);
                            
                            // check if this coupon created by admin or not
                            if (in_array('administrator', $user_info->roles)) {
                                $cu_coupon = true;
                            }
                        }
                        
                        
                        
                        // check if this coupon applicable for all merchants(created by cu admin)
                        if (!$cu_coupon) {
                            $args = array(
                                'author' => $vendor_id,
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'desc',
                                'post_type' => 'shop_coupon',
                                'post_status' => 'publish'
                            );
                            
                            /**
                             * get the particular author coupons data
                             */
                            $coupons = get_posts($args);
                            
                            // check if this coupon belongs current store id
                            if (count($coupons) > 0) {
                                foreach ($coupons as $coupon) {
                                    
                                    if ($coupon->post_title === $coupon_code) {
                                        $couponValid = true;
                                        break;
                                    } else {
                                        $couponValid = false;
                                        $msg         = 'Coupon is not valid for this store';
                                    }
                                    
                                }
                            } else {
                                
                                $couponValid = false;
                                $msg         = 'Coupon is not valid for this store';
                                
                            }
                        }
                        
                        if ($usage_left < 0) {
                            $couponValid = false;
                            $msg         = 'Entered Coupon Usage Limit has been Reached';
                            
                        }
                        if ($couponObject->amount <= 0) {
                            $couponValid = false;
                            $msg         = 'Invalid Coupon';
                        }                     
                       
                        $couponObject->expiline_itemsry_date;
                        $datetime1 = new DateTime('now');
                        $datetime2 = new DateTime($couponObject->expiry_date);
                        
                        if ($datetime1 > $datetime2) {
                            $couponValid = false;
                            $msg         = 'Entered Coupon has been expired';
                        }
                        
                        if ($couponValid) {
                            
                            //check if product discount enable for this product id
                            if (in_array($order_products['product_id'], $discountProducts)) {
                                $flagProduct = true;
                            }elseif (in_array($order_products['variation_id'], $discountProducts))
                            {
                                $flagProduct = true;
                            }
                            
                            // check if category discount enable for this product id
                            if (count($discountCategories) > 0) {
                                $terms       = get_the_terms($order_products['product_id'], 'product_cat');
                                $flagProduct = false;
                                $msg         = 'Coupon does not belong to any of this cart products';
                                foreach ($terms as $term) {
                                    if (in_array($term->term_id, $discountCategories)) {
                                        $flagCategory = true;
                                        break;
                                    }
                                }
                            }
                            
                            // check for category or product discount coupon
                            if ($flagCategory || $flagProduct) {
                                if(isset($order_products['variation_id'])){$variation_id=$order_products['variation_id'];}else{$variation_id=0;}
                                $discounts  = biz_create_discounted_cart_order_price($order_products['quantity'], $order_products['product_id'], $order_quantity, $coupon_code,$request,$variation_id);
                                $discountsData[]                            = $discounts;
                                $discountAmtTaxes[$discounts['product_id']] = $discounts['discounted_price'];
                                
                            }
                        }
                        
                    }
                    
                }
            }
            if (isset($data['remaining_items'])&&(count($data['remaining_items']) >= 1)) {
                $result = array(
                    "statuscode" => 500,
                    "message" => "Items Not available",
                    "items" => $data['remaining_items']
                );
                return $result;                
            }
            
            
            if ($couponValid && (!empty($discountsData) && isset($discountsData))) {
                $data['coupon_line'] = $discountsData;
                $result = array(
                    "statuscode" => 200,
                    "message" => "Success",
                    "data" => $data
                );
            } elseif (!$couponValid) {
                $data['coupon_line'] = array(
                    "coupon_name" => $coupon_code,
                    "message" => $msg
                );
                $result = array(
                    "statuscode" => 400,
                    "error" => $msg,
                    "message" => $msg,
                );
            } elseif(!$flagCategory && !$flagProduct){
                $result = array(
                    "statuscode" => 403,
                    "error" =>"Coupon not valid for this product",
                    "message" => "Coupon not valid for this product",
                );
            }else {
                $data['coupon_line'] = "Coupon Not Applied";
                $result = array(
                    "statuscode" => 404,
                    "error" => "Coupon Not Applied",
                    "message" => "Coupon Not Applied",
                );
                
            }
    } else{
        $result = array(
            "statuscode"=>477,
            "message"=> $jwt,
            "errors"=>$jwt
        );
    }  
    return $result; 
} 

	function bizgofer_apis_functions(){

        register_rest_route( 'bizgofer_apis/v1', '/get_categories/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_categories',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        
        register_rest_route( 'bizgofer_apis/v1', '/get_subcategories_with_product/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_subcategorieswithproducts',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/get_subcategories/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_subcategorieswithoutproduct',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/global_search/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_category_search',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        
        
        register_rest_route( 'bizgofer_apis/v1', '/get_products/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_product_list_admin',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        register_rest_route( 'bizgofer_apis/v1', '/get_service_title/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_product_list_vendor',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        

        register_rest_route( 'bizgofer_apis/v1', '/particular_products/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_product_detail',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/add_user/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'signup_any_user',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/identify_user/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_login_user',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/check_user/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'check_user_exist',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
       
        register_rest_route( 'bizgofer_apis/v1', '/update_user/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'update_user',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/update_user_password/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'change_password',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/add_user_address/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_add_user_address',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/update_user_address/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_update_user_address',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/list_user_addresses/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_all_user_address',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/delete_user_address/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_delete_user_address',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        
        register_rest_route( 'bizgofer_apis/v1', '/upload_media_file/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_upload_image',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
         
        register_rest_route( 'bizgofer_apis/v1', '/suborder_create/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_create_sub_orders',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/suborder_create_test/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_create_sub_orders_demo',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
               
           
        register_rest_route( 'bizgofer_apis/v1', '/get_cart/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_get_cart',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        register_rest_route( 'bizgofer_apis/v1', '/add_to_cart/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'komal_add_to_cart',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        register_rest_route( 'bizgofer_apis/v1', '/rayadd_to_cart/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_add_to_cart',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/remove_cart/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_remove_cart',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/clear_cart/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_clear_cart',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );

        /***vendor apis */
        register_rest_route( 'bizgofer_apis/v1', '/get_categories_without_location/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_categories_without_location',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        
        register_rest_route( 'bizgofer_apis/v1', '/products_attribute/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_get_product_detail_without_vendor',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
        
        register_rest_route( 'bizgofer_apis/v1', '/add_service/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_add_product',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/edit_service/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_edit_product',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/service_list/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_product_list_vendor',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/order_list/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_vendor_order_list',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/vendor_particular_order/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_vendor_particular_order',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/user_order_list/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_customer_order_list',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );
        
        register_rest_route( 'bizgofer_apis/v1', '/update_status/', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_update_status',
            'permission_callback' => function (WP_REST_Request $request){
                return true;
            }
        ) );
        
        register_rest_route( 'bizgofer_apis/v1', '/list_workers/', array(
         'methods' => WP_REST_Server::EDITABLE,
         'callback' => 'bizgofer_list_workers',
         'permission_callback' => function (WP_REST_Request $request){
             return true;
         }
     ) );

        register_rest_route( 'bizgofer_apis/v1', '/particular_order/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_order_detail',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/update_particular_order/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_update_particular_order',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/update_order_payment_details/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_update_particular_order_payment_details',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/update_device_token/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_update_device_token',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/logout/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_logout_all_users',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );
        register_rest_route( 'bizgofer_apis/v1', '/notification_list/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_all_notification',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );

        register_rest_route( 'bizgofer_apis/v1', '/get_page_content/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_get_page_content',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) );    

        register_rest_route( 'bizgofer_apis/v1', '/get_lock_key/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_change_lock_key',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) ); 

        register_rest_route( 'bizgofer_apis/v1', '/lost_lock_key/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_lost_lock_key',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) ); 

        register_rest_route( 'bizgofer_apis/v1', '/retrieve_password/', array(
            'methods'  => WP_REST_Server::EDITABLE,
            'callback' => 'bizgofer_retrieve_password',
                'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ) ); 

        register_rest_route( 'bizgofer_apis/v1', '/apply_coupon/', array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'bizgofer_pre_checkout',
				'permission_callback' => function (WP_REST_Request $request) {
				   return true;
			   }
        ) );
    }
    add_action( 'rest_api_init', 'bizgofer_apis_functions' );  
?>
