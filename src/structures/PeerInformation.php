<?php

namespace genaside\PHPTorrent\Structures;

/**
 * Peer Infomation Structure
 */
class PeerInformation
{

    /* ----- Network ----- */

    /**
     * Peer's Socket connection.
     * @var Resource
     */
    public $resource;

    /**
     * Peer's IP Address.
     * @var String
     */
    public $address;

    /**
     * Peer's Network Port.
     * @var Interger
     */
    public $port;

    /**
     * Time out when connection
     * @var Interger
     */
    public $conection_timeout;

    /**
     * Connecting state while either connecting to socket or getting a handshake
     * @var bool
     */
    public $is_connecting = false;

    /**
     * In connection state where the socket is already connected but no handshake yet
     * @var bool
     */
    public $is_partially_connected = false;

    /**
     * Connected state where socket and handshake were successfull
     * @var bool
     */
    public $is_connected = false;

    /**
     *
     * @var string
     */
    public $buffer = '';

    /* ----- Handshake ----- */

    /**
     * Sent our handshake
     * @var bool
     */
    public $sent_handshake = false;

    /**
     * got peer's handshake
     * @var bool
     */
    public $received_handshake = false;


    /* ----- Ident ----- */

    /**
     * Peer's ID.
     * @var String
     */
    public $peer_id;


    /* ----- Torrent Data ----- */

    /**
     * The Torrent this Peer is tied to.
     * @var String
     */
    public $info_hash;

    /**
     * The Pieces the Peer have and don't have in the form of bits.
     * 1 = have, 0 = don't have.
     * @var array(Binary)
     */
    public $bitfield = null;

    /* ----- Statistical ----- */

    /**
     * Bytes Downloaded from Peer.
     * @var Interger
     */
    public $downloaded;

    /**
     * Bytes Uploaded to Peer.
     * @var Interger
     */
    public $uploaded;

    // NOTE remember of th peer's prospective

    /**
     * The speed in which we are uploading to the peer.
     * @var Interger
     */
    public $download_speed;

    /**
     * The speed in which we are downloading from the peer.
     * @var Interger
     */
    public $upload_speed;

    /**
     * A temporary var to accumlation of lengths for the next second in in timestamp
     * @var Interger
     */
    public $downloaded_temp;
    /**
     * A temporary var to accumlation of lengths for the next second in in timestamp
     * @var Interger
     */
    public $uploaded_temp;

    /**
     * Last time data was downloaded. Helps with getting to next second.
     * @var Interger
     */
    public $last_download_time = 0;

    /**
     * Last time data was uploaded. Helps with getting to next second.
     * @var Interger
     */
    public $last_upload_time = 0;

    /* ----- Other ----- */

    /**
     * The Tracker Url this peer was seen from.
     * @deprecated
     * @var String
     */
    public $tracker_url;

    /* ----- Administration ----- */

    /**
     * Status of peer's choke on client
     * @var Bool
     */
    public $choked_client = true; // by defualt choke is enable

    /**
     * Status of client choke on peer
     * @var Bool
     */
    public $choked = true; // by defualt choke is enable

    /**
     * This peer is interested in a torrent mangaged by Daemon
     * @var Bool
     */
    public $interested_in_client = false;

    /**
     * The torrent mangaged by the daemon is interested in the peer
     * @var Bool
     */
    public $client_interested = false;

    /* ----- Other Timers ----- */

    /**
     * Time of sent interedted message
     * @deprecated
     * @var Interger
     */
    public $last_interested_time = 0;

    /**
     * Time of last keep alive message
     * @var Interger
     */
    public $last_keep_alive_time = 0;

    /**
     * A counter of how many bad payloads the peer gave
     * @var Interger(unsigned)
     */
    public $number_of_bad_data = 0;

    /* ----- Specail Downloading ----- */


    public $piece_buffers = array();
    // DEPRECATED
    public $number_of_requests = 0;
    public $current_piece_index;
    public $piece_buffer;


}



// END