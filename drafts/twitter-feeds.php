<?php

// GET FOLLOWERS COUNT
--------------------------------
add_action( 'init', function() {

    $instance = new Scoda_Twitter();
    $instance ->tags_set_option( 'twitter', array(
        'consumer_key' => 'cRjDqgXSnT3bRaUvwxxEFEJS4',
        'consumer_secret' => 'zkugHGY5YfKjbDn5McTC6gMiXwdUhIuHHj2MEacrPtIZARzNrx',
        'access_token' => '2973799125-oz1KSwAd1TP5fYxNIJYPbk7GWQjr1kY2AaZ2Acd',
        'secret_access_token' => 'go3r8Wbyi0CIG2gOeJ3Av5h1JlMTSYj89GvLZtWdWhdW6'
    ));

    $aps = $instance->tags_get_option( 'twitter' );
    echo '<pre>';
    print_r( $instance->get_user_info( 'montasser_88' ) );
    echo '</pre>';
});






















// Get Feeds
$api_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    $method = 'GET';
    $consumer_secret_key = 'zkugHGY5YfKjbDn5McTC6gMiXwdUhIuHHj2MEacrPtIZARzNrx';
    $token_secret_key = 'go3r8Wbyi0CIG2gOeJ3Av5h1JlMTSYj89GvLZtWdWhdW6';
    $oauth_args = array(
        'oauth_consumer_key' => 'cRjDqgXSnT3bRaUvwxxEFEJS4',
        'oauth_token' => '2973799125-oz1KSwAd1TP5fYxNIJYPbk7GWQjr1kY2AaZ2Acd',
        'oauth_nonce' => md5( mt_rand() . time() ), // a stronger nonce is recommended
        'oauth_timestamp' => time(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_version' => '1.0'
    );

    $field_args = array(
        'screen_name' => 'montasser_88',
        'count' => '5'
    );

    $outh = $inst->oauth_signature( $api_url, $method, $consumer_secret_key, $token_secret_key, $oauth_args, $field_args  ) ;
    $api_url = add_query_arg(
        $field_args,
        $api_url
    );

    $result = $inst->request($api_url, array(
        'headers' => array(
            'Authorization' => $outh['header']
        )
    ), $method );

    echo "<pre>";
    print_r( $result );
    echo "</pre>";




























    public function test( $status ) {

        $credentials = array(
            'secret_access_token'  => $this->consumer_secret_access_token,
            'access_token'         => $this->consumer_access_token,
            'consumer_key'         => $this->consumer_key,
            'secret_consumer_key'  => $this->consumer_secret_key
        );

        $fields = array(
            'type' => 1,
            'data' => array(
                'status' => 'Hello world, this tweet is create by our app'
            )
        );
        $method  = 'POST';
        $api_url = $this->host . $this->version . $this->endpoint['add_tweet'];
        $extra_oauth = array();

        $buid = $this->oauth_signature( $api_url, $method, $credentials, $fields, $extra_oauth );

        $res = $this->request( $api_url, array(
            'headers' => $buid['header'],
            'body'    => $fields['data']
        ), $method);
        echo "<pre>";
        print_r( $res );
        echo "</pre>";
    }



    public function test2( $screen_name) {

        $credentials = array(
            'secret_access_token'  => $this->consumer_secret_access_token,
            'access_token'         => $this->consumer_access_token,
            'consumer_key'         => $this->consumer_key,
            'secret_consumer_key'  => $this->consumer_secret_key
        );

        $fields = array(
            'type' => 0,
            'data' => array(
               'screen_name' => $screen_name,
               'count'      => 3
            )
        );
        $method  = 'GET';
        $api_url = $this->host . $this->version . $this->endpoint['timeline_feeds'];
        $extra_oauth = array();

        $buid = $this->oauth_signature( $api_url, $method, $credentials, $fields, $extra_oauth );

        $api_url = add_query_arg(
            $fields['data'],
            $api_url
        );
        $res = $this->request( $api_url, array(
            'headers' => $buid['header']
        ), $method);
        echo "<pre>";
        print_r( $res );
        echo "</pre>";
    }



    public function test3() {


        $credentials = array(
            'secret_access_token'  => $this->consumer_secret_access_token,
            'access_token'         => $this->consumer_access_token,
            'consumer_key'         => $this->consumer_key,
            'secret_consumer_key'  => $this->consumer_secret_key
        );

        $fields = array(
            'type' => 0,
            'data' => array()
        );
        $method  = 'POST';
        $api_url = $this->host . $this->endpoint['oauth_token'];
        $extra_oauth = array(
            'oauth_callback'          => $this->redirect_url
        );

        $buid = $this->oauth_signature( $api_url, $method, $credentials, $fields, $extra_oauth );

        $res = $this->request( $api_url, array(
            'headers' => $buid['header']
        ), $method);
        echo "<pre>";
        print_r( $res );
        echo "</pre>";
    }





















    // Request Token
    $inst = new Eratags_Helper();
    $api_url = 'https://api.twitter.com/oauth/request_token';
    $method = 'POST';
    $consumer_secret_key = 'zkugHGY5YfKjbDn5McTC6gMiXwdUhIuHHj2MEacrPtIZARzNrx';
    $token_secret_key = 'go3r8Wbyi0CIG2gOeJ3Av5h1JlMTSYj89GvLZtWdWhdW6';
    $oauth_args = array(
        'oauth_consumer_key' => 'cRjDqgXSnT3bRaUvwxxEFEJS4',
        'oauth_token' => '2973799125-oz1KSwAd1TP5fYxNIJYPbk7GWQjr1kY2AaZ2Acd',
        'oauth_nonce' => md5( mt_rand() . time() ), // a stronger nonce is recommended
        'oauth_timestamp' => time(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_version' => '1.0',
        'oauth_callback' => 'http://localhost/wp'
    );

    $field_args = array();

    $outh = $inst->oauth_signature( $api_url, $method, $consumer_secret_key, $token_secret_key, $oauth_args, $field_args  ) ;
    $api_url = add_query_arg(
        $field_args,
        $api_url
    );

    $result = $inst->request($api_url, array(
        'headers' => array(
            'Authorization' => $outh['header']
        )
    ), $method );

    echo "<pre>";
    print_r( $result );
    echo "</pre>";











    // Follower Counts
    $dasdsd = $this->request( 'https://api.twitter.com/1.1/users/show.json?screen_name=montasser_88',
            array(
                'headers' => array(  'Authorization' => 'Bearer ' . $this->bearer_token )
            ), "GET" );
            print_r( $dasdsd );
