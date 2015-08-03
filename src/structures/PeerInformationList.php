<?php

/**
 * File Infomation List
 */
class PeerInformationList implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable{
    
    private $container = array();    
    private $count = 0;
    
    public function __construct(){      
    }
    
    // implement functions
    
    public function count(){
        return $this->count;
    }
    
    public function getIterator() {
        return new ArrayIterator( $this->container );        
    }
    
    public function offsetSet( $offset, $value ){
        if( is_null( $offset ) ){
            $this->container[] = $value;
        }else{
            $this->container[ $offset ] = $value;
        }
    }

    public function offsetExists( $offset ){
        return isset( $this->container[ $offset ] );
    }

    public function offsetUnset( $offset ){
        unset( $this->container[ $offset ] );
        --$this->count;
    }

    public function offsetGet( $offset ){
        return isset( $this->container[ $offset ] ) ? $this->container[ $offset ] : null;
    }
    
    public function jsonSerialize() {
        return $this->container;
    }
    
    // My functions    
    
    
    public function add( PeerInformation $value ){
        // Unique constraints
        /*
        foreach( $this->container as $element ){
            if( $element->peer_id == $value->peer_id && $element->info_hash == $value->peer_id->info_hash ){
                echo "Error peer already exists\n";
                return;
            }
        }
        */
        $this->container[ $this->count++ ] = $value;
    }
    
    public function isEmpty(){
        if( $this->count == 0 ){
            return true;
        }else{
            return false;
        }        
    }
    
    public function getRandomPeer(){
        if( $this->count > 0 ){
            $random_idx = array_rand( $this->container, 1 );
            return $this->container[ $random_idx ];  
        }else{
            return null;
        }
               
    }
    
    public function toArray(){
        return $this->container;               
    }
    
    
    
    
    
    
    
    
}




