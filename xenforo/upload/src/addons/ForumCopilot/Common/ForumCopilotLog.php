<?php

namespace ForumCopilot\Common;

class ForumCopilotLog
{
    /**
     * Logs a message to a specified log file.
     *
     * @param string $message The message to log.
     * @param string $logFile The log file path.
     */
    public static function logMessage($message, $logFile = 'logs/app.log')
    {
        // Ensure the log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Format the message with a timestamp
        $formattedMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        // Write the message to the log file
        file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
    }
}

