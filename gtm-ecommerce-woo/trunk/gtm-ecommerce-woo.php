<?php
/**
 * Plugin Name: GTM Ecommerce for WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/gtm-ecommerce-woo
 * Description: Push WooCommerce Ecommerce (Enhanced Ecommerce and GA4) information to GTM DataLayer. Use any GTM integration to measure your customers' activites.
 * Version:     1.0.0
 * Author:      Handcraft Byte
 * Author URI:  https://handcraftbyte.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 */
namespace GtmEcommerceWoo;

require_once 'inc/utils.php';

$upperCamelCaseNamespace = "GtmEcommerceWoo";
$undescoreNamespace = "gtm_ecommerce_woo";
$dashscoreNamespace = "gtm-ecommerce-woo";


/**
 * Tracks puchase event
 *
 * @param  order id
 * @return void
 */
function purchase( $order_id ) {
	if ( get_option( $undescoreNamespace . '_disabled' ) === 1 ) {
		return;
	}
	$order         = wc_get_order( $order_id );
	$dataLayerCode = Utils\obWrap(
		function() use ( $order ) {
			?>
		dataLayer.push({
			'ecommerce': {
				'currencyCode': '<?php echo $order->get_currency(); ?>',
				'purchase': {
					'actionField':{
					'id': '<?php echo $order->get_order_number(); ?>',
					<?php /* 'affiliation': 'WooCommerce', */ ?>
					'affiliation': '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>',
					'revenue': <?php echo number_format( $order->get_subtotal() - $order->get_total_discount(), 2, '.', '' ); ?>,
					'tax': <?php echo number_format( $order->get_total_tax(), 2, '.', '' ); ?>,
					'shipping': <?php echo number_format( $order->get_total_shipping(), 2, '.', '' ); ?>,
					<?php if ( $order->get_coupon_codes() ) : ?>
						'coupon': '<?php echo implode( ',', $order->get_coupon_codes() ); ?>'
					<?php endif; ?>
				  },
				  'products': [
					  <?php
						foreach ( $order->get_items() as $key => $item ) :
							$product      = $item->get_product();
							$variant_name = ( $item['variation_id'] ) ? wc_get_product( $item['variation_id'] ) : '';
							?>
						  {
							'name': '<?php echo $item['name']; ?>',
							'id': '<?php echo $item['product_id']; ?>',
							'price': '<?php echo number_format( $order->get_line_subtotal( $item ), 2, '.', '' ); ?>',
							'brand': '',
							'category': '<?php echo strip_tags( wc_get_product_category_list( $item['product_id'], ', ', '', '' ) ); ?>',
							'variant': '<?php echo ( $variant_name ) ? implode( '-', $variant_name->get_variation_attributes() ) : ''; ?>',
							'quantity': <?php echo $item['qty']; ?>
						  },
					  <?php endforeach; ?>
					]
				}
			  }
		  });
			<?php
		}
	);

	wc_enqueue_js( $dataLayerCode );
}

/**
 * Tracks when product was added to cart.
 */
function addToCart() {
	global $product, $undescoreNamespace;

	if ( get_option( $undescoreNamespace . '_disabled' ) === '1' ) {
		return;
	}

	if ( ! is_single() ) {
		return;
	}

	wc_enqueue_js(
		"
            jQuery('.cart').submit(function(ev) {
                var quantity = jQuery('[name=\"quantity\"]', ev.currentTarget).val();
                dataLayer.push({
                  'event': 'addToCart',
                  'ecommerce': {
                    'currencyCode': '" . get_woocommerce_currency() . "',
                    'add': {                                // 'add' actionFieldObject measures.
                      'products': [{                        //  adding a product to a shopping cart.
                        'name': '" . esc_js( $product->get_title() ) . "',
                        'id': '" . esc_js( $product->get_id() ) . "',
                        'price': '" . esc_js( $product->get_price() ) . "',
                        'quantity': quantity
                       }]
                    }
                  }
                });
            });
        "
	);
}


add_action( 'woocommerce_thankyou', $upperCamelCaseNamespace . '\purchase' );
add_action( 'woocommerce_after_add_to_cart_button', $upperCamelCaseNamespace . '\addToCart' );



function gtm_snippet_head() {
	global $undescoreNamespace;
	if ( get_option( $undescoreNamespace . '_gtm_head' ) === false || get_option( $undescoreNamespace . '_disabled' ) === '1' ) {
		return;
	}
	echo get_option( $undescoreNamespace . '_gtm_head' ) . "\n";
}


function gtm_snippet_body() {
	global $undescoreNamespace;
	if ( get_option( $undescoreNamespace . '_gtm_body' ) === false || get_option( $undescoreNamespace . '_disabled' ) === '1' ) {
		return;
	}
	echo get_option( $undescoreNamespace . '_gtm_body' ) . "\n";
}

add_action( 'wp_head', $upperCamelCaseNamespace . '\gtm_snippet_head', 0 );
add_action( 'wp_body_open', $upperCamelCaseNamespace . '\gtm_snippet_body', 0 );

function activation_hook() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		set_transient( 'EnhancedEcommerceDatalayer\activation-transient', true, 5 );
	}
}
register_activation_hook( __FILE__, $upperCamelCaseNamespace . '\activation_hook' );

function activation_notice_success() {
	if ( get_transient( 'EnhancedEcommerceDatalayer\activation-transient' ) ) {
		// Build and escape the URL.
		$url = esc_url(
			add_query_arg(
				'page',
				$dashscoreNamespace,
				get_admin_url() . 'options-general.php'
			)
		);
		// Create the link.
		?>
	  <div class="notice notice-success is-dismissible">
		  <p><?php _e( 'Enhanced Ecommerce GTM for WooCommerce activated succesfully 🎉  If you already have GTM implemented in your shop plugin will start to send Enhanced Ecommerce data right away, if not navigate to <a href="' . $url . '">settings</a>.', $dashscoreNamespace ); ?></p>
	  </div>
		<?php
		/* Delete transient, only display this notice once. */
		delete_transient( 'EnhancedEcommerceDatalayer\activation-transient' );
	}
}
add_action( 'admin_notices', $upperCamelCaseNamespace . '\activation_notice_success' );

/**
 * Check if WooCommerce is active
 */

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function woocommerce_inative_notice_error() {
		$class   = 'notice notice-error';
		$message = __( 'Enhanced Ecommerce GTM for WooCommerce: it seems WooCommerce is not installed or activated in this WordPress installation. Enhanced Ecommerce won\'t work without WooCommerce.', 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
	add_action( 'admin_notices', $upperCamelCaseNamespace . '\woocommerce_inative_notice_error' );
}


/**
 * custom option and settings
 */
function settings_init() {
	global $undescoreNamespace, $upperCamelCaseNamespace, $dashscoreNamespace;
	// Register a new setting for "wporg" page.
	register_setting( $undescoreNamespace, $undescoreNamespace . '_disabled' );
	register_setting( $undescoreNamespace, $undescoreNamespace . '_gtm_head' );
	register_setting( $undescoreNamespace, $undescoreNamespace . '_gtm_body' );

	add_settings_section(
		$undescoreNamespace . '_section_basic',
		__( 'Basic settings', $dashscoreNamespace ),
		$upperCamelCaseNamespace . '\section_developers_callback',
		$undescoreNamespace
	);

	add_settings_section(
		$undescoreNamespace . '_section_gtm_snippet',
		__( 'Google Tag Manager snippet', $dashscoreNamespace ),
		$upperCamelCaseNamespace . '\section_gtm_snippet_callback',
		$undescoreNamespace
	);

	// Register a new field in the "wporg_section_developers" section, inside the "wporg" page.
	add_settings_field(
		$undescoreNamespace . '_disabled', // As of WP 4.6 this value is used only internally.
		// Use $args' label_for to populate the id inside the callback.
		__( 'Disable?', $dashscoreNamespace ),
		$upperCamelCaseNamespace . '\field_checkbox_cb',
		$undescoreNamespace,
		$undescoreNamespace . '_section_basic',
		array(
			'label_for'   =>  $undescoreNamespace . '_disabled',
			'description' => 'When disabled plugin won\'t load anything in the page.',
		)
	);

	add_settings_field(
		$undescoreNamespace . '_gtm_head', // As of WP 4.6 this value is used only internally.
		// Use $args' label_for to populate the id inside the callback.
		__( 'GTM Snippet head', $dashscoreNamespace ),
		$upperCamelCaseNamespace . '\field_textarea_cb',
		$undescoreNamespace,
		$undescoreNamespace . '_section_gtm_snippet',
		array(
			'label_for'   => $undescoreNamespace . '_gtm_head',
			'rows'        => 9,
			'description' => 'Paste the first snippet provided by GTM. It will be loaded in the <head> of the page.',
		)
	);

	add_settings_field(
		$undescoreNamespace . '_gtm_body', // As of WP 4.6 this value is used only internally.
		// Use $args' label_for to populate the id inside the callback.
		__( 'GTM Snippet body', $dashscoreNamespace ),
		$upperCamelCaseNamespace . '\field_textarea_cb',
		$undescoreNamespace,
		$undescoreNamespace . '_section_gtm_snippet',
		array(
			'label_for'   => $undescoreNamespace . '_gtm_body',
			'rows'        => 6,
			'description' => 'Paste the second snippet provided by GTM. It will be load after opening <body> tag.',
		)
	);
}

/**
 * Register our wporg_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', $upperCamelCaseNamespace . '\settings_init' );


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function section_developers_callback( $args ) {
	global $dashscoreNamespace;
	?>
  <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'This plugin push basic Enhanced Ecommerce events from WooCommerce shop to Google Tag Manager instance. After enabling add tags and triggers to your GTM container in order to use and analyze captured data. It just work and does not require any additional configuration.', $dashscoreNamespace ); ?></p>
	<?php
}

/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function section_gtm_snippet_callback( $args ) {
	global $dashscoreNamespace;
	?>
  <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Enhanced Ecommerce GTM for WooCommerce can work with any GTM implementation in the page. If you already implemented GTM using other plugin or directly in the theme code leave the settings below empty. If you want to implement GTM using this plugin paste in two snippets provided by GTM. To find those snippets navigate to `Admin` tab in GTM console and click `Install Google Tag Manager`.', $dashscoreNamespace ); ?></p>
	<?php
}

/**
 * Pill field callbakc function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function field_checkbox_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$value = get_option( $args['label_for'] );
	?>
  <input
	type="checkbox"
	id="<?php echo esc_attr( $args['label_for'] ); ?>"
	name="<?php echo esc_attr( $args['label_for'] ); ?>"
	value="1"
	<?php checked( $value, 1 ); ?> />
  <p class="description">
	<?php echo esc_html( $args['description'] ); ?>
  </p>
	<?php
}


function field_textarea_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$value = get_option( $args['label_for'] );
	?>
  <textarea
	id="<?php echo esc_attr( $args['label_for'] ); ?>"
	class="large-text code"
	rows="<?php echo esc_html( $args['rows'] ); ?>"
	name="<?php echo esc_attr( $args['label_for'] ); ?>"><?php echo $value; ?></textarea>
  <p class="description">
	<?php echo esc_html( $args['description'] ); ?>
  </p>
	<?php
}

function options_page_html() {
	global $undescoreNamespace, $upperCamelCaseNamespace, $dashscoreNamespace;
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		// add_settings_error( 'wporg_messages', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
	}

	// show error/update messages
	settings_errors( $undescoreNamespace . '_messages' );
	?>
  <div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
	  <?php
		// output security fields for the registered setting "wporg_options"
		settings_fields( $undescoreNamespace );
		// output setting sections and their fields
		// (sections are registered for "wporg", each field is registered to a specific section)
		do_settings_sections( $undescoreNamespace );
		// output save settings button
		submit_button( __( 'Save Settings', $dashscoreNamespace ) );
		?>
	</form>
  </div>
	<?php
}

function options_page() {
	global $dashscoreNamespace, $upperCamelCaseNamespace;
	add_submenu_page(
		'options-general.php',
		'GTM Ecommerce for WooCommerce',
		'GTM Ecommerce',
		'manage_options',
		$dashscoreNamespace,
		$upperCamelCaseNamespace . '\options_page_html'
	);
}
add_action( 'admin_menu', $upperCamelCaseNamespace . '\options_page' );
