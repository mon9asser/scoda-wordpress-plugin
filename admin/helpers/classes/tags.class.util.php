<?php 

 
/**
 * Eratags Helper Class contains some helper methods built-in wordpress functionalities
 * 
 * @since 1.0  
 * @package  Social Coda => SCoda
 * @author eratags.com 
 * @version 1.0
 * @link http://eratags.com
 */

if ( !class_exists( 'EratagsUtil' ) ) {
    
    class EratagsUtil { 

		/**
		 * 
		 * @var $instance Store values
		 */
		private static $instance;

		/** 
		 * @uses Restricts the instantiation of a class to one "single" instance
		 * 
		 * @return EratagsUtil  
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self; 
			}

			return self::$instance;

		}

		/**
		 * Constructor  
		 */
		private function __construct() {
			
		}

        /**
         * Store tags Option By social name or option setting 
         * 
         * @param $social_name OR setting name
         * @param $social option keys with values or string value
         * 
         * @return boolean => to detect if data is stored 
         */
        public function set_option( $option_name, $options = null ) {

            $tags_opt_key = apply_filters( 'eratags/option_key', '' );
            $tags_opts    = get_option( $tags_opt_key );

            // Store default values if we have no data exists 
            if ( empty( $tags_opts ) ) {
            
                $tags_opts = array();
                $tags_opts[$option_name] = is_array( $options ) ? array(): '';
            
            } else {
                if ( !isset( $tags_opts[$option_name] ) ) {
                    $tags_opts[$option_name] = is_array( $options ) ? array(): '';
                }
            }

            // Store Options ( Multi options or single option with our structure ) 
            if ( is_array( $options ) ) {
                
                foreach ( $options as $key => $value ) {
                    $tags_opts[$option_name][$key] = $value;
                }

            } else {
                
                $tags_opts[$option_name] = $options;

            }

            // store or update the new data into our database fields 
            $updated = update_option( $tags_opt_key, $tags_opts );

            return $updated;

        }

        /**
         * Delete option from our option structure
         * 
         * @param $social_name OR setting name
         * @param $social option keys with values or string value
         * 
         * @return boolean
         */
        public function delete_option( $option_name, $options = array() ) {
            
            $tags_opt_key = apply_filters( 'eratags/option_key', '' );
            $tags_opts    = get_option( $tags_opt_key );
            $is_deleted = false; 

            if ( empty( $tags_opts ) ) {
                return true;
            }

            // To Delete all tags sturcure from wp_option 
            if ( 'all' === $option_name ) {

                // Delete all 
                $is_deleted = delete_option( $tags_opt_key );

                return $is_deleted;

            }

            // To Delete by option name or social value 
            if ( isset( $tags_opts[$option_name] ) && ( is_array( $options ) && ! count( $options ) ) ) {
                unset( $tags_opts[$option_name] );
            }
             
            // To delete sub option from dom 
            if ( is_array( $options ) && count( $options ) && isset( $tags_opts[$option_name] ) ) {
                
                foreach ( $options as $key_name ) {
                   
                    if ( isset( $tags_opts[$option_name][$key_name] ) ) {
                      
                        unset( $tags_opts[$option_name][$key_name] );
                    }

                    if ( !count( $tags_opts[$option_name] ) ) {
                        unset( $tags_opts[$option_name] );
                    }
                }

            }

            if ( count( $tags_opts ) ) {
                $is_deleted = update_option( $tags_opt_key, $tags_opts );
            } else {
                $is_deleted = delete_option( $tags_opt_key );
            }

            return $is_deleted;

        }

        /**
         * Get Option By Key Name Or tree option name 
         * 
         * @param $option_name => key name of option 
         * @param $return => default it returns an empty array 
         * 
         * @return array|string => according to parameter and data 
         */
        public function get_option( $option_name = null, $return = array(), $option_field = null ) {
            
            $result = get_option(  
                apply_filters( 'eratags/option_key', '' )
            );

            if ( ! is_null( $option_name ) ) {

                if ( empty( $result ) || !isset( $result[$option_name] ) ) {
                    return $return;
                }
    
                $result = $result[$option_name];
              
                if ( ! is_null( $option_field ) && isset( $result[$option_field] ) ) {
                    $result =  $result[$option_field];
                }

                return $result;
            }
            
            if ( empty( $result ) ) {
                return $return;
            }

            return $result; 
        }

        /**
         * Send HTTP Rquest to apis 
         * 
         * @param $api_url => string of api url 
         * @param $args => the array contains all thing to send 
         * @param $method => the http request method 
         * 
         * @return object 
         */
        protected function request( $api_url, $args = array(), $method = 'GET' ) {

            // To custom error 
            $request = new WP_Error();
            $result = (object) array(); 
            $api_url = esc_url_raw( $api_url );

            // to Unduplicate method fields
            if ( isset( $args['method'] ) ) {
                unset( $args['method'] );
            }

            if ( !isset( $args['timeout'] ) ) {
               $args['timeout'] = 15;
            }

            // Check method type 
            $methods = array( 'get', 'post', 'delete', 'patch' );
            if ( !in_array( strtolower( $method ), $methods ) ) {
                $request->add( 'http_request_method', sprintf( __( 'ERROR: %1$s is not allowed method' ), $method ) );
            }
            $method = strtoupper( $method ); 

            // Error of request method type 
            if ( is_wp_error( $request ) && count( $request->get_error_messages() ) ) {
                $result->error = true;
                $result->status_code = 405; // Method Not Allowed 
                $result->message = $request->get_error_message();
                return $result;
            }

            // HTTP Remote Post METHOD
            if ( $method === 'POST' ) {
                $request = wp_remote_post( $api_url, $args );
            }

            // HTTP Remote Post METHOD
            else if ( $method === 'PATCH' || $method === 'DELETE' ) {
                $args['method'] = $method;
                $request = wp_remote_request( $api_url, $args );
            }

            // HTTP Remote Get METHOD
            else {
                $request = wp_remote_get( $api_url, $args );
            }

            // GET status code 
            $status_code = wp_remote_retrieve_response_code( $request );
            $result->status_code = $status_code;
            $result->header = wp_remote_retrieve_header( $request, 'last-modified' );
            
            // Handling error 
            if ( is_wp_error( $request ) ) {
                $result->error = true;
                $result->message = is_wp_error( $request ) ? $request->get_error_message(): __( "Something went wrong !" );
                return $result;
            } 
			 
            if ( $status_code !== 200 ) {
                $decode_error = is_string( json_decode( $request['body'] ) ) ? $request['body'] : json_decode( $request['body'], true );
                $result->error = true;
                $result->message = $decode_error ; 
                return $result;
            }

            $body = wp_remote_retrieve_body( $request );
			
            // Handling Fields & Body request   
            $result->error 	 = false;
            $result->message = __( 'Success' );
            $result->data 	 = json_decode( $body, true ); 
			
            // To handling string result 
            if ( is_array( $result->data ) ) {
                $result->data = (object) $result->data;
            } else {
                $result->data = $body;
            }
			
            return $result;

        }

        /**
         * Find Value in array and get returned index of the needed value 
         * 
         * @param $args the main array 
         * @param $search_key the key of child array 
         * @param $search_value the value to searh in array 
         * 
         * @return integer 
         */
        public function find_index( $args, $search_key, $search_value ) {
            
            $that_index = -1;
            
            for( $i = 0; $i < count( $args ); $i++ ) { 
                
                if (  $args[$i][$search_key] === $search_value ) {
                    $that_index = $i;
                    break;
                }
                
            }

            return $that_index;
        
        }

        /**
         * Aouth Signature is the generated string to request token 
         * 
         * @param $api_url
         * @param $method 
         * @param $credentials
         * @param $fields
         * @param $extra_oauth 
         * 
         * @return array 
         */
        protected function oauth_signature( $api_url, $method, $credentials, $fields = array(), $extra_oauth = array(), $return = false ) {

			// Load Token Keys 
			$access_token 		 = isset( $credentials['access_token'] ) ?  $credentials['access_token']: '';
			$secret_access_token = isset( $credentials['secret_access_token'] )? $credentials['secret_access_token']: '';

            // Build Basic OAuth Fields 
            $oauth = array(
                'oauth_consumer_key'     => $credentials['consumer_key'],
                'oauth_nonce'            => md5( mt_rand() . time() ),
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_token'            => $access_token, 
                'oauth_timestamp'        => time(),
                'oauth_version'          => '1.0'
            );
			
            // If We have an aditional oauth fields we need to do merge with prev one
            $oauth = wp_parse_args( $oauth, $extra_oauth );
             
            // To Check what is the data field type 
            $type = isset( $fields['type'] ) ? $fields['type']: 0;

            // Insert fields data into oAuth Array 
            if ( !isset( $fields['data'] ) ) {
                $fields['data'] = array();
            }

            // Fill field data into basic oauth array 
            if ( count( $fields['data'] ) ) {
                foreach ( $fields['data'] as $key => $value) {
                    $oauth[$key] = ( $type ) ? $value: urldecode_deep( $value );
                }
            }

            // Sort oauth by keys 
            ksort( $oauth );
            
            // Encode array keys and values 
            $encoded = array();
            foreach ( $oauth as $key => $value ) { 
                $encoded[] = rawurlencode_deep( $key ) . '=' . rawurlencode_deep( $value );
            }

            $base = sprintf(
                '%1$s&%2$s&%3$s',
                $method,
                rawurlencode_deep( $api_url ),
                rawurlencode_deep( implode('&', $encoded ) )
            );

			
			$keys = sprintf(
				'%1$s&%2$s',
				rawurlencode_deep( $credentials['secret_consumer_key'] ),
				rawurlencode_deep( $secret_access_token ),
			);

            $oauth_signature = base64_encode( hash_hmac('sha1', $base, $keys, true) );

            // Store oAuth Signature to main array 
            $oauth['oauth_signature'] = $oauth_signature; 
			
            // Build Header 
            $oauth_header = 'OAuth ';
            $oauth_args   = array();
            foreach ( $oauth as $key => $value) { 				
                $oauth_args[] = "$key=\"" . rawurlencode_deep( $value ) . "\"";
            }
            $oauth_header .= implode( ', ', $oauth_args );
            
			// to get only array 
			if ( $return ) {
				return $oauth;
			}
			
            // Build exportable array 
            return array(
                'header' => $oauth_header,
                'oauth'  => $oauth
            );
        }
        
        /**
         * Getting specific fields and add another fields into array 
         * 
         * @param  $main_args
         * @param  $filter_args
         * @param  $add_args
         * 
         * @return array 
         */
        public function needed_args( $main_args, $filter_args, $add_args ) {
            
            $results = array();

            foreach ( $main_args as $arg ) {
                
                $collected = array();
                
                for ($i=0; $i < count( $filter_args ) ; $i++) { 

                    if ( isset( $arg[$filter_args[$i]] ) ) {

                        $collected[$filter_args[$i]] = sanitize_text_field( $arg[$filter_args[$i]] );

                    }
                    
                    $collected = wp_parse_args( $collected, $add_args );
                }

                $results[] = $collected;
            }

            return $results;

        } 

		/**
         * Getting MySQL Version   
         * 
         * @return string
         */
		public function get_mysql_version() {

			global $wpdb;
	
			$db_server_version = $wpdb->get_results( "SHOW VARIABLES WHERE `Variable_name` IN ( 'version_comment', 'innodb_version' )", OBJECT_K );
			
			return $db_server_version['version_comment']->Value . ' v' . $db_server_version['innodb_version']->Value;
	
		} 
		  

		
    }
        
}



