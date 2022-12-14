<?php
add_action('woocommerce_product_options_general_product_data', function() {
	?><?php
 	woocommerce_wp_text_input([
		'id' => '_username_text_input',
		'label' => __('Add Username For Reward', 'txtdomain'),
		'wrapper_class' => 'show_if_simple',
	]);
});
add_action('woocommerce_process_product_meta', function($post_id) {
	$product = wc_get_product($post_id);
	$product->update_meta_data('_username_text_input', sanitize_text_field($_POST['_username_text_input']));
 	$product->save();
});
  
add_action( 'woocommerce_single_product_summary', 'bbloomer_product_sold_count', 11 );
  
function bbloomer_product_sold_count() {
   global $product;
   $units_sold = $product->get_total_sales();
   if ( $units_sold ) echo '<p>' . sprintf( __( 'Units Sold: %s', 'woocommerce' ), $units_sold ) . '</p>';
}


add_action( 'woocommerce_order_status_changed', 'update_product_total_sales_on_cancelled_orders', 10, 4 );
function update_product_total_sales_on_cancelled_orders( $order_id, $old_status, $new_status, $order ){
    if ( in_array( $old_status, array('processing', 'completed') ) && 'cancelled' === $new_status 
    && ! $order->get_meta('_order_is_canceled') ) {

        // Loop through order items
        foreach ( $order->get_items() as $item ) {
            // Get the WC_product object (and for product variation, the parent variable product)
            $product = $item->get_variation_id() > 0 ? wc_get_product( $item->get_product_id() ) : $item->get_product();

            $total_sales   = (int) $product->get_total_sales(); // get product total sales
            $item_quantity = (int) $item->get_quantity(); // Get order item quantity

            $product_saling  = $product->set_total_sales( $total_sales - $item_quantity ); // Decrease product total sales
            $product->save(); // save to database
        }
        $order->update_meta_data('_order_is_canceled', '1'); // Flag the order as been cancelled to avoid repetitions
        $order->save(); // save to database
    }
}

add_filter( 'wc_points_rewards_action_settings', 'wdm_points_rewards_first_product_settings' );

function wdm_points_rewards_first_product_settings( $settings ) {
  
	$settings[] = array(
		'title'    => __( 'Points earned for first Product Purchesh' ),
		'desc_tip' => __( 'Enter the amount of points earned when a customer first Purchesh product.' ),
		'id'       => 'wdm_points_rewards_first_product',
	);

	return $settings;
}

add_filter( 'wc_points_rewards_event_description', 'add_points_rewards_newsletter_action_event_description', 10, 3 );
function add_points_rewards_newsletter_action_event_description( $event_description, $event_type, $event ) {
	global $wpdb;
	$wpdb_prefix = $wpdb->prefix;
	$wpdb_tablename = $wpdb_prefix.'users';
	
		$product_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb_prefix}posts WHERE post_type IN ('product')");
		$points_label = get_option( 'wc_points_rewards_points_label' );
		//$product_name = get_the_title( $product_ids );
	// set the description if we know the type
	switch ( $event_type ) {
		case 'first-product-purchesh_sucess': $event_description = sprintf( __( 'Earned for First product Purcheshed' ), $points_label ); break;
	}

	return $event_description;
}

function after_product_sale_reword($order_id){
	$order = wc_get_order( $order_id );
		echo $items = $order->get_items();
		foreach ( $items as $item ) {
			$product_name = $item->get_name();
			$pro_id = $item->get_product_id();
			$product_variation_id = $item->get_variation_id();

			if($order_id){ 
				global $wpdb;
				$wpdb_prefix = $wpdb->prefix;
				$wpdb_tablename = $wpdb_prefix.'users';
				
				 $reword_user = get_post_meta( $pro_id, '_username_text_input', true);
				 $points = get_post_meta( $pro_id , '_regular_price', true);
				 $user_result = $wpdb->get_results(sprintf("SELECT * FROM $wpdb_tablename WHERE user_login = '$reword_user'"));
				
				$rewuser_id = $user_result[0]->ID;
				if($pro_id){ 
					if(!empty($reword_user) ){
						$total_sales = get_post_meta( $pro_id, 'total_sales', true );
				  		$user_id = 0;
				  		$customer_id = $user_id == 0 ? get_current_user_id() : $user_id;
				
					$rew_order = wc_get_order( $order_id );
					$completed_dates[ $order_id ]    = $rew_order->get_date_completed();
					if ($rew_order !== false) {
						$order_data = $rew_order->get_data(); // The Order data
						$order_id = $order_data['id'];
						$order_parent_id = $order_data['parent_id'];
						 $order_status = $order_data['status'];
						if($order_status == 'completed'){
					 
						  if (!empty($points)) {
							$user = new WP_User( $rewuser_id );
							$user_role = $user->roles;
							
								if($user_role[0] == 'subscriber'){
									$product_sale = 12;
								}elseif($user_role[0] == 'customer'){
									$product_sale = 40;	
								}
								
							if( $product_sale == $total_sales){
								echo $product_sale;
								$data = array( 'first_product_id' => $first_product_id );
								$point_test = WC_Points_Rewards_Manager::increase_points($rewuser_id , $points, 'first-product-purchesh_sucess', $order_id, $data);
								   }  
								}
					 		}
					   	}
				   	}
				}
			}
		}
 } 

add_action( 'woocommerce_order_status_completed', 'after_product_sale_reword');

add_filter('woocommerce_product_data_tabs', 'hs_product_settings_tabs' );
function hs_product_settings_tabs( $tabs ){
 
$tabs['hs'] = array(
		'label'    => 'AffiliatesFactor',
		'target'   => 'hs_product_data',
		//'class'    => array('show_if_virtual'),
		'priority' => 21,
	);
	return $tabs;
 
}
 
/*
 * Tab content
 */
add_action( 'woocommerce_product_data_panels', 'hs_product_panels' );
function hs_product_panels(){
    global $mor;
 
	echo '<div id="hs_product_data" class="panel woocommerce_options_panel">';
 
	woocommerce_wp_text_input( array(
		'id'                => 'hs_very_value',
		'value'             => get_post_meta( get_the_ID(), 'hs_very_value', true ),
		'label'             => 'Ver Y',
		'description'       => 'Description when desc_tip param is not true'
	) );
    woocommerce_wp_text_input( array(
		'id'                => 'hs_verm_value',
		'value'             => get_post_meta( get_the_ID(), 'hs_verm_value', true ),
		'label'             => 'Ver M',
		'description'       => 'Description when desc_tip param is not true'
	) );
    woocommerce_wp_text_input( array(
		'id'                => 'hs_veraf_value',
		'value'             => get_post_meta( get_the_ID(), 'hs_veraf_value', true ),
		'label'             => 'Ver AF',
		'description'       => 'Description when desc_tip param is not true'
	) );
 

  //  $_product = wc_get_product( $product_id );     
	
$ver_x = get_post_meta( get_the_ID(), '_regular_price', true);
if(empty($ver_x)){
 $ver_x = 0;
}elseif(100 <= $ver_x){
 $ver_x;
}else{
    $ver_x = 100;
}
$x = $ver_x;


$ver_y = get_post_meta( get_the_ID(), 'hs_very_value', true );
if(empty($ver_y)){
 $ver_y = 1;
}else{
 $ver_y;
}
$y = $ver_y;

$ver_m = get_post_meta( get_the_ID(), 'hs_verm_value', true );
if(empty($ver_m)){
 $ver_m = 1;
}else{
 $ver_m;
}
$m = $ver_m;

$ver_af = get_post_meta( get_the_ID(), 'hs_veraf_value', true );
if(empty($ver_af)){
$ver_af = 0;
}else{
$ver_af;
}

$AffiliatesFactor =  $ver_af;

$mf = ($x/$y)*$m;
$mor = round((1+$mf)*10,1)+$AffiliatesFactor;
//echo $mor; 

echo '$x = '.$ver_x.'<br>
$y = '.$ver_y.';<br>
$m = '.$ver_m.';<br>
$AffiliatesFactor = '.$ver_af.';<br>
$mf = ($x/$y)*$m;<br>
$mor = round((1+$mf)*10,1)+$AffiliatesFactor<br>';
echo 'ans =';
echo round($mor); 
 
	echo '</div>';
 
}
 
 
/*
 * Save
 */
add_action('woocommerce_process_product_meta', 'save_my_custom_settings'); 
function save_my_custom_settings($post_id) {
	$product = wc_get_product($post_id);
	
	$product->update_meta_data('hs_very_value', sanitize_text_field($_POST['hs_very_value']));
    $product->update_meta_data('hs_verm_value', sanitize_text_field($_POST['hs_verm_value']));
    $product->update_meta_data('hs_veraf_value', sanitize_text_field($_POST['hs_veraf_value']));
 
	$product->save();
};
