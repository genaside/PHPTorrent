<?php

namespace genaside\PHPTorrent\Logger;

use genaside\PHPTorrent\Config\Config;

/**
 * Class Logger
 * @package genaside\PHPTorrent\Logger
 */
class Logger
{
    // Message types
    const STATUS = 1;
    const CRITICAL = 2; // exit program
    const WARNING = 3;
    const DEBUG = 4;

    /**
     * @param string $programName
     * @param int $type
     * @param string $message
     */
    public static function logMessage($programName, $type, $message)
    {
        $dateTime = date('Y-m-d H:i:s');

        switch ($type) {
            case self::STATUS:
                $type_str = 'Status';
                break;
            case self::CRITICAL:
                $type_str = 'Critical';
                break;
            case self::WARNING:
                $type_str = 'Warning';
                break;
            case self::DEBUG:
            default:
                $type_str = 'Debug';
                break;
        }


        $message_log = "[$dateTime][$programName][$type_str]: $message\n";

        $report = function () use (&$message_log) {
            if (Config::ENABLE_LOGGING_ON_STDOUT) {
                // Output message to console
                echo $message_log;
            }
            // Log the message
            error_log($message_log, 3, Config::LOGFILE_LOCATION);
        };


        //TODO CHECK file permissions

        if (Config::LOG_LEVEL == 1) {
            switch ($type) {
                case self::STATUS:
                case self::CRITICAL:
                case self::WARNING:
                    $report();
                    break;
                default:
                    break;
            }
        } else {
            if (Config::LOG_LEVEL == 2) {
                switch ($type) {
                    case self::STATUS:
                    case self::CRITICAL:
                    case self::WARNING:
                    case self::DEBUG:
                        $report();
                        break;
                    default:
                        break;
                }
            }
        }
    }
}