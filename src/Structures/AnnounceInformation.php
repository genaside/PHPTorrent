<?php

namespace genaside\PHPTorrent\Structures;

/**
 * Announce Infomation Structure.
 * This can be used as tracker information.
 */
class AnnounceInformation
{

    /**
     * Url of announce.
     * @var String
     */
    public $url;

    /**
     * Tracker Interval.
     * @var int
     */
    public $interval = 0;

    /**
     * Minimum Tracker Interval.
     * @var int
     */
    public $min_interval;

    /**
     * Time of announcement
     * @var int TIMESTAMP
     */
    public $last_access_time = 0;

    /**
     * The status of the connectivity of the tracker
     * @var int
     */
    public $network_status;


    /* ----- Network ----- */

    /**
     * @var resource
     */
    public $resource;

    /**
     * @var int
     */
    public $address;

    /**
     * @var int
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
     * @var bool
     */
    public $connection_failed = false;

    /**
     * @var int
     */
    public $read_write_timeout;

    /**
     * @var int
     * @deprecated
     */
    public $number_of_failed_connections = 0;

    /**
     * @var int
     * @deprecated
     */
    public $bad_response = false;

    /* ----- UDP ----- */

    /**
     * @var int
     */
    public $partial_connect = false;

    /**
     * @var int
     */
    public $udp_connect_id;

    /**
     * @var int
     */
    public $udp_transaction_id;

}
