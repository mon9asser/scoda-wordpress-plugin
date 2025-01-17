<?php 

/**
 * Extract one array from big array contains a lot of arrays 
 * @param $main_array
 * @param $args to select 
 * 
 * @return object 
 */
if ( ! function_exists( 'tags_get_array_field_from_big_array' ) ) {

    function tags_get_array_field_from_big_array( $arrays, $args ) {
        
        $result = array();

        foreach ( $arrays as $single ) {

           
            foreach ( $args as $key => $value) {
                
                if ( isset( $single[$key] ) ) {
                    
                    if (  $value === $single[$key] ) {
                    
                        $result = $single; 

                    }

                }

            }

            if ( count( $result ) ) {
                break;
            }
            
        }
        
        return $result;
    }

    add_filter( 'eratags/get_array_field', 'tags_get_array_field_from_big_array', 10, 2 );

}

/**
 * Filter Of Eratags to set option name for our current item 
 *  
 * @param $opts_key default option key name 
 * 
 * @return string option key name 
 */
if ( !function_exists( 'eratags_item_option_name' ) ) {
    
    function eratags_item_option_name( $opts_key ) {
        return 'eratags_options';
    }

    add_filter( 'eratags/option_key', 'eratags_item_option_name', 10, 1 );

}