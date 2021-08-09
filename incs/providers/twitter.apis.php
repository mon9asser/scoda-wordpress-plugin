<?php  
/**
 * This file contains all Twitter Api services 
 * 
 * @author Eratags
 * @link eratags.com
 * @since 1.0
 * @package Scoail Coda => ( Scoda )
 * @subpackage incs/providers/twitter 
 * 
 * ------------------------------------------------------------------------
 * Table of Methods
 * ------------------------------------------------------------------------
 * @method credentials            : return an array contains all app keys
 * @method get_bearer_token       : generate authorization bearer token
 * @method get_oauth_token        : generate OAuth Token   
 * @method authorization_url      : to build an authorization link
 * @method get_user_info          : retrieve user followers count and some information
 * @method screen_timeline_tweets : to get timeline tweets from selected account by screen name  
 * @method home_timeline_tweets   : to get all user home tweets provided by parameters as an optional 
 * @method timeline_create_tweet  : to create and tweet a status on user timeline twitter
 * @method destroy_status         : to delete a status from user timeline twitter
 * @method share_url              : to generate share url with selected parameters  
 * @method get_follower_counts	  : to get followers count for current account 
 * 
 */

if ( !class_exists( 'Scoda_Twitter' ) ) {
    
    class Scoda_Twitter extends Eratags_Helper {
        
        /**
         * Basic Options
         */
        private $social_name;
        private $host;
        private $endpoint;
        private $redirect_url;
        private $version;

        /**
         * To Load Basic Credentials 
         */
        private $consumer_key;
        private $consumer_secret_key;
        private $consumer_access_token;
        private $consumer_secret_access_token;  

        /**
         * To Load Bearer token  
         */
        private $bearer_token;
        
        /**
         * To Load Oauth Signature 
         */
        private $oauth_token; 

        /**
         * Store Default options from database once is loaded 
         */
        public function __construct() {
            
            // Basic Options 
            $this->social_name = 'twitter'; 
            $this->host = 'https://api.twitter.com';
            $this->version = '/1.1';  
            $this->endpoint = array(
                'oauth2_token'   => '/oauth2/token',
                'user_info'      => '/users/show.json',
                'oauth_token'    => '/oauth/request_token',
                'authorization'  => '/oauth/authorize',
                'timeline_feeds' => '/statuses/user_timeline.json',
                'home_feeds'     => '/statuses/home_timeline.json',
                'tweets'         => '/statuses/show.json',
                'add_tweet'      => '/statuses/update.json',
                'destroy_tweet'  => '/statuses/destroy/%1$s.json'
            );

            // Load Credential From Database Option 
            $options                            = $this->tags_get_option( $this->social_name );
            $this->redirect_url                 = $this->tags_get_option( 'redirect_url', '' );
            $this->consumer_key                 = isset( $options['consumer_key'] )? $options['consumer_key']: '';
            $this->consumer_secret_key          = isset( $options['consumer_secret'] )? $options['consumer_secret']: '';
            $this->consumer_access_token        = isset( $options['access_token'] )? $options['access_token']: '';
            $this->consumer_secret_access_token = isset( $options['secret_access_token'] )? $options['secret_access_token']: '';
            
        }  
        
        /**
         * Load App Credentials 
         */
        private function credentials() {
            
            $credentials = array(
                'secret_access_token'  => $this->consumer_secret_access_token,
                'access_token'         => $this->consumer_access_token,
                'consumer_key'         => $this->consumer_key,
                'secret_consumer_key'  => $this->consumer_secret_key
            );

            return $credentials ;

        }

        /**
         * Generate and request bearer token
         *  
         * @api /oauth2/token
         * 
         * @return string 
         */
        public function get_bearer_token() {

            // Generate Basic Authorization 
            $credentials = sprintf(
                '%1$s:%2$s',
                $this->consumer_key,
                $this->consumer_secret_key
            );

            // base64 
            $authorization = sprintf(
                'Basic %1$s',
                base64_encode( $credentials )
            ); 

            // we already used wp built-in escalization function in request callback 
            $api_url  = $this->host . $this->endpoint['oauth2_token'];

            // collect args to send 
            $args = array(
                'headers' => array(  'Authorization' => $authorization ),
                'body' => array( 'grant_type' => 'client_credentials' )
            );

            // Send HTTP Request
            $response = $this->request( $api_url, $args, "POST" );
            if ( $response->error || !isset( $response->data->access_token ) ) {
                return false;
            } 

            // Store it in our class attributes ( we dont need to store it in db)
            $this->bearer_token = sprintf( 'Bearer %1$s', $response->data->access_token );
            
            return $this->bearer_token;
        }

        /**
         * Generate OAuth Signature & Request OAuth Token 
         * 
         * @api /oauth/request_token
         * 
         * @return string
         */
        public function get_oauth_token() {
            
            // Build Oauth API url 
            $api_url = $this->host . $this->endpoint['oauth_token'];

            // Load Needed Creadentials 
            $credentials = $this->credentials(); 
            
            // Add new oauth fields 
            $oauth = array( 'oauth_callback' => $this->redirect_url );
            
            // Build oAuth Signature 
            $build = $this->oauth_signature(
                $api_url, 
                "POST", 
                $credentials, 
                array(), 
                $oauth
            );
            
            if ( !isset( $build['header'] ) ) {
                return '';
            }
            
            // Set Authorization string header  and send http request
            $auth_header = array( 'Authorization' => $build['header'] );
            
            // Send HTTP Request 
            $response    = $this->request( $api_url, array( 'headers' => $auth_header ), "POST" ); 
            
            if ( ! $response->error ) {
                
                // Handling string response and extract oauth_token 
                $data_args = explode( '&', $response->data );

                if ( count( $data_args ) ) {
                    
                    $oauth_token_arr =  explode( '=', $data_args[0] );
                    
                    $this->oauth_token = trim( $oauth_token_arr[1] );
                    
                    return $this->oauth_token;
                
                }

            }

            return '';
        }

        /**
         * Generate Authorization Url
         * 
         * @api /oauth/authorize  
         * 
         * @return string 
         */
        public function authorization_url() {

            // Request OAuth token 
            $this->get_oauth_token();

            // build api url 
            $api_url = add_query_arg(
                array( 'oauth_token' => $this->oauth_token ),
                $this->host . $this->endpoint['authorization']
            );

            return esc_url_raw( $api_url );

        }

        /**
         * Get Follower Counts and user data
         * 
         * @api /users/show.json
         * @param $user_name => screen name 
         * 
         * @return array contains user info and follower counts  
         */
        public function get_user_info( $user_name ) {
            
            // Get Bearer Token 
            $this->get_bearer_token();

            $args = array(
                'headers' => array(
                    'Authorization' => $this->bearer_token
                )
            );

            $api_url = add_query_arg(
                array( 'screen_name' => $user_name ),
                $this->host . $this->version . $this->endpoint['user_info']
            );

            $response = $this->request( $api_url, $args, "GET" );
            if ( $response->error ) {
                return $response;
            }

            // Custom data Fields by filter  
            $twitter_user_info = apply_filters( 'eratags/scoda/twitter/timeline_tweets', $response->data );
            
            return $twitter_user_info;
        }

        /**
         * Get Tweets of timeline
         *  
         * @api /statuses/user_timeline.json
         * @param $screen_name
         * @param $params => props are ( user_id, since_id, count, max_id, exclude_replies, include_rts )
         * 
         */
        public function screen_timeline_tweets( $screen_name , $params = array() ) {
    
            // Build Oauth API url 
            $api_url =  $this->host . $this->version . $this->endpoint['timeline_feeds'];

            // Load Needed Creadentials and fields
            $credentials = $this->credentials(); 
            $fields      = array(
                'data' => array(
                    'screen_name' => $screen_name
                ),
                'type' => 0
            );

            if ( count( $params ) ) {
                $fields['data'] = wp_parse_args( $fields['data'], $params );
            }

            // Build oAuth Signature 
            $build = $this->oauth_signature(
                $api_url, 
                "GET", 
                $credentials, 
                $fields 
            );
            
            if ( !isset( $build['header'] ) ) {
                return '';
            }
            
            // Build request arguments 
            $args = array(
                'headers' => array(
                    'Authorization' => $build['header']
                )
            );
            
            // build api uri 
            $api_url = add_query_arg( $fields['data'], $api_url );

            // Send to http request 
            $response = $this->request( $api_url, $args );

            // Handling errors 
            if ( $response->error ) {
                return $response;
            }

            $response->data = ( array ) $response->data;

            // Execute filter 
            return apply_filters( 'eratags/scoda/twitter/timeline_tweets', $response->data, $screen_name );
        }

        /**
         * Get Tweets of Home Timeline
         *  
         * @api /statuses/hometimeline.json 
         * @param $params => ( count, since_id, max_id, trim_user, exclude_replies, include_entities )
         * 
         */
        public function home_timeline_tweets( $params = array() ) {

            // build api url
            $api_url = $this->host . $this->version . $this->endpoint['home_feeds'];
            
            // default counts 
            if ( !isset( $params['count'] ) ) {
                $params['count'] = 5;
            }

            // Load Needed Creadentials and fields
            $credentials = $this->credentials(); 
            $fields      = array(
                'data' => $params,
                'type' => 0
            );
            
            // Build oAuth Signature 
            $build = $this->oauth_signature(
                $api_url, 
                "GET", 
                $credentials, 
                $fields 
            );
            
            if ( !isset( $build['header'] ) ) {
                return '';
            }
            
            // Build request arguments 
            $args = array(
                'headers' => array(
                    'Authorization' => $build['header']
                )
            );

            // build api uri 
            $api_url = add_query_arg( $fields['data'], $api_url );

            // Send to http request 
            $response = $this->request( $api_url, $args );

            // Handling errors 
            if ( $response->error ) {
                return $response;
            }

            $response->data = ( array ) $response->data;

            // Execute filter 
            return apply_filters( 'eratags/scoda/twitter/timeline_tweets', $response->data, null );

        }

        /**
         * Post Tweet in timeline
         * 
         * @api statuses/update.json
         * @param $status_text
         * @param $params
         * 
         * @return object
         */
        public function timeline_create_tweet( $status_text, $params = array() ) {

            // Build URL 
            $api_url = $this->host . $this->version . $this->endpoint['add_tweet'];
            
            // Load Needed Creadentials and fields
            $credentials = $this->credentials(); 
            $fields = array(

                'data' => array(
                    'status' => $status_text
                ),
                
                'type' => 1
            );

            // unset status field from array because we already have status argument by default 
            if ( count( $params ) ) {
                
                if ( isset( $params['status'] ) ) {
                    unset($params['status']);
                }

                $fields['data'] = wp_parse_args( $fields['data'], $params );
            }

            // Build oAuth Signature 
            $build = $this->oauth_signature(
                $api_url, 
                "POST", 
                $credentials, 
                $fields 
            );
            
            if ( ! isset( $build['header'] ) ) {
                return array();
            }
            
            $args = array(
                'headers' => array(
                    'Authorization' => $build['header'],
                    'Content-Type'  => 'application/x-www-form-urlencoded;charset=UTF-8'
                ),
                'body' => $fields['data'],
                'timeout' => 15
            );
 

            // Send request 
            $response = $this->request( $api_url, $args, "POST" );
             
            if ( $response->error ) {
                return $response;
            }

            return $response->data; 
             
        }
        
        /**
         * Destroy statuses by id
         * 
         * @api statuses/destroy/:id
         * @param $status_id
         * 
         * @return object
         */
        public function destroy_status( $status_id ) {

            // Build Api URL 
            $api_url = sprintf(
                $this->host . $this->version . $this->endpoint['destroy_tweet'],
                $status_id
            );
            
            // App credentials 
            $credentials = $this->credentials();

            // Build oAuth Signature 
            $build = $this->oauth_signature(
                $api_url, 
                "POST", 
                $credentials  
            );

            if ( !isset( $build['header'] ) ) {
                return '';
            }

            // Build request arguments 
            $args = array(
                'headers' => array(
                    'Authorization' => $build['header']
                )
            );

            // Send to http request 
            $response = $this->request( $api_url, $args, "POST" );

            // Handling errors 
            if ( $response->error ) {
                return $response;
            }

            return $response->data;
        }

        /**
         * Share Into Twitter 
         */
        public function share_url( $text, $url = null, $hashtag = null, $via = null, $related = null, $in_reply_to = null ){
            
            // Collect parameters 
            $args = array();
            
            $args['text'] = $text;

            if ( ! is_null( $url ) ) {
                $args['url'] = $url;
            }

            if ( !is_null( $hashtag ) ) {
                $args['hashtags'] = $hashtag; 
            }        

            if ( !is_null( $via ) ) {
                $args['via'] = $via; 
            } 
            
            if ( !is_null( $related ) ) {
                $args['related'] = $related; 
            } 

            if ( !is_null( $in_reply_to ) ) {
                $args['in_reply_to'] = $in_reply_to; 
            } 

            // build share link 
            $share = esc_url_raw(add_query_arg(
                $args,
                'https://twitter.com/intent/tweet'
            ));

            return $share;
        }

		public function get_follower_counts( $id ) {
			
		}
    }

}