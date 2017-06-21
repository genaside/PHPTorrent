<?php

class BenchmarkTest
{
    /*
        From the beginning torrent is added to the time it finishes, time
        how long it takes. This benchmark will be compared to Ktorrent.
        Remember to give PHPTorrent some slack.
    */

    public function testLeechingBenchmarks()
    {
        // Connect to daemon's interface
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, '127.0.0.1', 7423);
        $status = socket_read($socket, 1);
        $status = current(unpack('C', $status));
        if ($status != 1) {
            echo "Couldn't connect error.\n";
            exit();
        }

        //$start_time = time();

        // Add a torrent
        $torrent_source = "http://linuxtracker.org/download.php?id=7063785beefd3a9816d3a279242d80f5a27a5390&f=kali_linux_1.1.0a_amd64_mini.torrent&key=6c2d037a";

        // Set the download destination
        $download_destination = __DIR__ . '/../resources';
        $active = true;

        // Pack it nicely for socket
        $message = pack('C', 50); // operation 50 means add torrent from source
        $message .= pack('N', strlen($torrent_source)) . $torrent_source;
        $message .= pack('N', strlen($download_destination)) . $download_destination;
        $message .= pack('C', $active);

        // Send it
        socket_write($socket, $message, strlen($message));

        // Now wait for the response
        if (($response = socket_read($socket, 1024)) == "success") {
            echo "Congratulations torrent has been added\n";
        } else {
            echo $response; // Got an error
        }
    }
}