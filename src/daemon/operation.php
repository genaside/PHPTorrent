<?php

namespace genaside\PHPTorrent\Daemon;

/**
 * A list of defined operations.
 *
 * @note
 * 'code', is unique operational number from 0 to 255
 * 'length' is when code requies more infomation.
 * 'path', is full location of a file
 * @todo fix the doc here
 */
class Operation
{

    /**
     * -----------------
     * | Daemon Options
     * -----------------
     */

    /**
     * Shutdown cleanly
     */
    const SHUTDOWN = 1; // {code}

    /**
     * Restart, by closing the current one and running a new one
     * via background command.
     */
    const RESTART = 2; // {code}

    /**
     * -----------------
     * | Torrent Options
     * -----------------
     */
    const CREATE_TORRENT = 255; // create a brand new torrent

    /**
     * Add torrent to client using torrent file
     */
    const ADD_TORRENT = 50; // {code}{path length}{path}{distination lenght}{destination}{activate}
    const REMOVE_TORRENT = 51; // {code}{info_hash}{files too}

    const ACTIVATE_TORRENT = 56;
    const DEACTIVATE_TORRENT = 57;

    //const DISPLAY_TORRENT_PROGRESS = 255;
    const DISPLAY_ALL_RUNNING_TORRENTS = 75; // {code}
    const DISPLAY_ALL_TORRENTS = 76; // {code}


}









