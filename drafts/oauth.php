// build api url
$api_url = $this->host . $this->version . $this->endpoint['home_feeds'];

// getting fields
$fields = array(
   'count'       => 15 // default
);

// Merge params with fields array
$fields = wp_parse_args( $params, $fields);

// Build array of oauth
$oauth = array(
   'oauth_consumer_key'      => $this->consumer_key,
   'oauth_nonce'             => md5( time() . mt_rand() ),
   'oauth_timestamp'         => time(),
   'oauth_signature_method'  => 'HMAC-SHA1',
   'oauth_token'             => $this->consumer_access_token,
   'oauth_version'           => '1.0'
);

// Build and hash oauth signature header
$build = $this->oauth_signature( $api_url, "GET", $this->consumer_secret_key, $this->consumer_secret_access_token, $oauth, $fields );
if ( !isset( $build['header'] ) ) {
   return array();
}

// Build Authorization Header
$auth_header = array(
   'Authorization' => $build['header']
);

// Build Api URL Fields
$api_url = add_query_arg( $fields,  $api_url );

// Send Request
$response = $this->request( $api_url, array( 'headers' => $auth_header ), "GET" );

if ( $response->error ) {
   return $response;
}

// Get Retweet Counts
$response->data = ( array ) $response->data;

// Timeline Tweet Data
$tweet_data = apply_filters( 'eratags/scoda/twitter/timeline_tweets', $response->data, null );

return $tweet_data;
