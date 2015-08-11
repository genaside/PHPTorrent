<?php

/**
 * 64bit unsigned int
 */
class Uint64BE{
    //$byte_array = "00000000";
    
    const MIN_VAL = "0";
    const MAX_VAL = "18446744073709551615";
    
    private $binary_string = 0; // should always be 8 bytes long
    //private $high_32 = 0;
    //private $low_32 = 0;
    
    
    /**
     * Assign an initial value
     * @param $value The value repersenting as a string to assign
     */
    public function __construct( $value ){
        $temp = strval( $value );
        $this->convert( $temp );        
    }
    
    
    public function __toString(){ 
        return "hello";    
    }
    
    /**
     * Convert numeric string into binary and place into varibles
     * @param $value The value repersenting as a string to assign
     */
    public function convert( $value ){ 
        // Check for negative
        $is_negative = false;        
        if( strpos( $value, '-' ) !== false ){
            $is_negative = true;
        }
        
        // convert to binary
        $lookup = array(
            "1",
            "2",
            "4",
            "8",
            "16",
            "32",
            ""
        );
        $length = strlen( $value );
        for( $i = 0; $i < $length; ++$i ){
            
        }
        
    }
    
    // Arithmetic
    
    /**
     * Add value to this one.
     * @param $value The value repersenting as a string to assign
     */
    public function add( $value ){ }

}

















