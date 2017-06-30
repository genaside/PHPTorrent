<?php

use genaside\PHPTorrent\Interfacing\AddTorrentForm;
use genaside\PHPTorrent\Interfacing\PHPTorrentInterface;

class InterfaceTest extends \PHPUnit\Framework\TestCase
{
    public function testInterfaceUsage()
    {
        $test = new PHPTorrentInterface();
        $test->connect(7423);
        $test->connect(7423, '127.0.0.1', 'user', 'pasfs');


        $form = new AddTorrentForm();
        $form->torrent_source_path = "http://linuxtracker.org/download.php?id=7063785beefd3a9816d3a279242d80f5a27a5390&f=kali_linux_1.1.0a_amd64_mini.torrent&key=6c2d037a";
        $form->download_destination = __DIR__ . '/../resources';
        $form->active = 1;

        $test->addTorrent($form);
        $test->removeTorrent("7063785beefd3a9816d3a279242d80f5a27a5390", true);
        $test->activateTorrent("7063785beefd3a9816d3a279242d80f5a27a5390");
        $test->deactivateTorrent("7063785beefd3a9816d3a279242d80f5a27a5390");

        $data = $test->displayAllRunningTorrent();
        var_dump($data);

        $data = $test->displayAllTorrent();
        var_dump($data);

        $test->shutdownDaemon();
    }
}