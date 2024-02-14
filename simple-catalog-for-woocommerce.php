<?php
/*
Plugin Name: Simple Catalog for WooCommerce
Plugin URI: http://maddisondesigns.com/simple-catalog-for-woocommerce
Description: Turn your WooCommerce store into a simple online catalog. You can disable your eCommerce functionality for all users or only for users that aren't logged in.
Version: 1.5.0
WC requires at least: 2.6
WC tested up to: 8.5
Author: Anthony Hortin
Author URI: http://maddisondesigns.com
Text Domain: simple-catalog-for-woocommerce
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/


class scw_simple_catalog_woocommerce_plugin {

	const SETTINGS_NAMESPACE = 'simple_woocommerce_catalog';

	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'scw_add_settings_tab' ), 50) ;
			add_action( 'woocommerce_settings_tabs_' . self::SETTINGS_NAMESPACE, array( $this, 'scw_catalog_settings_tab' ) );
			add_action( 'woocommerce_update_options_' . self::SETTINGS_NAMESPACE, array( $this, 'scw_catalog_update_settings' ) );
			add_filter( 'plugin_action_links', array( $this, 'scw_add_settings_link'), 10, 2);
		}
		else {
			add_action( 'init', array( $this, 'scw_catalog_remove_prices' ) );
			add_action( 'init', array( $this, 'scw_catalog_remove_ratings' ) );
			add_action( 'init', array( $this, 'scw_catalog_remove_reviews' ) );
			add_action( 'init', array( $this, 'scw_shop_remove_cart_buttons' ) );
			add_action( 'woocommerce_single_product_summary', array( $this, 'scw_single_product_remove_cart_buttons' ) );
			add_action('template_redirect', array( $this, 'scw_catalog_cart_redirect') );
			add_action('template_redirect', array( $this, 'scw_catalog_checkout_redirect') );
		}
	}

	/**
	 * Add a new tab to the WooCommerce Settings page
	 */
	public static function scw_add_settings_tab( $settings_tabs ) {
		$settings_tabs[self::SETTINGS_NAMESPACE] = __( 'Simple Catalog', 'simple-catalog-for-woocommerce' );
		return $settings_tabs;
	}

	/**
	 * Add a settings link to plugin page
	 */
	public function scw_add_settings_link( $links, $file ) {
		static $this_plugin;

		if( !$this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}

		if( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=wc-settings&tab=' . self::SETTINGS_NAMESPACE . '">' . __( 'Settings', 'simple-catalog-for-woocommerce' ) . '</a>';
			array_unshift( $links, $settings_link ) ;
		}

		return $links;
	}

	/**
	 * Get the settings for our WooCommerce tab
	 */
	public function scw_catalog_settings_tab() {
		woocommerce_admin_fields( $this->scw_get_tab_settings() );
	}

	/**
	 * Update the settings for our WooCommerce tab
	 */
	public function scw_catalog_update_settings() {
		woocommerce_update_options( $this->scw_get_tab_settings() );
	}

	/**
	* Get an option set in our settings tab
	*/
	public function scw_catalog_get_option( $key ) {
		$fields = $this->scw_get_tab_settings();

		return apply_filters( 'wc_option_' . $key, get_option( 'wc_settings_' . self::SETTINGS_NAMESPACE . '_' . $key, ( ( isset( $fields[$key] ) && isset( $fields[$key]['default'] ) ) ? $fields[$key]['default'] : '' ) ) );
	}

	/**
	 * Add all our settings to our WooCommerce tab
	 */
	private function scw_get_tab_settings() {
		$list_of_pages = get_pages();
		$pages_array = array();
		$displayString = __( 'Display for all Users', 'simple-catalog-for-woocommerce' );
		$hideString = __( 'Hide for all Users', 'simple-catalog-for-woocommerce' );
		$loggedinString = __( 'Display for Logged In Users', 'simple-catalog-for-woocommerce' );

		$pages_array['none'] = __( 'Do not link Price Text Replacement', 'simple-catalog-for-woocommerce' );
		foreach( $list_of_pages as $page ) {
			$pages_array[esc_attr( $page->ID )] = esc_attr( $page->post_title );
		}

		$settings = array(
			'section_title' => array(
				'name'     => __( 'Catalog Options', 'simple-catalog-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_section_title'
			),
			'addtocart' => array(
				'name' => __( 'Add to Cart Button', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Hide the Add to Cart Button for all customers or just Logged In customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'loggedin',
				'options' => array(
					'display' => $displayString,
					'hide' => $hideString,
					'loggedin' => $loggedinString
				),
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_addtocart'
			),
			'showprices' => array(
				'name' => __( 'Prices', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Hide the prices for all customers or just Logged In customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'loggedin',
				'options' => array(
					'display' => $displayString,
					'hide' => $hideString,
					'loggedin' => $loggedinString
				),
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_showprices'
			),
			'showratings' => array(
				'name' => __( 'Product Ratings', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Hide the product ratings for all customers or just Logged In customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'loggedin',
				'options' => array(
					'display' => $displayString,
					'hide' => $hideString,
					'loggedin' => $loggedinString
				),
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_showratings'
			),
			'showreviews' => array(
				'name' => __( 'Product Reviews', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Hide the product reviews for all customers or just Logged In customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'loggedin',
				'options' => array(
					'display' => $displayString,
					'hide' => $hideString,
					'loggedin' => $loggedinString
				),
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_showreviews'
			),
			'pricetext' => array(
				'name' => __( 'Price Text Replacement', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Display this text instead of the price. Only displayed when price is hidden from customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'text',
				'default' => '',
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_pricetext'
			),
			'pricetextlink' => array(
				'name' => __( 'Price Text Link', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Link to this page when displaying text instead of the price. Only displayed when price is hidden from customers and Price Text Replacement field is used.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'none',
				'options' => $pages_array,
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_pricetextlink'
			),
			'hidecart' => array(
				'name' => __( 'Cart Page', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Hide the Cart page from all customers or just Logged In customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'display',
				'options' => array(
					'display' => $displayString,
					'hide' => $hideString,
					'loggedin' => $loggedinString
				),
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_hidecart'
			),
			'hidecheckout' => array(
				'name' => __( 'Checkout Page', 'simple-catalog-for-woocommerce' ),
				'desc_tip' => __( 'Hide the Checkout page from all customers or just Logged In customers.', 'simple-catalog-for-woocommerce' ),
				'type' => 'select',
				'default' => 'display',
				'options' => array(
					'display' => $displayString,
					'hide' => $hideString,
					'loggedin' => $loggedinString
				),
				'id'   => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_hidecheckout'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_section_end'
			)
		);
		return apply_filters( 'wc_settings_tab_' . self::SETTINGS_NAMESPACE, $settings );
	}

	/**
	 * Check what we should do with the Add to Cart button on the shop page
	 */
	public function scw_shop_remove_cart_buttons() {
		switch ( $this->scw_catalog_get_option( 'addtocart' ) ) {
			case 'hide':
				$this->scw_shop_hide_cart_buttons();
				break;

			case 'loggedin':
				if ( !is_user_logged_in() ) {
					$this->scw_shop_hide_cart_buttons();
				}
				break;
		}
	}

	/**
	 * Hide the Add to Cart button on the shop page
	 */
	public function scw_shop_hide_cart_buttons() {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
	}

	/**
	 * Check what we should do with the Add to Cart button on the single product page
	 */
	public function scw_single_product_remove_cart_buttons() {
		switch ( $this->scw_catalog_get_option( 'addtocart' ) ) {
			case 'hide':
				$this->scw_single_product_hide_cart_buttons();
				break;

			case 'loggedin':
				if ( !is_user_logged_in() ) {
					$this->scw_single_product_hide_cart_buttons();
				}
				break;
		}
	}

	/**
	 * Hide the Add to Cart button on the single product page
	 */
	public function scw_single_product_hide_cart_buttons() {
		global $product;

		if( $product->is_type('variable') ) {
			// Remove price on Variation
			remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10);

			// Remove Add to Cart on Variation
			remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
		}
		else {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		}
	}

	/**
	 * Check what we should do with the product prices
	 */
	public function scw_catalog_remove_prices() {
		switch ( $this->scw_catalog_get_option( 'showprices' ) ) {
			case 'hide':
				$this->scw_hide_product_prices();
				break;

			case 'loggedin':
				if ( !is_user_logged_in() ) {
					$this->scw_hide_product_prices();
				}
				break;
		}
	}

	/**
	 * Hide the product price on the shop page and the single product page. Change the price text, if needed.
	 */
	public function scw_hide_product_prices() {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' );
		if ( !empty( $this->scw_catalog_get_option( 'pricetext' ) ) ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'scw_catalog_price_text_replacement' ), 11 );
			add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'scw_catalog_price_text_replacement' ), 11 );
		}
	}

	/**
	 * Change the price text when the prices are hidden from customers
	 */
	public function scw_catalog_price_text_replacement() {
		$price_str = "";
		$price_str_link_id = $this->scw_catalog_get_option( 'pricetextlink' );

		if ( empty( $price_str_link_id ) || $price_str_link_id === 'none' ) {
			$price_str = sprintf( '<p class="price">%1$s</p>',
				$this->scw_catalog_get_option( 'pricetext' )
			);
		}
		else {
			$price_str = sprintf( '<p class="price"><a href="%1$s">%2$s</a></p>',
				get_page_link( $price_str_link_id ),
				$this->scw_catalog_get_option( 'pricetext' )
			);
		}

		echo $price_str;
	}

	/**
	 * Check what we should do with the product ratings
	 */
	public function scw_catalog_remove_ratings() {
		switch ( $this->scw_catalog_get_option( 'showratings' ) ) {
			case 'hide':
				$this->scw_hide_product_ratings();
				break;

			case 'loggedin':
				if ( !is_user_logged_in() ) {
					$this->scw_hide_product_ratings();
				}
				break;
		}
	}

	/**
	 * Hide the product ratings on the shop page and the single product page.
	 */
	public function scw_hide_product_ratings() {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
	}

	/**
	 * Check what we should do with the product review
	 */
	public function scw_catalog_remove_reviews() {
		switch ( $this->scw_catalog_get_option( 'showreviews' ) ) {
			case 'hide':
				$this->scw_hide_product_reviews();
				break;

			case 'loggedin':
				if ( !is_user_logged_in() ) {
					$this->scw_hide_product_reviews();
				}
				break;
		}
	}

	/**
	 * Hide the product review on the single product page.
	 */
	public function scw_hide_product_reviews() {
		add_filter( 'woocommerce_product_tabs', array( $this, 'scw_catalog_woo_remove_reviews_tab' ), 98 );
	}

	/**
	 * Remove the product reviews tab
	 */
	public function scw_catalog_woo_remove_reviews_tab( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	}

	/**
	 * Check if we should be hiding the cart page
	 */
	public function scw_catalog_cart_redirect() {
		switch ( $this->scw_catalog_get_option( 'hidecart' ) ) {
			case 'hide':
				if ( is_cart() ) {
					wp_redirect( home_url() );
					exit;
				}
				break;

			case 'loggedin':
				if ( !is_user_logged_in() && is_cart() ) {
					wp_redirect( home_url() );
					exit;
				}
				break;

			default:
				return;
				break;
		}
	}

	/**
	 * Check if we should be hiding the checkout page
	 */
	public function scw_catalog_checkout_redirect() {
		switch ( $this->scw_catalog_get_option( 'hidecheckout' ) ) {
			case 'hide':
				if ( is_checkout() ) {
					wp_redirect( home_url() );
					exit;
				}
				break;

			case 'loggedin':
				if ( !is_user_logged_in() && is_checkout() ) {
					wp_redirect( home_url() );
					exit;
				}
				break;

			default:
				return;
				break;
		}
	}
}

if ( scw_is_woocommerce_active() ) {
	$scw_simple_catalog_woocommerce = new scw_simple_catalog_woocommerce_plugin();
}

/**
 * Check if WooCommerce is active
 */
function scw_is_woocommerce_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Declare WooCommerce HPOS compatibility
 */
function scw_declare_hpos_compat() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'scw_declare_hpos_compat' );
