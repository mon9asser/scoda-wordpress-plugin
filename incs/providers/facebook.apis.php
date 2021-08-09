<?php  
/**
 * This file contains all facebook open graph services
 * 
 * @author Eratags
 * @link eratags.com
 * @since 1.0
 * @package Scoail Coda => ( Scoda )
 * @subpackage incs/providers/facebook 
 * 
 * ------------------------------------------------------------------------
 * Table of Methods
 * ------------------------------------------------------------------------
 * @method credentials          : Get all credentials
 * @method authorize_url      	: Build Authorize URL
 * @method authentication       : User Accounts Authentication   
 * @method revoke      			: revoking authenticated accounts
 * @method get_feeds          	: Get Feeds From Accounts ( FB Pages - FB Groups - FB Timeline )
 * @method update_feed 			: Post Feed On Accounts ( FB Pages - FB Groups )
 * @method delete_feed   		: Delete Post Feed On Accounts ( FB Pages ) 
 * @method get_follower_counts  : Getting User Member Counts | Follower Counts | Fan Page Counts
 * 
 */

if ( !class_exists( 'Scoda_Facebook' ) ) {

    class Scoda_Facebook extends Eratags_Helper {

        /*
        * General Options
        */
        private $version;
        private $host;
        private $social_name;
        private $social_url;
        private $user_id;
        private $redirect_url;
        private $limit; 

        /**
         * Credentials
        */
        private $app_id;
        private $secret_id;

        /**
         * Tokens
        */
        private $access_token; 
        
        /**
         * List Of Endpoints and permissions
        */
        private $endpoint;
        private $permissions;

        /**
         * @todo Store all data in attributes
        */
        public function __construct() {
            
            // General Settings 
            $this->social_name = 'facebook'; 
            $this->social_url  = 'https://facebook.com';
            $this->host        = 'https://graph.facebook.com';
            $this->version     = '/v11.0';  
            $this->limit       = 25; // default 

            // Endpoints and permissions
            $this->endpoint    = array(
                'authorize'     => '/dialog/oauth',
                'access_token'  => '/oauth/access_token',
                'revoke'        => '/me/permissions',
                'pages'         => '/me/accounts',
                'groups'        => '/me/groups',
                'me'            => '/me',
                'feeds'         => '/%1$s/feed'
            );

            $this->permissions = array(

                // Scope of show all page accounts are managed by user 
                'pages_show_list', 

                // Scope of show all group accounts are managed by user 
                'groups_show_list',
                'user_managed_groups',

                // To Read And Get Posts From Pages 
                'pages_read_user_content',
                'pages_read_engagement',

				// To Allow Post Feed On Pages 
				'pages_manage_posts',

				// To Allow Post Feed On Groups
				'publish_to_groups',

                // To Display Person who publish the post case the page has more that manager
                'business_management',

                // Allow retrieve user timeline feed 
                'user_posts'

            );

            // Load Options  
            $options                = $this->tags_get_option( $this->social_name );
            $this->redirect_url     = $this->tags_get_option( 'redirect_url', '' );
            $this->user_id          = isset( $options['user_id'] )? $options['user_id']: '';
            $this->is_revoked       = isset( $options['is_revoked'] )? $options['is_revoked']: true;
            $this->app_id           = isset( $options['app_id'] )? $options['app_id']: '';
            $this->secret_id        = isset( $options['secret_id'] )? $options['secret_id']: '';
            $this->access_token     = isset( $options['access_token'] )? $options['access_token']: '';

        }

        /**
         * @todo Get all credentials
         * 
		 * @uses collect all credentials in one array 
		 * 
         * @return array 
         */
        private function credentials( $includes = array() ) {
            
            $credentials = array(
                'app_id'          => $this->app_id,
                'secret_id'       => $this->secret_id, 
                'redirect_uri'    => $this->redirect_url,
                'client_id'       => $this->app_id,
                'client_secret'   => $this->secret_id
            );

            $requested_fields = array(); 

            // exclude uneeded fields
            if ( count( $includes ) ) {
                foreach ( $includes as $include ) {
                    if ( isset( $credentials[$include] ) ) {
                        $requested_fields[$include] = $credentials[$include];
                    }
                }
            }

            return  $requested_fields;

        }

        /**
         * @todo Build Authorize URL 
         * 
		 * @uses generate url to do authorization 
		 * 
         * @return string 
         */
        public function authorize_url() {
             
            // Build Args 
            $args = $this->credentials(array( 'app_id', 'redirect_uri' ));
            
            // Load Permissions 
            if ( count( $this->permissions ) ) {
                $args['scope'] = implode( ',', $this->permissions );
            }

            // Build The Authorization URL  
            $url = esc_url_raw(add_query_arg( 
                $args,
                $this->social_url . $this->version . $this->endpoint['authorize']  
            ));

            return $url;

        }

        /**
         * @todo User Accounts Authentication 
         * 
         * @api /oauth/access_token
         * @api /me/accounts
         * @api /me/groups
         * @api /me
         * 
		 * @uses 1- Geneate An Access Token 
         * @uses 2- Geneate An Access Token For Long Live 
         * @uses 3- Retrieve Pages Managed By Authenticated User
         * @uses 4- Retrieve groups Managed By Authenticated User
         * @uses 5- Retrieve Timeline info That Authenticated By User
		 * 
         * @param $code
         * 
         * @return boolean 
         */
        public function authentication( $code ) {
       
            // Build needed givens 
            $access_token_api = $this->host . $this->version . $this->endpoint['access_token'];
            $args             = $this->credentials(array(
                'client_id',
                'redirect_uri',
                'client_secret'
            ));

            // merge two fields 
            $args = wp_parse_args( $args, array( 'code' => $code ) );

            // build api of access token generator 
            $at_url = add_query_arg( $args, $access_token_api );

            // Send http request to generate user access token 
            $request = $this->request( $at_url );
            if ( $request->error ) {
                return $request;
            }

            // Limited Access Token 
            $this->access_token = isset( $request->data->access_token )? $request->data->access_token: '';

            // generate long live access token 
            $args = $this->credentials(array(
                'client_id', 
                'client_secret'
            ));
            $args = wp_parse_args( $args, array(
                'grant_type' => 'fb_exchange_token',
                'fb_exchange_token' => $this->access_token
            ));
            // Build long live url for http request
            $url = add_query_arg( $args, $access_token_api );

            $request = $this->request( $url );

            if ( $request->error ) {
                return $request;
            }

            // Long Live Access Token 
            $this->access_token = isset( $request->data->access_token )? $request->data->access_token: '';

            // Store the reaults into our options 
            $this->tags_set_option( $this->social_name, array(
                'is_revoked'   => false,
                'access_token' => $this->access_token,
                'accounts'     => array() 
            ));

            // => Fields : id, name, token, type 
            $accounts = array();

            /**
             * @todo Retrieve Pages Managed By Authenticated User
             * 
             * @api '/me/accounts'
             * @uses 'pages_show_list' => needed permission
             */
            
            $pages_url = $this->host . $this->version . $this->endpoint['pages'];
            
            $pages_url = add_query_arg( array(
                'access_token' => $this->access_token,
                'limit'        => $this->limit,
                'fields'       => 'id,name,access_token'
            ), $pages_url );

            $request = $this->request( $pages_url ); 

            // Make pages accessible if we have no error 
            if ( ! $request->error ) {
                
                $managed_pages = isset( $request->data->data )? $request->data->data: array();
                
                if ( count( $managed_pages ) ) {
                
                    $scrapped_pages = $this->needed_args( $managed_pages, [ 'id', 'name', 'access_token' ], [ 'type' => 'page' ] );
                    
                    $accounts = wp_parse_args( $accounts, $scrapped_pages );

                }
            }
            
            /**
             * @todo Retrieve Groups Managed By Authenticated User
             * 
             * @api '/me/groups'
             * @uses 'groups_show_list,user_managed_groups' => needed permissions
             */
            $groups_url = $this->host . $this->version . $this->endpoint['groups'];

            $groups_url = add_query_arg( array(
                'admin_only'   => true, 
                'access_token' => $this->access_token,
                'limit'        => $this->limit,
                'fields'       => 'id,name,member_count'
            ), $groups_url );

            $request = $this->request( $groups_url );  

            // Make pages accessible if we have no error 
            if ( ! $request->error ) {
                
                $managed_groups = isset( $request->data->data )? $request->data->data: array();
                
                if ( count( $managed_groups ) ) {
                
                    $scrapped_groups = $this->needed_args( $managed_groups, [ 'id', 'name' ], [ 'type' => 'group', 'access_token' => false ] );

                    $accounts = wp_parse_args( $accounts, $scrapped_groups );

                }
            }

            
            /**
             * @todo Retrieve Timeline Managed By Authenticated User
             * 
             * @api '/me' 
             * 
             */
            $timeline_url =  $this->host . $this->version . $this->endpoint['me']; 

            $timeline_url = add_query_arg( array(
                'access_token' => $this->access_token
            ), $timeline_url );

            $request = $this->request( $timeline_url ); 
            
            if ( ! $request->error ) {
                
                $timeline = isset( $request->data )? $request->data: new stdClass();
                $timeline->type = 'timeline';
                $timeline->access_token = false;
                $accounts = wp_parse_args( $accounts, [ ( array ) $timeline ] );
            }

            $is_stored = $this->tags_set_option( $this->social_name, array(
                'accounts' => $accounts
            ));
            
            if ( $is_stored ) {
                return true;
            }

            return false;

        }

        /**
         * @todo revoking authenticated accounts
         * 
         * @api /me/permissions => DELETE
         * 
		 * @uses Revoke auth accounts 
		 * @uses Delete options these stored in our database like: accounts, access_token, is_revoked
		 * 
         * @return boolean
         */
        public function revoke() {

            // Build The Revoke Api URL 
            $url = add_query_arg(
                array( 'access_token' => $this->access_token ),
                $this->host . $this->version . $this->endpoint['revoke']
            );

            // Send http request 
            $request = $this->request( $url, array(), "DELETE" );
            
            // handling errors 
            if ( $request->error ) {
                 return false;
            } 
            
            // delete all facebook data 
            $this->tags_delete_option( $this->social_name, array( 'accounts', 'access_token', 'is_revoked' ));

            return true;

        }
        
        /**
         * @todo Get Feeds From Accounts ( FB Pages - FB Groups - FB Timeline )
         * 
         * @api /:account_id/feed => GET
         * Needed scopes => ( pages_read_user_content - pages_read_engagement - business_management - user_posts )
         * 
		 * @param $account_id
		 * @param $limit
		 * @param $params
		 * @param $paging
		 * 
		 * @uses getting all facebook accounts feeds by required $account_id
		 * @uses if we need to get more fields we have to build an array in $params 
		 * with needed new fields also make sure that filter related it is changed to proper 
		 * @uses if we have to use this method again it will be with the next paging, paging should be 
		 * started with one parent array contains needed fields like since, until, etc  
		 * 
         * @return array 
         */
        public function get_feeds( $account_id, $limit = null, $params = array(), $paging = array() ) {

            // Get all account stored in our database 
            $accounts = $this->tags_get_option( $this->social_name, array(), 'accounts'  );
            
            // Selected account by id 
            $account = apply_filters( 'eratags/get_array_field', $accounts, [ 'id' => $account_id ]);
            
            if ( !count( $account ) ) {
                return ( object ) array(
                    'error' => true,
                    'message' => __( 'This account is not authorized !' )
                );
            }   

            // Casting the-array to object 
            $account = ( object ) $account;

            // Add target token if this account is not page type 
            if ( ! $account->access_token && 'page' !== $account->type ) {
                $account->access_token = $this->access_token;
            }

            // Scope Fields ( Default For Pages )
            $fields = array(
                'id',
                'message',
                'full_picture',
                'created_time',
                'is_published',
                'is_popular',
				'permalink_url',
                'admin_creator', 
				'attachments' 
            );

            // override needed fields 
            $fields = wp_parse_args( $fields, $params );

            // Build Query 
            $query = array(
                'fields'        => implode( ',', $fields ),
                'access_token'  => $account->access_token,
                'limit'         => is_null( $limit ) ? $this->limit: $limit
            );
            
			// Case the request with paging 
			if ( count( $paging ) ) {
				$query = wp_parse_args( $query, $paging );
			}

            // Build url 
            $url = add_query_arg( 
                $query, 
                sprintf( $this->host . $this->version . $this->endpoint['feeds'], $account->id )
            );

            // Send Request 
            $request = $this->request( $url );
            
            if ( $request->error ) {
                return $request;
            }

			// Handling and split paging to pieces
			if ( isset( $request->data->paging ) ) {
				$request->data->paging = apply_filters( 'eratags/scoda/facebook/feed_paging', $request->data->paging, $account->type );
			}

            // Handling and filter fields to proper with our need 
			if ( isset( $request->data->data ) ) {
            	$request->data->data = apply_filters( 'eratags/scoda/facebook/get_feeds', $request->data->data );
			}

			return $request->data; 

        }

		/**
		 * @todo Post Feed On Accounts ( FB Pages - FB Groups )
		 * 
		 * @api /:account_oject_id/feed
		 * Need Scopes: pages_manage_posts - pages_read_engagement - publish_to_groups
		 * 
		 * @uses Post Feed 
		 * @uses Update The existing feed 
		 * @uses Post Feeds or update On ( FB Pages - FB Groups ) you manage
		 * 
		 * @param $access_id => it can be account id or post object id 
		 * @param $params => data with keys 
		 * 
		 * @return array 
		 */
		public function update_feed( $access_id, $params = array() ) {

			$account_id  = $access_id;
			$post_id   	 = $access_id;

			// extract object id  
			if ( strpos( $access_id, '_' ) ) {
				
				$ids 		= explode( '_', $access_id );
				$account_id = $ids [0];
				$post_id 	= $ids [1];

			} else {
				$post_id = null;
			}

			// Get all account stored in our database 
            $accounts = $this->tags_get_option( $this->social_name, array(), 'accounts'  );
            
            // get account information like access token and detect if it in our db  
            $account = apply_filters( 'eratags/get_array_field', $accounts, [ 'id' => $account_id ]);

			if ( !count( $account ) ) {
                return ( object ) array(
                    'error' => true,
                    'message' => __( 'This account is not authorized !' )
                );
            }   

            // Casting the-array to object 
            $account = ( object ) $account;

            // Add target token if this account is not page type 
            if ( ! $account->access_token && 'page' !== $account->type ) {
                $account->access_token = $this->access_token;
            }

			// Build url and body args 

			$args = array( 
				'body' 	  => $params	
			);

			$api_url = sprintf(
				$this->host . $this->version . $this->endpoint['feeds'],
				$account->id 
			);

			$api_url = add_query_arg(
				array( 'access_token' => $account->access_token ),
				$api_url
			);

			// Send https request 
			$request = $this->request( $api_url, $args, "POST" );
			
			if ( $request->error ) {
				return $request;
			}

			return $request->data; 

		}

		/**
		 * @todo Delete Post Feed On Accounts ( FB Pages )
		 * 
		 * @api /:account_oject_id => DELETE 
		 * 
		 * @uses Delete Facebook Page Posts  
		 * 
		 * @param $object_post_id 
		 * 
		 * @return object 
		 */
		public function delete_feed( $object_post_id ) {

			$account_id  = $object_post_id;
			$post_id   	 = $object_post_id;

			// extract object id  
			if ( ! strpos( $object_post_id, '_' ) ) {
				return ( object ) array(
                    'error' => true,
                    'message' => __( 'Unsupported Object Post Id !' )
                );
			}  

			$ids 		= explode( '_', $object_post_id );
			$account_id = $ids [0];
			$post_id 	= $ids [1];
			
			// Get all account stored in our database 
            $accounts = $this->tags_get_option( $this->social_name, array(), 'accounts'  );
            
            // get account information like access token and detect if it in our db  
            $account = apply_filters( 'eratags/get_array_field', $accounts, [ 'id' => $account_id ]);

			if ( !count( $account ) ) {
                return ( object ) array(
                    'error' => true,
                    'message' => __( 'This account is not authorized !' )
                );
            }   

            // Casting the-array to object 
            $account = ( object ) $account;

            // Add target token if this account is not page type 
            if ( ! $account->access_token && 'page' !== $account->type ) {
                $account->access_token = $this->access_token;
            }

			// Build url 
			$api_url = $this->host . $this->version . '/' . $object_post_id;
			$api_url = add_query_arg(
				array( 'access_token' => $account->access_token ),
				$api_url
			);

			// Send request
			$request = $this->request( $api_url, array(), "DELETE");
			
			if ( $request->error ) {
				return $request;
			}

			return $request->data; 

		}

		/**
		 * @todo Getting User Member Counts | Follower Counts | Fan Page Counts 
		 * 
		 * @api 
		 * 
		 * @uses
		 * 
		 * @param 
		 * 
		 * @return 
		 *  
		 */
		public function get_follower_counts( $id ) {
			
		}

    }

}

 