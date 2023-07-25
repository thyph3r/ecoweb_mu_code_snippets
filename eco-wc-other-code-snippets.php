<?php
/**
 * Plugin Name: Ecoweb Other Code Snippets
 * Description: Display Weight @ Cart & Checkout | Change "You may also like" | Sort rates by cost | Display "POA" for blank price products | Replaces "No shipping methods available in Settings -> Shipping -> No-shipping Message
 * Version: 2.3.0
 * Author: Othon Man
 * Author URI: http://ecoweb.gr
 */

/**
 * @author        Othon Man
 * @compatible    WC 7.9.0, WP 6.2.2, PHP 7.1.0
 */
 

/**
 * Track Contact Forms using Google Analytics
 */
function hook_track_contact() {
    ?>
		<script>
		document.addEventListener( 'wpcf7mailsent', function( event ) {
			ga('send', 'event', 'Contact Form', 'submit');
		}, false );
		</script>
    <?php
}
add_action('wp_head', 'hook_track_contact');

/**
 * Display Weight @ Cart & Checkout - WooCommerce
 */

add_action('woocommerce_before_cart', 'ecoweb_print_cart_weight');
 
function ecoweb_print_cart_weight( $posted ) {
global $woocommerce;
$notice = 'Total cart weight is: ' . $woocommerce->cart->cart_contents_weight . get_option('woocommerce_weight_unit');
if( is_cart() ) {
    wc_print_notice( $notice, 'notice' );
} else {
    wc_add_notice( $notice, 'notice' );
}
}

/**
 * Description: Replaces "No shipping methods available ..." message with a provided text. Look for Settings -> Shipping -> No-shipping Message.
 */

call_user_func(function()
{
    define('WNSM_OPTION_ID', 'wnsm_no_shipping_message');

    if ($message = get_option(WNSM_OPTION_ID)) {
        foreach (array('woocommerce_cart_no_shipping_available_html', 'woocommerce_no_shipping_available_html') as $hook) {
            add_filter($hook, function() use($message) {
                return __($message, 'wc-no-shipping-methods');
            });
        }
    }

    if (is_admin()) {
        add_filter('woocommerce_shipping_settings', function ($settings) {
            $noShippingMessageField = array(
                'id' => WNSM_OPTION_ID,
                'type' => 'textarea',
                'title' => __('No-Shipping Message', 'wc-no-shipping-methods'),
                'desc' => __('Message shown to the customer when no shipping methods available', 'wc-no-shipping-methods'),
                'default' => '',
                'css' => 'width:350px; height: 65px;',
            );

            $maybeSectionEnd = end($settings);
            $newFieldPosition = @$maybeSectionEnd['type'] == 'sectionend' ? -1 : count($settings);
            array_splice($settings, $newFieldPosition, 0, array($noShippingMessageField));

            return $settings;
        });

        add_filter('plugin_action_links_' . plugin_basename(wp_normalize_path(__FILE__)), function($links) {
            $settingsUrl = admin_url('admin.php?page=wc-settings&tab=shipping&section=options#' . urlencode(WNSM_OPTION_ID));
            array_unshift($links, '<a href="'.esc_html($settingsUrl).'">'.__('Edit message', 'wc-no-shipping-methods').'</a>');
            return $links;
        });
    }
});



/**
 * Description: Show limited number of shipping methods & sort shipping cost ****NEEDS WORK THROWS ERROR SERVER LOGS****
 */
add_filter( 'woocommerce_package_rates', 'wf_show_limited_number_of_shipping_methods_cheapest', 100, 2 );

if( ! function_exists('wf_show_limited_number_of_shipping_methods_cheapest') ) {
	function wf_show_limited_number_of_shipping_methods_cheapest( $rates, $package ) {

		$number_of_cheapest_method_to_show = 6;				//Number of cheapest shipping methods to be displayed

		if( ! $rates ) {
			return;
		}

		// get an array of prices to sort the shipping cost
		$prices = array();
		foreach( $rates as $rate ) {
			$prices[] = $rate->cost;
		}
		
		array_multisort( $prices, $rates );					// use the shipping prices to sort the shipping rates

		$rates = array_slice( $rates, 0, $number_of_cheapest_method_to_show );// Get the specified number of cheapest rates

		return $rates;
	}
}


/**
 * Hide Visual Composer license activation notification
 */
add_action('admin_head', 'ec_hide_vc_notification_css');
function ec_hide_vc_notification_css() {
	echo '<style>
    	#vc_license-activation-notice { display: none !important; } 
	</style>';
}

/**
 * Changing the cart empty message 
 */
add_filter( 'wc_empty_cart_message', 'ec_child_add_to_cart_message' );
if ( ! function_exists( 'ec_child_add_to_cart_message' ) ) {
    function ec_child_add_to_cart_message() {
        ob_start(); ?><span class="add-stuff" style="display: block; text-align: center;">
            <img src="https://your_domain_name_here.eu/wp-content/uploads/2035/12/emptycart.jpg" style="margin: auto;" />
        </span><?php
        return ob_get_clean();
    }
}

/*
* Remove unused user roles
*/
remove_role('subscriber');
remove_role('editor');
remove_role('contributor');
remove_role('author');
remove_role('wpseo_manager');
remove_role('wpseo_editor');

/*
* Add New User Role
*/
add_role('premium_customer', 'Premium Customer', array(
	'read' => false,
	'edit_posts' => false,
	'delete_posts' => false
));

/**
 * Change min password strength.
 */
function ecoweb_min_password_strength($strength)
	{
	return 0;
	}
add_filter('woocommerce_min_password_strength', 'ecoweb_min_password_strength', 10, 1);

/**
 * Remove "(can be backordered)" text from the product availability text
 * when product is in stock and backorders are allowed (with a customer notification)
 */
add_filter( 'woocommerce_get_availability_text', 'filter_product_availability_text', 10, 2 );
function filter_product_availability_text( $availability, $product ) {

    if( $product->backorders_require_notification() ) {
        $availability = str_replace('(can be backordered)', '', $availability);
        $availability = str_replace('(επιπλέον μπορεί να ζητηθεί κατόπιν παραγγελίας)', '', $availability);
        $availability = str_replace('(kann nachbestellt werden)', '', $availability);
        $availability = str_replace('(peut être commandé)', '', $availability);
        $availability = str_replace('(puede reservarse)', '', $availability);
        $availability = str_replace('(может быть предзаказано)', '', $availability);
	
    }
    return $availability;
}

/**
 * Stylize Login Page. 
 
function custom_login_stylesheet() { 
    wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/login/login-styles.css' );
}
add_action( 'login_enqueue_scripts', 'custom_login_stylesheet' ); // Load Login Styles

function my_login_logo_url() { 
    return home_url();
}
add_filter( 'login_headerurl', 'my_login_logo_url' ); // Logo click redirect to home

function my_login_logo_url_title() {
    return 'WEBSITE NAME & TAG INSERT HERE';
}
add_filter( 'login_headertitle', 'my_login_logo_url_title' ); // Change Logo Hover Text


function custom_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/login/logo_login_sm.png);
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'custom_login_logo' ); // Change Logo Image
*/



/**
 * BACS - Bank details rearrange. 
*/
add_filter('woocommerce_bacs_account_fields','custom_bacs_fields');
function custom_bacs_fields() {
	global $wpdb;
	$account_details = get_option( 'woocommerce_bacs_accounts',
				array(
					array(
						'account_name'   => get_option( 'account_name' ),
						'account_number' => get_option( 'account_number' ),
						'sort_code'      => get_option( 'sort_code' ),
						'bank_name'      => get_option( 'bank_name' ),
						'iban'           => get_option( 'iban' ),
						'bic'            => get_option( 'bic' )
					)
				)
			);
	$account_fields = array(
		'account_name'      => array(
			'label' => __( 'Account name', 'woocommerce' ),
			'value' => $account_details[0]['account_name']
		),
		'iban'      => array(
			'label' => __( 'IBAN', 'woocommerce' ),
			'value' => $account_details[0]['iban']
		),
		'account_number' => array(
			'label' => __( 'Account number', 'woocommerce' ),
			'value' => $account_details[0]['account_number']
		),
		'bic' => array(
			'label' => __( 'BIC', 'woocommerce' ),
			'value' => $account_details[0]['bic']
		),
		'sort_code' => array(
			'label' => __( 'BSB', 'woocommerce' ),
			'value' => $account_details[0]['sort_code']
		),		
		'bank_name'      => array(
			'label' => __( 'Bank', 'woocommerce' ),
			'value' => $account_details[0]['bank_name']
		)
	);
	
	$bank1 =  array(
			'label' => 'HQ Address',
			'value' => 'XXXXXXX, Athens Greece'
		);
	
	$bank2 =  array(
			'label' => 'Local Branch Address',
			'value' => 'XXXXXXX, Athens Greece'
		);	
	
	array_push($account_fields,$bank1,$bank2);
	//print_r($account_fields);

	return $account_fields;
} 


/**
 * Add a 42 EUR customs cost for Countries not in EU
 * If DHL Shipping method is selected do not add export cost for Non-EU Countries
 * Taxes, shipping costs and order subtotal are all included in the surcharge amount
 */
add_action( 'woocommerce_cart_calculate_fees','woocommerce_customs_surcharge' );

function woocommerce_customs_surcharge() {

  global $woocommerce;
  if ( is_admin() && ! defined( 'DOING_AJAX' ) )
	  return;
  $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
  $chosen_shipping = $chosen_methods[0];
  $country = array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'NL', 'MT', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'MC');
	if ( !in_array( $woocommerce->customer->get_shipping_country(), $country ) ) :
	if (($chosen_shipping == 'wf_dhl_shipping:1|1') OR ($chosen_shipping == 'wf_dhl_shipping:2|2') OR ($chosen_shipping == 'wf_dhl_shipping:3|3') OR ($chosen_shipping == 'wf_dhl_shipping:4|4') OR ($chosen_shipping == 'wf_dhl_shipping:5|5') OR ($chosen_shipping == 'wf_dhl_shipping:6|6') OR ($chosen_shipping == 'wf_dhl_shipping:7|7') OR ($chosen_shipping == 'wf_dhl_shipping:8|8') OR ($chosen_shipping == 'wf_dhl_shipping:9|9') OR ($chosen_shipping == 'wf_dhl_shipping:B|B') OR ($chosen_shipping == 'wf_dhl_shipping:D|D') OR ($chosen_shipping == 'wf_dhl_shipping:F|F') OR ($chosen_shipping == 'wf_dhl_shipping:G|G') OR ($chosen_shipping == 'wf_dhl_shipping:H|H') OR ($chosen_shipping == 'wf_dhl_shipping:J|J') OR ($chosen_shipping == 'wf_dhl_shipping:L|L') OR ($chosen_shipping == 'wf_dhl_shipping:M|M') OR ($chosen_shipping == 'wf_dhl_shipping:N|N') OR ($chosen_shipping == 'wf_dhl_shipping:O|O') OR ($chosen_shipping == 'wf_dhl_shipping:P|P') OR ($chosen_shipping == 'wf_dhl_shipping:R|R') OR ($chosen_shipping == 'wf_dhl_shipping:S|S') OR ($chosen_shipping == 'wf_dhl_shipping:T|T') OR ($chosen_shipping == 'wf_dhl_shipping:U|U') OR ($chosen_shipping == 'wf_dhl_shipping:V|V') OR ($chosen_shipping == 'wf_dhl_shipping:W|W') OR ($chosen_shipping == 'wf_dhl_shipping:Y|Y') OR ($chosen_shipping == 'wf_fedex_woocommerce_shipping:FEDEX_INTERNATIONAL_CONNECT_PLUS') OR ($chosen_shipping == 'wf_fedex_woocommerce_shipping:FEDEX_INTERNATIONAL_PRIORITY'))
	{
		return;  
	}
else
	$surcharge11 = 42;
	$woocommerce->cart->add_fee( 'Greek Customs Value', $surcharge11, true, '' );
	endif;
}

/**
 * @snippet       WooCommerce Add fee to checkout for a gateway ID
 * @testedwith    WooCommerce 5.0.2
 */
 
// Part 1: assign fee
add_action( 'woocommerce_cart_calculate_fees', 'ecoweb_add_checkout_fee_for_gateway' );
  
function ecoweb_add_checkout_fee_for_gateway() {
   
  global $woocommerce;
 
  $chosen_gateway = $woocommerce->session->chosen_payment_method;
    
  if ( $chosen_gateway == 'ppcp-gateway' ) {
    
    $percentage = 0.04;
    $surcharge = ( WC()->cart->cart_contents_total + WC()->cart->shipping_total + WC()->cart->shipping_tax_total + 					WC()->cart->tax_total ) * $percentage;
	$woocommerce->cart->add_fee( 'PayPal Fee', $surcharge, true, '');
  }
}
// Part 2: reload checkout on payment gateway change
add_action( 'woocommerce_review_order_before_payment', 'ecoweb_refresh_checkout_on_payment_methods_change' );
function ecoweb_refresh_checkout_on_payment_methods_change(){
    ?>
    <script type="text/javascript">
        (function($){
            $( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
                $('body').trigger('update_checkout');
            });
        })(jQuery);
    </script>
    <?php
}
/**
 * Add custom HTML below top bar (use for promos)

add_action( 'electro_before_header', 'ec_child_custom_html', 40 );
function ec_child_custom_html() {
    ?>
<p style= "text-align: center; color:#0787ea; padding-top:5px;">
🌞  Our company will be closed: <strong> 12 - 18 August </strong> due to Summer Holidays..  🌞
</p>
<?php
}   */

// Redirect WordPress Registration page to custom page
function my_registration_page_redirect()
{
	global $pagenow;

	if ( ( strtolower($pagenow) == 'wp-login.php') && ( strtolower( $_GET['action']) == 'register' ) ) {
		wp_redirect( home_url('https://your_domain_name_here.eu/my-account/'));
	}
}

add_filter( 'init', 'my_registration_page_redirect' );

/*
 * Create a column. And maybe remove some of the default ones
 * @param array $columns Array of all user table columns {column ID} => {column Name} 
 */
add_filter( 'manage_users_columns', 'rudr_modify_user_table' );
 
function rudr_modify_user_table( $columns ) {
 
	// unset( $columns['posts'] ); // maybe you would like to remove default columns
	$columns['registration_date'] = 'Registration date'; // add new
 
	return $columns;
 
}
 
/*
 * Fill our new column with the registration dates of the users
 * @param string $row_output text/HTML output of a table cell
 * @param string $column_id_attr column ID
 * @param int $user user ID (in fact - table row ID)
 */
add_filter( 'manage_users_custom_column', 'rudr_modify_user_table_row', 10, 3 );
 
function rudr_modify_user_table_row( $row_output, $column_id_attr, $user ) {
 
	$date_format = 'j M, Y H:i';
 
	switch ( $column_id_attr ) {
		case 'registration_date' :
			return date( $date_format, strtotime( get_the_author_meta( 'registered', $user ) ) );
			break;
		default:
	}
 
	return $row_output;
 
}
 
/*
 * Make our "Registration date" column sortable
 * @param array $columns Array of all user sortable columns {column ID} => {orderby GET-param} 
 */
add_filter( 'manage_users_sortable_columns', 'rudr_make_registered_column_sortable' );
 
function rudr_make_registered_column_sortable( $columns ) {
	return wp_parse_args( array( 'registration_date' => 'registered' ), $columns );
}

// Part 1
// Add the message notification and place it over the billing section
// The "display:none" hides it by default
  
add_action( 'woocommerce_before_checkout_billing_form', 'ecoweb_echo_notice_shipping' );
  
function ecoweb_echo_notice_shipping() {
echo '<div class="shipping-notice woocommerce-info" style="display:none">We cannot dispatch orders in Private Persons in Russia. Valid company info is required.</div>';
}
  
// Part 2
// Show or hide message based on billing country
// The "display:none" hides it by default
  
add_action( 'woocommerce_after_checkout_form', 'ecoweb_show_notice_shipping' );
  
function ecoweb_show_notice_shipping(){
     
    ?>
  
    <script>
        jQuery(document).ready(function($){
  
            // Set the country code (That will display the message)
            var countryCode = 'RU';
  
            $('select#billing_country').change(function(){
  
                selectedCountry = $('select#billing_country').val();
                  
                if( selectedCountry == countryCode ){
                    $('.shipping-notice').show();
                }
                else {
                    $('.shipping-notice').hide();
                }
            });
  
        });
    </script>
  
    <?php
     
}