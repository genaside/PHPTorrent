<?php

/*
    Here is a quick tutorial on controlling PHPTorrent while it's running.
    Make sure PHPTorrent/daemon is running on command line and not on apache.
    You can also look at config.php to change various options.
*/

/*
    --- Connecting to the daemon ---
    If you know the ip address and port of the daemon's interface socket, 
    try connection to it. Note, that the deamon has two server sockets
    and you should not try to confuse them. This is how we connect to daemon
    using defaults.
*/
