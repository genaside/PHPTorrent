<?php

namespace genaside\PHPTorrent\Config;

/**
 * PHPTorrent config file
 */
class Config
{

    /* ----- Daemon Port ----- */

    /**
     * Port used for incoming connections made by peers. If a
     * list/range is defined, then instead of the daemon failing
     * on a port because it's used by another program, deamon
     * will run down the list finding the next available port to use.
     * Use commas between numbers to create a list, or dash to
     * select a range of numbers.
     * For example '6881,7123' will set available ports 6881 and 7123,
     * while '6881-6889' will search every port between 6881 and 6889
     * for an available port to use. Also you can a combination
     * of commas and dashes, like  '6881-6889,7123'.
     *
     * @note The string must contain numbers, with one comma or dashes between numbers.
     *
     * @var string
     */
    const CLIENT_PORT_RANGE = "6881-6889";

    /* ----- Interface ----- */

    /**
     * A port used to control the behavior of the daemon
     * of this BitTorrent client.
     *
     * @var int (unsigned)
     */
    const CLIENT_INTERFACE_PORT = 7423;

    /**
     * Max number of open connections that can be made to the interface port.
     *
     * @note this might be deprecated to be just 1 always.
     * @var int (unsigned)
     */
    const MAX_INTERFACE_CONNECTIONS = 1;

    /* ----- Interface Authentication ----- */

    /**
     * The max size that will be accepted for the username
     *
     * @var int
     */
    const MAX_USERNAME_SIZE = 16;

    /**
     * The max size that will be accepted for the password
     *
     * @var int
     */
    const MAX_PASSWORD_SIZE = 16;

    /**
     * Authentication - username for when connecting to this deamon.
     * When connected to the daemon a username and pass must be sent to
     * finalize your connection.
     *
     * @note Username should not go over 15 characters
     * @warning Not very secure, since username sent plain text to socket
     * @var string
     */
    const INTERFACE_USERNAME = '';

    /**
     * Authentication - password for when connecting to this deamon.
     * When connected to the daemon a username and pass must be sent to
     * finalize your connection.
     *
     * @note Passeord should not go over 15 characters
     * @warning Not very secure, since password sent plain text to socket
     * @var string
     */
    const INTERFACE_PASSWORD = '';

    /* ----- Database ----- */

    /**
     * Set the location of the database
     *
     * @var string
     */
    const CLIENT_DATABASE_LOCATION = __DIR__ . "/../../resources/db.sqlite";

    /**
     * The interval to update all statistics to the
     * database(roughly).
     *
     * @var int(unsigned)
     */
    const UPDATE_STATISTICS_INTERVAL = 10;


    /* ----- Logger ----- */

    /**
     * Enable logging
     * @var bool
     */
    const ENABLE_LOGGING = true;

    /**
     * The directory path for the log file. You can set file name as well.
     * @var string
     */
    const LOGFILE_LOCATION = __DIR__ . "/../../resources/info.log";

    /**
     * Enable logs to be sent to stdout (terminal) as well
     * @var bool
     */
    const ENABLE_LOGGING_ON_STDOUT = true;

    /**
     * Level of logging.
     *
     * 1 = Show basic daemon operations
     * 2 = Debugging, shows ALL messages.
     *
     * @var int (unsigned)
     */
    const LOG_LEVEL = 2;

    /* ----- Tracker ----- */

    /**
     * The amount of time that the daemon should wait to
     * connect to a tracker in seconds.
     *
     * @note Take into account that waiting on a tracker for a long time will
     * make leeching/seeding suffer.     *
     * @var int (unsigned)
     */
    const TRACKER_CONNECTION_TIMEOUT = 5;

    /**
     * The amount of connection errors the tracker can give before before
     * penalizing it.
     *
     * @deprecated
     * @var int (unsigned)
     */
    const TRACKER_CONNECTION_ERROR_THRESHOLD = 1;

    /**
     * The amount of bad responses the tracker can give before before
     * penalizing it.
     *
     * @var int (unsigned)
     */
    const TRACKER_BAD_RESPONSE_THRESHOLD = 3;

    /**
     * The number of peer to ask each tracker for.
     *
     * @note after MAX_PEERS_PER_TORRENT is resolved extra tracker will be stored
     * for later use.
     * @var int (unsigned)
     */
    const TRACKER_PEER_REQUESTS = 30;

    /* ----- NOTE still fixing this ----- */

    /**
     * This will tell the tracker that we want the peers in compact form.
     * If you don't know, think of it as reducing bandwidth.
     * @deprecated
     * @var bool
     */
    const ENABLE_COMPACT_PEER = true;

    const TTL = 180;

    /* ----- Torrent ----- */

    /**
     * Max running (seeding/leeching) torrents that then daemon should handle.
     *
     * @var int (unsigned)
     */
    const MAX_ACTIVE_RUNNING_TORRENTS = 2;

    /**
     * When a torrent is completed, a command pointed by this option will run.
     *
     * @note "-t '$hash_ifo'" will be inserted at the end of the command for those who want to know which torrent finished.
     * @note value must be an empty string or a complete path to the script/command.
     * @note example: "php /path/to/command"
     * @var string
     */
    const TORRENT_COMPLETION_NOTIFICATION_SCRIPT = "";


    /* ----- Peer ----- */

    /**
     * Max open peers per torrent
     *
     * @var int (unsigned)
     */
    const MAX_PEERS_PER_TORRENT = 20;

    /**
     * The amount of time that this client gets to connect to a peer
     * in seconds.
     *
     * @note The lower the timeout the better chance to get a fast
     * connection and to get the leaching/seeding process started.
     * A higher timeout will probably get more peers to work with,
     * but doubtful.
     * @var int (unsigned)
     */
    const PEER_CONNECTION_TIMEOUT = 1;

    /**
     * The amount of bad payloads that is willing to be tolerated from the peer.
     *
     * @note peer will be disconnected if threshold has been reached.
     * @var int (unsigned)
     */
    const PEER_BAD_DATA_THRESHOLD = 8;


    /* ----- Data transfer ----- */

    /**
     * In bytes, specify the max number of bytes for the payload the daemon
     * should ask for the peer for.
     *
     * @note It seems that 32kib is the strict specification, and 16kb is "semi-official".
     * However, the specs says that clients will automatically close connection if asked to
     * do payload length greater than 128kib. To be safe use 16kb or 32kb.
     * @note According to Vuze, "16 kiB 'blocks', which are the actual
     * smallest transmission units in the BitTorrent protocol."
     * @note Testing show that peers behave better with 16KiB, so it will be the default.
     * @var int (unsigned)
     */
    const MAX_BLOCK_REQUEST_LENGTH = 16384; // 16384 or 32768

    /**
     * Well basically I was getting 512kib/s on local torrents so I was wondering why it's so slow.
     * I realized that when I am sending a piece request by the time I am waiting
     * I should send out more pieces, but to a limit.
     * This option tells the program you can use up to N number of slots.
     *
     * @warning I dont think the option should go more than 4, You might BREAK the peers.
     * I tested it at 48 and the torrent downloads fast ~20Mib, but the local peer( ktorrent )
     * stalled at the end. 2 is good especially when having more than one peer.
     * @todo I don't think this should be an option, I think it should be hardcoded and can change during circumstances
     * @var int (unsigned)
     */
    const MAX_NUMBER_OF_PIECE_BUFFERS = 1;

    /**
     * A piece will be broken up into segments by MAX_BLOCK_REQUEST_LENGTH, and will
     * have to wait on the peer to return ALL segments. A timeout for ALL segments to return
     * can be set here.
     *
     * @var int (unsigned)
     */
    const PIECE_SEGMENT_TIMEOUT = 60;


}