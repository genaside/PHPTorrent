<?php

    
/**
 * The main program that will be running in the background
 * TODO Remove all magic numbers
 * TODO error checking
 * TODO move database work to another file
 * TODO add transactions 
 * WARNING This is my mistake and i learn from my mistakes. NEVER wait on a socket after a write.
 * The system will just hang around not doing other things.
 * TODO It's still slow, Try to read only if a curtian amount of data is in the buffer 
 * NOTE If any implmentation of threads is going to happen, use them for connections.     
 * Some stuff to review 
 * http://stefan.buettcher.org/cs/conn_closed.html
 */
class Daemon{
    // Daemon personal constants
    // client id consiting of program abbreviation and version number
    const CLIENT_ID = "-PT0040-"; // Azureus-style
    // Name of the program
    const PROGRAM_NAME = "PHPTorrent"; 
    // Version of the program
    const PROGRAM_VERSION = "0.0.1";
    
    // Other constance
    const SUCCESS = 1;
    const FAILURE = 0;
    
    /**
     * A randomly created ID
     * @note each time the deamon starts you'll get a completly different id each time.
     * @note that ascii number and letters are being used right now, and complexity might change later on.
     * @var string
     */
    private $peer_id;
    
    /**
     * Daemon's only port for peers to connect to
     * @var resource
     */
    private $port;
    
    /**
     * A SQLite resource connection.
     * @var resource
     */
    private $db_conn;    
    
    /**
     * A port for local or remote operators to control and 
     * get information about the daemon.
     * @var resource
     */
    private $interface_conn;
    
    /**
     * Array of 
     * @var array
     */
    private $interface_clients = array();
    
    /**
     * A flag that tells the client to shutdown on the beginning of the next interation.
     * @var bool
     */
    private $is_running_flag = true;
       
    
    /**
     * Constructor
     */
    public function __construct(){}
    
    /**
     * Clean up.     * 
     */
    public function __destruct(){    
        //TODO check before closing
    
        // Close our port 
        socket_close( $this->port );
                
        // Close database connection
        $this->db_conn->close();
        
        // Close the Interface port
        socket_close( $this->interface_conn );
    }
    
    /**
     * Start running the bittorent client
     */
    public function start(){
        // initialize various components 
        logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, "Started PHPTorrent, the bittorrent client." );   
        
        $this->initializeID();       
        $this->initializePort();
        $this->initializeInterface();        
        $this->initializeDatabase();        
       
        // Run Main Loop, where all the magic happens
        $this->mainLoop();
    }    
    
    
    /**
     * Installize this running program's ID(peer_id).     
     * @note client id + 12 byte random string should total 20 bytes    
     * @return 
     */
    private function initializeID(){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = 12;
        $charactersLength = strlen( $characters );
         
        $randomString = '';        
        for( $i = 0; $i < $len; ++$i ){
            $randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
        }    
        $this->peer_id = self::CLIENT_ID . $randomString;  
        
        logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, "Peer ID: {$this->peer_id}" );          
    }
    
    
    /**
     * Start by finding one free port in the range defined in config.php
     * commas are treated as lists, and dashes are treated as ranges.     
     * 
     * @throws ? If no ports in config.php are avalible to be used.
     */
    private function initializePort(){ 
        // Func to reduce code.
        $create_server_socket = function( $port_number ){
            $socket = @socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
            if( !$socket ){
                //throw new exception( "Socket creation failed" );
                return false;
            }
            $status = @socket_bind( $socket, '127.0.0.1', $port_number );
            if( !$status ){
                //throw new exception( "Socket binding failed" );
                return false;
            }
            $status = @socket_listen( $socket );    
            if( !$status ){
                //throw new exception( "Socket listener failed" );
                return false;
            } 
            return $socket;
        };
          
        $list = explode( ',', Config::CLIENT_PORT_RANGE );
        foreach( $list AS $value ){
            if( strpos( $value, '-' ) ){   
                // Parse range                
                $range = explode( '-', $value );                
                for( $i = $range[ 0 ]; $i <= $range[ 1 ]; ++$i ){                    
                    if( $socket = $create_server_socket( $i ) ){
                        $this->port = $socket;                        
                        logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, "Created and binded port $i" );
                        return;                        
                    }                                
                }               
            }else{               
                if( $socket = $create_server_socket( $value ) ){
                    $this->port = $socket;                      
                    logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, "Created and binded port $value" );
                    return;
                } 
            }                       
        }
        
        // At this point no sockets were created
        logger::logMessage( self::PROGRAM_NAME, Logger::CRITICAL, "Creation and Binding of network port failed" );     
        exit();
    }
    
    /**
     * The creates A sort of interface for this bittorent.
     * By using a socket the client can be controlled or even
     * a gui wrapper can by use this mechinism.
     * 
     * @throws     
     */
    private function initializeInterface(){
        //$socket = @socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
        
        if( $socket = @socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) ){
            if( socket_bind( $socket, '127.0.0.1', Config::CLIENT_INTERFACE_PORT ) ){
                if( socket_listen( $socket ) ){
                    //socket_set_nonblock( $socket );
                    $this->interface_conn = $socket;
                    $port = Config::CLIENT_INTERFACE_PORT;
                    logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, "Created and binded interface port, $port." );
                    return;
                }
            }                
        }
        
        // At this point no sockets were created
        logger::logMessage( self::PROGRAM_NAME, Logger::WARNING, "Creation and Binding of iterface port failed" );        
        exit();
    }
    
    
    
    
    /**
     * Make a connection Using SQLite.
     * Create a database if haven't already done so.
     * If everything goes well the resource connection is stored.
     * 
     * @throws     
     */
    private function initializeDatabase(){
        try{
            $this->db_conn = new SQLite3( 
                Config::CLIENT_DATABASE_LOCATION, 
                SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE 
            );
        }catch( Exception $e ){
            $error_msg = $this->db_conn->lastErrorMsg();
            logger::logMessage( self::PROGRAM_NAME, Logger::CRITICAL, $error_msg );  
            exit();
        }
        
        if( !$this->db_conn->busyTimeout( 1 ) ){
            $error_msg = $this->db_conn->lastErrorMsg();
            logger::logMessage( self::PROGRAM_NAME, Logger::WARNING, $error_msg );
            // TODO to exit or not to exit, that is the question.
        }         
        
        $status = $this->db_conn->exec( 'PRAGMA foreign_keys = ON;' );
        if( !$status ){
            $error_msg = $this->db_conn->lastErrorMsg();
            logger::logMessage( self::PROGRAM_NAME, Logger::CRITICAL, $error_msg );
            exit();
        }
        
        $status = $this->db_conn->exec( "
        CREATE TABLE IF NOT EXISTS Torrents(
            -- Info about the torrent its self
            info_hash         TEXT  PRIMARY KEY  NOT NULL,   
            name              TEXT               NOT NULL,
            piece_length      INT                NOT NULL,
            pieces            BLOB               NOT NULL,
            is_private        INT   DEFAULT 0    NOT NULL,            
            -- Storage
            destination       TEXT               NOT NULL, -- Location for download file(s)
            -- Statistics           
            bytes_left        INT                NOT NULL, -- Bytes left till download completes
            bytes_uploaded    INT   DEFAULT 0    NOT NULL, -- Total bytes downloaded
            bytes_downloaded  INT   DEFAULT 0    NOT NULL, -- Total bytes uploaded 
            -- Options
            active            INT   DEFAULT 1    NOT NULL, -- 1 active, 0 inactive
            download_speed    INT   DEFAULT 0    NOT NULL, -- Maximum download speed of torrent
            upload_speed      INT   DEFAULT 0    NOT NULL  -- Maximum upload speed of torrent 
        );        
        CREATE INDEX IF NOT EXISTS idx_1 on Torrents( info_hash );
        
        CREATE TABLE IF NOT EXISTS Files(
            info_hash         TEXT               NOT NULL,
            filename          TEXT                       , -- null means that the filename is the name in torrent
            filesize          INT                NOT NULL,
            FOREIGN KEY( info_hash ) REFERENCES Torrents( info_hash ) ON DELETE CASCADE,
            UNIQUE( info_hash, filename )
        );        
        CREATE INDEX IF NOT EXISTS idx_2 on Files( info_hash );
        
        CREATE TABLE IF NOT EXISTS AnnounceUrls(
            info_hash         TEXT               NOT NULL,
            url               TEXT               NOT NULL,
            rank              INT   DEFAULT 0    NOT NULL, -- The rank of how good the tracker is in returning results
            FOREIGN KEY( info_hash ) REFERENCES Torrents( info_hash ) ON DELETE CASCADE,
            UNIQUE( info_hash, url )
        );
        CREATE INDEX IF NOT EXISTS idx_3 on AnnounceUrls( info_hash );
        ");
        
        if( !$status ){
            $error_msg = $this->db_conn->lastErrorMsg();
            logger::logMessage( self::PROGRAM_NAME, Logger::CRITICAL, $error_msg );  
            exit();
        }
        
        logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, "Database ok:" . Config::CLIENT_DATABASE_LOCATION );
    }
    
    /**
     * Function where all the magic happens.
     * First we will check the the interface port
     * to see if there are any other jobs to take other than
     * seeding and leaching.
     * For each running torrent we gonna announce ourselves then get tracker response.
     * from the peer list we handle each peer requests and then make our own.     * 
     * @throws     
     */
    private function mainLoop(){       
        $torrent_info_list = $this->getActiveTorrents( Config::MAX_ACTIVE_RUNNING_TORRENTS );        
        $peer_info_list = new PeerInformationList; //     
        $last_stat_update_time = time(); //  
        $have_arr = array(); // { info_hash|index }
        
        //        
        socket_getsockname( $this->port, $my_addr, $my_port_number );        
    
        while( $this->is_running_flag ){   
            // Proccess commands in the queue first.
            $this->processCommands( $torrent_info_list, $peer_info_list ); 
            
            if( $torrent_info_list->isEmpty() ){    
                // There are no torrents to work on
                // dont need to overwork the CPU for an empty list, sleeping for a little bit.
                $msg = "There are no torrents for the client to work on.";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );                 
                // We dont want to use up the cpu
                sleep( 5 ); 
                continue;
            } 
            
            // For each running torrent, 
            foreach( $torrent_info_list as &$torrent_info ){
                if( is_null( $torrent_info->bitfield ) ){
                    // Hash check file(s) if havent already done so, and  set bitfield with it
                    $torrent_info->bitfield = new BitArray( strlen( $torrent_info->pieces ) / 20 );
                    
                    $msg = "Checking the hash of file(s) for torrent {$torrent_info->info_hash}...";
                    logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );                     
                    
                    $torrent_info->bitfield->assignBinaryString( Storage::fullHashCheck( $torrent_info ) );                    
                    // calculate bytes remaining
                    $torrent_info->bytes_left = $this->BytesLeft( $torrent_info );
                }  
                                
                $ctime = time(); 
                
                // For each announce url
                foreach( $torrent_info->announce_infos as $key=>$announce_info ){                         
                    if( $ctime - $announce_info->last_access_time < $announce_info->interval ){                            
                        // Not within tracker's time interval                       
                        continue;
                    }                   
                    
                    $url_comp = parse_url( $announce_info->url );                    
                            
                    if( $url_comp[ "scheme" ] == "http" || $url_comp[ "scheme" ] == "https" ){                                            
                        if( !$announce_info->is_connected ){  
                            // Http(s) tracker has to connect
                            if( $announce_info->number_of_failed_connections > 0 ){ 
                                $announce_info->last_access_time = $ctime;
                                $announce_info->interval = 3600;
                                continue;
                            }                             
                            $this->connectTracker_HTTP( $announce_info );                            
                            continue;
                        }else{                           
                            $this->sendTrackerRequest_HTTP( $torrent_info, $announce_info, $my_port_number );
                            
                            if( !( $tracker_response = $this->recieveTrackerResponse_HTTP( $announce_info ) ) ){
                                // Tracker has no response yet or error                                                                
                                continue;
                            }else{
                                $num_of_peers = count( $tracker_response[ 'peers' ] );
                                echo "Tracker, {$announce_info->url}, returned $num_of_peers peers. ";
                                echo "Next announce interval near at {$tracker_response['interval']}.\n";
                            }                             
                        }                                            
                    }else if( $url_comp[ "scheme" ] == "udp" ){                  
                        if( !$announce_info->is_connected ){
                            // UDP Tracker hasn't connected and is trying to do so                 
                            if( $announce_info->number_of_failed_connections >= Config::TRACKER_CONNECTION_ERROR_THRESHOLD ){ 
                                // Tracker failed to connect x amount of times  
                                // Set the tracker interval with a very long interval, 30mim
                                $announce_info->last_access_time = $ctime;
                                $announce_info->interval = 3600;
                                //unset( $torrent_info->announce_infos[ $key ] );
                                continue;
                            } 
                            // begin connecting
                            $this->connectTracker_UDP( $announce_info );
                            continue;
                        }else{                              
                            $this->sendTrackerRequest_UDP( $torrent_info, $announce_info, $my_port_number );
                            if( !( $tracker_response = $this->recieveTrackerResponse_UDP( $announce_info ) ) ){                                
                                // Tracker has no response yet or error                                 
                                continue;
                            }else{
                                $num_of_peers = count( $tracker_response[ 'peers' ] );
                                echo "Tracker, {$announce_info->url}, returned $num_of_peers peers. ";
                                echo "Next announce interval near at {$tracker_response['interval']}.\n";
                            }                            
                        }      
                    
                    }else{
                        // This tracker is not supported    
                        echo "{$announce_info->url} is not a supported url.\n";
                        // get rid of it
                        unset( $torrent_info->announce_infos[ $key ] );                        
                        continue;
                    } 
                    
                                                            
                    $announce_info->interval = $tracker_response[ 'interval' ];
                    $announce_info->last_access_time = $ctime;
                    if( isset( $tracker_response[ 'min interval' ] ) ){
                        $announce_info->min_interval = $tracker_response[ 'min interval' ];
                    }
                    
                    shuffle( $tracker_response[ 'peers' ] );
                    foreach( $tracker_response[ 'peers' ] as $peer ){                       
                        if( $my_port_number == $peer[ 'port' ] && $my_addr == $peer[ 'ip' ] ){ 
                            // This is me, dont add.
                            continue;
                        }
                        
                        // dont allow duplicate peers from entering
                        foreach( $peer_info_list as $peer_info ){
                            if( $peer_info->address == $peer[ 'ip' ] && $peer_info->port == $peer[ 'port' ] ){
                                // we have a dup
                                continue 2;
                            }
                        }
                        
                        $peer_info_list->toArray();
                    
                        $peer_info = new PeerInformation;
                        $peer_info->tracker_url = $announce_info->url;
                        $peer_info->info_hash = $torrent_info->info_hash;
                        $peer_info->address = $peer[ 'ip' ];
                        $peer_info->port = $peer[ 'port' ];   
                        if( isset( $peer[ 'peer id' ] ) ){
                            $peer_info->peer_id = $peer[ 'peer id' ];   
                        }
                        
                        $peer_info_list->add( $peer_info );                                 
                    }                    
                }                 
            }
                         
                         
            $this->handleIncomingPeerConnection( $peer_info_list );             
            
            $failed_connects = 0;
            // Handle each peer
            foreach( $peer_info_list as $key=>&$peer_info ){                
                if( is_null( $peer_info ) ){
                    // A peer was nulled out, get rid of it
                    unset( $peer_info_list[ $key ] );   
                    continue;
                }
                
                $torrent_info = $torrent_info_list->findUsingInfoHash( $peer_info->info_hash );  
                
                //if( $torrent_info->bitfield == $peer_info->bitfield ){
                if( $torrent_info->bitfield == $peer_info->bitfield ){
                    // neither of us can gain from each other because we have the pieces                        
                    unset( $peer_info_list[ $key ] );                      
                    continue;
                }   
                
                // TODO
                if( !$this->handleNewPeerConnection( $peer_info_list, $peer_info, $torrent_info ) ){ 
                    
                    // not ready
                    if( is_null( $peer_info ) ){
                        unset( $peer_info_list[ $key ] );                       
                    } 
                    if( $failed_connects++ > 2 ){
                        break;
                    }                    
                    continue;
                }
                 
                
                if( !is_resource( $peer_info->resource ) ){
                    // Lost connection to peer                    
                    unset( $peer_info_list[ $key ] );                    
                    continue;
                }                
                
                $this->receiveRequests( $torrent_info, $peer_info, $have_arr );                
                if( is_null( $peer_info ) ){ continue; }
                
                
                // When is it a good time to make requests?
                // * Missing pieces( peer must have bifield, client  )
                // * Peer has piecies we don't have
                // * Peer has unchoked us
                if( $torrent_info->bytes_left != 0/* && !is_null( $peer_info->bitfield )*/ ){
                    // We don't have all the pieces, and we can move on using peer's bitfield     
                    // Check to see if peer has a piece we don't have                     
                    $this->makeRequests( $torrent_info, $peer_info );  
                }
                
                
                
                                               
                // Keep alive if no socket read/read is happening
                // NOTE according to the spec keep alive is sent when 2 min of inactivity occurs
                
                /*
                $ctime = time();
                if( $ctime - $peer_info->last_download_time > 120 &&
                    $ctime - $peer_info->last_upload_time > 120
                ){
                    $this->messageKeepAlive( $peer_info );                    
                }
                */
                                
            }
            
            
            
            // TODO not really sure about this have list
            // TODO do i really need have
            // Send have requests to all peers connected to a specific torrent
            
            /*
            if( !empty( $have_arr ) ){
                foreach( $peer_info_list as $peer_info ){
                    $temp_arr = array_filter( $have_arr, function( $item ) use ( $peer_info ){
                        if( $item[ 0 ] == $peer_info->info_hash ){
                            return $item;
                        }
                    });
                    
                    foreach( $temp_arr as $element ){
                        $this->messageHave( $peer_info, $element[ 1 ] );
                    }                   
                }
            }
            // clear have list
            $have_arr = array();
            */
            
            // Add statistics to database
            if( ( $ctime = time() ) - $last_stat_update_time > Config::UPDATE_STATISTICS_INTERVERL ){
                // Add statistics to database
                $this->addStatisticsToDatabase( $torrent_info_list );
                $last_stat_update_time = $ctime;
                
                $msg = "Updated Database.";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg ); 
            }
            
           
            continue;
        } 
        
        echo "Shutting down gracefully.\n";
        // Shutdown all peer sockets
        foreach( $peer_info_list as &$peer_info ){
            socket_close( $peer_info->resource );                         
        }
        
    }
    
    
    /**
     * Handle Peer connections to us.
     * @param TorrentInformation
     */
    private function handleIncomingPeerConnection( PeerInformationList $peer_info_list ){
        $read = array( $this->port );
        
        if( socket_select( $read, $write, $except, 0 ) > 0 ){
            $peer_socket = socket_accept( $this->port );           
            socket_getpeername ( $peer_socket, $addr, $port );                               
            
            // find duplicates in peer list
            foreach( $peer_info_list as $key=>$peer_info ){
                if( $peer_info->address == $addr && $peer_info->port == $port ){
                    // Found a dup
                    
                    if( $peer_info->partially_connected || $peer_info->is_connected ){
                        // We got a dup that has a socket connecting or connected already.
                        return;                            
                    }else{
                        // Dup probably used as peer in reserve, we can get rid of it
                        unset( $peer_info_list[ $key ] );
                    }
                }               
            }
            
            /*
            // Make sure we're in the limits of config.php                
            $farr = array_filter( $peer_info_list->toArray(), function( $element ) use( $peer_info ){                    
                if( $element->is_connected || $element->is_connecting ){                     
                    if( $element->info_hash == $peer_info->info_hash ){
                        return $element;
                    }                   
                }
            });
            
            if( count( $farr ) >= Config::MAX_PEERS_PER_TORRENT ){
                // Max number of connection already reached for this torrent. Leave the unconnected peer for later use.           
                break;
            }   
            */           
            if( !( $handshake = $this->recieveHandShake( $peer_socket ) ) ){
                return;
            }
            
            $peer_info = new PeerInformation;
            $peer_info->peer_id = $handshake->peer_id; // 
            $peer_info->info_hash = $handshake->info_hash;  //                       
            $peer_info->received_handshake = true;  
            
            // This peer is already deamed partially connected.       
            $peer_info->is_connecting = true;
            $peer_info->is_partially_connected = true;
            
            $peer_info->address = $addr;
            $peer_info->port = $port;          
            $peer_info->is_connecting = true;   
            $peer_info->is_partially_connected = true; 
            $peer_info->resource = $peer_socket; 
                                                
            $peer_info_list->add( $peer_info );    
            echo "Peer $addr:$port is conneting to us.\n";                
        }
    }
    
    /**
     * Handles the making od connections to peers.
     * @note all peers that connect to us, uses a different funtion.
     * @param TorrentInformation We just need the bitfield to show the peer
     */
    private function handleNewPeerConnection( PeerInformationList $peer_info_list, PeerInformation &$peer_info, TorrentInformation $torrent_info ){
               
        if( !$peer_info->is_connected && !$peer_info->is_connecting ){
            // Create a freash new connection
            
            // First look in ther peer list to filter out peers with this info_hash
            // then filter peers that are connecting or already connected states.
            $arr = $peer_info_list->toArray();
            $farr = array_filter( $arr, function( $element ) use( $peer_info ){
                // TODO partially_connected should be the same as is_connecting
                if( $element->is_connected || $element->is_connecting ){                     
                    if( $element->info_hash == $peer_info->info_hash ){
                        return $element;
                    }                   
                }
            });
            
            if( count( $farr ) >= Config::MAX_PEERS_PER_TORRENT ){
                // Max number of connection already reached for this torrent. Leave the unconnected peer for later use.           
                return false;
            }            
            // New connection
            $peer_info->resource = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );                           
            socket_set_nonblock( $peer_info->resource );
            $peer_info->conection_timeout = time();
            $peer_info->is_connecting = true;            
        }
        
        if( $peer_info->is_connecting && !$peer_info->is_partially_connected ){    
            // Attempt to connect to socket
            while( !( $result = @socket_connect( $peer_info->resource, $peer_info->address, $peer_info->port ) ) ){                
                if( ( time() - $peer_info->conection_timeout ) >= Config::PEER_CONNECTION_TIMEOUT ){     
                    $msg = "Connection to peer {$peer_info->address}:{$peer_info->port} timed out on socket.";
                    logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
                                                      
                    $peer_info->is_connecting = false;    
                    socket_close( $peer_info->resource ); // NOTE do i need this?
                    $peer_info = null;
                    return false;
                }                
            }
            if( $result ){
                // We connected to the peer socket, but not yet actually connected.
                // Were half way there                                  
                socket_set_block( $peer_info->resource );
                //socket_set_option( $peer_info->resource, SOL_SOCKET, SO_RCVTIMEO, array( "sec"=>1,"usec"=>0 ) );
                $peer_info->is_partially_connected = true;
                $peer_info->is_connecting = true;                
            }        
        }
        
        
        if( $peer_info->is_partially_connected ){
            // So were already connected to socket at this point we are half done with a full connection
            
            if( !$peer_info->received_handshake || !$peer_info->sent_handshake ){

                if( !$peer_info->received_handshake ){   
                    // Havent recievce handshake yet
                    $read = array( $peer_info->resource );
                    $write = null;
                    $except = null;            
                    if( ($test = socket_select( $read, $write, $except, 0 )) == 1 ){                        
                        $handshake = $this->recieveHandShake( $peer_info->resource );
                        if( !$handshake ){
                            $msg = "Connection to peer {$peer_info->address}:{$peer_info->port} timed out on handshake.";
                            logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
                            
                            socket_close( $peer_info->resource );                            
                            $peer_info = null;
                            return false;
                        }
                        $peer_info->peer_id = $handshake->peer_id; // 
                        $peer_info->info_hash = $handshake->info_hash;  //                       
                        $peer_info->received_handshake = true;                        
                    }   
                    
                    if( $test === false ){
                        echo "--{$peer_info->address}--\n";
                        exit(); // TODO
                    }
                }
                
                if( !$peer_info->sent_handshake && $peer_info->info_hash ){
                    $this->sendHandShake( $peer_info->info_hash, $peer_info->resource ); // TODO                    
                    $peer_info->sent_handshake = true;                    
                }         
                
            }else{
                // Everthing is ok   
                $client_id = substr( $peer_info->peer_id, 0, 8 );
                $msg = "Peer {$client_id}:{$peer_info->address}:{$peer_info->port} connected successfully.";
                logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, $msg );                
                
                $peer_info->is_connected = true;
                $peer_info->is_connecting = false;    
                $peer_info->is_partially_connected = false;
                // Send bitfield                
                $this->messageBitfield( $peer_info->resource, $torrent_info->bitfield );                
                return true;
            }
        }      
        
        if( $peer_info->is_connected ){
            // We are fully conected meaning socket is connected and handshake was successfull            
            return true;
        }
        
        return false;    
    }
    
    
    /**
     * Function to recive peers requests/messages
     */
    private function receiveRequests( TorrentInformation &$torrent_info, PeerInformation &$peer_info, &$have_arr ){
        $read = array( $peer_info->resource );
        
        if( socket_select( $read, $write, $except, 0 ) == 1 ){ 
            if( socket_recv( $peer_info->resource, $data, 4096, MSG_DONTWAIT ) === false ){
                // error on socket                
                socket_close( $peer_info->resource );
                return;                
            }else{
                $peer_info->buffer .= $data;                
            }
        }
        
        // This if statement is to check the first 4 bytes to determine the rest of the message length 
        if( strlen( $peer_info->buffer ) >= 4 ){
            $message_len = current( unpack( 'N', substr( $peer_info->buffer, 0, 4 ) ) );     
            $full_message_len = $message_len + 4;        
            
            //echo $message_id . ",";
            if( strlen( $peer_info->buffer ) < $full_message_len ){ 
                // we don't have the WHOLE message inorder to contine                
                return;
            }else{            
                // now we have the entire message we can proceed                
                $message_len = current( unpack( 'N', $this->bufferRead( $peer_info, 4 ) ) );                
                if( $message_len == 0 ){
                    //echo "keep alive\n";
                    return;
                }                
                $message_id = current( unpack( 'C', $this->bufferRead( $peer_info, 1 ) ) ); // TODO iam getting an error where 0 bytes are read                   
            }
        }else{
            // cant calulate message length yet, need atleast 4 bytes
            return;
        }
        
        echo "{$peer_info->address}[ $message_len | $message_id ] " . "\n";  
        // At this point we have the full message, soo lets do this
        switch( $message_id ){
            case 0:
                // Choke, 
                $peer_info->choked_client = true;
                break;
            case 1:
                // unchoke,                            
                $peer_info->choked_client = false;
                break;    
            case 2:
                // Interested,
                // Got an interested message, lets mark and deal with it later
                $peer_info->interested_in_client = true;
                
                // TODO there really should be an algorithm to determin the best time to unchoke
                // Peer is Interested in my data, lets unchoke peer                
                $this->messageUnChoke( $peer_info->resource );                
                $peer_info->choked = false;
                break;
            case 3:
                // Not Interested,
                // Peer is not interested in me, how sad.
                $peer_info->interested_in_client = false;                
               
                //Put peer back on choke
                $peer_info->choked = true;
                $this->messageChoke( $peer_info->resource ); 
                break;
            case 4:
                // have,                    
                $raw = $this->bufferRead( $peer_info, 4 );
                $have = current( unpack( 'N', $raw ) );                    
                //$peer_info->bitfield = $this->flipBitOn( $peer_info->bitfield, $have ); 
                $peer_info->bitfield[ $have ] = true;
                break;    
            case 5:
                // bitfield,                
                $peer_info->bitfield = new BitArray( count( $torrent_info->bitfield ) );
                try{                
                    $peer_info->bitfield->assignBinaryString( $this->bufferRead( $peer_info, $message_len - 1 ) );                     
                }catch( Exception $e ){
                    // Peer has a fualty bitfield, disconnect peer.
                    $peer_info = null;                    
                    return;
                }                  
                break;
            case 6:
                // request     
                if( $peer_info->choked ){
                    // Peer is in choke and is still making requests, what nerve.
                    echo "Peer {$peer_info->address}:{$peer_info->port} is making request while in choke,\n";   
                    $peer_info = null;
                    return;
                }                
                
                $index = current( unpack( 'N', $this->bufferRead( $peer_info, 4 ) ) );                            
                $begin = current( unpack( 'N', $this->bufferRead( $peer_info, 4 ) ) );
                $length = current( unpack( 'N', $this->bufferRead( $peer_info, 4 ) ) );
                                                    
                $seek = ( $torrent_info->piece_length * $index ) + $begin;
                
                $block = Storage::read( $torrent_info, $seek, $length );
                
                $this->messagePiece( $peer_info->resource, $index, $begin, $block  );               
                $torrent_info->bytes_uploaded += strlen( $block ); 
                
                $msg = "Gave a piece(index:$index, begin:$begin) to peer, {$peer_info->address}:{$peer_info->port}, for torrent {$peer_info->info_hash}";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
                break;
            case 7:
                // payload                            
                $index = current( unpack( 'N', $this->bufferRead( $peer_info, 4 ) ) );                            
                $begin = current( unpack( 'N', $this->bufferRead( $peer_info, 4 ) ) );  
                $block = $this->bufferRead( $peer_info, $message_len - 9 );         
                
                if( !isset( $peer_info->piece_buffers[ $index ] ) ){
                    // The index probably have been unset
                    continue;
                }
                                    
                $peer_info->piece_buffers[ $index ][ 'buffer' ][ $begin ] = $block;
                --$peer_info->piece_buffers[ $index ][ 'requests' ];
                
                if( $peer_info->piece_buffers[ $index ][ 'requests' ] != 0 ){
                    // need all segments before moving on                        
                    continue;
                }                    
                
                ksort( $peer_info->piece_buffers[ $index ][ 'buffer' ] );
                $full_piece_block = implode( '', $peer_info->piece_buffers[ $index ][ 'buffer' ] );                 
                unset( $peer_info->piece_buffers[ $index ] );
                
                // Check qualty of data before writing
                if( !$this->checkPiece( $torrent_info, $index, $full_piece_block ) ){
                    // Error peer did not give me the right piece                        
                    ++$peer_info->number_of_bad_data;
                    if( $peer_info->number_of_bad_data >= Config::PEER_BAD_DATA_THRESHOLD ){
                        echo "Notice, peer {$peer_info->address}:{$peer_info->port} gave way to many bad payloads,\n";
                        // disonnect for peer                        
                        $peer_info = null;
                        return false;
                    }                    
                }else{
                    $msg = "peer, {$peer_info->address}:{$peer_info->port}, gave good piece at index $index for torrent {$peer_info->info_hash}";
                    logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );                    
                }
                
                // Write piece to file
                $seek = $index * $torrent_info->piece_length;
                Storage::write( $torrent_info, $seek, $full_piece_block );
                                            
                // flip the bit on to indicate we have this piece
                // $torrent_info->bitfield = $this->flipBitOn( $torrent_info->bitfield, $index ); TODO
                $torrent_info->bitfield[ $index ] = true;
                $torrent_info->bytes_left = $this->bytesLeft( $torrent_info );               
                
                if( $torrent_info->bytes_left == 0 ){
                    $msg = "Torrent {$torrent_info->bytes_left} has finished completely";
                    logger::logMessage( self::PROGRAM_NAME, Logger::STATUS, $msg );                
                
                    if( Config::TORRENT_COMPETION_NOTIFICATION_SCRIPT != "" ){
                        // run special script
                        exec( Config::TORRENT_COMPETION_NOTIFICATION_SCRIPT . "-t {$peer_info->info_hash} > /dev/null &" );
                    }                   
                }
                
                // Update bytes left TODO                                    
                //array_push( $have_arr, array( $peer_info->info_hash, $index ) );
                
                $torrent_info->bytes_downloaded += strlen( $full_piece_block ); 
                break;
            case 8:
                // cancel
                // This is not supported at this time
                $this->bufferRead( $peer_info, $message_len - 1 );
                break;    
            case 9:
                // port
                // This is not supported at this time
                $this->bufferRead( $peer_info, $message_len - 1 );
                break;
            default:
                // Unsupported message id or possibly an errer
                echo "?--- unkown message id: $message_id ---?\n";
                // TODO Should i ignore this or diconnect peer?
                // empty out buffer
                $peer_info->buffer = '';                                    
        }        
    }
    
    /**
    * 
    */
    private function makeRequests( TorrentInformation $torrent_info, PeerInformation &$peer_info ){
       
        if( is_null( $peer_info->bitfield ) ){
            // peer is not ready with it's bitfield
            // TODO When will it ever be ready?            
            return;
        }                
    
        if( $peer_info->choked_client ){
            // You are in a choke by the peer. tell peer to change it's mind                    
            if( ( time() - $peer_info->last_interested_time ) < 30 ){
                // don't be annoying
                return;
            }
            
            $number_of_bits = count( $torrent_info->bitfield );           
            $interested = false;  
            for( $i = 0; $i < $number_of_bits; ++$i ){
                if( $peer_info->bitfield[ $i ] == true && $torrent_info->bitfield[ $i ] == false ){
                    // The peer has piece(s) you don't have          
                    $interested = true;                     
                    break;
                }               
            }            
            
            if( $interested ){
                // send interested message
                $this->messageInterested( $peer_info );
            }else{
                // not interested                
                $this->messageNotInterested( $peer_info->resource );
            } 
            
            $peer_info->last_interested_time = time();                     
        }else{
            // While not in choke, start grabbing data from peer 
            $ctime = time();
            
            if( ( $ctime - $peer_info->last_upload_time ) < 15 &&  $peer_info->last_upload_time != 0 ){
                //echo "peer timed out\n";
                //exit();
            }
            
            //echo "\nhere\n";
            
            // Check if any piece buffer timmed            
            foreach( $peer_info->piece_buffers as $key=>$buffer ){
                if( $ctime - $buffer[ 'timer' ] > Config::PIECE_SEGMENT_TIMEOUT ){
                
                    echo "A piece from peer, {$peer_info->address}:{$peer_info->port}, timed out.\n";
                    echo $buffer[ 'requests' ] . "\n";
                    unset( $peer_info->piece_buffers[ $key ] );
                }
            }
            
            if( count( $peer_info->piece_buffers ) >= Config::MAX_NUMBER_OF_PIECE_BUFFERS ){
                // In terms of slots, when one slot its free then we can use it, but if all slots are busy we cant contine
                return;
            }
                       
            
            $number_of_bits = count( $torrent_info->bitfield );
            $needed_pieces = array();
            for( $i = 0; $i < $number_of_bits; ++$i ){
                if( $peer_info->bitfield[ $i ] == true && $torrent_info->bitfield[ $i ] == false ){
                    // we dont have this piece
                    array_push( $needed_pieces, $i ); 
                }
            }
            
            if( empty( $needed_pieces ) ){
                // We got all the pieces from this peer and no longer interested in the peer's data.                
                $this->messageNotInterested( $peer_info->resource );
                echo "We got all of peer's pieces\n";
                // If peer is also not interested in us, there is no need to hang around
                if( $peer_info->choked == true ){
                    //$peer_info = null;
                    //return;
                }
                
                echo "Peer wants stuff.\n";
                // automatically choke our self
                $peer_info->choked_client = true;   
                return;
            }
            
            
           // echo 'here,';
            // NOTE Iam using random pieces requests, so no sequential requests are alowed
            // According to https://wiki.vuze.com/w/Sequential_downloading_is_bad
            $arr_length = count( $needed_pieces );
            $number_of_pieces = count( str_split( $torrent_info->pieces, 20 ) );
            for( $i = 0; $i < $arr_length && count( $peer_info->piece_buffers ) < Config::MAX_NUMBER_OF_PIECE_BUFFERS; ++$i ){
                $random_key = array_rand( $needed_pieces );     
                if( array_key_exists( $random_key, $peer_info->piece_buffers ) ){
                    // We are still running this
                    continue;
                }
                
                $peer_info->piece_buffers[ $needed_pieces[ $random_key ] ] =  array(
                    'buffer' => array(),
                    'requests' => 0,
                    'timer' => time()
                );
                
                $length = $torrent_info->piece_length;            
                $file_size = $torrent_info->files->getTotalFileSize();                
                
                if( $needed_pieces[ $random_key ] == $number_of_pieces - 1 ){
                    // The last piece's length is usally a different size                   
                    if( ( $reminder = $file_size % $length ) != 0 ){
                        $length = $reminder;                         
                    }                        
                }
                
                $piece_segments = ceil( $length / Config::MAX_BLOCK_REQUEST_LENGTH );
            
                // We only work with whole pieces, send all segments of this piece
                for( $n = 0; $n < $piece_segments - 1; ++$n ){ // exclude the last piece                   
                    $this->messageRequest( 
                        $peer_info, 
                        $needed_pieces[ $random_key ], 
                        $n * Config::MAX_BLOCK_REQUEST_LENGTH, 
                        Config::MAX_BLOCK_REQUEST_LENGTH
                    );
                    ++$peer_info->piece_buffers[ $needed_pieces[ $random_key ] ][ 'requests' ];                     
                }
                
                // last segment may have a smaller block length            
                $block_length = Config::MAX_BLOCK_REQUEST_LENGTH;
                if( $length % $block_length > 0 ){
                    $block_length = $length % $block_length;
                }
                $this->messageRequest( 
                    $peer_info, 
                    $needed_pieces[ $random_key ], 
                    ( $piece_segments - 1 ) * Config::MAX_BLOCK_REQUEST_LENGTH, 
                    $block_length 
                );
                ++$peer_info->piece_buffers[ $needed_pieces[ $random_key ] ][ 'requests' ];     
                                
                                
            }
            
           
        }
    }
    
    /**
     * Function that proccess any message from the interface socket.
     * Some number codes require a secondary option( or third), that will be found
     * by continueing to read the socket
     * 
     * @throws     
     */
    private function processCommands( TorrentInformationList &$torrent_info_list, PeerInformationList &$peer_info_list ){
        // Handle any new connections first
        $read = array( $this->interface_conn );
        $write = null;
        $except = null;
        
        if( socket_select( $read, $write, $except, 0 ) == 1 ){  
            // we have an incomming connection.
            $client = socket_accept( $this->interface_conn );
            
            if( count( $this->interface_clients ) >= Config::MAX_INTERFACE_CONNECTIONS ){
                // To many connections.
                echo "Can't access daemon controls, too many entities are already controlling it.\n";
                socket_close( $client );                
                return;
            }
            
            if( Config::INTERFACE_USERNAME == '' ){
                // Authentication is disabled, instant access
                socket_write( $client, pack( 'C', self::SUCCESS ), 1 );
                socket_getpeername ( $client, $addr, $port );                 
                array_push( $this->interface_clients, $client );
                echo "Client $addr:$port has successfully connected and stored to the Daemon's interface.\n";
            }else{
                // FIXME 
                // TODO http://blog.leenix.co.uk/2011/05/howto-php-tcp-serverclient-with-ssl.html
                // Before storing socket, authentication of client must Succeed                
                echo "2.\n";
                $estimated_size = Config::MAX_USERNAME_SIZE + Config::MAX_PASSWORD_SIZE + 1;
                
                //socket_set_option( $client, SOL_SOCKET, SO_RCVTIMEO, array( "sec"=>2,"usec"=>0 ) );
                socket_recv( $client, $userpass, $estimated_size, MSG_WAITALL );
                //while( ( $userpass = socket_read( $client, $estimated_size ) ) === 0 );
                
                
                if( $userpass == 0 ){
                    // Peer probably disconnected with use.
                    echo socket_strerror(socket_last_error()) . "\n";;
                    socket_close( $client );
                    return;
                }
                
                list( $username, $password ) = explode( ':', $userpass );
                echo "$userpass.\n";
                if( strlen( $username ) <= Config::MAX_USERNAME_SIZE && strlen( $username ) <= Config::MAX_PASSWORD_SIZE ){
                    echo "Interface client failed to authenticate becuase of userpass lenght exceeding limits in config.php.\n";
                }
                        
                if( Config::INTERFACE_USERNAME == $username && Config::INTERFACE_PASSWORD == $password ){
                    // Yeah we got a connection send a success to peer and and client to list
                    socket_write( $client, pack( 'C', self::SUCCESS ), 1 );
                    array_push( $this->interface_clients, $client );
                                        
                    socket_getpeername ( $client, $addr, $port ); 
                    echo "Client $addr:$port has successfully connected to the Daemon's interface.\n";
                }else{
                    // failed
                    // TODO logger
                    socket_write( $client, pack( 'C', self::FAILURE ), 1 );
                    socket_close( $client );
                    return;
                }
            }                 
        }
        
        // handle clients of interface sockets   
        if( count( $this->interface_clients ) == 0 ){
            // We have no clients to work with
            return;
        }       
              
        
        foreach( $this->interface_clients as $key=>$client ){
            // proccess each client            
            $read = array( $client );
            if( socket_select( $read, $write, $except, 0 ) == 0 ){
                // No activity 
                continue;
            }
                        
            $raw = socket_read( $client, 1 );        
            if( $raw === false || strlen( $raw ) == 0 ){
                socket_close( $client );
                unset( $this->interface_clients[ $key ] );
                continue;
            }
            
            $number_code = current( unpack( 'C', $raw ) );
            
            
            // proccess number codes
            switch( $number_code ){
               
                case Operation::SHUTDOWN:
                    // clean shutdown
                    $this->is_running_flag = false;                
                    break;
                case Operation::RESTART:                
                    echo "not yet implemented";
                    break;    
                    
                case Operation::ADD_TORRENT:                
                    // Get rest of data
                    $read_length = current( unpack( 'N', socket_read( $client, 4 ) ) );
                    $torrent_path = socket_read( $client, $read_length );
                    $read_length = current( unpack( 'N', socket_read( $client, 4 ) ) );
                    $destination_path = socket_read( $client, $read_length );
                    $active = current( unpack( 'C', socket_read( $client, 1 ) ) );
                                                       
                    //
                    $torrent_info = Torrent::getTorrentInfoFromSource( $torrent_path );
                    if( !$torrent_info ){
                        $opt_err = "Operation error: failed to read torrent $torrent_path.\n";
                        debug_print_backtrace();
                        echo $opt_err;
                        socket_write( $client, $opt_err, strlen( $opt_err ) );
                        return;
                    }
                    
                    // Create file(s)
                    $success = Storage::createStorage( $torrent_info->files, $destination_path );
                    if( !$success ){
                        $opt_err = "Operation error: failed to create file(s) for torrent..\n";     
                        echo $opt_err;
                        socket_write( $client, $opt_err, strlen( $opt_err ) );
                        return;                
                    }
                    
                    if( $active != 0  && $active != 1 ){ 
                        // not a valid number, set to zero
                        $active = 0;
                    }
                    $torrent_info->active = $active;
                    $torrent_info->destination = $destination_path;
                    
                    // Add torrent_info to database  
                    $this->addTorrentToDatabase( $torrent_info );
                    
                    if( count( $torrent_info_list ) < Config::MAX_ACTIVE_RUNNING_TORRENTS && $active == 1 ){
                        // Since were in the limit lets start running the torrent
                        $torrent_info_list->add( $torrent_info );
                    }
                    
                    socket_write( $client, 'success', 7 );                    
                    echo "Torrent ,{$torrent_info->info_hash}, has been added.\n";                    
                    break;
                case Operation::REMOVE_TORRENT:
                    $torrent_info_hash = socket_read( $client, 40 );
                    $delete_files = socket_read( $client, 1 );
                                       
                    // Find the torrent
                    if( !( $torrent_info = $this->getTorrent( $torrent_info_hash ) ) ){
                        $opt_err = "Operation error: couldn't delete torrent cuase it does not exist.\n"; 
                        echo $opt_err;
                        socket_write( $client, $opt_err, strlen( $opt_err ) );                        
                    }
                    
                    // If the torrent is active there is a chance that it's running right now
                    foreach( $peer_info_list as $key=>$peer_info ){
                        if( $torrent_info->info_hash == $peer_info->info_hash ){
                            unset( $peer_info_list[ $key ] );
                        }
                    }                        
                    // remove torrent from the list
                    $torrent_info_list->remove( $torrent_info->info_hash );
                                                          
                    // Remove torrent from the database
                    $this->removeTorrentFromDatabase( $torrent_info );
                    
                    // Finally Roemove files if $delete_files is set to 1/true   
                    if( $delete_files == true ){
                        Storage::deleteStorage( $torrent_info );
                    } 
                    
                    // wait we also gotta to add a free active torrent to the torrent list
                    $need = Config::MAX_ACTIVE_RUNNING_TORRENTS - count( $torrent_info_list );
                    $torrent_info_list->addList( $this->getActiveTorrents( Config::MAX_ACTIVE_RUNNING_TORRENTS ), $need  );
                    
                    socket_write( $client, 'success', 7 ); 
                    echo "Torrent ,{$torrent_info->info_hash}, has been Deleted.\n";
                    break;
                case Operation::ACTIVATE_TORRENT:
                    $torrent_info_hash = socket_read( $client, 40 );
                    
                    if( !( $torrent_info = $this->getTorrent( $torrent_info_hash ) ) ){
                        $opt_err = "Operation error: Couldn't activate torrent $torrent_info_hash. Torrent does not exist.\n"; 
                        echo $opt_err;                       
                    }
                    
                    $this->activateTorrent( $torrent_info );
                    
                    // Try to add it to the torrent list
                    if( count( $torrent_info_list ) < Config::MAX_ACTIVE_RUNNING_TORRENTS ){                        
                        $torrent_info_list->add( $torrent_info );
                    }
                    
                    echo "Torrent ,{$torrent_info->info_hash}, has been activated.\n";
                    break;        
                case Operation::DEACTIVATE_TORRENT:
                    $torrent_info_hash = socket_read( $client, 40 );
                    if( !( $torrent_info = $this->getTorrent( $torrent_info_hash ) ) ){
                        $opt_err = "Operation error: Couldn't deactivate torrent $torrent_info_hash. Torrent does not exist.\n"; 
                        echo $opt_err;                       
                    }
                    
                    // If the torrent is active there is a chance that it's running right now
                    foreach( $peer_info_list as $key=>$peer_info ){
                        if( $torrent_info->info_hash == $peer_info->info_hash ){
                            unset( $peer_info_list[ $key ] );
                        }
                    }
                    $torrent_info_list->remove( $torrent_info->info_hash );
                    
                    $this->deactivateTorrent( $torrent_info );
                    
                    // We jusy need to find a free active torrent to take its place
                    $need = Config::MAX_ACTIVE_RUNNING_TORRENTS - count( $torrent_info_list );
                    $torrent_info_list->addList( $this->getActiveTorrents( Config::MAX_ACTIVE_RUNNING_TORRENTS ), $need  );                           
                    
                    echo "Torrent ,{$torrent_info->info_hash}, has been deactivated.\n";
                    break;
                    
                case Operation::DISPLAY_ALL_RUNNING_TORRENTS:
                    $json_data = json_encode( $torrent_info_list );                    
                    socket_write( $client, $json_data, strlen( $json_data ) ); 
                    break;
                case Operation::DISPLAY_ALL_TORRENTS:
                    // Display the information in json
                    $json_data = json_encode( $this->getAllTorrents() );
                    // write back
                    socket_write( $client, $json_data, strlen( $json_data ) );               
                    break;
                default:
                    // TODO disconnect on too many bad operations in a row
                    echo "operation unkown\n";
                    break;        
            }
            
        }
        //
    }
    
    /**
     * Check the data with the piece define in the torrent,
     * @todo how will i handle partial data?
     * @param $torrent_info
     * @param $index
     * @param $block
     * @return True if match
     */
    private function checkPiece( TorrentInformation $torrent_info, $index, $block ){
        $pieces = str_split( $torrent_info->pieces, 20 );        
                     
        if( sha1( $block, true ) == $pieces[ $index ] ){
            // It's match
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Switch a bit at a spicific location from 0 to 1
     * @note The index of 0 starts at the beginning.
     * @param $binary_string
     * @param $bit_index
     * @return Edited Binary String
     */
    private function flipBitOn( $binary_string, $bit_index ){
        $binary_array = unpack( 'C*', $binary_string );                           
        
        // arrays from unpack starts with index 1
        $real_idx = floor( $bit_index / 8 ) + 1; // The byte(char) index
        $value = $binary_array[ $real_idx ];
                            
        $value = $value | ( 128 >> ( $bit_index % 8 ) );
        $binary_array[ $real_idx ] = $value;
        // repack and return new string
        return call_user_func_array( 'pack', array_merge( array( 'C*' ), $binary_array ) );    
    }
    
    /**
     * Gather information about completed pieces and total filesize
     * to determine how many bytes are left for the torrent.
     * @param TorrentInformation
     * @return The number of bytes left.
     */
    private function BytesLeft( TorrentInformation $torrent_info ){                
        $total_filesize = $torrent_info->files->getTotalFileSize();        
        $has_left = $total_filesize;
        
        $bits = count( $torrent_info->bitfield );
                
        for( $i = 0; $i < $bits; ++$i ){
            if( $torrent_info->bitfield[ $i ] == true ){
                if( $i == $bits - 1 && ( $reminder = $total_filesize % $torrent_info->piece_length ) > 0 ){
                    // The last bit might is a different size
                    $has_left -= $reminder;
                    continue;
                }
                $has_left -= $torrent_info->piece_length;
            }
        }          
        
        $msg = "Torrent, {$torrent_info->info_hash}, has $has_left byte(s) left.";
        logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
        
        return $has_left;               
    }
    
    
    /**
     * Algorithm to get torrents that can be worked on right away.
     * This means torrent must be started(active)...
     * @throws     
     * @param $limit
     * @returns A list of torrents will be returned 
     */
    private function getActiveTorrents( $limit ){
        $torrent_list = array();    
        
        $stmt = $this->db_conn->prepare( "
        SELECT 
            *
        FROM Torrents        
        WHERE active = 1        
        ORDER BY RANDOM() LIMIT ?;" );
        
        $stmt->bindParam( 1, $limit, SQLITE3_INTEGER );          
        $results = $stmt->execute();
        
        return $this->buildTorrentInfoHelper( $results );
    }
            
    /**
     * Get a specific torrent.  
     * @param $info_hash 
     * @return A TorrentInformation object.
     */
    private function getTorrent( $info_hash ){
           
        $stmt = $this->db_conn->prepare( "
        SELECT 
            *
        FROM Torrents          
        WHERE info_hash = ?;" );
        
        $stmt->bindParam( 1, $info_hash, SQLITE3_TEXT );          
        $results = $stmt->execute();
        
        return $this->buildTorrentInfoHelper( $results )[ 0 ];
    }
    
    /**
     * Get all torrents, active or not. EVERTHING.
     * @returns TorrentInformationList.
     */
    private function getAllTorrents(){
        $results = $this->db_conn->query( "SELECT * FROM Torrents;" );            
        return $this->buildTorrentInfoHelper( $results );         
    } 
     
    /**
     * This function reduces duplication of code.
     * Basically this function continues to build
     * the TorrentInformation object using the database.
     * @returns TorrentInformationList
     */
    private function buildTorrentInfoHelper( $results ){       
         
        $torrent_info_list = new TorrentInformationList;
        while( $row = $results->fetchArray( SQLITE3_ASSOC ) ){
            $torrent_info = new TorrentInformation;
            $torrent_info->info_hash    = $row[ 'info_hash' ];
            $torrent_info->name         = $row[ 'name' ];
            $torrent_info->pieces       = $row[ 'pieces' ];
            $torrent_info->piece_length = $row[ 'piece_length' ];  
            $torrent_info->private      = $row[ 'is_private' ];  
            // storage
            $torrent_info->destination  = $row[ 'destination' ];
            // Statistics
            $torrent_info->bytes_left       = $row[ 'bytes_left' ];
            $torrent_info->bytes_uploaded   = $row[ 'bytes_uploaded' ];
            $torrent_info->bytes_downloaded = $row[ 'bytes_downloaded' ];
            $torrent_info_list->add( $torrent_info );            
        }        
        
        
        foreach( $torrent_info_list as &$torrent_info ){
            // Announce
            $stmt = $this->db_conn->prepare( "SELECT * FROM AnnounceUrls WHERE info_hash = ?;" );
            $stmt->bindParam( 1, $torrent_info->info_hash, SQLITE3_TEXT );
            $results = $stmt->execute();
            
            $announce_info_list = new AnnounceInformationList;
            while( $row = $results->fetchArray( SQLITE3_ASSOC ) ){
                $announce_info = new AnnounceInformation;
                $announce_info->url = $row[ "url" ];      
                $announce_info_list->add( $announce_info );                
            }            
            $torrent_info->announce_infos = $announce_info_list;
                        
            
            $stmt = $this->db_conn->prepare( "SELECT * FROM Files WHERE info_hash = ?;" );
            $stmt->bindParam( 1, $torrent_info->info_hash, SQLITE3_TEXT );
            $results = $stmt->execute();
            
            $file_info_list = new FileInformationList;
            while( $row = $results->fetchArray( SQLITE3_ASSOC ) ){
                $file_info = new FileInformation;
                $file_info->name = $row[ "filename" ];    
                $file_info->size = $row[ "filesize" ];
                $file_info_list->add( $file_info );               
            }            
            $torrent_info->files = $file_info_list;            
        }
               
        return $torrent_info_list;        
    }   
    
    /**
     * 
     */
    private function removeTorrentFromDatabase( TorrentInformation $torrent_info ){        
        $this->db_conn->exec( 'PRAGMA foreign_keys = ON;' );        
        $stmt = $this->db_conn->prepare( "DELETE FROM Torrents WHERE info_hash = ?;" );        
        $stmt->bindParam( 1, $torrent_info->info_hash, SQLITE3_TEXT );          
        $results = $stmt->execute();
    }
    
    /**
     * 
     */
    private function activateTorrent( TorrentInformation $torrent_info ){           
        $stmt = $this->db_conn->prepare( "UPDATE Torrents SET active = ? WHERE info_hash = ?;" );    
        $active = 1;
        $stmt->bindParam( 1, $active, SQLITE3_INTEGER );      
        $stmt->bindParam( 2, $torrent_info->info_hash, SQLITE3_TEXT ); 
        $results = $stmt->execute();
    }
    
    /**
     * 
     */
    private function deactivateTorrent( TorrentInformation $torrent_info ){           
        $stmt = $this->db_conn->prepare( "UPDATE Torrents SET active = ? WHERE info_hash = ?;" );    
        $active = 0;
        $stmt->bindParam( 1, $active, SQLITE3_INTEGER );      
        $stmt->bindParam( 2, $torrent_info->info_hash, SQLITE3_TEXT ); 
        $results = $stmt->execute();
    }
    
    /**
     * 
     */
    private function addStatisticsToDatabase( TorrentInformationList $torrent_info_list ){
        foreach( $torrent_info_list as $torrent_info ){
            $stmt = $this->db_conn->prepare( "UPDATE Torrents SET bytes_downloaded = ?, bytes_uploaded = ?, bytes_left = ? WHERE info_hash = ?;" );   
            $stmt->bindParam( 1, $torrent_info->bytes_downloaded, SQLITE3_INTEGER );
            $stmt->bindParam( 2, $torrent_info->bytes_uploaded, SQLITE3_INTEGER );
            $stmt->bindParam( 3, $torrent_info->bytes_left, SQLITE3_INTEGER );
            $stmt->bindParam( 4, $torrent_info->info_hash, SQLITE3_TEXT );
            $stmt->execute();
            $stmt->close();            
        }             
    }
    
    
    /**
     * Using the database get all the announce urls for a specific torrent.
     * @deprecated
     * @returns An array of urls from the torrent
     */
    private function getAnnounceUrls( $info_hash ){
        $announce_list = array();
        
        $stmt = $this->db_conn->prepare( "SELECT url FROM AnnounceUrls WHERE info_hash = ? ORDER BY rank DESC" );
        $stmt->bindParam( 1, $info_hash, SQLITE3_TEXT );  
        
        $results = $stmt->execute();
        while( $row = $results->fetchArray( SQLITE3_ASSOC ) ){
            array_push( $announce_list, $row[ "url" ] );
        }        
        
        $stmt->close();      
        
        return $announce_list;
    }
    
    
    /**
     * Add torrent and other things to the database
     * @throws     
     * @bug not a bug with this program but the phptracker. anounce is showing as an array.
     * plus it shows up twice, one in announce and another in announce-list
     */
    private function addTorrentToDatabase( $torrent_info ){
        
        $total_length = 0;
        foreach( $torrent_info->files AS $file_info ){ 
            $total_length += $file_info->size;            
        }     
            
        // Add main torrent infomation into database
        $count = 0;
        $stmt = $this->db_conn->prepare( 'INSERT INTO Torrents( info_hash, name, piece_length, pieces, destination, bytes_left, active ) VALUES( ?, ?, ?, ?, ?, ?, ? );' );        
        $stmt->bindParam( ++$count, $torrent_info->info_hash, SQLITE3_TEXT );  
        $stmt->bindParam( ++$count, $torrent_info->name, SQLITE3_TEXT );
        $stmt->bindParam( ++$count, $torrent_info->piece_length, SQLITE3_INTEGER );
        $stmt->bindParam( ++$count, $torrent_info->pieces, SQLITE3_BLOB );
        $stmt->bindParam( ++$count, $torrent_info->destination, SQLITE3_TEXT );
        $stmt->bindParam( ++$count, $total_length, SQLITE3_INTEGER ); 
        $stmt->bindParam( ++$count, $torrent_info->active, SQLITE3_INTEGER ); 
        $stmt->execute();        
        
        // Add file(s) to the database 
        $stmt = $this->db_conn->prepare( 'INSERT INTO Files( info_hash, filename, filesize ) VALUES( ?, ?, ? );' );        
        foreach( $torrent_info->files AS $file_info ){             
            $count = 0;
            $stmt->bindParam( ++$count, $torrent_info->info_hash, SQLITE3_TEXT );        
            $stmt->bindParam( ++$count, $file_info->name, SQLITE3_TEXT );
            $stmt->bindParam( ++$count, $file_info->size, SQLITE3_INTEGER );
            $stmt->execute();
        } 
                       
        // Add all announce urls to the database for the torrent
        $stmt = $this->db_conn->prepare( 'INSERT OR IGNORE INTO AnnounceUrls( info_hash, url ) VALUES( ?, ? );' );  
        foreach( $torrent_info->announce_infos AS $announce_info ){            
            $count = 0;
            $stmt->bindParam( ++$count, $torrent_info->info_hash, SQLITE3_TEXT );
            if( is_array( $announce_info->url ) ){
                $announce_info->url = $announce_info->url[ 0 ];
            }
            $stmt->bindParam( ++$count, $announce_info->url, SQLITE3_TEXT );            
            $stmt->execute();
        } 
       
    }
    //----------------
    
    /**
     * Open a socket to the HTTP tracker
     *     
     * @param &AnnounceInformation    
     * @return True if conneced, or false if not connected YET or timedout.
     */    
    private function connectTracker_HTTP( AnnounceInformation &$announce_info ){ 
        $url_comp = parse_url( $announce_info->url ); 
        
        if( !isset( $announce_info->address ) ){         
            // The real address is not set yet
            $announce_info->address = gethostbyname( $url_comp[ "host" ] ); // NOTE at times this is slow            
            if( !filter_var( $announce_info->address, FILTER_VALIDATE_IP ) ){
                // Can't get ip address, don't bother
                $msg = "Can't get ip address from tracker {$announce_info->url}";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
                
                ++$announce_info->number_of_failed_connections;
                return false;
            }           
        }
                
        $port = 80; // default http port     
        if( isset( $url_comp[ "port" ] ) ){
            $port = $url_comp[ "port" ];
        }  
        
        if( !$announce_info->is_connected && !$announce_info->is_connecting ){         
            // Create a connection to the torrent
            $announce_info->resource = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );       
            //socket_set_option( $announce_info->resource, SOL_SOCKET, SO_RCVTIMEO, array( "sec"=>5,"usec"=>0 ) );
            socket_set_nonblock( $announce_info->resource );
            $announce_info->conection_timeout = time();
            $announce_info->is_connecting = true;               
        }
        
        if( $announce_info->is_connecting ){           
            while( !( $result = @socket_connect( $announce_info->resource, $announce_info->address, $port ) ) ){  
                //NOTE even though it's nonblocking at curtian times it stalls for a view seconds            
                if( ( time() - $announce_info->conection_timeout ) >= 5 ){     
                    echo "Connection to tracker, {$announce_info->url}, timed out.\n";    
                    $announce_info->is_connected = false;
                    $announce_info->is_connecting = false;
                    ++$announce_info->number_of_failed_connections;
                    return false;
                }                
            }
            if( $result ){
                // We are connected
                $msg = "Tracker, {$announce_info->url}, successfully connected.";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );                
                
                socket_set_block( $announce_info->resource );
                $announce_info->is_connected = true;
                $announce_info->is_connecting = false;   
                return true;                
            }        
        }
        return false;
    }
    
    
    /**
     * Send a request to the tracker location with our GET varibles.     
     *     
     * @param &TorrentInformation
     * @param &AnnounceInformation    
     *
     */    
    private function sendTrackerRequest_HTTP( TorrentInformation &$torrent_info, AnnounceInformation &$announce_info, $my_port ){
        
        $url_comp = parse_url( $announce_info->url );     
        $address = gethostbyname( $url_comp[ "host" ] );
        $path = $url_comp[ "path" ];                       
        
        if( $announce_info->is_connected ){
            $read = array( $announce_info->resource );
                  
            if( socket_select( $read, $write, $except, 0 ) > 0 ){
                // Don't write any data while there is data in the read buffer
                return;
            }   
            
            // Finally send your request
            $getdata = http_build_query(
                array(        
                    'info_hash' => pack( "H*", $torrent_info->info_hash ),
                    'peer_id' => $this->peer_id,
                    'event' => 'started', // TODO on the tracker project
                    //'compact' => 0,
                    'numwant' => Config::TRACKER_NUMWANT,
                    'port' =>  $my_port,
                    'uploaded' => $torrent_info->bytes_uploaded,
                    'downloaded' => $torrent_info->bytes_downloaded,
                    'left' => $torrent_info->bytes_left + 2
                )
            );
         
            // TODO fix / TODO follow 301 redirects? /TODO Some tracker redirects back to me
            $out = "";
            $out .= "GET $path/?$getdata HTTP/1.1\r\n";
            $out .= "Host: {$url_comp["host"]}\r\n";            
            $out .= "Connection: keep-alive\r\n\r\n";
            
            
            socket_write( $announce_info->resource, $out, strlen( $out ) );  
            
            //$msg = "Sent {$url_comp["host"]} $path/?$getdata to tracker {$announce_info->url}";
            //logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
        }       
    }         
    
   /**
     * Check the for any repsponce from the tacker   
     *
     * @param &AnnounceInformation    
     * @return dictionary(array) format responce.
     */    
    private function recieveTrackerResponse_HTTP( AnnounceInformation &$announce_info ){
        if( $announce_info->is_connected ){
            $read = array( $announce_info->resource );
            $write = null;
            $except = null;
            
            if( socket_select( $read, $write, $except, 0 ) > 0 ){             
                $raw_http = socket_read( $announce_info->resource, 2048 );                  
                
                // search http response for the bencode string
                $raw_response = substr( $raw_http, strpos( $raw_http, "\r\n\r\n" ) + 4 );
                                
                // parse response message
                try{
                    $tracker_response = Bencode::decode( $raw_response );
                }catch( Exception $e ){                    
                    echo "Error getting response from {$announce_info->url} $raw_response $raw_http\n";
                    $announce_info->interval = 128;
                    $announce_info->last_access_time = time();                    
                    // TODO 
                    return false;
                }  
                
                
                // convert to normal array if compact
                if( !is_array( $tracker_response[ 'peers' ] ) ){
                    // This is compact mode                        
                    // covert into list
                    $temp_array = array();
                    $length = strlen( $tracker_response[ 'peers' ] );
                    for( $i = 0; $i < $length; $i += 6 ){
                        $data = substr( $tracker_response[ 'peers' ], $i, 6 );
                        $peer = unpack( "Nip/nport", $data );
                        $peer[ 'ip' ] = long2ip( $peer[ 'ip' ] );
                        array_push( $temp_array, $peer );                             
                    }
                    $tracker_response[ 'peers' ] = $temp_array;                       
                }
                
                
                return $tracker_response;
            }else{
                // No data yet               
                return false;
            }              
        }  
        // Not connected        
        return false;
    }
    
    /**
     * This will open a socket to the UDP and then finallize the 
     * connection with a connection ID 
     * @note for more information check out
     * {http://xbtt.sourceforge.net/udp_tracker_protocol.html}
     * @note tracker is not considered to be connected until the udp trackers sends
     *      
     * @param &AnnounceInformation 
     *
     */
    private function connectTracker_UDP( AnnounceInformation &$announce_info ){
        $url_comp = parse_url( $announce_info->url );     
                
        if( !isset( $announce_info->address ) ){  
            // Store real address
            $announce_info->address = gethostbyname( $url_comp[ "host" ] ); // NOTE at times this is slow            
            if( !filter_var( $announce_info->address, FILTER_VALIDATE_IP ) ){
                // Can't get ip address, don't bother
                $msg = "Can't get ip address from tracker {$announce_info->url}";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );
                
                ++$announce_info->number_of_failed_connections;
                return false;
            }           
        }        
                
        if( !isset( $url_comp[ "port" ] ) ){
            // Port is needed
            echo "UDP Tracker, {$announce_info->address}, doesn't have a port number.\n";
            ++$announce_info->number_of_failed_connections;
            return false;
        }
        $port = $url_comp[ "port" ];
        
                               
        if( !$announce_info->is_connected && !$announce_info->is_connecting ){ 
            // Create a udp connection to the udp tracker
            $announce_info->resource = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
            // Set nonblocking to implement timeouts
            socket_set_nonblock( $announce_info->resource );
            // begin timer
            $announce_info->conection_timeout = time();
            $announce_info->is_connecting = true;                
        }
        
        if( $announce_info->is_connecting && !$announce_info->partial_connect ){                                
            while( !( $result = @socket_connect( $announce_info->resource, $announce_info->address, $port ) ) ){                
                if( ( time() - $announce_info->conection_timeout ) >= config::TRACKER_CONNECTION_TIMEOUT ){     
                    echo "Connection to tracker, {$announce_info->url}, timed out.\n";                      
                    $announce_info->is_connecting = false;                      
                    ++$announce_info->number_of_failed_connections;
                    return false;
                }                
            }
            
            if( $result ){
                // We are connected but not really                
                socket_set_block( $announce_info->resource );                
                $announce_info->partial_connect = true;                
                
                // Send connection input
                $connect_id = "\x00\x00\x04\x17\x27\x10\x19\x80";            
                $transaction_id = mt_rand( 0, 65535 );
                $connect_msg = 
                    $connect_id . // connection_id
                    pack( 'N', 0 ) .              // action
                    pack( 'N', $transaction_id )  // transaction_id 
                ;        
                socket_write( $announce_info->resource, $connect_msg, strlen( $connect_msg ) );
            }        
        }
        
        
        if( $announce_info->partial_connect ){ 
            // Need the connection id from the tracker to complete the connection
            
            $read = array( $announce_info->resource );
            $write = null;
            $except = null;            
            if( socket_select( $read, $write, $except, 0 ) > 0 ){
                // This might be the connection output
                $raw_response = socket_read( $announce_info->resource, 16 ); 
                if( strlen( $raw_response ) != 16 ){
                    socket_close( $announce_info->resource );
                    ++$announce_info->number_of_failed_connections;
                    echo "Tracker, {$announce_info->url}, connect output is wrong.\n"; 
                    return false;
                }
                
                echo "Tracker, {$announce_info->url}, connected.\n";
                $announce_info->udp_connect_id = substr( $raw_response, 8, 8 );
                $announce_info->is_connected = true;
                $announce_info->partial_connect = false;
                $announce_info->is_connecting = false;
            }else{
                // UDP connection output hasn't came yet
                if( ( time() - $announce_info->conection_timeout ) >= config::TRACKER_CONNECTION_TIMEOUT ){ 
                    $announce_info->is_connecting = false;   
                    $announce_info->partial_connect = false;       
                    socket_close( $announce_info->resource );
                    echo "Connection to tracker, {$announce_info->url}, timed out.\n";
                    ++$announce_info->number_of_failed_connections;
                    return false;
                }
                 // Nothing yet
            }
        }       
    }
        
    
    /**
     * Send a request with our info to UDP tracker
     * @note for more information check out
     * {http://xbtt.sourceforge.net/udp_tracker_protocol.html}
     *     
     * @param &TorrentInformation
     * @param &AnnounceInformation     
     * @param $my_port
     */
    private function sendTrackerRequest_UDP( TorrentInformation &$torrent_info, AnnounceInformation &$announce_info, $my_port ){  
        $read = array( $announce_info->resource );
        $write = null;
        $except = null;            
        if( socket_select( $read, $write, $except, 0 ) > 0 ){
            // No data yet
            return false;
        }
        
        // NOTE packing with 'J' is not supportied on my version of php(5.4)
        // NOTE Forums said php doesnt support 64 bit int/long values
        $transaction_id = mt_rand( 0, 65535 );
        $announce_msg = 
            $announce_info->udp_connect_id .             // connection_id
            pack( 'N', 1 ) .               // action
            pack( 'N', $transaction_id ) . // transaction_id 
            pack( "H*", $torrent_info->info_hash ) .
            $this->peer_id .
            pack( 'N', 0 ) .
            pack( 'N', $torrent_info->bytes_downloaded ) .
            pack( 'N', 0 ) .
            pack( 'N', $torrent_info->bytes_left ) .
            pack( 'N', 0 ) .
            pack( 'N', $torrent_info->bytes_uploaded ) .
            pack( 'N', 0 ) .                      // event
            pack( 'N', ip2long( '127.0.0.1' ) ) . // IP address
            pack( 'N', 0 ) .     // key
            pack( 'N', Config::TRACKER_NUMWANT ) .     // num_what TODO what
            pack( 'n', $my_port )   // port
        ;        
        
        socket_write( $announce_info->resource, $announce_msg, strlen( $announce_msg ) );  
    }
    
    /**
     * Recieve respond from tracker
     * @note for more information check out
     * {http://xbtt.sourceforge.net/udp_tracker_protocol.html}
     *     
     * @param &TorrentInformation
     * @param &AnnounceInformation     
     * @param $my_port
     */
    private function recieveTrackerResponse_UDP( AnnounceInformation &$announce_info ){     
        $read = array( $announce_info->resource );
        $write = null;
        $except = null;            
        if( socket_select( $read, $write, $except, 0 ) == 0 ){
            // No data yet
            return false;
        }
    
        $n = Config::TRACKER_NUMWANT;
        $readlength = 20 + ( 6 * $n );
        $raw_response = socket_read( $announce_info->resource, $readlength );
        
        $action = current( unpack( 'Naction', $raw_response ) );
        switch( $action ){
            case 1:
                // output
                $response = unpack( 'Naction/Ntransaction_id/Ninterval/Nleechers/Nseeders', $raw_response );
                
                $peers = array();        
                for( $i = 20; $i < strlen( $raw_response ); $i = $i + 6 ){
                    $peer = unpack( 'Nip/nport', substr( $raw_response, $i, 6  ) );
                    $peer[ 'ip' ] = long2ip( $peer[ 'ip' ] );
                    array_push( $peers, $peer );
                }                                
                // Convert to array
                $true_responce = array(
                    'interval' => $response[ 'interval' ],
                    'complete' => $response[ 'seeders' ],
                    'incomplete' => $response[ 'leechers' ],
                    'peers' => $peers      
                ); 
                return $true_responce; 
                break;
            case 2:
                // scrape, we don't use this                
                break;
            case 3:
                // error
                $error_msg = strstr( $raw_response, 8 );
                $msg = "Error getting response from {$announce_info->url}, with message: $error_msg.";
                logger::logMessage( self::PROGRAM_NAME, Logger::DEBUG, $msg );                
                $announce_info->interval = 180;
                $announce_info->last_access_time = time();                    
                return false;
                break;
        };
               
                
    }
    
    
    
    /**
     * Send a handshake to the peer.
     *
     * @note make sure to call recieveHandShake after or before 
     * depending on the situation.
     *
     * @param $info_hash      
     * @param $socket 
     * @param $raw_input False for 40byte hex, True for 20byte binary
     * 
     */    
     private function sendHandShake( $info_hash, $socket ){
        if( strlen( $info_hash ) == 40 ){
            $info_hash = pack( "H*", $info_hash ); 
        }           
        
        // Signal for handshake
        $signal =
            pack( 'C', 19 ) .                          // Length of protocol string.
            'BitTorrent protocol' .                    // Protocol string.
            pack( 'a8', '' ) .                         // 8 void bytes.
            $info_hash .                               // Echoing the info hash that the client requested.
            pack( 'a20', $this->peer_id )              // Our peer id.
        ;
                       
        // send the handshake
        socket_write( $socket, $signal, strlen( $signal ) );      
     }
     
     /**
      * Recive the peer's handshake and validate
      *
      * @note make sure to call recieveHandShake after or before 
      * depending on the situation.
      *
      * @param $socket 
      * @returns False if failed or the PeerInfomation object if success
      */
     private function recieveHandShake( $socket ){
        //$status = socket_read( $socket, 1 );     
        $status = @socket_recv( $socket, $raw, 1, MSG_WAITALL );            
        if( $status == false ){
            // either the peer disconnected of gave me  0 bytes
            //echo "Socket error\n"; // why though
            return false;
        }
        
        
        $protocol_len = current( unpack( 'C', $raw ) );        
        if( $protocol_len != 19 ){
            // this is already a bad handshake
            echo "protacal length error\n";
            return false;
        }
        
        $protocol = socket_read( $socket, $protocol_len );            
        if( $protocol != 'BitTorrent protocol' ){
            // failed handshake, protacal doesn't matched
            echo "protacal mismatch\n";
            return false;
        }
        
        $reserved = socket_read( $socket, 8 ); 
        $info_hash = socket_read( $socket, 20 ); // TODO check torrent hash aganst the database
        $peer_id = socket_read( $socket, 20 );    
        
        if( strlen( $peer_id ) != 20 ){
            echo "No peer id in handshake";
            return false;
        }
        
        $temp_obj = new PeerInformation;
        $temp_obj->peer_id = $peer_id;
        $temp_obj->info_hash = implode( '', unpack( 'H*', $info_hash ) );
                
        return $temp_obj;
     }
     
     //------
     
     /**
      *
      */
     private function bufferRead( PeerInformation &$peer_info, $length ){ 
        $data = substr( $peer_info->buffer, 0, $length );
        // remove from buffer
        $peer_info->buffer = substr( $peer_info->buffer, $length );
        
        // peer's upload speed, our download speed? confusing FIXME
        $ctime = time();     
        $diff = $ctime - $peer_info->last_upload_time;
        if( $diff == 0 ){
            $peer_info->uploaded_temp += $length;           
        }else{
            $peer_info->upload_speed = ( $length + $peer_info->uploaded_temp ) / $diff;
            $peer_info->last_upload_time = $ctime;
            $peer_info->uploaded_temp = 0; 
        }    
        return $data;
     }
     
     /**
      * @deprecated
      */
     private function socketRead( PeerInformation &$peer_info, $length ){       
        $data = '';
        
        if( !socket_recv( $peer_info->resource, $data, $length, MSG_WAITALL ) ){        
            // Write to peer failed, connection must have failed.
            echo "Lost connection with peer when trying to read.\n";            
            socket_close( $peer_info->resource ); 
            return false;
        }
        
        // NOTE about prospectives when we are reading from a peer, peer is actually uploading 
        $ctime = time();     
        $diff = $ctime - $peer_info->last_upload_time;
        if( $diff == 0 ){
            $peer_info->uploaded_temp += $length;           
        }else{
            $peer_info->upload_speed = ( $length + $peer_info->uploaded_temp ) / $diff;
            $peer_info->last_upload_time = $ctime;
            $peer_info->uploaded_temp = 0; 
        }    
        
        return $data;
     }
     
     /**
      *
      */
     private function socketWrite( PeerInformation &$peer_info, $data, $length = 0 ){ 
        $length = strlen( $data );
        $success = @socket_write( $peer_info->resource, $data, $length );
        if( $success === false ){
            // Write to peer failed, connection must have failed.
            echo "Lost connection with peer when trying to write.\n";      
            @socket_close( $peer_info->resource ); 
            return;
        }
        
        // NOTE about prospectives when we are reading from a peer, peer is actually downloading 
        $ctime = time();
        $diff = $ctime - $peer_info->last_download_time;        
        $peer_info->last_download_time = $ctime;
        if( $diff == 0 ){
            $peer_info->downloaded_temp += $length;            
        }else{
            $peer_info->download_speed = ( $length + $peer_info->downloaded_temp ) / $diff;
            $peer_info->last_download_time = $ctime;
            $peer_info->downloaded_temp = 0;            
        }       
     }
     
     /**
      * Send Keep alive.
      */
     private function messageKeepAlive( PeerInformation &$peer_info ){     
        $message = pack( 'N', 0 );
        $this->socketWrite( $peer_info, $message ); 
     }
     
     /**
      * Send choke.
      */
     private function messageChoke( $socket ){       
        socket_write( $socket, pack( 'NC', 1, 0 ), 5 );
     }
     
     /**
      * Send unchoke.
      */
     private function messageUnChoke( $socket ){       
        socket_write( $socket, pack( 'NC', 1, 1 ), 5 );
     }
     
     /**
      * Send Interested.
      */
     private function messageInterested( PeerInformation &$peer_info ){  
        $message = pack( 'NC', 1, 2 );        
        $this->socketWrite( $peer_info, $message ); 
     }
     
     /**
      * Send Interested.
      */
     private function messageNotInterested( $socket ){        
        socket_write( $socket, pack( 'NC', 1, 3 ), 5 );
     }
     
     
     /**
      * have.
      */
     private function messageHave( PeerInformation &$peer_info, $index ){
        $message = pack( 'NCN', 5, 4, $index );       
        $this->socketWrite( $peer_info, $message );        
     }
     
    
     
     /**
      * Send bitfield
      */
     private function messageBitfield( $socket, $bitfield ){
        $message_id = pack( 'C', 5 ); 
        $message_len = pack( 'N', strlen( $bitfield ) + 1 );
        $message = $message_len . $message_id . $bitfield;
        
        socket_write( $socket, $message, strlen( $message ) );
     }
     
     
     /**
      * Request a piece from the peer
      * 
      * @returns the piece
      */
     private function messageRequest( PeerInformation &$peer_info, $index, $begin, $piece_length ){   
        // request
        $message = pack( 'NCNNN', 13, 6, $index, $begin, $piece_length );
        $this->socketWrite( $peer_info, $message );               
     }
     
     /**
      * Request a piece from the peer
      * 
      * @returns the piece
      */
     private function messagePiece( $socket, $index, $begin, $block ){  
        $message_id = pack( 'C', 7 );
        $payload = pack( 'NN', $index, $begin ) . $block;
        $message_len = pack( 'N', strlen( $payload ) + 1 );        
        $message = $message_len . $message_id . $payload;       
        socket_write( $socket, $message, strlen( $message ) );
     }
     
     
    
}













