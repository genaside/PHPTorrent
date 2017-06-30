<?php

namespace genaside\PHPTorrent\Interfacing;

/**
 * Class PHPTorrentInterface
 * @package genaside\PHPTorrent\Interfacing
 */
class PHPTorrentInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * An open socket the the daemon
     */
    public $socket;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (!is_null($this->socket)) {
            socket_close($this->socket);
        }
    }


    /**
     * Connect to the PHPTorrent's control interface.
     * This will connect to the daemons server socket
     * that is manly used for the control of the daemon.
     *
     * @param int $port
     * @param string $address
     * @param string $username
     * @param string $password
     * @return False if connection failed, True if success
     */
    public function connect($port, $address = '127.0.0.1', $username = '', $password = '')
    {
        $socket = null;
        if (!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            return false;
        }

        if (!socket_connect($socket, $address, $port)) {
            return false;
        }

        if ($username != '') {
            $userPass = "$username:$password";
            socket_write($socket, $userPass, strlen($userPass));
        }

        if (($status = socket_read($socket, 1)) === false) {//
            // No data            
            return false;
        }

        if (current(unpack('C', $status)) == self::FAILURE) {
            return false;
        }

        $this->socket = $socket;
        return true;
    }

    /**
     * Disconnect from the daemon.
     */
    public function disconnect()
    {
        socket_close($this->socket);
    }


    /**
     * Shutdown the daemon.
     * Tell daemon to shutdown
     */
    public function shutdownDaemon()
    {
        socket_write($this->socket, pack('C', 1), 1);
    }


    /**
     * Add Torrent.
     * Tell the daemon to add a torrent based on the input.
     *
     * @param AddTorrentForm $form
     * @return True if success
     */
    public function addTorrent(AddTorrentForm $form)
    {
        $message = pack('C', 50); // operation 50 means add torrent from source
        $message .= pack('N', strlen($form->torrent_source_path)) . $form->torrent_source_path;
        $message .= pack('N', strlen($form->download_destination)) . $form->download_destination;
        $message .= pack('C', $form->active);

        socket_write($this->socket, $message, strlen($message));

        // Wait for status
        $status = socket_read($this->socket, 1);
        if (current(unpack('C', $status)) == self::SUCCESS) {
            return true;
        }
        return false;
    }

    /**
     * Remove Torrent.
     * Tell the daemon to remove a torrent from the database
     * and running list
     *
     * @param string $info_hash
     * @param bool $delete_files - Delete the files too if true.
     * @return True if success
     */
    public function removeTorrent($info_hash, $delete_files = false)
    {
        $message = pack('C', 51);
        $message .= $info_hash;
        $message .= pack('C', $delete_files);
        socket_write($this->socket, $message, strlen($message));

        $status = socket_read($this->socket, 1);
        if (current(unpack('C', $status)) == self::SUCCESS) {
            return true;
        }
        return false;
    }

    /**
     * Activate Torrent.
     * Tell the daemon to activate a torrent from the database.
     * When activated and there space for a running torrent, then
     * activated torrent will start running
     *
     * @param string $info_hash
     * @return True if success
     */
    public function activateTorrent($info_hash)
    {
        $message = pack('C', 56);
        $message .= $info_hash;
        socket_write($this->socket, $message, strlen($message));

        $status = socket_read($this->socket, 1);
        if (current(unpack('C', $status)) == self::SUCCESS) {
            return true;
        }
        return false;
    }

    /**
     * Activate Torrent.
     * Tell the daemon to activate a torrent from the database.
     * When activated and there space for a running torrent, then
     * activated torrent will start running
     *
     * @param string $info_hash
     * @return True if success
     */
    public function deactivateTorrent($info_hash)
    {
        $message = pack('C', 57);
        $message .= $info_hash;
        socket_write($this->socket, $message, strlen($message));

        $status = socket_read($this->socket, 1);
        if (current(unpack('C', $status)) == self::SUCCESS) {
            return true;
        }
        return false;
    }

    /**
     * Display all running torrents.
     * Tell the daemon to send a json of all the torrents
     * that are currently running.
     *
     * @returns string
     */
    public function displayAllRunningTorrent()
    {
        $message = pack('C', 75);
        socket_write($this->socket, $message, strlen($message));

        $response = socket_read($this->socket, 4096);

        $obj = json_decode($response);
        return $obj;
    }

    /**
     * Display ALL torrents.
     * Tell the daemon to send a json of all the torrents
     * that are in the database.
     *
     * @returns string
     */
    public function displayAllTorrent()
    {
        $message = pack('C', 76);
        socket_write($this->socket, $message, strlen($message));

        $response = socket_read($this->socket, 8192);

        $obj = json_decode($response);
        return $obj;
    }


}