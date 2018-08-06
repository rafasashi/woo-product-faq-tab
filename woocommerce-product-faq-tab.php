<?php
/*
 * Plugin Name: WooCommerce Product FAQ Tab
 * Plugin URI: https://code.recuweb.com/download/woocommerce-product-faq-tab/
 * Description: Extends WooCommerce to allow you to display all images attached to a product in a new tab on the single product page.
 * Version: 3.0.5
 * Author: Rafasashi
 * Author URI: https://code.recuweb.com/about-us/
 * Requires at least: 4.6
 * Tested up to: 4.9.8
 *
 * Text Domain: wc-faq
 * Domain Path: /lang/
 * 
 * Copyright: © 2018 Recuweb.
 * License: GNU General Public License v3.0
 * License URI: https://code.recuweb.com/product-licenses/
 */

	if(!defined('ABSPATH')) exit; // Exit if accessed directly
 
	/**
	* Minimum version required
	*
	*/
	if ( get_bloginfo('version') < 3.3 ) return;
	
	// This is the secret key for API authentication.
	if(!defined('RW_SECRET_KEY')){
		define('RW_SECRET_KEY', '5ad860ff15b435.76265870');
	}

	// This is the URL where API query request will be sent to.
	if(!defined('RW_SERVER_URL')){
		define('RW_SERVER_URL', 'https://code.recuweb.com');
	}
	
	if(!defined('WFAQ_PRODUCT_ID')){
		define('WFAQ_PRODUCT_ID', '5701');
	}	

	// Load plugin class files
	require_once( 'includes/class-woocommerce-product-faq-tab.php' );
	require_once( 'includes/class-woocommerce-product-faq-tab-settings.php' );
	
	// Load plugin libraries
	require_once( 'includes/lib/class-woocommerce-product-faq-tab-admin-api.php' );
	require_once( 'includes/lib/class-woocommerce-product-faq-tab-admin-notices.php' );
	require_once( 'includes/lib/class-woocommerce-product-faq-tab-license.php' );
	require_once( 'includes/lib/class-woocommerce-product-faq-tab-post-type.php' );
	require_once( 'includes/lib/class-woocommerce-product-faq-tab-taxonomy.php' );		
	
	/**
	 * Returns the main instance of WooCommerce_Product_FAQ_Tab to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object WooCommerce_Product_FAQ_Tab
	 */
	function WooCommerce_Product_FAQ_Tab() {
				
		$instance = WooCommerce_Product_FAQ_Tab::instance( __FILE__, '1.0.6' );	
		
		if ( is_null( $instance->notices ) ) {
			
			$instance->notices = WooCommerce_Product_FAQ_Tab_Admin_Notices::instance( $instance );
		}
		
		if ( is_null( $instance->settings ) ) {
			
			$instance->settings = WooCommerce_Product_FAQ_Tab_Settings::instance( $instance );
		}

		return $instance;
	}	

	// Checks if the WooCommerce plugins is installed and active.
	
	if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
		
		WooCommerce_Product_FAQ_Tab();
	}
	else{
		
		add_action('admin_notices', function(){
			
			global $current_screen;
			
			if( $current_screen->parent_base == 'plugins' ){
				
				echo '<div class="error"><p>WooCommerce Product FAQ Tab '.__('requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.', 'wc-faq').'</p></div>';
			}
		});
	}
