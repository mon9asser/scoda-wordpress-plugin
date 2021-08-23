<?php 

if ( !class_exists( 'ScodaAdminUI' ) ) {
	
	class ScodaAdminUI extends ScodaUI {
		
		/**
		 * 
		 * @var $instance Store values
		 */
		private static $instance;

		/** 
		 * @uses Restricts the instantiation of a class to one "single" instance
		 * 
		 * @return ScodaAdminUI  
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self; 
			}

			return self::$instance;

		} 

		/**
		 * Class Constructor 
		 */ 
		private function __construct() {}

		/**
		 * @uses Render Scoda Admin Panel and manage plugin options 
		 * Getting executed from another methods 
		 * 
		 */
		public function render() {
			
			// Render Scoda Admin Styles and scripts 
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Render Scoda Admin Menu Elements
			add_action( 'admin_menu', array( $this, 'load_scoda_menus' ) );
			
			// Render Scoda Ajax request  
			if ( is_admin() ) {
				add_action( 'wp_ajax_nopriv_sorting_scoda_social_networks', array( $this, 'sorting_networks_callback' ) );
				add_action( 'wp_ajax_sorting_scoda_social_networks', array( $this, 'sorting_networks_callback' ) );
			}

		}

		/**
		 * @uses to include javascript and stylesheets of admin panel 
		 */
		public function enqueue_scripts() {
			
			// Load All Stylesheets 
			wp_register_style( 'scoda-admin-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), SCODA_VERSION, 'all' );
			wp_register_style( 'scoda-admin-awesomefonts', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), SCODA_VERSION, 'all' );
			wp_register_style( 'scoda-admin-styles', SCODA_URL . 'admin/assets/css/styles.css', array(), SCODA_VERSION, 'all' );
			wp_register_style( 'scoda-social-styles', SCODA_URL . 'assets/css/social-colors.css', array(), SCODA_VERSION, 'all' );

			// Load All Scripts sortable.min.js
			wp_register_script( 'scoda-admin-select2-scripts', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array(), SCODA_VERSION, true );
			wp_register_script( 'scoda-admin-scripts', SCODA_URL . 'admin/assets/js/scripts.js', array( 'jquery' ), SCODA_VERSION, true );

			// To Localize Scripts
			wp_localize_script( 'scoda-admin-scripts', 'scoda_obj', array(
				'ajax_url'	=> admin_url( 'admin-ajax.php' ),
				'nonce' 	=> wp_create_nonce( 'scoda-set-secured-data' ),
				'networks'	=> $this->get_option( 'networks' )
			));

			// Enqueue all stylesheets 
			wp_enqueue_style( 'scoda-admin-select2-css' ); 
			wp_enqueue_style( 'scoda-admin-awesomefonts' );
			wp_enqueue_style( 'scoda-social-styles' );
			wp_enqueue_style( 'scoda-admin-styles' );


			// To Avoid any conflicts with other plugins ( Scripts )
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'scoda-admin-select2-scripts' );
			wp_enqueue_script( 'scoda-admin-scripts' ); 

		}

		/**
		 * @uses Saving the new result of sort network 
		 */
		public function sorting_networks_callback() {
			
			// Verify Nonce
			if ( !isset( $_POST['secure'] ) || !wp_verify_nonce( $_POST['secure'], 'scoda-set-secured-data' ) ) {
				return;
			}

			// basic networks 
			$networks = $this->get_option( 'networks' );

			// its time work with sorting our array 
			if ( $_POST['networks'] ) {
				
				$sortd 	= array();
				$values = $_POST['networks'];

				for ( $i=0; $i < count( $values ); $i++ ) { 
					
					foreach( $networks as $netwrk ) {
						
						if ( $netwrk['slug'] === $values[$i] ) {
							$sortd[] = $netwrk;
						}

					}
					
				}

				// its time to update the new sorted data 
				$result = $this->set_option( 'networks', $sortd );
				
				// Send Successful result 
				wp_send_json_success( $result );
			}
			
			// send error by default if we didn't send a success results 
			wp_send_json_error(__('Something went wrong try later'));
			  	
		}

		/**
		 * @uses Building and apply admin pages for our plugin 
		 */
		public function load_scoda_menus() {

			// Needed Givens
			$page_title		= esc_html__( 'Social Coda Options', 'SOCIAL-CODA' ); 
			$menu_title		= esc_html__( 'Social Coda', 'SOCIAL-CODA' );
			$capability		= 'manage_options';
			$menu_slug		= 'eratags-scoda-options';
			$function		= array( $this, 'scoda_plugin_options_page_callback' ); 
			$icon_url		= SCODA_URL . 'admin/assets/img/logo.light@25.png'; 
			$position		= 99;
			$submenu_pages	= array(
				

				// Plugin Options Page 
				array(
					'parent_slug' 	=> $menu_slug,
					'page_title' 	=> $page_title,
					'menu_title'	=> esc_html__( 'Plugin Options', 'SOCIAL-CODA' ),
					'capability'	=> $capability,
					'slug'			=> $menu_slug,
					'callback'		=> $function
				),

				// Getting started Page 
				array(

					'parent_slug' 	=> $menu_slug,
					'page_title' 	=> esc_html__( 'Scoda Getting Started', 'SOCIAL-CODA' ),
					'menu_title'	=> esc_html__( 'Getting Started', 'SOCIAL-CODA' ),
					'capability'	=> $capability,
					'slug'			=> 'eratags-scoda-getting-started',
					'callback'		=> array( $this, 'scoda_plugin_getting_page_callback' )

					
				),

				// System Status Page 
				array(
					'parent_slug' 	=> $menu_slug,
					'page_title' 	=> esc_html__( 'Scoda System Status', 'SOCIAL-CODA' ),
					'menu_title'	=> esc_html__( 'System Status', 'SOCIAL-CODA' ),
					'capability'	=> $capability,
					'slug'			=> 'eratags-scoda-system-status',
					'callback'		=> array( $this, 'scoda_system_status_page_callback' )
				)

			);
			
			// Scoda Admin Menu ( Main Directory )
			add_menu_page( 
				$page_title, 
				$menu_title, 
				$capability, 
				$menu_slug, 
				$function, 
				$icon_url, 
				$position 
			);

			// Submenu Pages
			foreach ( $submenu_pages as $curr_submenu ) {

				add_submenu_page(
					$curr_submenu['parent_slug'],
					$curr_submenu['page_title'],
					$curr_submenu['menu_title'],
					$curr_submenu['capability'],
					$curr_submenu['slug'],
					$curr_submenu['callback'] 
				);
				
			}

		}
		
		/**
		 * @uses Getting Started Callback
		 */
		public function scoda_plugin_getting_page_callback() {
			
			echo '<div class=\'wrap\'>';
			
			// Get Tabs
			$this->tabs();

			// Help Container 
			$this->help_container();

			echo '</div>';
		}

		/**
		 * @uses System Status Callback
		 */
		public function scoda_system_status_page_callback() {

			echo '<div class=\'wrap\'>';
				
				echo '<div class=\'scoda-wrap\'>';
					$this->tabs();
				echo '</div>';

				echo '<div class=\'scoda-wrap\'>';
					
					echo '<div class=\'scoda-row scoda-mlr-20-minus\'>';
						
						echo '<div class=\'scoda-col-md-8\'>';

							echo '<div class=\'scoda-wrap scoda-pb-space scoda-plr-20\'>';
							
								// Generate system status
								$this->system_status();

							echo '</div>';

							echo '<div class=\'scoda-wrap scoda-pb-space scoda-plr-20\'>';
							
								// Wp Environment
								$this->wp_environment();

							echo '</div>';

						echo '</div>';

						echo '<div class=\'scoda-col-md-4\'>';
							
							echo '<div class=\'scoda-sticky scoda-t-20\'>';
						
								echo '<div class=\'scoda-wrap scoda-plr-20 scoda-pb-space\'>';
									echo $this->subscribe();
								echo '</div>';

								echo '<div class=\'scoda-wrap scoda-plr-20 scoda-pb-space\'>';
									echo $this->documentation();
								echo '</div>';

							echo '</div>';
							
						echo '</div>';

					echo '</div>';

				echo '</div>';

			echo '</div>';

		}	 

		/**
		 * @uses Our Plugin Options Callback
		 */
		public function scoda_plugin_options_page_callback() {

			echo '<div class=\'wrap\'>';
				
				echo '<div class=\'scoda-wrap scoda-panel\'>'; 

					// Preloading Panels
					$this->svg_preload();

					// Sidebar list of panel tabs 
					echo '<div class=\'scoda-panel-tabs\'>';
				 		
						// Add Scoda Logo 
						$this->add_panel_logo();

						// Set Panel Tabs 
						$this->add_option_panel_tabs( 'General Options' );
						
						// Set Panel Tabs 
						$this->add_helpful_panel_tabs( 'Helpful Links' );

					echo '</div>';
										
					// Panel Options 
					echo '<div class=\'scoda-panel-contents\'>';
						
						// Networking Settings
						$this->add_networking_settings_page();

						// Plugin Dashboard
						$this->add_dashboard_page();

						// Social Counter 
						$this->add_social_counter_page();

						// Social Feed Page
						$this->add_social_feeds_page();

						// Auto Poster Page
						$this->add_auto_poster_page();

						// Share Options 
						$this->add_share_option_page();
						
						// Network Switcher 
						$this->network_switcher();

						// List all social networks here 
						$this->add_facebook_setting_page(); # Facebook
						$this->add_twitter_setting_page();	# Twitter

					echo '</div>';

					// Copyright of eratags
					echo '<div class=\'scoda-add-copyright-bottom\'>';
					$this->copyright();
					echo '</div>';

				echo '</div>';
			
			echo '</div>';
			
		}
		
		/**
		 * @uses setup networks in our database 
		 * "instagram", "facebook", "twitter", "tumblr", "linked-in", "tiktok"
		*/
		public function setup_networks() {
		
			$networks = array(

				array(
					'icon' 		=> sanitize_text_field( 'fab fa-facebook' ),
					'title'		=> __( 'Facebook' ),
					'slug'		=> sanitize_text_field( 'facebook' ),
					'tab_slug'	=> sanitize_text_field( 'scoda-networks-facebook' )
				),
				array(
					'icon' 		=> sanitize_text_field( 'fab fa-twitter' ),
					'title'		=> __( 'Twitter' ),
					'slug'		=> sanitize_text_field( 'twitter' ),
					'tab_slug'	=> sanitize_text_field( 'scoda-networks-twitter' ) 
				),
				array(
					'icon' 		=> sanitize_text_field( 'fab fa-instagram' ),
					'title'		=> __( 'Instagram' ),
					'slug'		=> sanitize_text_field( 'instagram' ),
					'tab_slug'	=> sanitize_text_field( 'scoda-networks-instagram' ) 
				),
				array(
					'icon' 		=> sanitize_text_field( 'fab fa-tumblr' ),
					'title'		=> __( 'Tumblr' ),
					'slug'		=> sanitize_text_field(  'tumblr' ),
					'tab_slug'	=> sanitize_text_field( 'scoda-networks-tumblr' ) 
				),
				array(
					'icon' 		=> sanitize_text_field( 'fab fa-linkedin-in' ),
					'title'		=> __( 'Linked In' ),
					'slug'		=> sanitize_text_field( 'linkedin' ),
					'tab_slug'	=> sanitize_text_field( 'scoda-networks-linkedin-in' ) 
				),
				array(
					'icon' 		=> sanitize_text_field( 'fab fa-tiktok' ),
					'title'		=> __( 'TikTok' ),
					'slug'		=> sanitize_text_field( 'tiktok' ),
					'tab_slug'	=> sanitize_text_field( 'scoda-networks-tiktok' )
				)
				
			);
			
			$this->set_option( 'networks', $networks );
		}
		 
	}	

	// Render Our Plugin Admin UI 
	ScodaAdminUI::get_instance()->render();
}