<?php 

/**
 * Eratags Helper Class contains form fields built-in wordpress functionalities like escalization and output sanitization 
 * 
 * @since 1.0  
 * @package  Social Coda => SCoda
 * @author eratags.com 
 * @version 1.0
 * @link http://eratags.com
 */

 if ( !class_exists( 'ScodaUI' ) ) {

	class ScodaUI extends EratagsUtil {

		/**
		 * 
		 * @var $instance Store values
		 */
		private static $instance;

		/** 
		 * @uses Restricts the instantiation of a class to one "single" instance
		 * 
		 * @return TagsElement  
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self; 
			}

			return self::$instance;

		}

		/**
		 * Register all stylesheet and js of admin pannel 
		 */
		private function __consturct() {
			
		}

		/**
		 * Display any form elements with escalization data
		 * 
		 * @param string $field_type
		 * @param array $attributes
		 * @param array $child_nodes
		 * 
		 * @return string $field 
		 */
		private function form_field( $field_type, $attributes = array(), $child_nodes = array() ) {

			// stored fields to return dom element 
			$field 	   = ''; 
			$collected = array();
			$text 	   = '';

			// Handle value attribute if current element is textarea 
			if ( $field_type === 'textarea' ) {
				
				if ( isset( $attributes['value'] ) ) {
					
					$text = $attributes['value'];
					unset( $attributes['value'] );
				
				}

			}

			// Setup Attributes inside array  
			if ( count( $attributes ) ) {

				foreach ( $attributes as $key => $value ) {
					
					$collected [] = $key . '="' . esc_attr( $value ) . '"';

				}

			}

			// setup dom elements 
			switch ( $field_type ) {
				
				case 'input':
					$field = sprintf( '<input %1$s/>', implode( ' ', $collected ) );
					break;

				case 'textarea':
					$field = sprintf( '<textarea %1$s>%2$s</textarea>', implode( ' ', $collected ), esc_textarea( $text ) );
					break;

				case 'select':
					
					// Build select element options 
					$options = array();
					
					if ( count( $child_nodes ) ) {
						
						foreach ( $child_nodes as $opts_node ) {
							
							$text 		  = '';
							$option_array = array();

							foreach ( $opts_node as $nkey => $nvalue ) {

								if ( is_integer( $nkey ) ) {
									
									$option_array[] = esc_attr( $nvalue );

								} else {

									if ( $nkey === 'text' ) {
										$text = esc_html( $opts_node['text'] );
									} else {
										$option_array[] = $nkey . '="' . esc_attr( $nvalue ) . '"';
									}

								}

							}
							
							$options[] = sprintf( '<option %1$s>%2$s</option>', implode( ' ',  $option_array ), $text );

						}

					}
					
					$field = sprintf( '<select %1$s>%2$s</select>', implode( ' ', $collected ), implode('', $options ) );
					break;

				default:
					$field 	   = '';
					break;
			}
			

			// print form tag with attributes 
			return $field;

		}

		/**
		 * @uses form_field
		 * to print input tag element 
		 * 
		 * @param array $atts
		 * 
		 * @return $field of input element 
		 */
		public function input( $atts = array() ) {
			
			return $this->form_field( 'input', $atts );

		}

		/**
		 * @uses form_field
		 * to print textarea tag element 
		 * 
		 * @param array $atts
		 * 
		 * @return $field of textarea element 
		 */
		public function textarea( $atts = array() ) {

			return $this->form_field( 'textarea', $atts );
		
		}

		/**
		 * @uses form_field
		 * to print select tag element 
		 * 
		 * @param array $atts 
		 * @param array $child_nodes 
		 * 
		 * @return $field of select element 
		 */
		public function select( $atts, $child_nodes  ) {

			return $this->form_field( 'select', $atts, $child_nodes );
		
		}
		
		/**
		 * @uses External links like a button send users to helpful links ,etc
		 * 
		 * @var $args need a Localization with wp built-in __()  callback from outside
		 * @param $args array contains ( title - details - icon  - link ( text - url ) )
		 * 
		 * @return string render html elements 
		 */
		protected function box( $args ) {
			
			if ( ! is_array( $args ) ) {
				return;
			}

			$title 	 	 = '';
			$details 	 = '';
			$icon_class	 = '';
			$link 	 	 = array(
				'text' => '',
				'url' => ''
			);

			if ( isset( $args['title'] ) ) {
				$title = $args['title'] ;
			}

			if ( isset( $args['details'] ) ) {
				$details = $args['details'];
			}

			if ( isset( $args['link'] ) ) {
				
				if ( $args['link']['text'] ) {
					$link['text'] = $args['link']['text'];
				}

				if ( $args['link']['url'] ) {
					$link['url'] = $args['link']['url'];
				}

			}

			if ( isset( $args['icon'] ) ) {
				$icon_class = $args['icon'];
			}

			$render = array(

				// External links
				'<div class=\'scoda-link-box\'>',
					
					// Container of icon and text  
					'<div class=\'scoda-flexbox\'>',
						
						// Icon Container
						'<div class=\'scoda-resource-icon\'>',
							sprintf( '<i class=\'%1$s\'></i>', esc_attr( $icon_class ) ),
						'</div>',

						// Texts Container 
						'<div class=\'scoda-resource-texts\'>',
							sprintf( '<h3>%1$s<h3>', esc_html( $title ) ),
							sprintf( '<p>%1$s</p>', esc_html( $details ) ),
						'</div>',

					'</div>',

					// Button Container 
					sprintf( '<a href=\'%1$s\'>%2$s</a>', esc_url( $link['url'] ), esc_html( $link['text'] ) ),

				'</div>'

			);

			return implode( "\n", $render );
			
		}

		/**
		 * @uses Panel
		 * 
		 * @return string html markup
		 */
		protected function tabs() {
			
			// Get Submenu items with their links 
			global $submenu;

			$scoda_subitems = $submenu['eratags-scoda-options'];
			$all_tabs 	    = array();
			$page_slug 		= isset( $_GET['page'] )? $_GET['page']: '';

			// Setup needed array for tabs
			foreach ( $scoda_subitems as $scoda_smenu ) {

				$sub_menu = array(
					'text' 		=> $scoda_smenu[0],
					'link'	 	=> add_query_arg( array( 'page' => $scoda_smenu[2] ), admin_url( 'admin.php' ) ),
					'is_active' => ( $page_slug === $scoda_smenu[2] ) ? 'active': ''
				);

				$all_tabs[] = $sub_menu; 

			}

			// Build getting started page
			$render = array(

				// Tabs and plugin information 
				'<div class=\'scoda-info-tabs\'>',

					//- Plugin Infotmation 
					'<div class=\'scoda-flexbox\'>',
						
						// Logo 
						'<div>',
							
							sprintf( 
								'<img src=\'%1$s\'  class=\'scoda-logo-image\'  alt=\'Social Coda\'  loading=\'lazy\' />',
								esc_attr( SCODA_URL . 'admin/assets/img/logo.black@75.png' )
							),
							
							sprintf(
								'<span>%1$s</span>',
								esc_html__( 'Scoda V', 'SOCIAL-CODA' ) . SCODA_VERSION
							),

						'</div>',

						// Details
						'<div class=\'details\'>',
							'<h2>' . esc_html__( 'Welcome To Social Coda', 'SOCIAL-CODA' ) . '</h2>',
							'<p>' . esc_html__( 'SocialCoda let you see social counters and social feeds also automatically post all your content to several different social networks in one time and social share buttons, share to Facebook, WhatsApp, Messenger, Twitter, Instagram, Tumblr and much more', 'SOCIAL-CODA') . '</p>',
						'</div>',

					'</div>',

					//- Plugin Taps
					'<div class=\'scoda-tabs\'>',
						$this->tab_lists( $all_tabs ),
					'</div>',

				'</div>',
				
			);

			// render markup of getting started page 
			echo implode( "\n", $render );
		}

		/**
		 * @uses Panel Tabs
		 * 
		 * @return string html markup
		 */
		public function tab_lists( $tabs = array() ) {

			if ( !count( $tabs ) ) {
				return;
			}
			
			$lists  = array(); 

			foreach ( $tabs as $tab ) {
				
				$text 	= esc_html( $tab['text'] );
				$link 	= esc_url( $tab['link'] );
				$active = esc_attr( $tab['is_active' ]);

				$lists[] = sprintf( '<li class=\'%1$s\'><a href=\'%2$s\'>%3$s</a></li>', $active, $link, $text );
			}

			$tabs = array(
					'<ul>',
						implode( "\n", $lists ),
					'</ul>'
			);

			return implode( "\n", $tabs );
			
		}

		/**
		 * @uses Help Container
		 * 
		 * @return string html markups 
		 */
		protected function help_container() {
			
			// Help Text 
			$this->help_texts();

			// Build resources 
			$render = array(
				
				'<div class=\'scoda-flexbox enable-media-sm enable-media-md scoda-center-flexbox scoda-resources-boxes\'>',
					'<div class=\'scoda-grids-1 scoda-p-10\'>' . $this->submit_ticket() . '</div>',
					'<div class=\'scoda-grids-1 scoda-p-10\'>' . $this->documentation() . '</div>', 
				'</div>'

			);

			echo implode( "\n", $render );
		}

		/**
		 * @uses Offering a free support for 6 months  
		 * 
		 * @return string html markups 
		 */
		protected function help_texts() {
			
			$render = array(
				'<div class=\'scoda-helper-texts\'>',
					'<h3>' . esc_html__( 'Need help? We\'re here', 'SOCIAL-CODA') . '</h3>',
					'<p>' . esc_html__( 'The Scoda Plugin comes with 6 months of free support for every license you purchase. Support can be extended through subscriptions via CodeCanyon. Below are all the resources we offer in our support center.', 'SOCIAL-CODA' ) . '</p>',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * @uses Online Documentation Link
		 * 
		 * @return string html markups
		 */
		protected function documentation() {

			$args = array(
				'title'	 	=> __( 'Online Documentation' ),
				'details'	=> __( 'Please read the documentation for Scoda' ),
				'icon'		=> 'dashicons dashicons-media-document',
				'link'		=> array( 'text' => __( 'Visit This Page' ), 'url' => '#' )
			);

			return $this->box( $args );
			
		}

		/**
		 * @uses Subscribe use box 
		 * 
		 * @return string html markups
		 */
		protected function subscribe() {
		
			$render = array(

				// External links
				'<div class=\'scoda-link-box\'>',
					
					// Container of icon and text  
					'<div class=\'scoda-flexbox enable-columns\'>', 

						// Texts Container 
						'<div class=\'scoda-resource-texts scoda-remove-spaces\'>',
							sprintf( '<h3>%1$s<h3>', esc_html( 'Subscribe to updates' ) ),
							sprintf( '<p>%1$s</p>', esc_html( 'Sign up for the newsletter to be the first to know about news and discounts.' ) ),
						'</div>',

						'<div class=\'scoda-wrap\'>',
							
						// esc_html and esc_attr inside callback

							$this->input(array(
								'class'		  => 'scoda-subscribe-inputs',
								'placeholder' => __( 'Your Name', 'SOCIAL-CODA')
							)),

							$this->input(array(
								'class'		  => 'scoda-subscribe-inputs',
								'placeholder' => __( 'Your Name', 'SOCIAL-CODA')
							)),

						'</div>',

					'</div>',

					// Button Container 
					sprintf( '<a>%1$s</a>', esc_html( 'Subscribe' ) ),

				'</div>'

			);

			echo implode( "\n", $render );

		}

		/**
		 * @uses submit an online ticket 
		 * 
		 * @return string html markups
		 */
		protected function submit_ticket() {
			
			$args = array(
				'title'	 	=> __( 'Submit a Ticket' ),
				'details'	=> __( 'Direct help from our qualified support team' ),
				'icon'		=> 'dashicons dashicons-sos',
				'link'		=> array( 'text' => __( 'Submit a Ticket' ), 'url' => '#' )
			);

			return $this->box( $args );

		}

		/**
		 * @uses List arrays in tables
		 * 
		 * @return string html markups 
		 */
		private function array_lists( $panel_title, $args = array(), $expanded_col = 'value' ) {
			
			if ( ! count( $args ) ) {
				return;
			}

			$render   = array();

			echo '<h3 class=\'scoda-table-title\'>' . esc_html( $panel_title ) . '</h3>';

			$render[] = '<ul class=\'scoda-table-list\'>';

			foreach ( $args as $row ) {

				$compatible_icon_class = 'scoda-expand-col';
				

				$render[] = '<li class=\'scoda-flexbox\'>';
				foreach ( $row as $key => $value ) { 
				
					$real_class = ( $key === $expanded_col )? 'scoda-grow-1': 'scoda-shrink-0 scoda-min-width-x scoda-plr-10'; 
					
					if ( is_bool( $value ) && 'is_compatible' === $key ) {
					
						if ( $value ) {
							$compatible_icon_class = 'dashicons dashicons-yes scoda-set-correct-icon';
						} else {
							$compatible_icon_class = 'dashicons dashicons-no-alt scoda-set-correct-icon';
						}
						
						$render[] 	= sprintf( '<span class=\'%1$s\'></span>', esc_attr( $compatible_icon_class ) );
					
					} else {
						$render[] 	= sprintf( '<span class=\'%1$s\'>%2$s</span>', esc_attr( $real_class ), $value );
					}

					
				
				}
				
				$render[] = '</li>';

			}

			$render[] = '</ul>';

			echo implode( "\n", $render );
		} 

		/**
		 * @uses Getting Server operating system
		 * 
		 * @return string html markups
		 */
		protected function system_status() {
			
			$curl 		= extension_loaded( 'curl' );
			$title 		= esc_html__( "System Status", "SOCIAL-CODA" );
			$array_info = array(
				
				array(
					'name' 				=> esc_html__( 'Operating System', 'SOCIAL-CODA' ),
					'value' 			=> PHP_OS, // Server operating system
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'Software', 'SOCIAL-CODA' ),
					'value' 			=> $_SERVER['SERVER_SOFTWARE'],
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'PHP Version', 'SOCIAL-CODA' ),
					'value' 			=> PHP_VERSION,
					'is_compatible'		=> true
				),

				array(
					'name' 				=> esc_html__( 'MYSQL Version', 'SOCIAL-CODA' ),
					'value' 			=> $this->get_mysql_version(),
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'cURL Installed', 'SOCIAL-CODA' ),
					'value' 			=> $curl ? esc_html__( 'Yes', 'SOCIAL-CODA' ): sprintf('<i class=\'scoda-red-highlight-color\'>%1$s</i>', esc_html__( 'Please Install CURL Extension', 'SOCIAL-CODA' ) ),
					'is_compatible'		=> $curl ? true : false
				)

			); 

			return $this->array_lists( $title, $array_info, 'value' );

		}

		/**
		 * @uses Display info table of current wordpress software
		 * 
		 * @return string html markups 
		 */
		protected function wp_environment() {
			
			 
			$title 		= esc_html__( "WordPress Environment", "SOCIAL-CODA" );
			global $wp_version;

			$array_info = array(
				
				array(
					'name' 				=> esc_html__( 'Language', 'SOCIAL-CODA' ),
					'value' 			=> get_bloginfo( 'language' ), // Server operating system
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'Software Version:', 'SOCIAL-CODA' ),
					'value' 			=> $wp_version,
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'Site URL', 'SOCIAL-CODA' ),
					'value' 			=> site_url(),
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'Max Upload Size', 'SOCIAL-CODA' ),
					'value' 			=> size_format( wp_max_upload_size() ),
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'Memory Limit', 'SOCIAL-CODA' ),
					'value' 			=> ini_get( 'memory_limit' ),
					'is_compatible'		=> ''
				),

				array(
					'name' 				=> esc_html__( 'Debug Mode', 'SOCIAL-CODA' ),
					'value' 			=> WP_DEBUG ? esc_html__( 'Active', 'SOCIAL-CODA' ) : esc_html__( 'Inactive', 'SOCIAL-CODA' ),
					'is_compatible'		=> ''
				),				 

			); 

			return $this->array_lists( $title, $array_info, 'value' );

		}
		
		/**
		 * @uses list panel tabs 
		 * 
		 * @return string html markups
		 */
		protected function panel_tabs( $tabs, $panel_name = null, $selector = '' ) {

			$all_tabs = array();

			if ( $selector ) {
				$selector = ' ' . $selector;
			}

			foreach ( $tabs as $tab ) {
				$all_tabs[] = sprintf( '<li><a href=\'#%1$s\'><i class=\'dashicons %2$s\'></i><span>%3$s</span></a></li>', esc_attr( $tab['hash'] ), esc_attr( $tab['icon'] ), esc_html( $tab['text'] ) );
			}

			$headtitle = '';
			if ( !is_null( $panel_name ) ) {
				$headtitle = sprintf( '<h5 class=\'scoda-tab-title\'>%1$s</h5>', esc_html( $panel_name ) );
			}

			$render = array(  
				'<div class=\'scoda-wrap\'>',
					$headtitle,
					'<ul class=\'scoda-tab-lists' . $selector . '\'>',
						implode( "\n", $all_tabs ),
					'</ul>',
				'</div>'
			);

			// remove empty space 
			if ( in_array( '', $render ) ) {
				 unset( $render[1] );
			}

			echo implode( "\n", $render );

		}

		/**
		 * Display Panel Taps
		 * 
		 * @return string html markups
		 */
		protected function add_option_panel_tabs( $panel_name = null ) {

			$selector = 'fired-tabs';

			$tabs 	  = array(
				array(
					'hash'	=> 'scoda-dashboard',
					'text'	=> esc_html__( 'Dashboard', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-dashboard'
				),
				array(
					'hash'	=> 'scoda-networking',
					'text'	=> esc_html__( 'Networks', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-networking'
				),
				array(
					'hash'	=> 'scoda-social-counters',
					'text'	=> esc_html__( 'Social Counters', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-calculator'
				),
				array(
					'hash'	=> 'scoda-social-feeds',
					'text'	=> esc_html__( 'Social Feeds', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-welcome-widgets-menus'
				),
				array(
					'hash'	=> 'scoda-auto-publish',
					'text'	=> esc_html__( 'Social Auto Poster', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-format-aside'
				),
				array(
					'hash'	=> 'scoda-share-options',
					'text'	=> esc_html__( 'Share Options', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-share'
				),
			);

			return $this->panel_tabs( $tabs, $panel_name, $selector );
		}

		/**
		 * Disaply Helpful link tabs grouped in one tab 
		 * 
		 * @return string html markups
		 */
		protected function add_helpful_panel_tabs( $panel_name = null ) {
 
			$tabs 	  = array(
				array(
					'hash'	=> '#',
					'text'	=> esc_html__( 'Documentation', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-media-document'
				),
				array(
					'hash'	=> '#',
					'text'	=> esc_html__( 'Open a new ticket', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-sos'
				),
				array(
					'hash'	=> '#',
					'text'	=> esc_html__( 'Rate Social Coda', 'SOCIAL-CODA' ),
					'icon'	=> 'dashicons-star-empty'
				) 
			);

			return $this->panel_tabs( $tabs, $panel_name );
		}

		/**
		 * @uses creating and display panel logo 
		 * 
		 * @return string html markups
		 */
		protected function add_panel_logo() {

			$imgsrc = SCODA_URL . 'admin/assets/img/logo.black@120.png'; 

			$render = array(  
				'<div class=\'scoda-flexbox scoda-tab-head-contents\'>',
					
					'<div>',
						sprintf( '<img class=\'scoda-panel-logo\' src=\'%1$s\' alt=\'Scoda Logo\'/>', esc_attr( $imgsrc ) ),
					'</div>',

					'<div>',
						sprintf( '<h3>%1$s</h3>', esc_html__( 'Social Coda', 'SOCIAL-CODA' )),
						sprintf( '<span>%1$s %2$s</span>', esc_html__( 'Version', 'SOCIAL-CODA' ), SCODA_VERSION ),
					'</div>',

				'</div>'
			);

			echo implode( "\n", $render );

		}

		/**
		 * Display SVG Animated Preloader before load plugin admin dashboard
		 * 
		 * @return string html markups 
		 */
		protected function svg_preload() {
			$render = array(
				'<div class="scoda-preloader">',
					'<div class="loader loader--style4" title="3">',
						'<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 24 24" style="enable-background:new 0 0 50 50;" xml:space="preserve">',
							
							'<rect x="0" y="0" width="3" height="10" fill="#2F3556">',
								'<animateTransform  attributeType="xml" attributeName="transform" type="scale" values="1,1; 1,3; 1,1" begin="0s" dur="0.6s" repeatCount="indefinite" />',
							'</rect>',

							'<rect x="10" y="0" width="3" height="10" fill="#2F3556">',
								'<animateTransform  attributeType="xml" attributeName="transform" type="scale" values="1,1; 1,3; 1,1" begin="0.2s" dur="0.6s" repeatCount="indefinite" />',
							'</rect>',

							'<rect x="20" y="0" width="3" height="10" fill="#2F3556">',
								'<animateTransform  attributeType="xml"  attributeName="transform" type="scale" values="1,1; 1,3; 1,1" begin="0.4s" dur="0.6s" repeatCount="indefinite" />',
							'</rect>',

						'</svg>',
					'</div>',
				'</div>'
			);

			echo implode( "\n", $render );
		}

		/**
		 * To Disply Dashboard Page Options 
		 */
		protected function add_dashboard_page() {
			
			$id = 'scoda-dashboard-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'<h1>Dashboard Page</h1>',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * To Display Social Counter Settings Page 
		 */
		protected function add_social_counter_page() {
			
			$id = 'scoda-social-counters-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'<h1>Social Counter</h1>',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * To Display Networking Settings Page 
		 */
		protected function add_networking_settings_page() {
			
			$id 	  	= 'scoda-networking-content';  
			$network_ui = array();
			$networks 	= $this->get_option( 'networks' );

			if ( count( $networks ) ) {
				
				foreach ( $networks as $network ) {
					/*
					$build_setting_url = add_query_arg(
						array(
							'page' => 'eratags-scoda-options#' . $network['tab_slug']
						),
						admin_url( 'admin.php' )
					);
					*/

					$li = array(
						sprintf('<li data-network=\'%1$s\'>', esc_attr($network['slug']) ),
							sprintf( '<div class=\'scoda-%1$s scoda-network-layer\'>', esc_attr( $network['slug'] ) ),
								
								sprintf( '<div class=\'scoda-icon-contents\'><span class=\'scoda-%1$s-bg %2$s\'></span><span class=\'\'>%3$s</span></div>', esc_attr( $network['slug']  ),  esc_attr( $network['icon']  ), esc_html( $network['title']  ) ),
								sprintf( '<a href=\'#%1$s\'><span class=\'\'>' .  esc_html__( 'Settings', 'SOCIAL-CODA' ). '</span></a>', $network['tab_slug'] ),
								
							'</div>',
						'</li>'
					);
	
					$network_ui[] = implode( "\n", $li );
				}
				 
				$ul_arr = array(
					'<ul id=\'scoda-sortable-ui\' class=\'scoda-flexbox scoda-same-sizes enable-wrap scoda-max-2-grids scoda-social-setting-tabs enable-media-sm fired-tabs\'>',
						implode( "\n", $network_ui ),
					'</ul>',
				);
			} else {
				$ul_arr = array(
					'<p class=\'scoda-error-no-networks\'>',
						esc_html( 'We have no networks please unsintall the scoda plugin and try to install it again .' ),
					'</p>',
				);
			}

			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					
					'<div class=\'socda-wrap scoda-head-tab-title\'>',
						'<h3>Networks Settings</h3>',
					'</div>',
					
					'<div class=\'scoda-wrap scoda-minus-5\'>',
						implode( "\n", $ul_arr ),
					'</div>',

				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * Display Network Switcher 
		 */
		protected function network_switcher() {
			
			$render = array(
				'<div class=\'scoda-wrap scoda-hide-all scoda-network-switcher-block\'>',
					'<h4>',
						esc_html__( 'Network Switcher', 'SOCIAL-CODA' ),
					'</h4>',
					'<select class=\'scoda-network-switcher\'></select>',
				'</div>'
			);

			echo implode( "\n", $render );

		}

		/**
		 * To Display Facebook Settings
		 */
		protected function add_facebook_setting_page() {
			
			$id = 'scoda-networks-facebook-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'facebook setting page',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * To Display Facebook Settings
		 */
		protected function add_twitter_setting_page() {
			
			$id = 'scoda-networks-twitter-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'twitter setting page',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * To Display Social Feed Settings Page
		 */
		protected function add_social_feeds_page() {
			
			$id = 'scoda-social-feeds-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'<h1>Social Feed</h1>',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * TO Display Auto Poster Settings Page
		 */
		protected function add_auto_poster_page() {
			
			$id = 'scoda-auto-publish-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'<h1>Auto Poster</h1>',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * To Display Share Settings Page
		 */
		protected function add_share_option_page() {
			
			$id = 'scoda-share-options-content'; 


			$render = array(
				'<div class=\'scoda-hide-all\' id=\'' . $id . '\'>',
					'<h1>Share Options</h1>',
				'</div>'
			);
			
			echo implode( "\n", $render );

		}

		/**
		 * it prints copyright of plugin 
		 */
		protected function copyright() {
		 	echo sprintf( esc_html__( 'Copyright ©%1$s Eratags.com', 'SOCIAL-CODA' ), date( 'Y' ) );
		}
		
	}

 }

