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
 * Class WC_PowerUp_Integrations.
 *
 * Adds integration code for other WooCommerce extensions.
 *
 * @since 1.0.0
 */
class WC_PowerUp_Integrations {


	/**
	 * WC_PowerUp_Integrations constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( WC_PowerUp::is_plugin_active( 'woocommerce-product-bundles.php' ) ) {

			add_filter( 'wc_powerup_settings', array( $this, 'add_bundles_settings' ) );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'customize_bundle_add_to_cart_text' ), 150, 2 );
		}
	}


	/**
	 * Adds settings when Product Bundles is active.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings the settings array
	 * @return array updated settings
	 */
	public function add_bundles_settings( $settings ) {

		$new_settings = array();

		foreach ( $settings as $section => $settings_group ) {

			$new_settings[ $section ] = array();

			foreach ( $settings_group as $setting ) {

				$new_settings[ $section ][] = $setting;

				if ( 'shop_page' === $section && isset( $setting['id'] ) && 'grouped_add_to_cart_text' === $setting['id'] ) {

					// insert bundle settings after the grouped product text
					$new_settings[ $section ][] = array(
						'id'       => 'bundle_add_to_cart_text',
						'title'    => __( 'Bundle Product', 'woocommerce-powerup' ),
						'desc_tip' => __( 'Changes the add to cart button text for bundle products on all loop pages', 'woocommerce-powerup' ),
						'type'     => 'text'
					);
				}
			}
		}

		return $new_settings;
	}


	/**
	 * Customizes the add to cart button for bundle products.
	 *
	 * @since  1.0.0
	 *
	 * @param string $text add to cart text
	 * @param WC_Product $product product object
	 * @return string modified add to cart text
	 */
	public function customize_bundle_add_to_cart_text( $text, $product ) {

		if ( isset( wc_powerup()->filters['bundle_add_to_cart_text'] ) && $product->is_type( 'bundle' ) ) {

			// bundle add to cart text
			$text = wc_powerup()->filters['bundle_add_to_cart_text'];
		}

		return $text;
	}


}
