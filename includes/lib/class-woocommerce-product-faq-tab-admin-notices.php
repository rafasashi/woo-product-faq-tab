<?php 

	if ( ! defined( 'ABSPATH' ) ) exit;

	class WooCommerce_Product_FAQ_Tab_Admin_Notices {
		
		/**
		 * The single instance of WooCommerce_Product_FAQ_Tab_Admin_Notices.
		 * @var 	object
		 * @access  private
		 * @since 	1.0.0
		 */
		private static $_instance = null;

		/**
		 * The main plugin object.
		 * @var 	object
		 * @access  public
		 * @since 	1.0.0
		 */
		public $parent = null;		

		private $session_var = 'wp_persistent_notices';
		private $max_age;
		private $default_notice;
		private $notices;
		
		private function __construct() {
			$this->max_age = max( intval( ini_get( 'max_execution_time' ) ) * 2, 30 );
			$this->notices = array();
			$this->default_notice = array(
				'id'           => '',
				'type'         => 'info',
				'custom_class' => '',
				'message'      => '',
				'dismissible'  => false,
				'html'         => '',
				'location'     => 'default'
			);
			add_action( 'admin_init'                    , array( $this, 'admin_init'       ) );
			add_action( 'admin_notices'                 , array( $this, 'render'   ) );
			add_action( 'wp_ajax_get_persistent_notices', array( $this, 'ajax_get_notices' ) );
			add_action( 'shutdown'                      , array( $this, 'save_notices'     ) );
		}
		
		public function admin_init() {
			
			// add a filter and that returns true to replace or prevent the session initialization functionality
			
			if( !apply_filters( 'wp_persistent_notices_replace_initialization', false ) ) {
				
				if( session_status() == PHP_SESSION_NONE ) {
				
					session_start();
				}
			}
		}
		
		public function add( $notice = array() ) {
			
			if( !is_array( $notice ) ) {
				$notice = array(
					'message' => $notice
				);
			}
			
			$notice = array_merge( $this->default_notice, $notice );
			
			$notice['created'] = time();
			
			$this->notices[] = $notice;
			
			return true;
		}
		
		public function add_error( $notice = '' ) {
			
			return $this->add(array(
			
				'type' 		=> 'error',
				'message' 	=> $notice,
			));
		}
		
		public function add_success( $notice = '' ) {
			
			return $this->add(array(
			
				'type' 		=> 'success',
				'message' 	=> $notice,
			));
		}		
		
		public function render( $location = 'default' ) {
			
			if( !$location ) {
				
				$location = 'default';
			}
			
			if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			
			$notices = $this->retrieve_notices( $location );
			
			$output_array = array();
			
			foreach ( $notices as $notice ) {
				
				if( !is_array( $notice ) ) {
					
					$notice = array(
						'message' => $notice
					);
				}
				
				if( $notice['html'] ) {
					
					$output_array[] = wp_kses_post( $notice['html'] );
					
				} 
				elseif( $notice['message'] ) {
					
					$notice = array_merge( $this->default_notice, $notice );
					$id = '';
					if( $notice['id'] ) {
						$id = ' id="wp-persistent-notice-' . sanitize_title( $id ) . '"';
					}
					$type = '';
					if( $notice['type'] ) {
						$type = ' notice-' . sanitize_title( $notice['type'] );
					}
					$cclass = '';
					if( $notice['custom_class'] ) {
						$cclass = ' ' . sanitize_title( $notice['custom_class'] );
					}
					$dismissible = '';
					if( $notice['dismissible'] ) {
						$dismissible = ' is-dismissible';
					}
					$class = ' class="notice' . $type . $cclass . $dismissible . '"';
					$message = wp_kses_post( $notice['message'] );
					$output_array[] = sprintf( '<div%1$s%2$s><p>%3$s</p></div>', $id, $class, $message );
				}
			}
			echo implode( "\n", $output_array );
		}
		public function ajax_get_notices() {
			$location = 'default';
			if( isset( $_GET['location'] ) && $_GET['location'] ) {
				$location = $_GET['location'];
			}
			if( isset( $_POST['location'] ) && $_POST['location'] ) {
				$location = $_POST['location'];
			}
			$notices = $this->retrieve_notices( $location );
			echo json_encode( $notices );
			wp_die();
		}
		public function save_notices() {
			$notices = $this->retrieve_notices( 'all' );
			if ( isset( $notices ) ) {
				// add a filter that returns true to replace the notice saving functionality
				if( apply_filters( 'wp_persistent_notices_replace_save_notices', false, $notices ) !== true ) {
					$_SESSION[ $this->session_var ] = $notices;
				}
			}
		}
		private function retrieve_notices( $location = 'default' ) {
			// note: once a notice is retrieved, it's gone
			$notices = array();
			// add a filter that returns true to prevent retrieving messages on specific pages
			if( !apply_filters( 'wp_persistent_notices_prevent_retrieval', false ) ) {
				// add a filter that returns notices or empty array() to replace the session retrieval functionality
				$raw_notices = apply_filters( 'wp_persistent_notices_replace_retrieve_notices', false );
				if( $raw_notices === false ) {
					$raw_notices = array();
					if( isset( $_SESSION[ $this->session_var ] ) && is_array( $_SESSION[ $this->session_var ] ) ) {
						$session_notices = $_SESSION[ $this->session_var ];
						if( is_array( $session_notices ) ) {
							$raw_notices = $session_notices;
						}
					}
				}
				
				$raw_notices += $this->notices;
				$raw_notices = apply_filters( 'wp_persistent_notices', $raw_notices );
				
				// clear all places notices are stored
				
				if( isset($_SESSION[ $this->session_var ]) ){
				
					unset( $_SESSION[ $this->session_var ] );
				}
				
				$this->notices = array();
				remove_all_filters( 'wp_persistent_notices' );
				$notices = array();
				// filter for age and location
				foreach( $raw_notices as $notice ) {
					if( !isset( $notice['created'] ) || !$notice['created'] ) {
						$notice['created'] = time();
					} else {
						if( time() - $notice['created'] > $this->max_age ) {
							continue;
						}
					}
					if( $location == 'all' || $notice['location'] == $location ) {
						$notices[] = $notice;
					} else {
						$this->notices[] = $notice;
					}
				}
				// attach a filter that returns true to disable grouping notices
				if( !apply_filters( 'wp_persistent_notices_display_ungrouped', false ) ) {
					usort( $notices, array( $this, 'compare_notices' ) );
				}
			}
			return $notices;
		}
		private function compare_notices( $a, $b ) {
			// attach a filter that returns an integer { -1, 0, 1 } to replace comparison function
			$compare = apply_filters( 'wp_persistent_notices_replace_comparison', false, $a, $b );
			if( $compare !== false ) {
				return $compare;
			}
			// if one of them doesn't have a type
			if( !isset( $a['type'] ) ) {
				if( !isset( $b['type'] ) ) {
					return 0;
				}
				return -1;
			}
			// if types are equal
			if( $a['type'] == $b['type'] ) {
				return 0;
			}
			// give a weight to each type
			$test = array( $a['type'], $b['type'] );
			$results = array();
			foreach( $test as $val ) {
				$i = false;
				switch( $val ) {
					case 'error':
						$i = apply_filters( 'wp_persistent_notices_sort_error_weight', 0 );
						break;
					case 'warning':
						$i = apply_filters( 'wp_persistent_notices_sort_warning_weight', 5 );
						break;
					case 'success':
						$i = apply_filters( 'wp_persistent_notices_sort_success_weight', 10 );
						break;
					case 'info':
						$i = apply_filters( 'wp_persistent_notices_sort_info_weight', 15 );
						break;
					default:
						// use the val to determine weight
						$i = apply_filters( 'wp_persistent_notices_sort_custom_weight', 20, $val );
				}
				$results[] = $i;
			}
			return ( $results[0] - $results[1] );
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
	