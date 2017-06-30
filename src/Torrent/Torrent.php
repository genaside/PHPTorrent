<?php

namespace genaside\PHPTorrent\Torrent;

use genaside\PHPTorrent\Structures\AnnounceInformation;
use genaside\PHPTorrent\Structures\AnnounceInformationList;
use genaside\PHPTorrent\Structures\FileInformation;
use genaside\PHPTorrent\Structures\FileInformationList;
use genaside\PHPTorrent\Structures\TorrentInformation;
use Rych\Bencode\Bencode;

/**
 * A class to handle torrent files
 */
class Torrent
{

    /**
     * From a source covert torrent to a TorrentInformation object.
     * @note It turns out Magnet links are not really good. might need think about it TODO
     *
     * @todo Magnet link parser will take some time to create, so magnet links will not work
     * @param string $torrent_location The loccation of the torrent or magnet
     * @return TorrentInformation|bool (false on failure)
     */
    public static function getTorrentInfoFromSource($torrent_location)
    {

        // Start by getter the raw torrent data
        $raw_data = null;
        if (preg_match("/https{0,1}:\/\//i", $torrent_location)) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $torrent_location);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $raw_data = curl_exec($ch);
            curl_close($ch);

        } else {
            if (preg_match("/magnet:/i", $torrent_location)) {
                // TODO All i need to do is parse the magnet url correctly
                $url_components = parse_url($torrent_location);
                parse_str($url_components["query"], $test);
                var_dump($test);
                exit();
            } else {
                // Everything else like ftp and local files
                $raw_data = file_get_contents($torrent_location);
            }
        }

        // Now parse and convert  
        $data = null;
        try {
            $data = Bencode::decode($raw_data);
        } catch (\Exception $e) {
            return false;
        }

        // Start constructing the torrent info object        
        $torrent_info = new TorrentInformation();

        // Build the list of announce URIs
        $announce_info_list = new AnnounceInformationList;
        $announce_info = new AnnounceInformation;
        $announce_info->url = $data['announce'];
        $announce_info_list->add($announce_info);
        if (isset($data['announce-list'])) {
            foreach ($data['announce-list'] as $url) {
                $announce_info = new AnnounceInformation;
                $announce_info->url = $url;
                $announce_info_list->add($announce_info);
            }
        }

        // Build the file list
        $file_info_list = new FileInformationList;
        if (isset($data['info']['files'])) {
            foreach ($data['info']['files'] as $file) {
                $file_info = new FileInformation;
                $relative_name = implode(DIRECTORY_SEPARATOR, $file['path']);
                $file_info->name = $data['info']['name'] . DIRECTORY_SEPARATOR . $relative_name;
                $file_info->size = $file['length'];
                $file_info_list->add($file_info);
            }
        } else {
            $file_info = new FileInformation;
            $file_info->name = $data['info']['name'];
            $file_info->size = $data['info']['length'];
            $file_info_list->add($file_info);
        }

        // Build the remaining information
        $torrent_info->piece_length = $data['info']['piece length'];
        $torrent_info->pieces = $data['info']['pieces'];
        $torrent_info->announce_infos = $announce_info_list;
        $torrent_info->files = $file_info_list;
        $torrent_info->name = $data['info']['name'];
        $torrent_info->info_hash = sha1(Bencode::encode($data['info']), false);

        return $torrent_info;
    }


    /**
     * Create Torrent from scratch.
     * @param TorrentInformation $torrent_info
     * @param bool $flag
     */
    public static function createTorrentFile(TorrentInformation $torrent_info, $flag = true)
    {

    }

    /**
     * Edit Torrent file.
     * @param TorrentInformation $torrent_info
     * @param bool $flag
     */
    public static function editTorrentFile(TorrentInformation $torrent_info, $flag = true)
    {

    }

    /**
     * Parse Magnet Uri
     * @param $uri
     */
    public static function parseMagnetURI($uri)
    {

    }


}