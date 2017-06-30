<?php

namespace genaside\PHPTorrent\Database;

use genaside\PHPTorrent\Config\Config;
use genaside\PHPTorrent\Structures\AnnounceInformation;
use genaside\PHPTorrent\Structures\AnnounceInformationList;
use genaside\PHPTorrent\Structures\FileInformation;
use genaside\PHPTorrent\Structures\FileInformationList;
use genaside\PHPTorrent\Structures\TorrentInformation;
use genaside\PHPTorrent\Structures\TorrentInformationList;

/**
 * Class Database
 * @package genaside\PHPTorrent\Database
 */
class Database
{

    /**
     * Connection to the database
     *
     * @var \SQLite3
     */
    public $db_connection;

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (is_resource($this->db_connection)) {
            $this->db_connection->close();
        }
    }


    /**
     * Connect to database file.
     * Find the sqlite file and open it, if file
     * does not exist create one.
     * Also the foreign_keys options is turned on here.
     *
     * @throws \Exception
     */
    public function connect()
    {
        $this->db_connection = new \SQLite3(
            Config::CLIENT_DATABASE_LOCATION,
            SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
        );

        if (!$this->db_connection->exec('PRAGMA foreign_keys = ON;')) {
            throw new \Exception($this->db_connection->lastErrorMsg());
        }
    }

    /**
     * Close database connection.
     */
    public function disconnect()
    {
        $this->db_connection->close();
    }


    /**
     * Build database.
     * If haven't already done so, the database will be rebuilt using a file as template which is in the resources/ directory
     *
     * @throws \Exception
     */
    public function buildDatabase()
    {
        $sql = file_get_contents(__DIR__ . '/../../resources/tables.sql');
        if (!$this->db_connection->exec($sql)) {
            throw new \Exception($this->db_connection->lastErrorMsg());
        }
    }

    /**
     * Get a specific torrent.
     *
     * @param string $info_hash
     * @return TorrentInformation A TorrentInformation object.
     */
    public function getTorrent($info_hash)
    {

        $stmt = $this->db_connection->prepare("
        SELECT 
            *
        FROM Torrents          
        WHERE info_hash = ?;");

        $stmt->bindParam(1, $info_hash, SQLITE3_TEXT);
        $results = $stmt->execute();

        return $this->buildTorrentInfoHelper($results)[0];
    }

    /**
     * Get all torrents, active or not. EVERYTHING.
     *
     * @returns TorrentInformationList.
     */
    public function getAllTorrents()
    {
        $results = $this->db_connection->query("SELECT * FROM Torrents;");
        return $this->buildTorrentInfoHelper($results);
    }

    /**
     * Get all active torrents, but to a limit.
     *
     * @throws \Exception
     * @param int $limit
     * @returns TorrentInformationList A list of torrents will be returned
     */
    public function getActiveTorrents($limit)
    {
        $stmt = $this->db_connection->prepare("
        SELECT 
            *
        FROM Torrents        
        WHERE active = 1        
        ORDER BY RANDOM() LIMIT ?;");

        $stmt->bindParam(1, $limit, SQLITE3_INTEGER);
        $results = $stmt->execute();

        return $this->buildTorrentInfoHelper($results);
    }


    /**
     * This function reduces duplication of code.
     * Basically this function continues to build
     * the TorrentInformation object using the database.
     *
     * @param \SQLite3Result $results
     * @return TorrentInformationList
     */
    private function buildTorrentInfoHelper($results)
    {
        $torrent_info_list = new TorrentInformationList;

        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $torrent_info = new TorrentInformation;
            $torrent_info->info_hash = $row['info_hash'];
            $torrent_info->name = $row['name'];
            $torrent_info->pieces = $row['pieces'];
            $torrent_info->piece_length = $row['piece_length'];
            $torrent_info->private = $row['is_private'];

            // Storage
            $torrent_info->destination = $row['destination'];

            // Statistics
            $torrent_info->bytes_left = $row['bytes_left'];
            $torrent_info->bytes_uploaded = $row['bytes_uploaded'];
            $torrent_info->bytes_downloaded = $row['bytes_downloaded'];
            $torrent_info_list->add($torrent_info);
        }

        foreach ($torrent_info_list as &$torrent_info) {
            // Announce
            $stmt = $this->db_connection->prepare("SELECT * FROM AnnounceUrls WHERE info_hash = ?;");
            $stmt->bindParam(1, $torrent_info->info_hash, SQLITE3_TEXT);
            $results = $stmt->execute();

            $announce_info_list = new AnnounceInformationList;
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                $announce_info = new AnnounceInformation;
                $announce_info->url = $row["url"];
                $announce_info_list->add($announce_info);
            }
            $torrent_info->announce_infos = $announce_info_list;


            $stmt = $this->db_connection->prepare("SELECT * FROM Files WHERE info_hash = ?;");
            $stmt->bindParam(1, $torrent_info->info_hash, SQLITE3_TEXT);
            $results = $stmt->execute();

            $file_info_list = new FileInformationList;
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                $file_info = new FileInformation;
                $file_info->name = $row["filename"];
                $file_info->size = $row["filesize"];
                $file_info_list->add($file_info);
            }
            $torrent_info->files = $file_info_list;
        }

        return $torrent_info_list;
    }

    /**
     * Set database with current Statistics
     *
     * @param TorrentInformationList $torrent_info_list
     * @internal param $TorrentInformationList
     */
    public function addStatisticsToDatabase(TorrentInformationList $torrent_info_list)
    {
        foreach ($torrent_info_list as $torrent_info) {
            $stmt = $this->db_connection->prepare("UPDATE Torrents SET bytes_downloaded = ?, bytes_uploaded = ?, bytes_left = ? WHERE info_hash = ?;");
            $stmt->bindParam(1, $torrent_info->bytes_downloaded, SQLITE3_INTEGER);
            $stmt->bindParam(2, $torrent_info->bytes_uploaded, SQLITE3_INTEGER);
            $stmt->bindParam(3, $torrent_info->bytes_left, SQLITE3_INTEGER);
            $stmt->bindParam(4, $torrent_info->info_hash, SQLITE3_TEXT);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Add a torrent and other things to the database
     *
     * @param $torrent_info
     */
    public function addTorrentToDatabase($torrent_info)
    {

        $total_length = 0;
        foreach ($torrent_info->files AS $file_info) {
            $total_length += $file_info->size;
        }

        // Add main torrent information into database
        $count = 0;
        $stmt = $this->db_connection->prepare('INSERT INTO Torrents( info_hash, name, piece_length, pieces, destination, bytes_left, active ) VALUES( ?, ?, ?, ?, ?, ?, ? );');
        $stmt->bindParam(++$count, $torrent_info->info_hash, SQLITE3_TEXT);
        $stmt->bindParam(++$count, $torrent_info->name, SQLITE3_TEXT);
        $stmt->bindParam(++$count, $torrent_info->piece_length, SQLITE3_INTEGER);
        $stmt->bindParam(++$count, $torrent_info->pieces, SQLITE3_BLOB);
        $stmt->bindParam(++$count, $torrent_info->destination, SQLITE3_TEXT);
        $stmt->bindParam(++$count, $total_length, SQLITE3_INTEGER);
        $stmt->bindParam(++$count, $torrent_info->active, SQLITE3_INTEGER);
        $stmt->execute();

        // Add file(s) to the database 
        $stmt = $this->db_connection->prepare('INSERT INTO Files( info_hash, filename, filesize ) VALUES( ?, ?, ? );');
        foreach ($torrent_info->files AS $file_info) {
            $count = 0;
            $stmt->bindParam(++$count, $torrent_info->info_hash, SQLITE3_TEXT);
            $stmt->bindParam(++$count, $file_info->name, SQLITE3_TEXT);
            $stmt->bindParam(++$count, $file_info->size, SQLITE3_INTEGER);
            $stmt->execute();
        }

        // Add all announce urls to the database for the torrent
        $stmt = $this->db_connection->prepare('INSERT OR IGNORE INTO AnnounceUrls( info_hash, url ) VALUES( ?, ? );');
        foreach ($torrent_info->announce_infos AS $announce_info) {
            $count = 0;
            $stmt->bindParam(++$count, $torrent_info->info_hash, SQLITE3_TEXT);
            if (is_array($announce_info->url)) {
                $announce_info->url = $announce_info->url[0];
            }
            $stmt->bindParam(++$count, $announce_info->url, SQLITE3_TEXT);
            $stmt->execute();
        }

    }


    /**
     * @param TorrentInformation $torrent_info
     */
    public function removeTorrentFromDatabase(TorrentInformation $torrent_info)
    {
        $stmt = $this->db_connection->prepare("DELETE FROM Torrents WHERE info_hash = ?;");
        $stmt->bindParam(1, $torrent_info->info_hash, SQLITE3_TEXT);
        $results = $stmt->execute();
    }

    /**
     * @param TorrentInformation $torrent_info
     */
    public function activateTorrent(TorrentInformation $torrent_info)
    {
        $stmt = $this->db_connection->prepare("UPDATE Torrents SET active = ? WHERE info_hash = ?;");
        $active = 1;
        $stmt->bindParam(1, $active, SQLITE3_INTEGER);
        $stmt->bindParam(2, $torrent_info->info_hash, SQLITE3_TEXT);
        $results = $stmt->execute();
    }

    /**
     * @param TorrentInformation $torrent_info
     */
    public function deactivateTorrent(TorrentInformation $torrent_info)
    {
        $stmt = $this->db_connection->prepare("UPDATE Torrents SET active = ? WHERE info_hash = ?;");
        $active = 0;
        $stmt->bindParam(1, $active, SQLITE3_INTEGER);
        $stmt->bindParam(2, $torrent_info->info_hash, SQLITE3_TEXT);
        $results = $stmt->execute();
    }
}
