<?php


/**
 * 
 */
class Config{
    
    /**
     * Port used for incoming connections. If a list/range is defined
     * then instead of the deamon failing on a port used by another program,
     * deamon will run down the list finding the next available port.
     * Use commas between numbers to create a list,
     * or dash to select a range of numbers.
     * For example '6881,7123' will set ports 6881 and 7123,
     * while '6881-6889' will use every port between 6881 and 6889.
     * Also you can a combination of commas and dashes, like 
     * '6881-6889,7123'.
     * 
     * @note The string must contain numbers, commas, and dashes.
     *
     * @var string
     */
    const CLIENT_PORT_RANGE = "6881-6889"; 
    
    /* ----- Interface ----- */
    
    /**
     * A port used by another program to control the behavior
     * of this BitTorrent client. 
     * @note I, one of the developers, think this should be mandatory.
     * @var int(unsigned)
     */
    const CLIENT_INTERFACE_PORT = 7423; // This is a spec port that instructs the client what to do
    
    /**
     * Max number of open connections that can be made to the Interface connection
     * @note this might be deprecated to be just 1 always.
     * @var int(unsigned)
     */
    const MAX_INTERFACE_CONNECTIONS = 1; 
    
    /**
     * Authentication - username for when connecting to this deamon.      
     * @note Username should not go over 16 characters
     * @warning Not very secure, since username sent plain text to socket
     * @var string
     */
    const INTERFACE_USERNAME = 'user'; 
    
    /**
     * Authentication - password for when connecting to this deamon. 
     * @note Passeord should not go over 16 characters
     * @warning Not very secure, since password sent plain text to socket
     * @var string
     */
    const INTERFACE_PASSWORD = 'pass'; 
    
    /* ----- Database ----- */
    
    /**
     * Set the location of the database
     * @var string
     */
    const CLIENT_DATABASE_LOCATION = "./data/data.sqlite";
    
    /**
     * The interval to update all statistics to the database(roughly).
     * @var int(unsigned)
     */
    const UPDATE_STATISTICS_INTERVERL = 5;
       
       
    /* ----- Logger ----- */   
    
    /**
     * Enable logging
     * @var bool
     */
    const ENABLE_LOGGING = true;
    
    /**
     * The directory path for the log file. You can set file name 
     * as well.
     * @var string
     */
    const LOGFILE_LOCATION = "./data/log.txt";
    
    /**
     * Enable logs to be sent to stdout(terminal) as well
     * @var bool
     */
    const ENABLE_LOGGING_ON_STDOUT = true;
    
    /**
     * Level of logging.
     * @var int(unsigned)
     */
    const LOG_LEVEL = 5;
    
    /* ----- Tracker ----- */
    
    /**
     * The amount of time that this client should wait to connect to a tracker in seconds.
     * @note Take into acount that there might be multiple trackers being processed for one torrent,
     * which will slow download/upload     
     * @var int(unsigned)
     */
    const TRACKER_CONNECTION_TIMEOUT = 5;
    
    /**
     * The amount of connection errors the tracker can give before before client
     * can give up on it entirely.
     * @var int(unsigned)
     */
    const TRACKER_CONNECTION_ERROR_THRESHOLD = 1;
    
    /**
     * The amount of bad reponses a tracker can give before client
     * gives up enterly     
     * @var int(unsigned)
     */
    const TRACKER_BAD_RESPONCE_THRESHOLD = 3;
    
    /**
     * The number of peer to ask the tracker for.
     * @note after MAX_PEERS_PER_TORRENT is resolved extra tracker will be stored
     * for later user
     * @var int(unsigned)
     */
    const TRACKER_NUMWANT = 30;
    
    /* ----- * ----- */
    
    /**
     * This will tell the tracker that we  
     * want the peers in compact form.
     * If you don't know, think of it as reducing bandwith
     * @var bool
     */
    const ENABLE_COMPACT_PEER = true;
    
    
    //const MAX_NUMBER_OF = 7423;
    const TTL = 180;
    
    /* ----- Torrent ----- */
    
    /**
     * Max running(seeding/leeching) torrents     
     * @var int(unsigned)
     */
    const MAX_ACTIVE_RUNNING_TORRENTS = 1;
    
    /* ----- Peer ----- */    
    
    /**
     * Max open peers per torrent
     * @var int(unsigned)
     */
    const MAX_PEERS_PER_TORRENT = 10;    
    
    /**
     * The amount of time that this client gets to connect to a peer 
     * in seconds.
     * @note The lower the timeout the better chance to get a fast connection and to 
     * finish the connecting multiple peers step. A higher timeout will probably get more peers
     * to work with.     
     * @var int(unsigned)
     */
    const PEER_CONNECTION_TIMEOUT = 1;
    
    /**
     * The amount of bad payloads that is willing to be tolerated from the peer
     * @var int(unsigned)
     */
    const PEER_BAD_DATA_THRESHOLD = 8;
    
    
    /* ----- Data transfer ----- */   
    
    /**
     * In bytes, specify the max number of bytes a peer's piece paylod
     * should return.    
     * @note It seems that 32kib is the strict specification, and 16kb is "semi-official".
     * However, the specs says that clients will automaticly close connection if asked to
     * do paylod length greater than 128kib. To be safe use 16kb or 32kb.
     * @note According to vuze, "16 kiB 'blocks', which are the actual 
     * smallest transmission units in the bittorent protocol."
     * @note Testing show that peers behave better with 16KiB, so it will be the default.
     * @var int(unsigned)
     */
    const MAX_BLOCK_REQUEST_LENGTH = 16384; // 16384 or 32768
    
    /**
     * Well basicly i was getting 512kib/s on local torrents so i was wondering why so slow.
     * I realize when iam sending a piece request i then wait for the payload,
     * by the time iam waiting i should send out more piesces, but to a limit.
     * this option tells the program you can use up to N number of slots.
     * @warning I dont think the option should go more than 2, You might BREAK the peers.
     * I tested it at 48 and the torrent downloads fast ~20Mib, but the local peer using ktorrent
     * stalled at the end. 2 is good especially when having more than one peer.
     * @todo I dont think this should be a option, I think it should be hardcoded and can change
     * based on algorithms
     * @var int(unsigned)
     */
    const MAX_NUMBER_OF_PIECE_BUFFERS = 3;   
    
    /**
     * A piece will be broken up into segments by MAX_BLOCK_REQUEST_LENGTH, and will
     * have to wait on the peer to return ALL segments. A timeout for ALL segments to return
     * can be set here.     
     * @var int(unsigned)
     */
    const PIECE_SEGMENT_TIMEOUT = 30;
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}