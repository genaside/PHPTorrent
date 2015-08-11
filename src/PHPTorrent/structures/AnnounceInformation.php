<?php

/**
 * Announce Infomation Structure.
 * This can be used as tracker information.
 */
class AnnounceInformation{
    
    /**
     * Url of announce.
     * @var String
     */
    public $url;
    
    /**
     * Tracker Interval.
     * @var Interger
     */
    public $interval = 0;
    
    /**
     * Minimum Tracker Interval.
     * @var Interger
     */
    public $min_interval;
    
    /**
     * Time of announcement
     * @var Timestamp
     */
    public $last_access_time = 0;    
    
    /**
     * The status of the connectivity of the tracker
     * @var Interger
     */
    public $network_status;
    
    
    /* ----- Network ----- */  
    
    /**    
     * @var resource
     */
    public $resource;
    
    /**    
     * @var Interger
     */
    public $address;
    
    /**    
     * @var Interger
     */
    public $conection_timeout;
    
    /**    
     * @var bool
     */
    public $is_connecting = false;
    
    /**    
     * @var bool
     */
    public $is_connected = false;
    
    /**    
     * @var Interger
     */
    public $read_write_timeout;
    
    /**    
     * @var Interger
     */
    public $number_of_failed_connections = 0;
    
    /**    
     * @var Interger
     */
    public $number_of_bad_respones = 0;
    
    /* ----- UDP ----- */ 
    
    /**     
     * @var Interger
     */
    public $partial_connect = false;
    
    /**     
     * @var Interger
     */
    public $udp_connect_id;
    
    /**     
     * @var Interger
     */
    public $udp_transaction_id;
    
    
    


}