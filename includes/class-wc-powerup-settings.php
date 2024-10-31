<?php
/**
 * WooCommerce PowerUp!
 *
 */

/*  Copyright (c) 3 Mini Monsters (email: hello@3minimonsters.com)

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

/**
 * Settings
 *
 * Adds UX for adding/modifying customizations
 *
 * @since 2.0.0
 */
class WC_PowerUp_Settings extends WC_Settings_Page {


	/**
	 * Add various admin hooks/filters
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		$this->id    = 'powerup';
		$this->label = __( 'PowerUp!', 'woocommerce-powerup' );

		parent::__construct();

		$this->customizations = get_option( 'wc_powerup_active_customizations', array() );
	}


	/**
	 * Get sections
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_sections() {

		return array(
			'shop_page'    	=> __( 'Shop', 'woocommerce-powerup' ),
			'product_page' 	=> __( 'Product', 'woocommerce-powerup' ),
			'cart'     		=> __( 'Cart', 'woocommerce-powerup' ),
			'checkout'     	=> __( 'Checkout', 'woocommerce-powerup' ),
			'tax'      		=> __( 'Tax', 'woocommerce-powerup' ),
			'product_image' => __( 'Product Image', 'woocommerce-powerup' ),
			'misc' 			=> __( 'Misc', 'woocommerce-powerup' ),
		);
	}


	/**
	 * Render the settings for the current section
	 *
	 * @since 2.0.0
	 */
	public function output() {

		$settings = $this->get_settings();

		// inject the actual setting value before outputting the fields
		// ::output_fields() uses get_option() but customizations are stored
		// in a single option so this dynamically returns the correct value
		foreach ( $this->customizations as $filter => $value ) {

			add_filter( "pre_option_{$filter}", array( $this, 'get_customization' ) );
		}

		WC_Admin_Settings::output_fields( $settings );
	}


	/**
	 * Return the customization value for the given filter
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_customization() {

		$filter = str_replace( 'pre_option_', '', current_filter() );

		return isset( $this->customizations[ $filter ] ) ? $this->customizations[ $filter ] : '';
	}


	/**
	 * Save the customizations
	 *
	 * @since 2.0.0
	 */
	public function save() {

		foreach ( $this->get_settings() as $field ) {

			// skip titles, etc
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			if ( ! empty( $_POST[ $field['id'] ] ) ) {

				$this->customizations[ $field['id'] ] = wp_kses_post( stripslashes( $_POST[ $field['id'] ] ) );

			} elseif ( isset( $this->customizations[ $field['id'] ] ) ) {

				unset( $this->customizations[ $field['id'] ] );
			}
		}

		update_option( 'wc_powerup_active_customizations', $this->customizations );
	}


	/**
	 * Return admin fields in proper format for outputting / saving
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = array(

			'shop_page' =>

				array(

					array(
						'title' 	=> __( 'Title & Description', 'woocommerce-powerup' ),
						'type'  	=> 'title'
					),

					array(
						'id'       => 'shop_breadcrumbs_hide',
						'title'    => __( 'Hide Page Breadcrumbs' ),
						'desc_tip' => __( 'Show/Hide default breadcrumbs on top of the Shop page', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array(
						'id'       => 'shop_title_hide',
						'title'    => __( 'Hide Shop Heading Title' ),
						'desc_tip' => __( 'Show/Hide default heading title on top of the Shop page', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array(
						'id'       	=> 'shop_title',
						'title'    	=> __( 'Heading Title', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the default shop page heading title - "Shop"', 'woocommerce-powerup' ),
						'type'     	=> 'text',
						'placeholder' 	=> 'Shop'
					),

					array(
						'id'       	=> 'shop_description',
						'title'    	=> __( 'Description', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Add the shop page description / introduction under Heading Title above', 'woocommerce-powerup' ),
						'type'    	=> 'text'
					),

					array( 'type' 	=> 'sectionend' ),

					array(
						'title' 	=> __( 'Shop Layout', 'woocommerce-powerup' ),
						'type' 	 	=> 'title'
					),

					array(
						'id'       	=> 'loop_shop_per_page',
						'title'    	=> __( 'Products displayed per page', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the number of products displayed per page', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array(
						'id'       	=> 'loop_shop_columns',
						'title'    	=> __( 'Product columns per page', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the number of columns displayed per page', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array(
						'id'       	=> 'woocommerce_product_thumbnails_columns',
						'title'    	=> __( 'Product thumbnail columns', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the number of product thumbnail columns displayed', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array( 'type' 	=> 'sectionend' ),

					array(
						'title' 	=> __( 'Add to Cart Button Text', 'woocommerce-powerup' ),
						'type'  	=> 'title'
					),

					array(
						'id'       	=> 'add_to_cart_text',
						'title'    	=> __( 'Simple Product', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the add to cart button text for simple products on all loop pages', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array(
						'id'       	=> 'variable_add_to_cart_text',
						'title'    	=> __( 'Variable Product', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the add to cart button text for variable products on all loop pages', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array(
						'id'       	=> 'grouped_add_to_cart_text',
						'title'    	=> __( 'Grouped Product', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the add to cart button text for grouped products on all loop pages', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array(
						'id'       	=> 'out_of_stock_add_to_cart_text',
						'title'    	=> __( 'Out of Stock Product', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change the add to cart button text for out of stock products on all loop pages', 'woocommerce-powerup' ),
						'type'     	=> 'text'
					),

					array( 'type' 	=> 'sectionend' ),

					array(
						'title' 	=> __( 'Sale Flash', 'woocommerce-powerup' ),
						'type'  	=> 'title'
					),

					array(
						'id'      	=> 'loop_sale_flash_text',
						'title'    	=> __( 'Sale badge text', 'woocommerce-powerup' ),
						'desc_tip' 	=> __( 'Change text for the sale flash on all loop pages. Default: "Sale!"', 'woocommerce-powerup' ),
						'type'     	=> 'text',
						/* translators: Placeholders: %1$s - <code>, %2$s - </code> */
						'desc'     	=> sprintf( __( 'Use %1$s{percent}%2$s to insert percent off / Use %1$s{amount}%2$s to insert total amount saved, e.g., "{percent} off!", "{amount} {percent} off!"', 'woocommerce-powerup' ), '<code>', '</code>' ) . '<br />' . __( 'Shows "up to n%" for grouped or variable products.', 'woocommerce-powerup' ),
					),

					array( 'type' 	=> 'sectionend' ),

					

				),

			'product_page' =>

				array(

					array(
						'title' => __( 'Settings', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'product_buy_now_button_simple',
						'title'    => __( 'Display "Buy Now" Button' ),
						'desc_tip' => __( 'Display "Buy Now" button at simple product page.', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array(
						'id'       => 'product_total_units_sold',
						'title'    => __( 'Display Total Units Sold' ),
						'desc_tip' => __( 'Display total units sold / total sales at individual product page.', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' 	=> __( 'Tab Titles', 'woocommerce-powerup' ),
						'type'  	=> 'title'
					),

					array(
						'id'       => 'woocommerce_product_description_tab_title',
						'title'    => __( 'Product Description', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Production Description tab title', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array(
						'id'       => 'woocommerce_product_additional_information_tab_title',
						'title'    => __( 'Additional Information', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Additional Information tab title. “Additional Information” tab will only show if the product has weight, dimensions or attributes (with “Visible on the product page” checked).', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' => __( 'Tab Content Headings', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'woocommerce_product_description_heading',
						'title'    => __( 'Product Description', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Product Description tab heading', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array(
						'id'       => 'woocommerce_product_additional_information_heading',
						'title'    => __( 'Additional Information', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Additional Information tab heading. “Additional Information” tab will only show if the product has weight, dimensions or attributes (with “Visible on the product page” checked).', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' => __( 'Add to Cart Button Text', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'single_add_to_cart_text',
						'title'    => __( 'All Product Types', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Add to Cart button text on the single product page for all product type', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' => __( 'Out of Stock Text', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'single_out_of_stock_text',
						'title'    => __( 'Out of Stock text', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the out of stock on product pages. Default: "Out of stock"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'single_backorder_text',
						'title'    => __( 'Backorder text', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the backorder on product pages. Default: "Available on backorder"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' => __( 'Sale Flash', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'single_sale_flash_text',
						'title'    => __( 'Sale badge text', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the sale flash on product pages. Default: "Sale!"', 'woocommerce-powerup' ),
						'type'     => 'text',
						/* translators: Placeholders: %1$s - <code>, %2$s - </code> */
						'desc'     => sprintf( __( 'Use %1$s{percent}%2$s to insert percent off, e.g., "{percent} off!"', 'woocommerce-powerup' ), '<code>', '</code>' ) . '<br />' . __( 'Shows "up to n%" for grouped or variable products if multiple percentages are possible.', 'woocommerce-powerup' ),
					),

					array( 'type' => 'sectionend' ),
				),

			'cart' =>

				array(

					array(
						'title' => __( 'Settings', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'skip_cart_to_checkout',
						'title'    => __( 'Enable Skip Cart to Checkout', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Skip cart page and redirect to checkout page after adding to cart.', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array(
						'id'       => 'limit_one_product_per_order',
						'title'    => __( 'Limit One Product Per Order', 'woocommerce-powerup' ),
						'desc_tip' => __( 'The customer only can buy one product, if the customer goes to another product and tries to buy it, the cart will be cleaned and the last item added.', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array(
						'id'       => 'show_featured_products_after_cart_content',
						'title'    => __( 'Show Featured Products', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Show 3 featured products after cart content as the product suggestion.', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' => __( 'Change Text To', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'coupon_code_text',
						'title'    => __( 'Coupon code', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Coupon code" text on cart page. Default: "Coupon code"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'apply_coupon_text',
						'title'    => __( 'Apply coupon', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Apply coupon" text on cart page. Default: "Apply coupon"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'update_cart_text',
						'title'    => __( 'Update cart', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Update cart" text on cart page. Default: "Update cart"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'cart_totals_text',
						'title'    => __( 'Cart totals', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Cart totals" text on cart page. Default: "Cart totals"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'cart_subtotal_text',
						'title'    => __( 'Subtotal', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Subtotal" text on cart page. Default: "Subtotal"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'cart_total_text',
						'title'    => __( 'Total', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Total" text on cart page. Default: "Total"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array(
						'id'       => 'proceed_to_checkout_text',
						'title'    => __( 'Proceed to checkout', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change text for the "Proeed to checkout" text on cart page. Default: "Proceed to checkout"', 'woocommerce-powerup' ),
						'type'     => 'text',
					),

					array( 'type' => 'sectionend' ),

				),

			'checkout' =>

				array(

					array(
						'title' => __( 'Settings', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'hide_others_when_free_shipping',
						'title'    => __( 'Hide Other Shipping Methods If Free Shipping Is Available' ),
						'desc_tip' => __( 'Hide other shipping methods if free shipping is available at the checkout page', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array(
						'id'       => 'remove_paypal_icons',
						'title'    => __( 'Remove PayPal icons', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Remove Paypal icons at the checkout form under payment section.', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
							1		=> __( 'Yes', 'woocommerce-powerup' ),
							0 		=> __( 'No', 'woocommerce-powerup' ),
						),
						'default'	=> 0
					),

					array( 'type' => 'sectionend' ),

					array(
						'title' => __( 'Change Text To', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'woocommerce_checkout_must_be_logged_in_message',
						'title'    => __( 'Must be logged in text', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the message displayed when a customer must be logged in to checkout the order', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array(
						'id'       => 'woocommerce_checkout_login_message',
						'title'    => __( 'Login text', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the message displayed if customers can login at checkout page', 'woocommerce-powerup' ),
						'type'     => 'text',
						'desc'	   => sprintf( '<code>%s </code>', 'Returning customer?' ),
					),

					array(
						'id'       => 'woocommerce_checkout_coupon_message',
						'title'    => __( 'Coupon text', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the message displayed if the coupon code form is enabled on checkout page', 'woocommerce-powerup' ),
						'type'     => 'text',
						'desc'     => sprintf( '<code>%s ' . esc_attr( '<a href="#" class="showcoupon">%s</a>' ) . '</code>', 'Have a coupon?', 'Click here to enter your code' ),
					),

					array(
						'id'       => 'woocommerce_order_button_text',
						'title'    => __( 'Place Order button', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Place Order button text on checkout', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' )

				),

			'tax' =>

				array(

					array(
						'title' => __( 'Change Text To', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'woocommerce_countries_tax_or_vat',
						'title'    => __( 'Tax Label', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Taxes label. Defaults to Tax for USA, VAT for European countries', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array(
						'id'       => 'woocommerce_countries_inc_tax_or_vat',
						'title'    => __( 'Including Tax Label', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Including Taxes label. Defaults to Inc. tax for USA, Inc. VAT for European countries', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array(
						'id'       => 'woocommerce_countries_ex_tax_or_vat',
						'title'    => __( 'Excluding Tax Label', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the Excluding Taxes label. Defaults to Exc. tax for USA, Exc. VAT for European countries', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' )

				),

			'product_image' =>

				array(

					array(
						'title' => __( 'Product Image', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'woocommerce_placeholder_img_src',
						'title'    => __( 'Default Product Thumbnail URL', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the default product image thumbnail by setting this to a valid image URL', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' )

				),

			'misc' =>

				array(

					array(
						'title' => __( 'Misc', 'woocommerce-powerup' ),
						'type'  => 'title'
					),

					array(
						'id'       => 'misc_custom_currency_code',
						'title'    => __( 'Select Currency Name' ),
						'desc_tip' => __( 'Select one of the currency from ISO 4217 Currency Codes', 'woocommerce-powerup' ),
						'type'     => 'select',
						'options'  => array(
										''		=> "---(None)---",
										'AED'	=> "United Arab Emirates Dirham",
										'AFN'	=> "Afghanistan Afghani",
										'ALL'	=> "Albania Lek",
										'AMD'	=> "Armenia Dram",
										'ANG'	=> "Netherlands Antilles Guilder",
										'AOA'	=> "Angola Kwanza",
										'ARS'	=> "Argentina Peso",
										'AUD'	=> "Australia Dollar",
										'AWG'	=> "Aruba Guilder",
										'AZN'	=> "Azerbaijan Manat",
										'BAM'	=> "Bosnia and Herzegovina Convertible Mark",
										'BBD'	=> "Barbados Dollar",
										'BDT'	=> "Bangladesh Taka",
										'BGN'	=> "Bulgaria Lev",
										'BHD'	=> "Bahrain Dinar",
										'BIF'	=> "Burundi Franc",
										'BMD'	=> "Bermuda Dollar",
										'BND'	=> "Brunei Darussalam Dollar",
										'BOB'	=> "Bolivia Bolíviano",
										'BRL'	=> "Brazil Real",
										'BSD'	=> "Bahamas Dollar",
										'BTN'	=> "Bhutan Ngultrum",
										'BWP'	=> "Botswana Pula",
										'BYN'	=> "Belarus Ruble",
										'BZD'	=> "Belize Dollar",
										'CAD'	=> "Canada Dollar",
										'CDF'	=> "Congo/Kinshasa Franc",
										'CHF'	=> "Switzerland Franc",
										'CLP'	=> "Chile Peso",
										'CNY'	=> "China Yuan Renminbi",
										'COP'	=> "Colombia Peso",
										'CRC'	=> "Costa Rica Colon",
										'CUC'	=> "Cuba Convertible Peso",
										'CUP'	=> "Cuba Peso",
										'CVE'	=> "Cape Verde Escudo",
										'CZK'	=> "Czech Republic Koruna",
										'DJF'	=> "Djibouti Franc",
										'DKK'	=> "Denmark Krone",
										'DOP'	=> "Dominican Republic Peso",
										'DZD'	=> "Algeria Dinar",
										'EGP'	=> "Egypt Pound",
										'ERN'	=> "Eritrea Nakfa",
										'ETB'	=> "Ethiopia Birr",
										'EUR'	=> "Euro Member Countries",
										'FJD'	=> "Fiji Dollar",
										'FKP'	=> "Falkland Islands (Malvinas) Pound",
										'GBP'	=> "United Kingdom Pound",
										'GEL'	=> "Georgia Lari",
										'GGP'	=> "Guernsey Pound",
										'GHS'	=> "Ghana Cedi",
										'GIP'	=> "Gibraltar Pound",
										'GMD'	=> "Gambia Dalasi",
										'GNF'	=> "Guinea Franc",
										'GTQ'	=> "Guatemala Quetzal",
										'GYD'	=> "Guyana Dollar",
										'HKD'	=> "Hong Kong Dollar",
										'HNL'	=> "Honduras Lempira",
										'HRK'	=> "Croatia Kuna",
										'HTG'	=> "Haiti Gourde",
										'HUF'	=> "Hungary Forint",
										'IDR'	=> "Indonesia Rupiah",
										'ILS'	=> "Israel Shekel",
										'IMP'	=> "Isle of Man Pound",
										'INR'	=> "India Rupee",
										'IQD'	=> "Iraq Dinar",
										'IRR'	=> "Iran Rial",
										'ISK'	=> "Iceland Krona",
										'JEP'	=> "Jersey Pound",
										'JMD'	=> "Jamaica Dollar",
										'JOD'	=> "Jordan Dinar",
										'JPY'	=> "Japan Yen",
										'KES'	=> "Kenya Shilling",
										'KGS'	=> "Kyrgyzstan Som",
										'KHR'	=> "Cambodia Riel",
										'KMF'	=> "Comorian Franc",
										'KPW'	=> "Korea (North) Won",
										'KRW'	=> "Korea (South) Won",
										'KWD'	=> "Kuwait Dinar",
										'KYD'	=> "Cayman Islands Dollar",
										'KZT'	=> "Kazakhstan Tenge",
										'LAK'	=> "Laos Kip",
										'LBP'	=> "Lebanon Pound",
										'LKR'	=> "Sri Lanka Rupee",
										'LRD'	=> "Liberia Dollar",
										'LSL'	=> "Lesotho Loti",
										'LYD'	=> "Libya Dinar",
										'MAD'	=> "Morocco Dirham",
										'MDL'	=> "Moldova Leu",
										'MGA'	=> "Madagascar Ariary",
										'MKD'	=> "Macedonia Denar",
										'MMK'	=> "Myanmar (Burma) Kyat",
										'MNT'	=> "Mongolia Tughrik",
										'MOP'	=> "Macau Pataca",
										'MRU'	=> "Mauritania Ouguiya",
										'MUR'	=> "Mauritius Rupee",
										'MVR'	=> "Maldives (Maldive Islands) Rufiyaa",
										'MWK'	=> "Malawi Kwacha",
										'MXN'	=> "Mexico Peso",
										'MYR'	=> "Malaysia Ringgit",
										'MZN'	=> "Mozambique Metical",
										'NAD'	=> "Namibia Dollar",
										'NGN'	=> "Nigeria Naira",
										'NIO'	=> "Nicaragua Cordoba",
										'NOK'	=> "Norway Krone",
										'NPR'	=> "Nepal Rupee",
										'NZD'	=> "New Zealand Dollar",
										'OMR'	=> "Oman Rial",
										'PAB'	=> "Panama Balboa",
										'PEN'	=> "Peru Sol",
										'PGK'	=> "Papua New Guinea Kina",
										'PHP'	=> "Philippines Peso",
										'PKR'	=> "Pakistan Rupee",
										'PLN'	=> "Poland Zloty",
										'PYG'	=> "Paraguay Guarani",
										'QAR'	=> "Qatar Riyal",
										'RON'	=> "Romania Leu",
										'RSD'	=> "Serbia Dinar",
										'RUB'	=> "Russia Ruble",
										'RWF'	=> "Rwanda Franc",
										'SAR'	=> "Saudi Arabia Riyal",
										'SBD'	=> "Solomon Islands Dollar",
										'SCR'	=> "Seychelles Rupee",
										'SDG'	=> "Sudan Pound",
										'SEK'	=> "Sweden Krona",
										'SGD'	=> "Singapore Dollar",
										'SHP'	=> "Saint Helena Pound",
										'SLL'	=> "Sierra Leone Leone",
										'SOS'	=> "Somalia Shilling",
										'SPL'	=> "Seborga Luigino",
										'SRD'	=> "Suriname Dollar",
										'STN'	=> "São Tomé and Príncipe Dobra",
										'SVC'	=> "El Salvador Colon",
										'SYP'	=> "Syria Pound",
										'SZL'	=> "eSwatini Lilangeni",
										'THB'	=> "Thailand Baht",
										'TJS'	=> "Tajikistan Somoni",
										'TMT'	=> "Turkmenistan Manat",
										'TND'	=> "Tunisia Dinar",
										'TOP'	=> "Tonga Pa'anga",
										'TRY'	=> "Turkey Lira",
										'TTD'	=> "Trinidad and Tobago Dollar",
										'TVD'	=> "Tuvalu Dollar",
										'TWD'	=> "Taiwan New Dollar",
										'TZS'	=> "Tanzania Shilling",
										'UAH'	=> "Ukraine Hryvnia",
										'UGX'	=> "Uganda Shilling",
										'USD'	=> "United States Dollar",
										'UYU'	=> "Uruguay Peso",
										'UZS'	=> "Uzbekistan Som",
										'VEF'	=> "Venezuela Bolívar",
										'VND'	=> "Viet Nam Dong",
										'VUV'	=> "Vanuatu Vatu",
										'WST'	=> "Samoa Tala",
										'XAF'	=> "Communauté Financière Africaine (BEAC) CFA Franc BEAC",
										'XCD'	=> "East Caribbean Dollar",
										'XDR'	=> "International Monetary Fund (IMF) Special Drawing Rights",
										'XOF'	=> "Communauté Financière Africaine (BCEAO) Franc",
										'XPF'	=> "Comptoirs Français du Pacifique (CFP) Franc",
										'YER'	=> "Yemen Rial",
										'ZAR'	=> "South Africa Rand",
										'ZMW'	=> "Zambia Kwacha",
										'ZWD'	=> "Zimbabwe Dollar",

									),
						'default'	=> ''
					),

					array(
						'id'       => 'misc_custom_currency_label',
						'title'    => __( 'Custom Currency Label', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the default currency label at your WooCommerce store. Leave it blank if you want to use the default one.', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array(
						'id'       => 'misc_custom_currency_symbol',
						'title'    => __( 'Custom Currency Symbol', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Change the default currency symbol at your WooCommerce store. Leave it blank if you want to use the default one.', 'woocommerce-powerup' ),
						'type'     => 'text'
					),

					array( 'type' => 'sectionend' )

				)



			
		);

		/**
		 * Filters the available powerup settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings the plugin settings
		 */
		$settings = apply_filters( 'wc_powerup_settings', $settings );

		$current_section = isset( $GLOBALS['current_section'] ) ? $GLOBALS['current_section'] : 'shop_page';

		return isset( $settings[ $current_section ] ) ?  $settings[ $current_section ] : $settings['shop_page'];
	}


}

// setup settings
return wc_powerup()->settings = new WC_PowerUp_Settings();
