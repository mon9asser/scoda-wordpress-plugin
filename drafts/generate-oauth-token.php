public function test_token( $url ) {
            
            // Build Oauth API url 
            $api_url = $url;

            // Load Needed Creadentials 
            $credentials = array(
				'secret_access_token'  => '',
                'access_token'         => '',
                'consumer_key'         => 'lr55t3IxXYQxlEkFEMzkDvAmUN2ttwEbUuwlawNctedt1HpShy',
                'secret_consumer_key'  => 'LXQyWfHBcQLLyevk0m392023ZRpunHfEKphIfQ9Gq1JCN9gGfb'
			); 
            
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
             
			return $response;

			
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















		public function oauth_signature( $api_url, $method, $credentials, $fields = array(), $extra_oauth = array() ) {

// Build Basic OAuth Fields 
$oauth = array(
	'oauth_consumer_key'     => $credentials['consumer_key'],
	'oauth_nonce'            => md5( mt_rand() . time() ),
	'oauth_signature_method' => 'HMAC-SHA1',
	'oauth_token'            => '', // $credentials['access_token']
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

/*
$keys = sprintf(
	'%1$s&%2$s',
	rawurlencode_deep( $credentials['secret_consumer_key'] ),
	rawurlencode_deep( $credentials['secret_access_token'] ),
); */

$keys = sprintf(
	'%1$s&%2$s',
	rawurlencode_deep( $credentials['secret_consumer_key'] ),
	rawurlencode_deep( '' ),
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

// Build exportable array 
return array(
	'header' => $oauth_header,
	'oauth'  => $oauth
);
}














