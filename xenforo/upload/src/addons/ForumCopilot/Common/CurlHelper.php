<?php
namespace ForumCopilot\Common;

class CurlHelper
{
    public static function getImageContentWithCurl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data === false) {
            return false;
        }

        return $data;
    }
}
