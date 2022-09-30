<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WooCommerce_Product_FAQ_Tab {

	/**
	 * The single instance of WooCommerce_Product_FAQ_Tab.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;
	
	public $_dev = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */

	public $notices = null;
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;
	public $views;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basefile;

	public $tab_data = null;
	
	public $woo_version = '2.0';
	
	public $title 		= 'FAQs';
	public $accordion 	= 'no';
	public $form 		= 'no';
	public $showEmpty 	= 'yes';
	public $priority 	= 20;
	 
	public function __construct ( $file = '', $version = '1.0.0' ) {

		$this->_version = $version;
		$this->_token = 'woocommerce-product-faq-tab';
		$this->_base = 'wfaq_';
		
		$this->premium_url = 'https://code.recuweb.com/download/woocommerce-product-faq-tab/';

		// Load plugin environment variables
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= trailingslashit( plugin_dir_url( $this->file ) . 'assets' );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		WooCommerce_Product_FAQ_Tab::$plugin_prefix = $this->_base;
		WooCommerce_Product_FAQ_Tab::$plugin_basefile = $this->file;
		WooCommerce_Product_FAQ_Tab::$plugin_url = plugin_dir_url($this->file); 
		WooCommerce_Product_FAQ_Tab::$plugin_path = trailingslashit($this->dir);

		// register plugin activation hook
		
		//register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions
		
		$this->admin = new WooCommerce_Product_FAQ_Tab_Admin_API($this);

		// get premium options
		
		$this->title 		= get_option('wfaq_tab_title','FAQs');
		$this->priority 	= intval( get_option('wfaq_tab_priority',20) );
		$this->accordion 	= get_option('wfaq_enable_accordion','yes');
		$this->form 		= get_option('wfaq_enable_contact_form','yes');
		$this->showEmpty 	= get_option('wfaq_show_empty','yes');		

		/* Localisation */
		
		$locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-product-faq-tab');
		load_textdomain('wc_faq', WP_PLUGIN_DIR . "/".plugin_basename(dirname(__FILE__)).'/lang/wc_faq-'.$locale.'.mo');
		load_plugin_textdomain('wc_faq', false, dirname(plugin_basename(__FILE__)).'/lang/');
		
		add_action('woocommerce_init', array($this, 'init'));
			
		add_filter('woocommerce_get_sections_products',function($sections){
			
			$sections['rew-tabs'] = __( 'Tabs', 'woocommerce' );
			
			return $sections;
			
		},10,1);
			
		add_filter('woocommerce_product_settings', function( $settings ){
			
			global $current_section;

			if( $current_section == 'rew-tabs' ){
				
				return array();
			}
			
			return $settings;
			
		},9999999999);
			
		$this->woo_settings = array(
			
			array(
			
				'name' 	=> __( 'Product FAQ Tab', 'wc_faq' ),
				'type' 	=> 'title',
				'desc' 	=> '',
				'id' 	=> 'product_faq_tab'
			),
			array(  
				'name' => __('Tab Name', 'wc_faq'),
				'desc' 		=> __('The name of the tab in the product page', 'wc_faq'),
				'id' 		=> 'wfaq_tab_title',
				'type' 		=> 'text',
				'default'	=> __('FAQs', 'wc_faq'),
			),
			array(  
				'name' => __('Tab Position', 'wc_faq'),
				'desc' 		=> __('The position of the tab in the list', 'wc_faq'),
				'id' 		=> 'wfaq_tab_priority',
				'type' 		=> 'number',
				'default'	=> 20,
			),
			array(  
				'name' => __('Enable Accordion', 'wc_faq'),
				'desc' 		=> __('Enable Accordion for questions in the tab', 'wc_faq'),
				'id' 		=> 'wfaq_enable_accordion',
				'default' 	=> 'yes',
				'type' 		=> 'checkbox',
			),
			array(  
				'name' => __('Enable Contact Form', 'wc_faq'),
				'desc' 		=> __('Enable a contact form to receive questions from the users', 'wc_faq'),
				'id' 		=> 'wfaq_enable_contact_form',
				'default' 	=> 'yes',
				'type' 		=> 'checkbox',
			),
			array(  
				'name' => __('Show Empty Tab', 'wc_faq'),
				'desc' 		=> __('Recommended if the contact form is enabled', 'wc_faq'),
				'id' 		=> 'wfaq_show_empty',
				'default' 	=> 'yes',
				'type' 		=> 'checkbox',
			),
			array(
			
				'title' 		=> __( 'Header background color', 'wc_faq' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Default background color of the accordion header', 'wc_faq' ),
				'class' 		=> 'colorpick',
				'default' 		=> '#ccc',
				'id' 			=> 'wfaq_head_bkg_color'
			),
			array(
			
				'title' 		=> __( 'Header text color', 'wc_faq' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Default text color of the accordion header', 'wc_faq' ),
				'class' 		=> 'colorpick',
				'default' 		=> '#000',
				'id' 			=> 'wfaq_head_txt_color'
			),
			array(
			
				'title' 		=> __( 'Opened header background color', 'wc_faq' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Background color of the opened accordion header', 'wc_faq' ),
				'class' 		=> 'colorpick',
				'default' 		=> '#000',
				'id' 			=> 'wfaq_op_head_bkg_color'
			),
			array(
			
				'title' 		=> __( 'Opened header text color', 'wc_faq' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Default text color of the opened accordion header', 'wc_faq' ),
				'class' 		=> 'colorpick',
				'default' 		=> '#fff',
				'id' 			=> 'wfaq_op_head_txt_color'
			),			
			array(
				'title' 		=> __( 'Header border top color', 'wc_faq' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Color of the accordion header border', 'wc_faq' ),
				'class' 		=> 'colorpick',
				'default' 		=> '#f0f0f0',
				'id' 			=> 'wfaq_bd_top_color'
			),
			array(
				'type' 	=> 'sectionend',
				'id' 	=> 'product_faq_tab'
			),
		);
		
	} // End __construct ()

	/**
	 * Init WooCommerce Product FAQ Tab extension once we know WooCommerce is active
	 */
	public function init(){
		
		if( version_compare(WOOCOMMERCE_VERSION, $this->woo_version, '>=') ){ 
			
			// backend
			
			add_filter('plugin_row_meta', array($this, 'add_support_link'), 10, 2);
			
			// Settings
			
			add_action('woocommerce_get_settings_products', array($this, 'faq_admin_settings'),10,2);
			add_action('woocommerce_update_options_products_rew-tabs', array($this, 'save_faq_admin_settings'));			
			
			// Product options
			
			add_filter( 'woocommerce_product_data_tabs', array($this, 'custom_product_tabs') );
				
			if( version_compare(WOOCOMMERCE_VERSION, "2.6", '>=') ){
				
				add_filter( 'woocommerce_product_data_panels', array($this, 'faq_options_product_tab_content') ); // WC 2.6 and up
			}
			else{
				
				add_filter( 'woocommerce_product_data_tabs', array($this, 'faq_options_product_tab_content') ); // WC 2.5 and below
		
			}

			add_action( 'save_post_product', array($this, 'save_faq_option_fields')  );
			
			//frontend
			
			add_filter('woocommerce_product_tabs', array($this, 'faqs'));
			
			//handle question request
			
			if( !empty($_POST) && !empty($_POST['wfaq-submit']) ){
				
				$this->handle_question_request();
			}
		}
		else{		
			
			$this->notices->add_error('WooCommerce Product FAQ Tab '.__('requires at least <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce ' . $this->woo_version . '</a> in order to work. Please upgrade <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.', 'wc_faq'));			
		}
	}
	
	public function handle_question_request(){
		
		$email = $question = '';
		
		if( !empty($_POST['wfaq-email']) && !empty($_POST['wfaq-question']) ){
		
			$email = sanitize_email( $_POST['wfaq-email'] );
			
			$question = sanitize_textarea_field( $_POST['wfaq-question'] );
			
			$title = sanitize_text_field( urldecode($_POST['wfaq-title']) );
		
			//var_dump($question);exit;
		}
		
		if( empty($email) ){
			
			wc_add_notice(__('Please use a valid email', 'wc_faq'),'error');
		}
		elseif( empty($question) ){
			
			wc_add_notice(__('Please ask a question', 'wc_faq'),'error');
		}
		else{
			
			// send email
			
			$to = get_option('admin_email');
			
			$subject = 'Question about: ' . $title;
			
			$body = 'From: (' . $email . ')' .PHP_EOL ;
			$body .= '__________________' . PHP_EOL . PHP_EOL ;
			$body .= $question . PHP_EOL ;

			$mailer = WC()->mailer();

			$wrapped_message = $mailer->wrap_message($subject, $body);

			$wc_email = new WC_Email;

			$html_message = $wc_email->style_inline($wrapped_message);

			$mailer->send( $to, $subject, $html_message, HTML_EMAIL_HEADERS );
			
			wc_add_notice(__('Thanks for asking, we will answer you soon!', 'wc_faq'),'success');

            $pageURL = 'http';
			
            if ( ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ) || $_SERVER["SERVER_PORT"] == "443" ) {
                $pageURL .= "s";
            }
			
            $pageURL .= "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

			wp_redirect($pageURL);
			exit;
		}
	}
	
	// Adds a few settings to control the images in the tab.
	
	function faq_admin_settings( $settings, $current_section ){
		
		if( $current_section == 'rew-tabs' ){
			
			return array_merge($settings,$this->woo_settings);
		}
		
		return $settings;
	}

	function save_faq_admin_settings(){
		
		woocommerce_update_options($this->woo_settings);
	}
	
	/**
	 * Add a custom product tab.
	 */
	 
	public function custom_product_tabs( $tabs) {
		
		$tabs['faq'] = array(
		
			'label'		=> __( 'FAQs', 'wc_faq' ),
			'target'	=> 'faq_options',
			'class'		=> array( 'show_if_simple', 'show_if_variable'  ),
		);
		
		return $tabs;
	}
	
	/**
	 * Contents of the gift card options product tab.
	 */
	 
	function faq_options_product_tab_content() {
		
		global $post;
		
		// Note the 'id' attribute needs to match the 'target' parameter set above
		?><div id='faq_options' class='panel woocommerce_options_panel'><?php
			?><div class='options_group'>
			
			<?php
					
				$this->admin->display_field( array(
				
					'type'				=> 'question_answer',
					'id'				=> 'wfaq_items',
					'name'				=> 'wfaq_items',
					'description'		=> ''
					
				), $post );
			?>
			
			</div>

		</div><?php
	}
	
	/**
	 * Save the custom fields.
	 */
	 
	function save_faq_option_fields( $post_id ) {
		
		if( !empty($_POST['wfaq_items']) && is_array($_POST['wfaq_items']) && isset($_POST['wfaq_items']['question']) && isset($_POST['wfaq_items']['answer']) && count($_POST['wfaq_items']['question']) == count($_POST['wfaq_items']['answer']) ){
			
			$items = $_POST['wfaq_items'];
			
			if( !empty($items['answer']) ){
				
				foreach( $items['answer'] as $i => $answer ){
					
					$items['answer'][$i] = str_replace('src=\"../','src=\"/',$answer);
				}
			}
			
			update_post_meta( $post_id, 'wfaq_items', $items );
		}
	}
	
	/**
	 * Add links to plugin page.
	 */
	 
	public function add_support_link($links, $file){
		
		if(!current_user_can('install_plugins')){
			
			return $links;
		}
		
		if($file == WooCommerce_Product_FAQ_Tab::$plugin_basefile){
			
			$links[] = '<a href="https://code.recuweb.com" target="_blank">'.__('Docs', 'wc_faq').'</a>';
		}
		
		return $links;
	}
	
	public function get_product_faqs($product_id){
		
		$faqs = array();
		
		if( is_null($this->tab_data) ){
		
			$this->tab_data = get_post_meta( $product_id, 'wfaq_items', true );
		}
		
		if( !empty($this->tab_data['question']) ){
			
			foreach( $this->tab_data['question'] as $e => $question ){

				if( !empty($question) ){
					
					$answer = $this->tab_data['answer'][$e];
					
					$faqs[$question] = $answer;
				}
			}
		}
		
		return $faqs;
	}
	
	/**
	 * Write the images tab on the product view page for WC 2.0+.
	 * In WooCommerce these are handled by templates.
	 */
	public function faqs($tabs){
		
		global $post, $wpdb, $product;
		
		$faqs = $this->get_product_faqs( $post->ID );
		
		$countItems = count($faqs);

		if( $this->showEmpty == 'yes' || $countItems > 0 ){

			$tabs['faqs'] = array(
			
				'title'    => __($this->title, 'woocommerce-product-faq-tab').' ('.$countItems.')',
				'priority' => $this->priority,
				'callback' => array($this, 'faqs_panel')
			);
		}
		
		return $tabs;
	}

	/**
	 * Write the images tab panel on the product view page.
	 * In WooCommerce these are handled by templates.
	 */
	public function faqs_panel(){
		
		global $post, $wpdb, $product;
		
		$faqs = $this->get_product_faqs( $post->ID );

		/**
		 * Checks if any images are attached to the product.
		 */
		$countItems = count($faqs);

		if( $this->showEmpty == 'yes' || $countItems > 0  ){
			
			echo '<h2>' . __($this->title, 'wc_faq') . '</h2>';
		
			if( $this->form == 'yes' && $countItems > 0 ){
			
				echo '<a href="#ask-a-question" class="button wfaq-button" style="float:right;margin:0px 0 20px 0px;">Ask question</a>';
			}
		
			echo '<div id="wfaq_items" class="accordion-container wfaq-accordion">';

				$i=1;
				
				foreach( $faqs as $question => $answer ){
					
					echo '<div class="accordion-section' . ( $i==1 ? ' open' : '' ) . '">';
					
						echo '<h3 id="wfaq-section-'.$i.'" class="accordion-section-title wfaq-question" aria-expanded="' . ( $i==1 ? 'true' : 'false' ) . '">' . $question . '<span></span></h3>';
				
						echo '<div class="accordion-section-content wfaq-answer">' . $answer . '</div>';
					
					echo'</div>';
					
					++$i;
				}
				
			echo '</div>';
			
			if( $this->form == 'yes' ){
				
				echo'<div id="review_form_wrapper">';
					echo'<div id="review_form">';
						echo'<div id="respond" class="comment-respond">';
							
							echo'<form id="ask-a-question" action="" method="post">';
								
								echo'<h3 id="reply-title" class="comment-reply-title">'.__('Ask a question', 'wc_faq').'</h3>';
								
								echo'<p class="form-email">';	
								
									echo'<label for="email" style="display:block;">'.__('Your email', 'wc_faq').'</label>';
									
									$this->admin->display_field( array(
									
										'type'				=> 'text',
										'id'				=> 'wfaq-email',
										'name'				=> 'wfaq-email',
										'placeholder'		=> __('Email', 'wc_faq'),
										'description'		=> '',
										
									), false );
								
								echo'</p>';
								
								echo'<p class="form-question">';	
								
									echo'<label for="question">'.__('Your question', 'wc_faq').'</label>';
								
									$this->admin->display_field( array(
									
										'type'				=> 'textarea',
										'id'				=> 'wfaq-question',
										'name'				=> 'wfaq-question',
										'placeholder'		=> __('Question', 'wc_faq'),
										'description'		=> '',
										
									), false );
								
								echo'</p>';

								echo'<p class="form-submit">';							
								
									echo'<input name="wfaq-submit" type="submit" id="submit" class="submit" value="Submit">';
									echo'<input type="hidden" name="wfaq-title" value="' . urlencode($post->post_title) . '">';
								
								echo'</p>';
								
							echo'</form>';
							
						echo'</div>';
					echo'</div>';
				echo'</div>';
			}
		}
	}

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new WooCommerce_Product_FAQ_Tab_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new WooCommerce_Product_FAQ_Tab_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}
	
	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {

		wp_register_style( $this->_token . '-custom-style', false );
		wp_enqueue_style( $this->_token . '-custom-style' );
		wp_add_inline_style( $this->_token . '-custom-style', '

			.accordion-section .wfaq-answer{
				
				display:' . ( $this->accordion == 'yes' ? 'none' : 'block' ) . ';
			}

			.accordion-section.open .wfaq-answer{
				
				display:block;
			}

			.wfaq-accordion {
				display:inline-block;
				width:100%;
			}
			.wfaq-accordion h3 {
				cursor:' . ( $this->accordion == 'yes' ? 'pointer' : 'auto' ) . ';
				margin: 0;
				padding:10px;
				line-height:20px;
				min-height: 20px;
				display:block;
				text-decoration:none;
				text-transform:uppercase;
				font-size: 15px;
				font-weight:bold;
				border: '.get_option('wfaq_bd_top_color','#f0f0f0').' 1px solid;
				background: '.( $this->accordion == 'yes' ? get_option('wfaq_head_bkg_color','#ccc') : get_option('wfaq_op_head_bkg_color','#000') ).';
				color: '.( $this->accordion == 'yes' ? get_option('wfaq_head_txt_color','#000') : get_option('wfaq_op_head_txt_color','#fff') ).';
			}
			.wfaq-answer {
				border: none !important;
				display: block; 
				margin: 5px !important;
				line-height: 25px !important;
			}				
			.wfaq-accordion .accordion-open span {
				display:block;
				float:right;
				padding:10px;
			}				
			.wfaq-accordion .accordion-open {
				background: '.get_option('wfaq_op_head_bkg_color','#000').';
				color: '.get_option('wfaq_op_head_txt_color','#fff').';
			}
			.wfaq-accordion .accordion-open span {
				display:block;
				float:right;
				padding:10px;
			}
			/*
			.wfaq-accordion .accordion-open span {
				background:url(../images/minus.png) center center no-repeat;
			}
			.wfaq-accordion .accordion-close span {
				display:block;
				float:right;
				background:url(../images/plus.png) center center no-repeat;
				padding:10px;
			}
			*/
		');

	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		if( $this->accordion == 'yes' ){
			
			wp_register_script('rew-jquery-accordion', esc_url( $this->assets_url ) . 'js/jquery.accordion.js', array( 'jquery' ), $this->_version );
			wp_enqueue_script('rew-jquery-accordion' );
		}
		
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
		
		if( isset($_GET['page']) && $_GET['page'] == 'woocommerce-product-faq-tab' ){
		
			wp_register_style( $this->_token . '-simpleLightbox', esc_url( $this->assets_url ) . 'css/simpleLightbox.min.css', array(), $this->_version );
			wp_enqueue_style( $this->_token . '-simpleLightbox' );
		}		
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
		 
		if( isset($_GET['page']) && $_GET['page'] == 'woocommerce-product-faq-tab' ){
		
			wp_register_script( $this->_token . '-simpleLightbox', esc_url( $this->assets_url ) . 'js/simpleLightbox.min.js', array( 'jquery' ), $this->_version );
			wp_enqueue_script( $this->_token . '-simpleLightbox' );
		
			wp_register_script( $this->_token . '-lightbox-admin', esc_url( $this->assets_url ) . 'js/lightbox-admin.js', array( 'jquery' ), $this->_version );
			wp_enqueue_script( $this->_token . '-lightbox-admin' );			
		}		

	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'woocommerce-product-faq-tab', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
		
	    $domain = 'woocommerce-product-faq-tab';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()
	
	/**
	 * Main WooCommerce_Product_FAQ_Tab Instance
	 *
	 * Ensures only one instance of WooCommerce_Product_FAQ_Tab is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WooCommerce_Product_FAQ_Tab()
	 * @return Main WooCommerce_Product_FAQ_Tab instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		
		if ( is_null( self::$_instance ) ) {
			
			self::$_instance = new self( $file, $version );
		}
		
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()
}
