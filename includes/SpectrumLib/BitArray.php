<?php


/**
 * Array of bits
 */
class BitArray implements \Countable, \ArrayAccess{
       
    private $size;
    private $data;
    
    private $max_bits;
    private $end_shift;
    private $highest_bit;      
    
    /**
     * Assign an initial value
     *
     * @param mixed $value initialize BitArray with data or a defualt size
     * @note Once a size is set it can't be changed.     
     * 
     * Example Usage:
     * <code>
     * $bitarray = new BitArray( [ 1, 0, 0, 0 ] ); // initialize BitArray with array of 1s and 0s.
     * $bitarray = new BitArray( 7 ); // initialize BitArray's size, all bits are 0.     
     * </code>
     */
    public function __construct( $value ){
        $this->data = array();
        $this->size = 0;
        $this->max_bits = PHP_INT_SIZE * 8;
        $this->end_shift = $this->max_bits - 1;
        $this->highest_bit = 1 << $this->end_shift;
        
        if( is_array( $value ) ){
            // Initialize a BitArray with an array of 1s and 0s
            foreach( $value as $bit ){
                $data_byte_pos = (int)( $this->size / $this->max_bits  );
                if( !isset( $this->data[ $data_byte_pos ] ) ){
                    $this->data[ $data_byte_pos ] = 0;
                }
                
                $bit_position = $this->size % $this->max_bits;                
                // NOTE "Right shifts have copies of the sign bit shifted in on the left, meaning the sign of an operand is preserved."
                           
                if( $bit_position == 0 ){
                    $bit_on = ( $this->highest_bit >> $bit_position );   
                }else{
                    $bit_on = ( $this->highest_bit >> $bit_position ) ^ ( $this->highest_bit >> ( $bit_position - 1 ) );          
                }                
                
                if( $bit != 0 && $bit != 1 ){
                    throw new exception( "A bit must have a value of 0 or 1" );
                }else if( $bit == 1 ){                    
                    $this->data[ $data_byte_pos ] |= $bit_on;
                }else{
                    $this->data[ $data_byte_pos ] &= ~$bit_on;
                }  
                
                ++$this->size;
            }      
            ////echo $this->data[ 0 ];
        }else if( is_int( $value ) ){
            if( $value == 0 ){
                throw new exception( "Can't initialize BitArray of size 0" );
            }else if( $value < 0  ){
                throw new exception( "Can't initialize BitArray of a negative size" );
            }
            
            // Initialize a BitArray with a defualt size.
            $this->size = $value;
            // All bit are zero
            $ints = ceil( $this->size / $this->max_bits );            
            for( $i = 0; $i < $ints; ++$i ){
                array_push( $this->data, 0 );               
            }
        }else{
            throw new exception( "Datatype not supported" );
        }       
    }
    
    /**
     * Convert binary array to string representation
     */
    public function __toString(){ 
        return $this->toBinaryString();
    }
    
    public function count(){
        return $this->size; 
    }    
    
    public function offsetSet( $offset, $value ){
        $this->offsetTest( $offset );       
        $this->valueTest( $value );
        
        $int_array_position = (int)( $offset / $this->max_bits  );
        $bit_position = $offset % $this->max_bits ;
        
        if( $bit_position == 0 ){
            $bit_on = ( $this->highest_bit >> $bit_position );   
        }else{
            $bit_on = ( $this->highest_bit >> $bit_position ) ^ ( $this->highest_bit >> ( $bit_position - 1 ) );          
        }        
        
        if( $value == 1 ){       
            // Set bit
            $this->data[ $int_array_position ] |= $bit_on;            
        }else{
            // Clear bit
            $this->data[ $int_array_position ] &= ~$bit_on;
        }       
    }

    public function offsetExists( $offset ){
        if( $offset < $this->size && $offset > -1 ){
            return true;
        }else{
            return false;
        }
    }

    public function offsetUnset( $offset ){        
        throw new exception( "Can't unset array elements." );
    }

    public function offsetGet( $offset ){  
        $this->offsetTest( $offset );
        
        $int_array_position = (int)( $offset / $this->max_bits  );
        $bit_position = $offset % $this->max_bits ;
        
        if( $bit_position == 0 ){
            $bit_on = ( $this->highest_bit >> $bit_position );   
        }else{
            $bit_on = ( $this->highest_bit >> $bit_position ) ^ ( $this->highest_bit >> ( $bit_position - 1 ) );          
        }  
        
        if( ( $this->data[ $int_array_position ] & $bit_on ) != 0 ){         
            return 1;
        }else{
            return 0;
        }        
    }
    
    private function offsetTest( $offset ){
        $max_offset = $this->size;   
        
        if( !is_int( $offset ) ){
            throw new exception( "Offset:$offset is not a valid interger." );
        }
        
        if( $offset < 0 ){
            throw new exception( "A negative offset is not allowed." );
        }else if( $offset >= $this->size ){
            throw new exception( "Offset:$offset is beyond the size of BitArray, which is $max_offset." );
        }          
    }
    
    private function valueTest( $value ){
        if( $value != 0 && $value != 1 ){
            throw new exception( "Value must be 1, 0, true, or false, $value doesn't is not compatible." );
        }    
    }
      
    
    
    /**
     * BitArray to binary string. 
     * The bits will be grouped into chars and inserted into a string,
     * forming a binary string. Bits beyond  BitArray size are 
     * padded with 0(s).
     *
     * @return A binary string
     *
     * Example Usage:
     * <code>
     * $bitarray = new BitArray( [ 0, 1 ] );
     * $bitarray->toBinaryString();   
     * </code>
     */
    public function toBinaryString(){
        $bstr = '';
        
        $sp_size = $this->size; // real machine size       
        $remainder = $this->size % 8;        
        if( $remainder != 0 ){
            $sp_size = $this->size + 8 - $remainder;
        }
        
        for( $i = 0; $i < $sp_size; $i += 8 ){
            $int_array_position = (int)( $i / $this->max_bits );           
              
            $temp = $this->data[ $int_array_position ] >> ( $this->max_bits - ( $i + 8 ) );
            $temp &= 255;
            
            $bstr .= pack( 'C', $temp );
        }       
        return $bstr;        
    }
    
    
    /**
     * BitArray to ascii 1s and 0s. 
     * The bits will be converted into ascii 1s and 0s
     * and put into a string, so that it can be human readable
     *
     * @return string
     *
     * Example Usage:
     * <code>
     * $bitarray = new BitArray( [ 1, 1 ] );
     * $bitarray->toBinaryRepersentaion();   
     * </code>
     */
    public function toBinaryRepersentaion(){
        $rep = '';
        
        for( $i = 0; $i < $this->size; ++$i ){
            $int_array_position = (int)( $i / $this->max_bits );
            $int = $this->data[ $int_array_position ];       
            
            $bit_position = $i % $this->max_bits;            
            if( $bit_position == 0 ){
                $bit_on = ( $this->highest_bit >> $bit_position );   
            }else{
                $bit_on = ( $this->highest_bit >> $bit_position ) ^ ( $this->highest_bit >> ( $bit_position - 1 ) );          
            }
            
            if( $int & $bit_on ){                
                $rep .= '1';
            }else{
                $rep .= '0';
            }              
        }    
        return $rep;        
    }
    
    
    /**
     * Assign the bits in the binary string to the bit array.
     *
     * @param string $binary_string A string of characters
     * @note string must not exceed BitArray rounded to 
     * the next multiple of 8. 
     * @note Padding from the binary string that exceeds 
     * BitArray size will be ok as long the padding equals zeros.     
     *
     * Example Usage:
     * <code>
     * $bitarray = new BitArray( 5 );
     * $bitarray->assignBinaryString( pack( 'C', 248 ) ); // 11111000, 5 rounded up to 8, 5 bits usable, 3 off bits for padding
     * </code>
     */
    public function assignBinaryString( $binary_string ){
        // Clear all bits for new content 
        foreach( $this->data as &$int ){
            $int = 0;
        }
        
        // Create a char array
        $char_array = unpack( 'C*', $binary_string );
        $length = count( $char_array );
        
        $sp_size = $this->size;        
        $remainder = $this->size % 8;        
        if( $remainder != 0 ){
            $sp_size = $this->size + 8 - $remainder;
        }
         
        // check if not over by a character
        if( ( $length * 8 ) > $sp_size ){
            throw new exception( "Too many extra characters." );                    
        }
        // check if any padding is not 0s
        if( $this->size != $sp_size ){
            // Some padding exists
            $number_of_padded_bits = $sp_size - $this->size;
           
            if( ( ( end( $char_array ) << ( 8 - $number_of_padded_bits ) ) & 255 ) > 0 ){
                throw new exception( "Padded bits contain 1(s)." );
            }
        }        
        
        // 
        for( $i = 0; $i < $this->size && $i < ( $length * 8 ); $i += 8 ){
            $int_array_position = (int)( $i / $this->max_bits );           
            $char = $char_array[ ( $i / 8  ) + 1 ];    
            
            // move up higher            
            $char <<= ( $this->max_bits - ( $i + 8 ) );           
            
            $this->data[ $int_array_position ] |= $char;            
        }
                
    }
    
    
    /**
     * Test to see how much full ints the bit array take up
     */
    public function numberOfIntergersUsed(){        
        return count( $this->data );        
    }
    
    
    
    
    
    
    
    
    
    
    
}