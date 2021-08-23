<?php 



/**
 * Custom keys for our plugin option name
 * 
 * @package SCoda  
 * @return string 
 *  
 */
if ( !function_exists( 'scoda_filter_the_scoda_plugin_key_name_callback' ) ) {
	
	function scoda_filter_the_scoda_plugin_key_name_callback() {
		
		return 'eratags_scoda_options';

	}
	
	add_filter( 'eratags/option_key', 'scoda_filter_the_scoda_plugin_key_name_callback' );
}



/**
 * Custom user information data fields to be accessible in our backend
 * You know the apis are changing every moment so we made this filter to avoid any error 
 * 
 * @package twitter
 * @api follower counts and user information
 * @param $user_data
 * @return object 
 * 
 */
if ( !function_exists( 'scoda_custom_twitter_user_info_fields' ) ) {
    
    function scoda_custom_twitter_user_info_fields( $user ) {

		$obj = new stdClass();
		
        if ( ! isset( $user->id ) ) {
            return $user;
        } 

        $obj->id              = isset( $user->id )? $user->id: '';
        $obj->account_name    = isset( $user->name )? $user->name: '';
        $obj->followers_count = isset( $user->followers_count )? $user->followers_count: '';
        $obj->friends_count   = isset( $user->friends_count )? $user->friends_count: '';
        $obj->statuses_count  = isset( $user->statuses_count )? $user->statuses_count: '';
        
        return $obj;

    }

    add_filter( 'eratags/scoda/twitter/get_user_info', 'scoda_custom_twitter_user_info_fields', 10, 1 );

}

/**
 * Custom Timeline Tweet data fields to be accessible in our backend
 * You know the apis are changing every moment so we made this filter to avoid any error 
 * 
 * @package Twitter
 * @api timeline tweets
 * @param $tweets
 * @return array 
 * 
 */
if ( !function_exists( 'scoda_custom_timeline_tweet_fields' ) ) {
    
    function scoda_custom_timeline_tweet_fields( $tweets, $screen_name ) {

        if( !count( $tweets ) ) {
            return $tweets;
        }

        $obj = array();
        
        foreach ( $tweets as $key => $value) {
            
            // Default Values 
            $tweet = array(
                'id'            => isset( $value['id'] )? $value['id']: '',
                'created_at'    => isset( $value['created_at'] )? $value['created_at']: '',
                'tweet'         => '',
                'is_quoted'     => false,
                'link'          => '',
                'retweet_count' => 0,
                'likes'         => 0,
                'actions'       => array(
                    'retweet_link'  => '',
                    'reply_link'    => '',
                    'favorite_link' => ''
                ),
                'hashtags'      => array(), // => fields are  arrays contain ( text, link )
                'mentions'      => array(), // => fields are  arrays contain ( id, name, screen_name, link )
                'media'         => array()
            );

            // Handling Tweet Text 
            if ( isset( $value['text'] ) ) {
                $tweet['tweet'] = $value['text'];
            }

            // Tweet Hashtag with search link 
            if ( isset( $value['entities'] ) && isset( $value['entities']['hashtags'] ) ) {
                
                // Tweet Hashtags
                $hashtags = $value['entities']['hashtags'];

                if ( count( $hashtags ) ) {

                    foreach ( $hashtags as $hash_key => $hash_value) {

                        if (isset( $hash_value['text'] ) ) {

                            // Get Hash Text 
                            $hash_text = $hash_value['text'];
                            $hash_link = esc_url_raw(add_query_arg(
                                array(  
                                    'q' => rawurlencode_deep( '#' . $hash_value['text'] )
                                ),
                                'https://twitter.com/search'
                            ));

                            // Build and extract Tweet Hashtag 
                            $tweet['hashtags'][] = array(

                                'text'  => $hash_text,
                                'link'  => $hash_link

                            );

                            // Set Hash as a link 
                            $hash_url = sprintf( 
                                '<a href=\'%1$s\' target=\'_blank\'>#%2$s</a>', 
                                esc_attr( $hash_link ), 
                                esc_html( $hash_text ) 
                            );

                            // Replace Hashed With Their Links
                            $tweet['tweet'] = preg_replace( '/#' . $hash_text . '/i', $hash_url, $tweet['tweet'] );

                        } 

                    }

                }

            }

            // Tweet User Mentions  
            if ( isset( $value['entities'] ) && isset( $value['entities']['user_mentions'] ) ) {
                
                $mentions = $value['entities']['user_mentions'];
                
                if ( count( $mentions ) ) {

                    foreach ( $mentions as $mention_key => $mention_value) {
                        
                        if (isset( $mention_value['screen_name'] ) ) {

                            $user_screen_name = $mention_value['screen_name'];

                            // Build and extract User Mentions 
                            $tweet['mentions'][] = array(

                                'id'          => isset( $mention_value['id'] ) ? $mention_value['id']: '',
                                'name'        => isset( $mention_value['name'] ) ? $mention_value['name']: '',
                                'screen_name' => $user_screen_name,
                                'link'        => sprintf( esc_url_raw( 'https://twitter.com/%1$s' ), $mention_value['screen_name'] )
                            
                            );
                             
                            // Set user mentions as a link 
                            $user_mention_link = sprintf( 
                                '<a href=\'https://twitter.com/%1$s\' target=\'_blank\'>@%2$s</a>',
                                esc_attr( $user_screen_name ),  
                                esc_html( $user_screen_name ) 
                            );

                            // Replace Users these are mentioned with their links
                            $tweet['tweet'] = preg_replace( '/@' . $user_screen_name . '/i' , $user_mention_link , $tweet['tweet'] );

                        } 

                    }

                }

            }

            // Build and Extract Media From Tweet 
            if ( isset( $value['entities'] ) && isset( $value['entities']['media'] ) ) {

                $media = $value['entities']['media'];
                
                if ( count( $media ) ) {

                    foreach ( $media as $media_key => $media_value) {
                        
                        if ( isset( $media_value['media_url_https'] ) ) {

                            // Delete image links from tweets 
                            $tweet['tweet'] = str_replace( $media_value['url'], '', $tweet['tweet'] );

                            // Build and extract User Mentions 
                            $tweet['media'][] = array(
                                'id'           => isset( $media_value['id'] ) ? $media_value['id']: '',
                                'url'          => $media_value['media_url_https'],
                                'type'         => isset( $media_value['type'] )  ? $media_value['type'] : '', 
                                'expanded_url' => isset( $media_value['expanded_url'] )  ? $media_value['expanded_url']: ''
                            );
                        
                        } 

                    }

                }

            }

            // Build Tweet url 
            if ( $screen_name === null && isset( $value['user'] )) {
                $screen_name = $value['user']['screen_name'];
            }

            $tweet['link'] = esc_url_raw(sprintf(
                'https://twitter.com/%1$s/status/%2$s',
                $screen_name,
                $value['id']
            ));

            // Aditional info
            $tweet['retweet_count']     = isset( $value['retweet_count'] )? $value['retweet_count']: 0;
            $tweet['likes']             = isset( $value['favorite_count'] )?  $value['favorite_count']: 0;
            $tweet['is_quoted']         = isset( $value['is_quote_status'] )?  $value['is_quote_status']: false;
            
            // Add links to tweet ( Reply To, Retweet, Favorite )
            $tweet['actions']['retweet_link']   = esc_url_raw(add_query_arg(
                array( 'tweet_id' => $value['id'] ),
                'https://twitter.com/intent/retweet'
            ));
            $tweet['actions']['reply_link']     = esc_url_raw(add_query_arg(
                array( 'in_reply_to' => $value['id'] ),
                'https://twitter.com/intent/tweet'
            ));
            $tweet['actions']['favorite_link']  = esc_url_raw(add_query_arg(
                array( 'tweet_id' => $value['id'] ),
                'https://twitter.com/intent/favorite'
            ));
             
            // Change Links in tweet text to be anchors  
            if ( isset( $value['entities'] ) && isset( $value['entities']['urls'] ) ) {
                
                // List of tweet urls
                $urls = $value['entities']['urls'];
                
                if ( count( $urls ) ) {
                    
                    foreach ( $urls as $url_key => $url_value ) {
                        
                        if ( isset( $url_value['url'] ) ) {
                            
                            // Search by url text 
                            $search_url = $url_value['url'];
                            
                            // Tweet Link Anchor 
                            $tweet_link_anchor = sprintf(
                                '<a href=\'%1$s\' target=\'_blank\'>%2$s</a>',
                                esc_attr( $url_value['url'] ),
                                esc_html( $url_value['display_url'] )
                            ); 

                            // Replace target text with valid link
                            $tweet['tweet'] = str_replace( $url_value['url'], $tweet_link_anchor, $tweet['tweet'] );
                             
                        }
                        
                    }
                    
                }
                

            }

            $obj[] = ( object ) $tweet;
            
        }
        
        return $obj;

    }

    add_filter( 'eratags/scoda/twitter/timeline_tweets', 'scoda_custom_timeline_tweet_fields', 10, 2 );

}

/**
 * Custom Facebook Feed data fields to be accessible in our backend
 * You know the apis are changing every moment so we made this filter to avoid any error 
 * 
 * @package Facebook
 * @api /feed
 * @param $fields
 * @return array 
 * 
 */
if ( !function_exists( 'scoda_custom_facebook_feed_fields' ) ) {
    
    function scoda_custom_facebook_feed_fields( $fields ) {
        
        // Handling Attachment Fields
        foreach ( $fields as $ind => $field ) {
            
            $attachments = isset( $field['attachments'] )? $field['attachments']: array();
            $new_attachments = array(
                'id' => '',
                'type' => '',
                'data' => array()
            );

            if ( count( $attachments ) ) {
                
                $attachment              =  $attachments['data'][0];
                $new_attachments['id']   =  isset( $attachment['target']['id'] )? $attachment['target']['id']: '';
                $new_attachments['type'] =  isset( $attachment['type'] )? $attachment['type']: '';
                // Case album media type
                if (  isset( $attachment['subattachments'] ) ) {
                    
                    if ( count( $attachment['subattachments'] ) ) {
                        
                        $subattachments = $attachment['subattachments']['data'];
                                                 
                        foreach ( $subattachments as $media_data ) {
                            $album           = array();
                            $album['type']   = isset( $media_data['type'] )? $media_data['type']:'';
                            $album['url']    = isset( $media_data['url'] )? $media_data['url']:'';
                            $album['id']     = isset( $media_data['target']['id'] )? $media_data['target']['id']:'';
                            $album['src']    = isset( $media_data['target']['src'] )? $media_data['target']['src']:'';
                            $album['height'] = isset( $media_data['media']['image']['height'] )? $media_data['media']['image']['height']:'';
                            $album['width']  = isset( $media_data['media']['image']['width'] )? $media_data['media']['image']['width']:'';
                            
                            $album['description']  = isset( $media_data['description'] )? $media_data['description']:'';
                            $album['title']  	   = isset( $media_data['title'] )? $media_data['title']:'';
                            
                            $new_attachments['data'][] = $album;

                        }

                    }

                }

                // Case Single media 
                if ( $attachment['type'] !== 'album'  ) {
                    $new_attachments['data'][] = array(
                        'type'   => isset( $attachment['type'] )? $attachment['type']: '',
                        'url'    => isset( $attachment['url'] )? $attachment['url']: '', 
                        'id'     => isset( $attachment['target']['id'] )? $attachment['target']['id']: '',
                        'src'    => isset( $attachment['media']['image']['src'] )? $attachment['media']['image']['src']: '',
                        'height' => isset( $attachment['media']['image']['height'] )? $attachment['media']['image']['height']: '',
                        'width'  => isset( $attachment['media']['image']['width'] )? $attachment['media']['image']['width']: '',
                        
						// for native_templates
						'description'  => isset( $attachment['description'] )? $attachment['description']: '',
						'title'		   => isset( $attachment['title'] )? $attachment['title']: '',
                    );
                }

                
            }

            if ( count( $new_attachments['data']  ) ) {
                $fields[$ind]['attachments'] = $new_attachments;
            }
            
        }

        return $fields;
		
    }

    add_filter( 'eratags/scoda/facebook/get_feeds', 'scoda_custom_facebook_feed_fields', 10, 1 );

}
 
/**
 * Custom Facebook Feed Paging to be accessible in our backend
 * You know the apis are changing every moment so we made this filter to avoid any error 
 * 
 * @package Facebook
 * @api /feed
 * @param $paging
 * @param $account_type
 * @return array 
 * 
 */
if ( !function_exists( 'scoda_custom_facebook_feed_paging_fields' )  ) {

	function scoda_custom_facebook_feed_paging_fields( $paging, $account_type ) {

		$is_page = ( $account_type === 'page' ) ? true: false;

		if ( !isset( $paging['previous'] ) ) {
		
			$paging['previous'] = array();

		} else {

			$prev 		= explode( '&', $paging['previous'] );
			$previous	= array();

			foreach ( $prev as $field ) {

				$kv = explode( '=', $field ); // key and value array
				
				// Case account type is page 
				if ( $kv[0] === 'before' || $kv[0] === '__previous' || $kv[0] === 'since' || $kv[0] === '__paging_token' ) {
					$previous[trim($kv[0])] = trim( $kv[1] );
				}
				
			}
			
			$paging['previous'] = $previous;
		}

		if ( !isset( $paging['next'] ) ) {
			
			$paging['next'] = array();

		} else {

			$next 		= explode( '&', $paging['next'] );
			$next_field	= array();

			foreach ( $next as $field ) {

				$kv = explode( '=', $field ); // key and value array
				
				// Case account type is page 
				if ( $kv[0] === 'after' ||  $kv[0] === 'until' || $kv[0] === '__paging_token' ) {
					$next_field[trim($kv[0])] = trim( $kv[1] );
				}
				
			}
			
			$paging['next'] = $next_field;

		}

		return $paging;

	}

	add_filter( 'eratags/scoda/facebook/feed_paging', 'scoda_custom_facebook_feed_paging_fields', 10, 2  );
}

/**
 * Custom Blog Follower Counter to be accessible in our backend
 * You know the apis are changing every moment so we made this filter to avoid any error
 *
 * @package Tumblr
 * @api /:blog_name/follower
 *
 * @param $obj
 *
 * @return object
 *
 */
if ( !function_exists( 'scoda_custom_tumblr_blog_follower_fields' ) ) {

	function scoda_custom_tumblr_blog_follower_fields( $object_info, $obj_counter ) {

		$return = (  object ) array();

		// Getting Blog Follower Counts
		if ( isset( $obj_counter->meta ) ) {
			unset( $obj_counter->meta );
		}

		if ( isset( $obj_counter->response ) ) {
			$return->follower_counts = isset( $obj_counter->response['total_users'] ) ? $obj_counter->response['total_users']: 0;
		} else {
			$return->counter  = $obj_counter;
		}

		// Getting Blog Information
		if( isset( $object_info->response['blog'] ) ) {

			$blog = $object_info->response['blog'];

			foreach ( $blog as $key => $value ) {
				$return->{$key} = $value;
			}

		} else {
			$return->info  = $object_info;
		}

		return  $return;

	}

	add_filter( 'eratags/scoda/tumplr/blog_information', 'scoda_custom_tumblr_blog_follower_fields', 10, 2 );
}

/**
 * Custom Tumblr Blog Feeds Fields to be accessible in our backend
 * You know the apis are changing every moment so we made this filter to avoid any error
 *
 * @package Tumblr
 * @api /:blog_name/posts
 *
 * @param $feeds
 *
 * @return object
 *
 */
if ( !function_exists( 'scoda_custom_tumblr_blog_feed_fields' ) ) {

	if ( !function_exists( 'delete_unneeded_tumblr_blog_feed_fields' ) ) {

		function delete_unneeded_tumblr_blog_feed_fields( $field ) {

			$unneed = array( 'blog', 'id_string', 'trail' );
			foreach ( $unneed as $unneeded) {
				if ( isset( $field[$unneeded] ) ) {
					unset( $field[$unneeded] );
				}
			}
			return $field;

		}

	}

	function scoda_custom_tumblr_blog_feed_fields( $feeds ) {

		$posts = array();

		if ( !isset( $feeds->response ) || !isset( $feeds->response['posts'] ) ) {
			return $feeds;
		}

		$feeds = $feeds->response['posts'];

		// Delete unneded fields
		if ( function_exists( 'delete_unneeded_tumblr_blog_feed_fields' ) ) {
			$feeds = array_map( 'delete_unneeded_tumblr_blog_feed_fields', $feeds );
		}

		return $feeds;
	}

	add_filter( 'eratags/scoda/tumplr/blog_feeds', 'scoda_custom_tumblr_blog_feed_fields' );

}