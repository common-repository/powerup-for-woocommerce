<?php
/*
Plugin Name: PowerUp! for WooCommerce
Plugin URI: https://3minimonsters.com/powerup/
Description: Power up your WooCommerce with over 50 popular options without writing any code!
Version: 1.0.3
Author: 3 Mini Monsters
Author URI: https://3minimonsters.com
*/

/*  Copyright 3 Mini Monsters (email: hello@3minimonsters.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

// Check if WooCommerce plugin is active
if ( ! WC_PowerUp::is_plugin_active( 'woocommerce.php' ) ) {
	add_action( 'admin_notices', array( 'WC_PowerUp', 'display_wc_inactive_notice' ) );
	return;
}

// Check if WooCommerce version is less than minimum requirement
if ( version_compare( get_option( 'woocommerce_db_version' ), WC_PowerUp::MIN_WOOCOMMERCE_VERSION, '<' ) ) {
	add_action( 'admin_notices', array( 'WC_PowerUp', 'display_outdated_wc_version_notice' ) );
	return;
}


/**
 * WooCommerce PowerUp Main Plugin Class.
 *
 * @since 1.0.0
 */
class WC_PowerUp {


	/** plugin version number */
	const VERSION = '1.0.3';

	/** required WooCommerce version number */
	const MIN_WOOCOMMERCE_VERSION = '3.0.0';

	/** @var \WC_PowerUp single instance of this plugin */
	protected static $instance;

	/** @var \WC_PowerUp_Integrations integrations class instance */
	protected $integrations;

	/** @var \WC_PowerUp_Settings instance */
	public $settings;

	/** var array the active filters */
	public $filters;


	/**
	 * Initializes the plugin
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// load translation
		add_action( 'init', array( $this, 'load_translation' ) );

		// admin
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// load settings page
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );

			// add a 'Open PowerUp!' link to the plugin action links
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );

			// add extra links to the plugin row links
			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 2);

			// run every time
			$this->install();
		}

		$this->includes();

		add_action( 'woocommerce_init', array( $this, 'load_customizations' ) );
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 2.3.0
	 */
	public function __clone() {

		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'woocommerce-powerup' ), 'WooCommerce PowerUp' ), '1.0.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'woocommerce-powerup' ), 'WooCommerce PowerUp' ), '1.0.0' );
	}


	/**
	 * Loads required filed.
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		require_once( 'includes/class-wc-powerup-integrations.php' );
		$this->integrations = new WC_PowerUp_Integrations();
	}


	/**
	 * Add settings page
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_page( $settings ) {

		$settings[] = require_once( 'includes/class-wc-powerup-settings.php' );
		return $settings;
	}


	/**
     * check if we're on PowerUp! admin page
     *
     * @return bool
     */
    function is_plugin_page()
    {
    	$current_screen = get_current_screen();

    	if (!empty($current_screen->id) && $current_screen->id == 'woocommerce_page_wc-settings') {
      		return true;
    	} else {
      		return false;
    	}
  	} // is_plugin_page


	/**
	 * Load customizations after WC is loaded so the version can be checked
	 *
	 * @since 1.0.0
	 */
	public function load_customizations() {

		// load filter names and values
		$this->filters = get_option( 'wc_powerup_active_customizations' );

		// only add filters if some exist
		if ( ! empty( $this->filters ) ) {

			foreach ( $this->filters as $filter_name => $filter_value ) {

				// WC 2.1 changed the add to cart text filter signatures so conditionally add the new filters
				if ( false !== strpos( $filter_name, 'add_to_cart_text' ) ) {

					if ( $filter_name == 'single_add_to_cart_text' ) {

						add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'customize_single_add_to_cart_text' ), 50 );

					} else {

						add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'customize_add_to_cart_text' ), 50, 2 );
					}

				} elseif ( 'woocommerce_placeholder_img_src' === $filter_name ) {

					// only filter placeholder images on the frontend
					if ( ! is_admin() ) {
						add_filter( $filter_name, array( $this, 'customize' ), 50 );
					}

				} elseif ( 'loop_sale_flash_text' === $filter_name || 'single_sale_flash_text' === $filter_name ) {

					add_filter( 'woocommerce_sale_flash', array( $this, 'customize_woocommerce_sale_flash' ), 50, 3 );

				} elseif ( 'single_out_of_stock_text' === $filter_name ) {

					add_filter( 'woocommerce_get_availability_text', array( $this, 'customize_single_out_of_stock_text' ), 50, 2 );

				} elseif ( 'single_backorder_text' === $filter_name ) {

					add_filter( 'woocommerce_get_availability_text', array( $this, 'customize_single_backorder_text' ), 50, 2 );

				} elseif ( 'shop_breadcrumbs_hide' === $filter_name ) {

					add_filter( 'woocommerce_get_breadcrumb', array( $this, 'customize_shop_breadcrumbs_hide' ), 20, 2 );

				} elseif ( 'shop_title_hide' === $filter_name ) {

					add_filter( 'woocommerce_show_page_title', array( $this, 'customize_shop_title_hide' ), 20, 2 );

				} elseif ( 'shop_title' === $filter_name ) {

					add_filter( 'woocommerce_page_title', array( $this, 'customize_shop_title' ), 20, 2 );

				} elseif ( 'shop_description' === $filter_name ) {

					add_action( 'woocommerce_archive_description', array( $this, 'customize_woocommerce_archive_description' ), 11 );

				} elseif ( 'skip_cart_to_checkout' === $filter_name ) {

					add_filter('woocommerce_add_to_cart_redirect', array( $this, 'customize_add_to_cart_redirect') );

				} elseif ( 'coupon_code_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_coupon_code_text' ), 20, 3 );

				} elseif ( 'apply_coupon_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_apply_coupon_text' ), 20, 3 );

				} elseif ( 'update_cart_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_update_cart_text' ), 20, 3 );

				} elseif ( 'cart_totals_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_cart_totals_text' ), 20, 3 );

				} elseif ( 'cart_subtotal_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_cart_subtotal_text' ), 20, 3 );

				} elseif ( 'cart_total_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_cart_total_text' ), 20, 3 );

				} elseif ( 'proceed_to_checkout_text' === $filter_name ) {

					add_filter( 'gettext', array( $this, 'customize_proceed_to_checkout_text' ), 20, 3 );

				} elseif ( 'product_buy_now_button_simple' === $filter_name ) {

					add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'customize_buy_now_button_simple' ), 20, 3 );
					add_action( 'woocommerce_after_shop_loop_item', array( $this, 'customize_buy_now_button_simple' ), 1000 );

				} elseif ( 'product_total_units_sold' === $filter_name ) {

					add_action( 'woocommerce_single_product_summary',  array( $this, 'customize_product_total_units_sold'), 8 );

				} elseif ( 'limit_one_product_per_order' === $filter_name ) {

					add_filter( 'woocommerce_add_cart_item_data', array( $this, 'customize_limit_one_product_per_order' ) );

				} elseif ( 'hide_others_when_free_shipping' === $filter_name ) {

					add_filter( 'woocommerce_package_rates', array( $this, 'customize_hide_others_when_free_shipping' ), 200 );

				} elseif ( 'remove_paypal_icons' === $filter_name ) {

					add_action( 'wp_enqueue_scripts', array( $this, 'customize_remove_paypal_icons') );

				} elseif ( 'show_featured_products_after_cart_content' === $filter_name ) {

					add_action( 'woocommerce_after_cart_table', array( $this, 'customize_featured_products_after_cart_content'), 10, 0 );

				} elseif ( 'misc_custom_currency_code' === $filter_name ) {

					add_filter( 'woocommerce_currencies', array( $this, 'customize_woocommerce_currencies' ) );
					add_filter( 'woocommerce_currency_symbol', array( $this, 'customize_woocommerce_currency_symbol' ), 10, 2);

				} else {

					add_filter( $filter_name, array( $this, 'customize' ), 50 );
				}
			}
		}
	}


	/**
	 * Handle localization, WPML compatible
	 *
	 * @since 1.1.0
	 */
	public function load_translation() {

		// localization in the init action for WPML support
		load_plugin_textdomain( 'woocommerce-powerup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}


	/**
	 * Helper function to determine whether a plugin is active.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public static function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( false !== strpos( $plugin, '/' ) ) {

				// normal plugin name (plugin-dir/plugin-filename.php)
				list( , $filename ) = explode( '/', $plugin );

			} else {

				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( $plugin_name, $plugin_filenames );
	}


	/**
	 * Display a notice when WooCommerce version is not active
	 *
	 * @since 1.0.0
	 */
	public static function display_wc_inactive_notice() {

		$message = sprintf(
			/* translators: %1$s - <strong>, %2$s - </strong>, %3$s - <a>, %4$s - version number, %5$s - </a> */
			__( '%1$sWooCommerce PowerUp is inactive%2$s as it requires WooCommerce. Please %3$sactivate WooCommerce version %4$s or newer%5$s', 'woocommerce-powerup' ),
			'<strong>',
			'</strong>',
			'<a href="' . admin_url( 'plugins.php' ) . '">',
			self::MIN_WOOCOMMERCE_VERSION,
			'&nbsp;&raquo;</a>'
		);

		printf( '<div class="error"><p>%s</p></div>', $message );
	}


	/**
	 * Renders a notice when WooCommerce version is outdated
	 *
	 * @since 1.0.0
	 */
	public static function display_outdated_wc_version_notice() {

		$message = sprintf(
			/* translators: Placeholders: %1$s - <strong>, %2$s - </strong>, %3$s - version number, %4$s and %6$s - <a> tags, %5$s - </a> */
			__( '%1$sWooCommerce PowerUp is inactive.%2$s This plugin requires WooCommerce %3$s or newer. Please %4$supdate WooCommerce%5$s or %6$srun the WooCommerce database upgrade%5$s.', 'woocommerce-powerup' ),
			'<strong>',
			'</strong>',
			self::MIN_WOOCOMMERCE_VERSION,
			'<a href="' . admin_url( 'plugins.php' ) . '">',
			'</a>',
			'<a href="' . admin_url( 'plugins.php?do_update_woocommerce=true' ) . '">'
		);

		printf( '<div class="error"><p>%s</p></div>', $message );
	}


	/** Frontend methods ******************************************************/


	/**
	 * Add hook to selected filters
	 *
	 * @since 1.0.0
	 * @return void|string $filter_value value to use for selected hook
	 */
	public function customize() {

		$current_filter = current_filter();

		if ( isset( $this->filters[ $current_filter ] ) ) {

			if ( 'powerup_true' === $this->filters[ $current_filter] || 'powerup_true' === $this->filters[ $current_filter] ) {

				// helper to return a pure boolean value
				return 'powerup_true' === $this->filters[ $current_filter ];

			} else {

				return $this->filters[ $current_filter ];
			}
		}

		// no need to return a value passed in, because if a filter is set, it's designed to only return that value
	}


	/**
	 * Apply the customization to hide the breadcrumbs on the shop page
	 *
	 * @since 1.0.0
	 */
	public function customize_shop_breadcrumbs_hide() {

		if( is_shop() ) {
			$breadcrumbs = array();
		    foreach( $breadcrumbs as $key => $breadcrumb ){
		        if( $breadcrumb[0] === __('Shop', 'woocommerce') ) {
		        	unset($breadcrumbs[$key]);
		        }
		    }

		    return $breadcrumbs;
		}
	}


	/**
	 * Apply the customization to hide the heading "Shop" on the shop page
	 *
	 * @since 1.0.0
	 */
	public function customize_shop_title_hide() {

		if( is_shop() ) {
			add_filter( 'woocommerce_show_page_title', '__return_false' );
		}
	}


	/**
	 * Apply the customization to change "Shop" title
	 *
	 * @since 1.0.0
	 */
	function customize_shop_title( $page_title ) {

	    if ( is_shop() && isset( $this->filters['shop_title'] ) ) {

			return $this->filters['shop_title'];
		}
	}


	/**
	 * Add the description or introduction text after the "Shop" title
	 *
	 * @since 1.0.0
	 */
	function customize_woocommerce_archive_description() {

		if ( is_shop() && isset( $this->filters['shop_description'] ) ) {

			echo '<div>&nbsp;<div>' . $this->filters['shop_description'] .'<div>&nbsp;<div>';
		}
	}


	/**
	 * Add the skip cart page amnd redirect to checkout page function
	 *
	 * @since 1.0.0
	 */
	function customize_add_to_cart_redirect() {

	 	$custom_redirect_checkout = wc_get_checkout_url();

	 	return $custom_redirect_checkout;
	}


	/**
	 * Customize the "Coupon code" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_coupon_code_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['coupon_code_text'] ) ) {

	    	if( is_cart() && $translated == 'Coupon code' ){
		        $translated = __( $this->filters['coupon_code_text'], 'woocommerce-powerup' );
		    }
		}

	    return $translated;
	}


	/**
	 * Customize the "Apply coupon" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_apply_coupon_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['apply_coupon_text'] ) ) {

	    	if( is_cart() && $translated == 'Apply coupon' ){
		        $translated = __( $this->filters['apply_coupon_text'], 'woocommerce-powerup' );
		    }
		}

	    return $translated;
	}


	/**
	 * Customize the "Apply coupon" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_update_cart_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['update_cart_text'] ) ) {

	    	if( is_cart() && $translated == 'Update cart' ){
		        $translated = __( $this->filters['update_cart_text'], 'woocommerce-powerup' );
		    }
		}

	    return $translated;
	}


	/**
	 * Customize the "Cart totals" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_cart_totals_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['cart_totals_text'] ) ) {

	    	if( is_cart() && $translated == 'Cart totals' ){
		        $translated = __( $this->filters['cart_totals_text'], 'woocommerce-powerup' );
		    }
		}
		
	    return $translated;
	}


	/**
	 * Customize the "Subtotal" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_cart_subtotal_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['cart_subtotal_text'] ) ) {

	    	if( is_cart() && $translated == 'Subtotal' ){
		        $translated = __( $this->filters['cart_subtotal_text'], 'woocommerce-powerup' );
		    }
		}
		
	    return $translated;
	}


	/**
	 * Customize the "Total" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_cart_total_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['cart_total_text'] ) ) {

	    	if( is_cart() && $translated == 'Total' ){
		        $translated = __( $this->filters['cart_total_text'], 'woocommerce-powerup' );
		    }
		}
		
	    return $translated;
	}


	/**
	 * Customize the "Cart totals" text on the cart page
	 *
	 * @since 1.0.0
	 */
	function customize_proceed_to_checkout_text( $translated, $text, $domain ) {
	    
	    if ( isset( $this->filters['proceed_to_checkout_text'] ) ) {

	    	if( is_cart() && $translated == 'Proceed to checkout' ){
		        $translated = __( $this->filters['proceed_to_checkout_text'], 'woocommerce-powerup' );
		    }
		}
		
	    return $translated;
	}


	/**
	 * Apply the single add to cart button text customization
	 *
	 * @since 1.0.0
	 */
	public function customize_single_add_to_cart_text() {

		return $this->filters['single_add_to_cart_text'];
	}


	/**
	 * Apply the shop loop add to cart button text customization
	 *
	 * @since 1.0.0
	 * @param string $text add to cart text
	 * @param \WC_Product $product product object
	 * @return string modified add to cart text
	 */
	public function customize_add_to_cart_text( $text, $product ) {

		// out of stock add to cart text
		if ( isset( $this->filters['out_of_stock_add_to_cart_text'] ) && ! $product->is_in_stock() ) {

			return $this->filters['out_of_stock_add_to_cart_text'];
		}

		if ( isset( $this->filters['add_to_cart_text'] ) && $product->is_type( 'simple' ) ) {

			// simple add to cart text
			return $this->filters['add_to_cart_text'];

		} elseif ( isset( $this->filters['variable_add_to_cart_text'] ) && $product->is_type( 'variable') )  {

			// variable add to cart text
			return $this->filters['variable_add_to_cart_text'];

		} elseif ( isset( $this->filters['grouped_add_to_cart_text'] ) && $product->is_type( 'grouped' ) ) {

			// grouped add to cart text
			return $this->filters['grouped_add_to_cart_text'];

		} elseif ( isset( $this->filters['external_add_to_cart_text'] ) && $product->is_type( 'external' ) ) {

			// external add to cart text
			return $this->filters['external_add_to_cart_text'];

		}

		return $text;
	}


	/**
	 * Apply the product page out of stock text customization
	 *
	 * @since 1.0.0
	 *
	 * @param string $text out of stock text
	 * @param \WC_Product $product product object
	 * @return string modified out of stock text
	 */
	public function customize_single_out_of_stock_text( $text, $product ) {

		// out of stock text
		if ( isset( $this->filters['single_out_of_stock_text'] ) && ! $product->is_in_stock() ) {
			return $this->filters['single_out_of_stock_text'];
		}

		return $text;
	}


	/**
	 * Apply the product page backorder text customization
	 *
	 * @since 1.0.0
	 *
	 * @param string $text backorder text
	 * @param \WC_Product $product product object
	 * @return string modified backorder text
	 */
	public function customize_single_backorder_text( $text, $product ) {

		// backorder text
		if ( isset( $this->filters['single_backorder_text'] ) && $product->managing_stock() && $product->is_on_backorder( 1 ) ) {
			return $this->filters['single_backorder_text'];
		}

		return $text;
	}


	/**
	 * Apply the shop loop sale flash text customization.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html add to cart flash HTML
	 * @param \WP_Post $_ post object, unused
	 * @param \WC_Product $product the prdouct object
	 * @return string updated HTML
	 */
	public function customize_woocommerce_sale_flash( $html, $_, $product ) {

		$text = '';

		if ( is_product() && isset( $this->filters['single_sale_flash_text'] ) ) {

			$text = $this->filters['single_sale_flash_text'];

		} elseif ( ! is_product() && isset( $this->filters['loop_sale_flash_text'] ) ) {

			$text = $this->filters['loop_sale_flash_text'];
		}

		// only get sales percentages when we should be replacing text
		// check "false" specifically since the position could be 0
		if ( false !== strpos( $text, '{percent}' ) ) {

			$percent = $this->get_sale_percentage( $product );
			$text    = str_replace( '{percent}', "{$percent}%", $text );
		}
		
		if ( false !== strpos( $text, '{amount}' ) ) {

			$currency_symbol = get_woocommerce_currency_symbol();
			$amount  = $this->get_sale_amount( $product );
			$text    = str_replace( '{amount}', "$currency_symbol{$amount}", $text );
		}

		return ! empty( $text ) ? "<span class='onsale'>{$text}</span>" : $html;
	}


	function customize_product_total_units_sold() {

	  global $product;

	  if(is_product()) {
	  	$units_sold = get_post_meta( $product->get_id(), 'total_sales', true );
	 	
	  	echo '<p>' . sprintf( __( '%s Sold', 'woocommerce' ), $units_sold ) . '</p>';
	  }
	}


	function customize_limit_one_product_per_order( $cart_item_data ) {
		
		global $woocommerce;
		
		$woocommerce->cart->empty_cart();
 
		return $cart_item_data;
	}


	function customize_remove_paypal_icons() {

		wp_enqueue_style('plugin-styles', plugins_url('assets/css/remove-paypal-icons.css', __FILE__), array(), '1.0.0', 'all');

	}


	function customize_hide_others_when_free_shipping( $rates ) {

		$free = array();
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'free_shipping' === $rate->method_id ) {
				$free[ $rate_id ] = $rate;
				break;
			}
		}

		return ! empty( $free ) ? $free : $rates;
	}


	function customize_featured_products_after_cart_content() {

		echo '<h4>' . __( 'You May Also Like') . '</h4>';
		echo do_shortcode('[products limit="3" columns="3" visibility="featured"]');

	}


	function customize_buy_now_button_simple() {

		global $product;

	    $product_id = $product->get_id();
	    $redirect_url = wc_get_checkout_url();

	    //only show for Simple product page
	    if ( $product->is_type( 'simple' ) ) {
		    echo '&nbsp;&nbsp;<a class="button" href="' . esc_url( $redirect_url ) .'?add-to-cart=' . $product_id . '">' . __( "Buy Now" )  . '</a>';
		}

	}


	function customize_woocommerce_currencies( $currencies ) {

		$custom_currency_code = isset( $this->filters['misc_custom_currency_code'] ) ? $this->filters['misc_custom_currency_code'] : '';
		$custom_currency_label = isset( $this->filters['misc_custom_currency_label'] ) ? $this->filters['misc_custom_currency_label'] : '';

		if( isset( $currencies[$custom_currency_code] ) && $custom_currency_label != '' )
			$currencies[$custom_currency_code] = $custom_currency_label;

		return $currencies;

	}


	function customize_woocommerce_currency_symbol( $currency_symbol, $currency ) {

		$custom_currency_code = isset( $this->filters['misc_custom_currency_code'] ) ? $this->filters['misc_custom_currency_code'] : '';
		$custom_currency_symbol = isset( $this->filters['misc_custom_currency_symbol'] ) ? $this->filters['misc_custom_currency_symbol'] : '';

		switch( $currency ) {
          case $custom_currency_code: $currency_symbol = $custom_currency_symbol; break;
     	}

     	return $currency_symbol;

	}


	/** Admin methods ******************************************************/


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 1.0.0
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function add_plugin_action_links( $actions ) {

		$custom_actions = array(
			'configure' => sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=powerup&section=shop_page' ), __( 'Start PowerUp!', 'woocommerce-powerup' ) ),
			'support'   => sprintf( '<a href="%s">%s</a>', 'http://wordpress.org/support/plugin/woocommerce-powerup', __( 'Support', 'woocommerce-powerup' ) ),
			'reviews'   => sprintf( '<a href="%s">%s</a>', 'https://wordpress.org/support/plugin/woocommerce-powerup/reviews/', __( 'Reviews', 'woocommerce-powerup' ) ),
		);

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );
	}


	/**
     * Add links to plugin's description in plugins table
     *
     * @param array  $links  Initial list of links.
     * @param string $file   Basename of current plugin.
     *
     * @return array
     */
    function plugin_meta_links($links, $file)
	{
		if ($file !== plugin_basename(__FILE__)) {
		  return $links;
		}

		$support_link = '<a target="_blank" href="https://wordpress.org/support/plugin/woocommerce-powerup" title="' . __('Get help', 'w') . '">' . __('Support', 'woocommerce-powerup') . '</a>';
		$rate_link = '<a target="_blank" href="https://wordpress.org/support/plugin/woocommerce-powerup/reviews/#new-post" title="' . __('Rate this plugin', 'woocommerce-powerup') . '">' . __('Rate this plugin ★★★★★', 'woocommerce-powerup') . '</a>';

		$links[] = $support_link;
		$links[] = $rate_link;

		return $links;
	} // plugin_meta_links


	/** Helper methods ******************************************************/


	/**
	 * Helper to get the percent discount for a product on sale.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product product instance
	 * @return string percentage discount
	 */
	private function get_sale_amount( $product ) {

		$child_sale_percents = array();
		$percentage          = '0';

		if ( $product->is_type( 'grouped' ) || $product->is_type( 'variable' ) ) {

			foreach ( $product->get_children() as $child_id ) {

				$child = wc_get_product( $child_id );

				if ( $child->is_on_sale() ) {

					$regular_price         = $child->get_regular_price();
					$sale_price            = $child->get_sale_price();
					$child_sale_amounts[]   = $this->calculate_sale_amount( $regular_price, $sale_price );
				}
			}

			// filter out duplicate values
			$child_sale_amounts = array_unique( $child_sale_amounts );

			// only add "up to" if there's > 1 saved amount possible
			if ( ! empty ( $child_sale_amounts ) ) {

				/* translators: Placeholder: %s - sale percentage */
				$amount = count( $child_sale_amounts ) > 1 ? sprintf( esc_html__( 'up to %s', 'woocommerce-powerup' ), max( $child_sale_amounts ) ) : current( $child_sale_amounts );
			}

		} else {

			$amount = $this->calculate_sale_amount( $product->get_regular_price(), $product->get_sale_price() );
		}

		return $amount;
	}


	/**
	 * Helper to get the percent discount for a product on sale.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product product instance
	 * @return string percentage discount
	 */
	private function get_sale_percentage( $product ) {

		$child_sale_percents = array();
		$percentage          = '0';

		if ( $product->is_type( 'grouped' ) || $product->is_type( 'variable' ) ) {

			foreach ( $product->get_children() as $child_id ) {

				$child = wc_get_product( $child_id );

				if ( $child->is_on_sale() ) {

					$regular_price         = $child->get_regular_price();
					$sale_price            = $child->get_sale_price();
					$child_sale_percents[] = $this->calculate_sale_percentage( $regular_price, $sale_price );
				}
			}

			// filter out duplicate values
			$child_sale_percents = array_unique( $child_sale_percents );

			// only add "up to" if there's > 1 percentage possible
			if ( ! empty ( $child_sale_percents ) ) {

				/* translators: Placeholder: %s - sale percentage */
				$percentage = count( $child_sale_percents ) > 1 ? sprintf( esc_html__( 'up to %s', 'woocommerce-powerup' ), max( $child_sale_percents ) ) : current( $child_sale_percents );
			}

		} else {

			$percentage = $this->calculate_sale_percentage( $product->get_regular_price(), $product->get_sale_price() );
		}

		return $percentage;
	}


	/**
	 * Calculates a sales percentage difference given regular and sale prices for a product.
	 *
	 * @since 1.0.0
	 *
	 * @param string $regular_price product regular price
	 * @param string $sale_price product sale price
	 * @return float percentage difference
	 */
	private function calculate_sale_percentage( $regular_price, $sale_price ) {

		$percent = 0;
		$regular = (float) $regular_price;
		$sale    = (float) $sale_price;

		// in case of free products so we don't divide by 0
		if ( $regular ) {
			$percent = round( ( ( $regular - $sale ) / $regular ) * 100 );
		}

		return $percent;
	}


	/**
	 * Calculates a sales amount difference given regular and sale prices for a product.
	 *
	 * @since 1.0.0
	 *
	 * @param string $regular_price product regular price
	 * @param string $sale_price product sale price
	 * @return float amount difference
	 */
	private function calculate_sale_amount( $regular_price, $sale_price ) {

		$amount = 0;
		$regular = (float) $regular_price;
		$sale    = (float) $sale_price;

		// in case of free products so we don't divide by 0
		if ( $regular ) {
			$amount = $regular - $sale;
		}

		return $amount;
	}


	/**
	 * Main PowerUp Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.0.0
	 * @see wc_powerup()
	 * @return \WC_PowerUp
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Gets the integrations class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_PowerUp_Integrations
	 */
	public function get_integrations_instance() {
		return $this->integrations;
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Run every time.  Used since the activation hook is not executed when updating a plugin
	 *
	 * @since 1.0.0
	 */
	private function install() {

		// get current version to check for upgrade
		$installed_version = get_option( 'wc_powerup_version' );

		// install
		if ( ! $installed_version ) {

			// install default settings
		}

		// upgrade if installed version lower than plugin version
		if ( -1 === version_compare( $installed_version, self::VERSION ) ) {
			$this->upgrade( $installed_version );
		}
	}


	/**
	 * Perform any version-related changes.
	 *
	 * @since 1.0.0
	 * @param int $installed_version the currently installed version of the plugin
	 */
	private function upgrade( $installed_version ) {

		// update the installed version option
		update_option( 'wc_powerup_version', self::VERSION );
	}


}


/**
 * Returns the One True Instance of PowerUp
 *
 * @since 1.0.0
 * @return \WC_PowerUp
 */
function wc_powerup() {
	return WC_PowerUp::instance();
}


// fire it up!
wc_powerup();