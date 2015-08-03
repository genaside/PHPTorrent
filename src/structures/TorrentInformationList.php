<?php

/**
 * Torrent Infomation List
 */
class TorrentInformationList implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable{
    
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
    
    /**
     * Add a TorrentInformation object to the list
     * @todo info_hash should be validated
     * @note there is a unique constraint on the info_hash
     * @param TorrentInformation
     * @return true on success
     */
    public function add( TorrentInformation $torrent_info ){        
        foreach( $this->container as $element ){
            if( $element->info_hash == $torrent_info->info_hash ){
                // we already have this
                return false;
            }
        } 
        $this->container[ $this->count++ ] = $torrent_info;
        return true;
    }
    
    /**
     * Add the TorrentInformationList object to this list,
     * combining two lists into one
     * @todo info_hash should be validated     
     * @param TorrentInformationList     
     * @param $limit how much of the lits to insert
     */
    public function addList( TorrentInformationList $torrent_info_list, $limit = -1 ){     
        $count = 0;
        foreach( $torrent_info_list as $torrent_info ){
            if( $count == $limit ){
                break;
            }
        
            if( $this->add( $torrent_info ) ){
                ++$count;
            }            
        }       
    }
    
    
    
    public function remove( $info_hash ){
        foreach( $this->container as $key=>$element ){
            if( $element->info_hash == $info_hash ){
                unset( $this->container[ $key ] );
                --$this->count;
            }
        } 
    }
    
    public function isEmpty(){
        if( $this->count == 0 ){
            return true;
        }else{
            return false;
        }        
    }
    
    public function find( TorrentInformation $torrent_info ){
           
    }
    
    /**
     * @param $info_hash
     */
    public function &findUsingInfoHash( $info_hash ){        
        foreach( $this->container as $element ){
            if( $element->info_hash == $info_hash ){
                return $element;
            }
        }        
        return null;
    }
    
    
    
    
    
    
}