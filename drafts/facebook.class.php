<?php
/**
 * This file contains all facebook open graph services
 *
 * @link eratags.com
 * @since 1.0
 * @package Scoail Coda => ( Scoda )
 * @subpackage incs/vendor/facebook
 *
 */

if ( !class_exists( 'Scoda_Facebook' ) ) {

    class Scoda_Facebook extends Scoda_Helper {

        // API Version
        private $og_version = 'v11.0';

        // Open Graph Url
        private $graph_url  = "https://graph.facebook.com";

        // Get current social network name ( Don't change that )
        private $social_name = 'facebook';

        // Application Id
        private $app_id     = '';

        // The secret Id Of Application
        private $secret_id  = '';

        // Long Live User Token
        private  $long_live_token = '';

        // Access Token Of User Page
        private $page_token = '';

        // User Access Token
        private $access_token = '';

        // Redirect Url
        private $redirect_url = '';

        // Current User Id
        private $user_id = '';

        // Limit Of Records
        private $limits = 10;

        // List Of Supported Apis
        private $api_slug    = array(
            'authorize' => 'dialog/oauth',
            'authenticate' => 'oauth/access_token',
            'permissions' => 'me/permissions',
            'pages' => 'me/accounts',
            'groups' =>  'me/groups',
            'me' => 'me',
            'feed' => 'scoda_access_id/feed', // POST ( Groups and pages ) & GET ( Goups, Pages, Account )

        );

        // List Of Needed Permissions From OG Application
        private $scopes = array(

            // To Display All pages you manages ( Show all page managed by user )
            'pages_show_list',

            // To Display All Groups you joined ( Show all groups joined by user )
            'groups_show_list',

            // To Display Group Member Counts ( Get Group Info )
            'user_managed_groups',

            // To Allow publish On Pages You Manage ( Get - Post - Update - Delete )
            'pages_manage_posts',

            // To Allow publish on group You Manage ( Update & Delete Are Not Supported From Facebook Only Publish For Group )
            'publish_to_groups',

            // Read Feads From Pages
            'pages_read_engagement',
            'pages_read_user_content',

            // Read and access group
            'groups_access_member_info',

            // To Display Person who publish the post case the page has more that manager
            'business_management',

            // To Allow Get Current User Posts
            'user_posts',

            // Get Follower or friend counts
            'user_friends',

            // To Post video on accounts
            'publish_video'
        );
        /*

        pages_read_engagement
         */

        /**
         * Load Facebook Credentials And Init Facebook Options
         */
        public function __construct() {

            // Load Facebook Options then store them to our class attributes
            $facebook_opts = $this->scoda_get_option( $this->social_name );


            // Case option doesn't exists !
            if(  count( $facebook_opts )  === 0 ) {
                return;
            }

            // Store Options to our class attributes
            $this->app_id          = isset( $facebook_opts['app_id'] )     ? $facebook_opts['app_id']: '';
            $this->secret_id       = isset( $facebook_opts['secret_id'] )  ? $facebook_opts['secret_id']: '';
            $this->page_token      = isset( $facebook_opts['page_token'] ) ? $facebook_opts['page_token']: '';
            $this->access_token    = isset( $facebook_opts['access_token'] ) ? $facebook_opts['access_token']: '';
            $this->user_id         = isset( $facebook_opts['user_id'] ) ? $facebook_opts['user_id']: '';
            $this->long_live_token = isset( $facebook_opts['long_live_token'] ) ? $facebook_opts['long_live_token']: '';

            $options = $this->settings();
            $this->redirect_url = $options['redirect_url'];

        }

        /**
         * Get Extended Token ( Long Live Token )
         *
         * @return string
         */
        public function long_live_token() {

            $url_args = array(
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->app_id,
                'client_secret' => $this->secret_id,
                'fb_exchange_token' => $this->access_token
            );

            $url = esc_url_raw(add_query_arg(
                $url_args,
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['authenticate']
            ));

            $reqs = $this->request( $url, "GET" );

            if ( $reqs->error ) {
                return false;
            }

            $this->long_live_token = $reqs->results->access_token;

            $this->scoda_set_option( 'facebook', array(
                'long_live_token' => $this->long_live_token
            ));

            return $reqs->results->access_token;

        }

        /**
         * Load Facebook credentials
         *
         * @return mixed -> object contains app_id & secret_id
         */
        private function get_credentials() {

            $credentials = new stdClass();
            $credentials->app_id    = $this->app_id;
            $credentials->secret_id = $this->secret_id;
            $credentials->redirect_url = $this->redirect_url;

            return $credentials;
        }

        /**
         * Authorization
         * @param #scope
         *
         * @return string => Auth URL
         */
        public function authorize_url() {

            // App Credential
            $args = array(
                'app_id' => $this->app_id,
                'redirect_uri' => $this->redirect_url
            );

            // Handling the comma of permission scopes
            if ( count(  $this->scopes ) ) {

                $scope_str = '';

                for( $i= 0; $i < count( $this->scopes ); $i++ ) {

                    $scope_str .= $this->scopes[$i];

                    if ( $i < ( count( $this->scopes ) - 1 ) ) {
                        $scope_str .= ',';
                    }

                }

                $args['scope'] = $scope_str;
            }

            // Build the url of authorization
            $auth = add_query_arg(
                $args,
                str_replace( 'graph.', '', $this->graph_url ) . '/' . $this->og_version . '/' . $this->api_slug['authorize']
            );

            // Secure url
            $auth = apply_filters( 'eratags/scoda/facebook_authorize_url', esc_url_raw( $auth ) );

            return $auth;
        }

        /**
         * User Authentication ( Get Access Token )
         *
         * @param $code
         *
         * @return mixed => return an object contains the user access token
         */
        private function authentication( $code = null ) {

            // response object
            $auth_response = new stdClass();

            if ( $code === null ) {
                $auth_response->error = true;
                $auth_response->message = __( 'The Authorization Code Is Required' );
                $auth_response->results = array();
                return $auth_response;
            }

            // App Credential
            $args = array(
                'client_id' => $this->app_id,
                'redirect_uri' => $this->redirect_url,
                'client_secret' => $this->secret_id,
                'code' => $code
            );

            // Build Fields Of API Url
            $url = add_query_arg(
                $args,
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['authenticate']
            );

            // Send Request With Secured Url
            $request = $this->request( esc_url_raw( $url ), 'GET' );
            $auth_response->error = $request->error;

            // Handling Request Error
            if ( $request->error ) {

                $auth_response->message = $request->results->error['message'];
                $auth_response->results = array();

            } else {

                // Accessing data of request
                $auth_response->message = __( "Success !" );
                $auth_response->results = $request->results;

                if ( isset( $request->results->access_token ) ) {

                    // First Get user id before store it into database
                    $me_url = add_query_arg(array(
                        'access_token' =>  $request->results->access_token,
                        'fields' => 'id'
                    ), $this->graph_url . '/me' );

                    $req_uid = $this->request( esc_url_raw( $me_url ), 'GET' );
                    $current_user_id = '';

                    if ( !$req_uid->error ) {
                        $current_user_id = $req_uid->results->id;
                    }

                    // Store it in our class property
                    $this->access_token = $request->results->access_token;

                    // Store Long Live Access Token
                    $this->long_live_token();

                    // Store Access token in our database
                    $this->scoda_set_option( $this->social_name, array(
                        "access_token" => $this->access_token,
                        "is_revoked" => false,
                        "user_id" => $current_user_id
                    ));

                }

            }

            // Return an object
            return $auth_response;
        }


        /**
         * Show All Pages & Groups Managed By Current User
         *
         * @return boolean
         */
        private function get_current_pages_groups () {

            // Get Current Pages & groups
            $spreads = array();
            $is_stored = false;


            // Get Account Information
            $user_profile_api_url =  esc_url_raw(add_query_arg(
                array(
                    'access_token' => $this->access_token
                ) ,
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['me']
            ));
            $profile_req = $this->request( $user_profile_api_url);
            if ( !$profile_req->error ) {
                $spreads[] = array(
                    'id'          => isset( $profile_req->results->id )? $profile_req->results->id: 0,
                    'name'        => isset( $profile_req->results->name )? $profile_req->results->name: 0,
                    'users_count' =>  0,
                    'token'       => false,
                    'type'        => 'timeline'
                );
            }


            // Get Groups
            $groups_api_url = esc_url_raw(add_query_arg(
                array(
                    'admin_only' => true,
                    'access_token' => $this->access_token,
                    'limit' => $this->limits,
                    'fields' => 'name,member_count'
                ),
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['groups']
            ));
            $groups_req = $this->request( $groups_api_url, 'GET' );
            if ( ! $groups_req->error ) {

                // This detecter to prevent any bug in the future if the api changed
                if ( isset( $groups_req->results->data ) ) {
                    foreach( $groups_req->results->data as $curr_group ) {
                        $spreads[] = array(
                            'id'          => isset( $curr_group['id'] )? $curr_group['id']: 0,
                            'name'        => isset( $curr_group['name'] )? $curr_group['name']: 0,
                            'users_count' => isset( $curr_group['member_count'] )? $curr_group['member_count']: 0,
                            'token'       => false,
                            'type'        => 'group'
                        );
                    }
                }

            }

            // Get Pages
            $pages_api_url = esc_url_raw(add_query_arg(
                array(
                    'access_token' => $this->long_live_token,
                    'limit' => $this->limits,
                    'fields' => 'followers_count,name,access_token'
                ),
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['pages']
            ));

            // Send Request
            $page_req = $this->request( $pages_api_url, 'GET' );
            if ( ! $page_req->error ) {

                // This detecter to prevent any bug in the future if the api changed
                if ( isset( $page_req->results->data ) ) {
                    foreach( $page_req->results->data as $curr_page ) {
                        $spreads[] = array(
                            'id'          => isset( $curr_page['id'] )? $curr_page['id']: 0,
                            'name'        => isset( $curr_page['name'] )? $curr_page['name']: 0,
                            'users_count' => isset( $curr_page['followers_count'] )? $curr_page['followers_count']: 0,
                            'token'       => isset( $curr_page['access_token'] )? $curr_page['access_token']: '',
                            'type'        => 'page'
                        );
                    }
                }

            }


            // Save Pages and groups In Our Database
            if( count( $spreads ) ) {

                $update_opts = $this->scoda_set_option( $this->social_name, array(
                    'accounts' => $spreads
                ));

                if (  $update_opts ) {
                    $is_stored = true;
                }

            }

            return $is_stored;
        }


        /**
         * Get Current App Permissions
         *
         * @return array
         */
        private function get_permissions() {

            $permission_url = esc_url_raw(add_query_arg(
                array(
                    "access_token" => $this->access_token
                ),
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['permissions']
            ));

            $req = $this->request( $permission_url, 'GET' );

            if ( $req->error || !isset( $req->results->data ) ) {
                return array();
            }

            return $req->results->data;

        }

        /**
         * Revoke and delete current app tokens from our database
         *
         * @return boolean
         */
        public function revoking() {

            $status = false;

            $permission_url = esc_url_raw(add_query_arg(
                array(
                    'access_token' => $this->access_token
                ),
                $this->graph_url . '/' . $this->og_version . '/' . $this->api_slug['permissions']
            ));

            $req = $this->request( $permission_url, 'DELETE' );

            if( ! $req->error ) {

                $is_updated = $this->scoda_delete_option( $this->social_name, array(
                    'access_token',
                    'accounts'
                ));

                if ( $is_updated ) {

                    // Case we complete the revoking we have to show it in the app status
                    $this->scoda_set_option( $this->social_name, array(
                        'is_revoked' => true
                    ));

                    // store status
                    $status = true;

                }

            }

            return  $status;

        }

        /**
         * Authorize Steps ( One method contains all prev callable )
         *
         * @return object
         */
        public function oauth( $oauth_code ){

            // Build Status
            $error = new stdClass();

            $error->error = false;

            // Authenticating users with all permissions
            $authenticated = $this->authentication( $oauth_code );
            if ( $authenticated->error ) {
                $error->error   = true;
                $error->message = $authenticated->message;
                return $error;
            }

            // Read and reterieve all pages and groups
            $gps = $this->get_current_pages_groups();
            if ( !$gps ) {
                $error->error   = true;
                $error->message = __( "We Could't able to get all of your accounts, something went wrong" );
            }

            return $error;

        }


        /**
         * Get Feeds with paging For ( Pages - Groups - timeline )
         *
         * @param $account_id
         * @param $next_prev
         * @param $feed_limit
         *
         * @return object
         */
        public function get_feeds( $account_id, $next_prev = null, $feed_limit = null ) {

            $feeds = new stdClass();
            $feeds->error = true;

            // First we need to get all accounts from our database
            $fb = $this->scoda_get_option( $this->social_name );
            if ( ! isset( $fb['accounts'] ) || ! count( $fb['accounts'] ) ) {
                $feeds->message = __( "You don't have accounts anymore !" );
                return $feeds;
            }

            // Get index of needed by account id
            $index = $this->find_index(  $fb[ 'accounts' ], "id", $account_id );
            if ( -1 === $index ) {
                $feeds->message = __( "This account does not exists !" );
                return $feeds;
            }

            // Get current account details
            $account      = ( object ) $fb['accounts'][$index];
            $api_url_args = array(
                'access_token' => $account->token,
                'fields' => "id,message,full_picture,created_time,is_published,is_popular,attachments,admin_creator"
            );

            if ( $account->type !== 'page' ) {
                $api_url_args['access_token'] = $this->access_token;
            }

            if ( $feed_limit !== null ) {
                $api_url_args['limit'] = $feed_limit;
            }

            $api_url = esc_url_raw(add_query_arg(
                $api_url_args,
                $this->graph_url . '/' . $this->og_version . '/' . str_replace( "scoda_access_id", $account->id, $this->api_slug['feed'] )
            ));

            if ( $next_prev !== null ) {
                $api_url = $next_prev;
                if ( ! strpos( $api_url, 'limit' ) && $feed_limit !== null ) {
                    $api_url .= sprintf( '&limit=%1$s', $feed_limit );
                }
            }

            $request = $this->request( $api_url, "GET" );

            if ( ! $request->error ) {

                $feeds->error   = false;
                $feeds->message = __( "Success" );
                $feeds->results = (object) array(
                    'account_type' => $account->type,
                    'feeds' => $request->results->data,
                    'paging' => array(
                        "next" => isset( $request->results->paging['next'] )? $request->results->paging['next']: '',
                        "previous" => isset( $request->results->paging['previous'] )? $request->results->paging['previous']: ''
                    )
                );

            }

            return $feeds;

        }

        /**
         * Post Feeds Into ( Pages - Groups )
         *
         * @param $account_id => stored on scoda options
         * @param $args => message - scheduled_publish_time - ( link - description - name  - picture - thumbnail )
         *
         * @return object
         */
        public function post_feed( $account_id,  $body = array() ) {

            $feeds = new stdClass();
            $feeds->error = true;

            // First we need to get all accounts from our database
            $fb = $this->scoda_get_option( $this->social_name );
            if ( ! isset( $fb['accounts'] ) || ! count( $fb['accounts'] ) ) {
                $feeds->message = __( "You don't have accounts anymore !" );
                return $feeds;
            }

            // Get index of needed by account id
            $index = $this->find_index(  $fb[ 'accounts' ], "id", $account_id );
            if ( -1 === $index ) {
                $feeds->message = __( "This account does not exists !" );
                return $feeds;
            }

            // Get current account details
            $account = ( object ) $fb['accounts'][$index];
            $args = array(
                'access_token' => $account->token
            );

            if ( $account->type !== 'page' ) {
                $args['access_token'] = $this->access_token;
            }

            // Do Request
            $post_url = esc_url_raw(add_query_arg(
                $args,
                $this->graph_url . '/' . $this->og_version . '/' . str_replace( "scoda_access_id", $account->id, $this->api_slug['feed'] )
            ));

            $request = $this->request(  $post_url, "POST", $body );

            if ( !$request->error ) {

                $feeds->error = false;
                $feeds->results = $request->results;
                $feeds->message = __( "Success !" );

            } else {

                $feeds->results = array();
                $feeds->message = $request->message;
            }

            return $feeds;
        }

        /**
         * Delete Post Feed Only In ( Pages )
         * @param $post_object_id
         *
         * @return object
         */
        public function update_post_feed( $post_object_id, $body = array() ) {

            $feeds = new stdClass();
            $feeds->error = true;

            // extract page or group or timeline id
            $account_id = explode( "_", $post_object_id );
            $account_id = $account_id[0];

            // First we need to get all accounts from our database
            $fb = $this->scoda_get_option( $this->social_name );
            if ( ! isset( $fb['accounts'] ) || ! count( $fb['accounts'] ) ) {
                $feeds->message = __( "You don't have accounts anymore !" );
                return $feeds;
            }

            // Get index of needed by account id
            $index = $this->find_index(  $fb[ 'accounts' ], "id", $account_id );
            if ( -1 === $index ) {
                $feeds->message = __( "This account does not exists !" );
                return $feeds;
            }

            // Get current account details
            $account = ( object ) $fb['accounts'][$index];
            $args = array(
                'access_token' => $account->token
            );

            if ( $account->type !== 'page' ) {
                $args['access_token'] = $this->access_token;
            }

            $post_url = esc_url_raw(add_query_arg(
                $args,
                $this->graph_url . '/' . $this->og_version . '/' . $post_object_id
            ));

            $request = $this->request(  $post_url, "POST", $body );

            if ( !$request->error ) {

                $feeds->error = false;
                $feeds->results = $request->results;
                $feeds->message = __( "Success !" );

            } else {

                $feeds->results = array();
                $feeds->message = $request->message;
            }

            return $feeds;

        }

        /**
         * Delete Post Feed Only In ( Pages )
         * @param $post_object_id
         *
         * @return object
         */
        public function delete_post_feed( $post_object_id ) {

            $feeds = new stdClass();
            $feeds->error = true;

            // extract page or group or timeline id
            $account_id = explode( "_", $post_object_id );
            $account_id = $account_id[0];

            // First we need to get all accounts from our database
            $fb = $this->scoda_get_option( $this->social_name );
            if ( ! isset( $fb['accounts'] ) || ! count( $fb['accounts'] ) ) {
                $feeds->message = __( "You don't have accounts anymore !" );
                return $feeds;
            }

            // Get index of needed by account id
            $index = $this->find_index(  $fb[ 'accounts' ], "id", $account_id );
            if ( -1 === $index ) {
                $feeds->message = __( "This account does not exists !" );
                return $feeds;
            }

            // Get current account details
            $account = ( object ) $fb['accounts'][$index];
            $args = array(
                'access_token' => $account->token
            );

            if ( $account->type !== 'page' ) {
                $args['access_token'] = $this->access_token;
            }

            $post_url = esc_url_raw(add_query_arg(
               $args,
                $this->graph_url . '/' . $this->og_version . '/' . $post_object_id
            ));

            $request = $this->request(  $post_url, "DELETE" );

            if ( !$request->error ) {

                $feeds->error = false;
                $feeds->results = $request->results;
                $feeds->message = __( "Success !" );

            } else {

                $feeds->results = array();
                $feeds->message = $request->message;
            }

            return $feeds;

        }

        /**
         * Update Account Follower Counters
         * @param $ids array contains ids OR string with 'all' value
         * @param $exclude to exclude account type from being update
         *
         * @return boolean
         */
        public function update_follower_counters( $ids, $exclude = 'timeline' ){

            if ( $ids !== 'all' && !is_array( $ids ) ) {
                return false;
            }

            // Store it here by default
            $account_ids = array();
            $fb = $this->scoda_get_option( $this->social_name );

            if ( !isset( $fb['accounts'] ) || ! count( $fb['accounts'] ) ) {
                return false;
            }

            $accounts = $fb['accounts'];

            // extract all ids of our database
            if ( $ids === 'all' ) {

                foreach( $accounts as $account ):
                    if (  $account['type'] !== $exclude ) {
                        $account_ids[] = array(
                            'id' => $account['id'],
                            'token' => ( $account['type'] === 'page' ) ? $account['token']:false,
                            'type' => $account['type']
                        );
                    }
                endforeach;

            }

            // Lets check if current selected ids are exactly the same in our database
            if ( is_array( $ids ) ) {

                foreach( $ids as $id ):
                    $index =  $this->find_index(  $accounts, "id", $id  );
                    if ( -1 !== $index ) {
                        if ( $accounts[$index]['type'] !== $exclude ) {
                            $account_ids[] = array(
                                'id' => $accounts[$index]['id'],
                                'token' => ( $accounts[$index]['type'] === 'page' )? $accounts[$index]['token']:false,
                                'type' =>  $accounts[$index]['type']
                            );;
                        }
                    }
                endforeach;

            }


            // Send requests according to each account ( Pages or groups )
            foreach( $account_ids as $aid ) {

                $url_args = array(
                    'access_token' => $aid['token'],
                    'fields' => ( $aid['type'] === 'page' ) ? 'fan_count': 'member_count'
                );

                if ( $aid['type'] !== 'page'  ) {
                    $url_args['access_token'] = $this->access_token;
                }

                $url =  $this->graph_url . '/' . $this->og_version . '/' . $aid['id'];

                $api_url = add_query_arg( $url_args, $url );
                $request = $this->request( $api_url, "GET" );
                if ( $request->error ) {
                    continue;
                }

                $user_count = $aid['type'] !== 'page'? $request->results->member_count: $request->results->fan_count;

                $index = $this->find_index( $accounts, "id", $request->results->id );
                if ( $index !== -1 ) {
                    $accounts[$index]['users_count'] = $user_count + 3;
                }

            }

            // Updates
            $is_updated = $this->scoda_set_option(
                $this->social_name,
                array(
                    'accounts' => apply_filters('eratags/scoda/store_facebook_accounts', $accounts )
                )
            );

            if ( $is_updated ) {
                return true;
            }

            return false;
        }


    }

}
