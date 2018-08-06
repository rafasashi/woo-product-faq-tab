<?php 

	if ( ! defined( 'ABSPATH' ) ) exit;

	class WooCommerce_Product_FAQ_Tab_License {
		
		/**
		 * The single instance of WooCommerce_Product_FAQ_Tab_Admin_Notices.
		 * @var 	object
		 * @access  private
		 * @since 	1.0.0
		 */
		private static $_instance = null;
		
		private $license 	= null;

		/**
		 * The main plugin object.
		 * @var 	object
		 * @access  public
		 * @since 	1.0.0
		 */
		public $parent = null;
		
		public function __construct( $parent ) {
			
			$this->parent = $parent;
			
			// Handle license activation
			add_action( 'admin_init', array( $this, 'handle_license_activation' ), 0 );		
		}

		public function is_valid(){
			
			if( is_null($this->license) ){
				
				$this->license = false;
				
				$option_name = $this->parent->_base . 'license';
			
				$license = get_option($option_name);

				if( $license = json_decode($license) ){
					
					if( is_object($license) && isset($license->key) && !empty($license->key) ){
						
						if( !isset($_REQUEST['wlm_action']) && !empty($license->last_check) && 0 < (round((time() - $license->last_check) / (60 * 60 * 24))) ){
						
							// API query parameters
							
							$api_params = array(
								'wlm_action' 		=> 'wlm_check',
								'secret_key' 		=> RW_SECRET_KEY,
								'license_key'		=> $license->key,
							);

							// Send query to the license manager server
							
							$query = esc_url_raw(add_query_arg($api_params, RW_SERVER_URL));
							
							$response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));
							
							// Check for error in the response
							
							if (!is_wp_error($response)){
								
								// License data
								$response = json_decode(wp_remote_retrieve_body($response));
	
								if( is_object($response) && isset($response->result) && $response->result == 'success' ){ //Success was returned for the license activation
									
									if( $response->status == 'active' ){
										
										$this->license = $license;
										
										//Update the license last check

										$license = json_encode(array(
										
											'key' 			=> $this->license->key,
											'last_check' 	=> time(),
										));
										
										update_option($option_name, $license);
										update_option($option_name.'_key', $this->license->key);
										update_option($option_name.'_md5', md5($license));									
									}
									else{

										//Cancel the license in the options table
										
										update_option($option_name, '');
										update_option($option_name.'_key', '');
										update_option($option_name.'_md5', '');									
									}
								}								
							}
							else{
								
								// server busy retry later
								
								$this->license = $license;
							}							
						}
						else{
							
							$license_key = get_option($option_name . '_key');
							
							if( !empty($license_key) ){
							
								$this->license = $license;
							}
						}
					}
				}
			}
			
			if( !empty($this->license) ){
				
				return true;
			}
			
			return false;
		}
		
		public function handle_license_activation(){
		
			$option_name = $this->parent->_base . 'license';
		
			/*** License activate button was clicked ***/
			if (isset($_REQUEST['activate_license']) && isset($_REQUEST[$option_name.'_key'])) {
				
				$license_key = $_REQUEST[$option_name.'_key'];
				
				$plugin_data = get_plugin_data( $this->parent->file );

				// API query parameters
				$api_params = array(
					'wlm_action' 		=> 'wlm_activate',
					'secret_key' 		=> RW_SECRET_KEY,
					'license_key'		=> $license_key,
					'registered_domain' => $_SERVER['SERVER_NAME'],
					'item_reference' 	=> urlencode($plugin_data['Name']),
					'item_id' 			=> WFAQ_PRODUCT_ID,
				);

				// Send query to the license manager server
				
				$query = esc_url_raw(add_query_arg($api_params, RW_SERVER_URL));
				
				$response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));
				
				// Check for error in the response
				if (is_wp_error($response)){
					
					$this->parent->notices->add_error("Unexpected Error! The query returned with an error.");
				}
				
				// License data
				$response = json_decode(wp_remote_retrieve_body($response));
			
				if( is_object($response) && isset($response->result) ){ //Success was returned for the license activation
					
					if($response->result == 'success'){
						
						$this->parent->notices->add_success('Success: ' . $response->message);			
						
						//Save the license in the options table

						$license = json_encode(array(
						
							'key' 			=> $license_key,
							'last_check' 	=> time(),
						));
						
						update_option($option_name, $license);
						update_option($option_name.'_key', $license_key);
						update_option($option_name.'_md5', md5($license));
					}
					else{
						
						update_option($option_name, '');
						update_option($option_name.'_key', '');
						update_option($option_name.'_md5', '');
						
						$this->parent->notices->add_error('Error: ' . $response->message);
					}
				}
				else{
					
					$this->parent->notices->add_error('Error requesting data, try again later...');
				}
			}
			elseif (isset($_REQUEST['deactivate_license']) && isset($_REQUEST[$option_name.'_key'])) {

				/*** End of license activation ***/
				
				$license_key = $_REQUEST[$option_name.'_key'];

				$plugin_data = get_plugin_data( $this->parent->file );
				
				// API query parameters
				$api_params = array(
					'wlm_action' 		=> 'wlm_deactivate',
					'secret_key' 		=> RW_SECRET_KEY,
					'license_key' 		=> $license_key,
					'registered_domain' => $_SERVER['SERVER_NAME'],
					'item_reference' 	=> urlencode($plugin_data['Name']),
					'item_id' 			=> WFAQ_PRODUCT_ID,
				);

				// Send query to the license manager server
				$query = esc_url_raw(add_query_arg($api_params, RW_SERVER_URL));
				$response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

				// Check for error in the response
				if (is_wp_error($response)){
					
					$this->parent->notices->add_error("Unexpected Error! The query returned with an error.");
				}

				// License data.
				$response = json_decode(wp_remote_retrieve_body($response));
				
				
				
				if($response->result == 'success'){//Success was returned for the license activation
					
					$this->parent->notices->add_success('Success: ' . $response->message);
					
					//Save the license in the options table
					
					update_option($option_name, '');
					update_option($option_name.'_key', '');
					update_option($option_name.'_md5', '');
				}
				else{

					$this->parent->notices->add_error('Error: ' . $response->message);
				}
			}
		}		
		
		public static function instance ( $parent ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $parent );
			}
			return self::$_instance;
		} // End instance()

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __clone () {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
		} // End __clone()

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup () {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
		} // End __wakeup()
	}
	