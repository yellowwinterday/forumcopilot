<?php

namespace ForumCopilot\Controller;

require_once \XF::getRootDirectory().'/src/addons/ForumCopilot/Lib/autoload.php';

use ForumCopilot\Common\CurlHelper;
use XF\Http\Upload;

class ImageApi extends \ForumCopilot\Base\BaseApi
{
    private $maxDimension = 200;

    public function checkImage($json)
    {

        if (extension_loaded('gd')) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];


            // Check if $json is an instance of XF\Http\Upload
            if ($json instanceof Upload) {
                $filename = $json->getFileName();
                $tempFile = $json->getTempFile();
                $extension = strtolower($json->getExtension());
            } else {

                if (!isset($json['attachment']['filename']) || !isset($json['attachment']['link'])) {
                    return ['status' => 1, 'reason' => 'Invalid image or unsupported format.'];
                }
                $filename = $json['attachment']['filename'];
                $link = $json['attachment']['link'];
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                $fullUrl = $protocol . $host . $link;

                // Get the image content
                $imageContent = @file_get_contents($fullUrl);
                if ($imageContent === false) {
                    $imageContent = CurlHelper::getImageContentWithCurl($fullUrl);
                }
                if ($imageContent === false) {
                    return ['status' => 1, 'reason' => 'Invalid image or unsupported format.'];
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'img_');
                file_put_contents($tempFile, $imageContent);
            }

            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (in_array($extension, $imageExtensions)) {
                // Create image resource
                $sourceImage = imagecreatefromstring(file_get_contents($tempFile));

                if ($sourceImage !== false) {
                    // Get original image dimensions
                    list($sourceWidth, $sourceHeight) = getimagesize($tempFile);

                    // Calculate new dimensions while maintaining aspect ratio
                    if ($sourceWidth > $this->maxDimension || $sourceHeight > $this->maxDimension) {
                        $aspectRatio = $sourceWidth / $sourceHeight;
                        if ($sourceWidth > $sourceHeight) {
                            $newWidth = $this->maxDimension;
                            $newHeight = $this->maxDimension / $aspectRatio;
                        } else {
                            $newHeight = $this->maxDimension;
                            $newWidth = $this->maxDimension * $aspectRatio;
                        }
                    } else {
                        $newWidth =  $sourceWidth;
                        $newHeight =  $sourceHeight;
                    }
                    // Create target image resource
                    $targetImage = imagecreatetruecolor($newWidth, $newHeight);

                    // Copy and resize the image
                    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

                    // Generate a unique filename and construct the save path
                    $randomFileName = uniqid('img_', true) . '.' . $extension;
                    $savePath = \XF::getRootDirectory() . '/compressed_' . $randomFileName;

                    // Save the resized image based on its format
                    switch ($extension) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg($targetImage, $savePath);
                            break;
                        case 'png':
                            imagepng($targetImage, $savePath);
                            break;
                        case 'gif':
                            imagegif($targetImage, $savePath);
                            break;
                        case 'bmp':
                            imagewbmp($targetImage, $savePath);
                            break;
                        case 'webp':
                            imagewebp($targetImage, $savePath);
                            break;
                    }

                    // Free up memory
                    imagedestroy($sourceImage);
                    imagedestroy($targetImage);

                    // Construct public URL for the resized image
                    $publicUrl = $protocol . $host . '/compressed_' . $randomFileName;

                    // Call GPT-4 API
//                        $result = $this->callGPT4WithImage($publicUrl);
                    $result = $this->callGPT4WithImage("https://endpoint.forumcopilot.com/uploads/46bd5cbbd1247e30.png");

                    // Delete the local saved resized image
                    if (file_exists($savePath)) {
                        unlink($savePath);
                    }

                    return json_decode(json_encode($result), true);;

                }
            }
        }

        return ['status' => 1, 'reason' => 'Invalid image or unsupported format.'];
    }

    public function callGPT4WithImage($imageUrl)
    {
        $prompt = "Check and rate the image in the following categories: nude (Nudity or sexually explicit content). For each category, provide a reason if the value is higher than 0. Return the results as a JSON object with both ratings and reasons.";

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text'=>$prompt],
                        ['type' => 'image_url', 'image_url'=>['url'=>$imageUrl]],
                    ]
                ]
            ],
            'functions' => [
                [
                    'name' => 'rate_image',
                    'description' => 'Rate the image in different categories and provide reasons',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nude' => [
                                'type' => 'integer',
                                'minimum' => 0,
                                'maximum' => 4,
                                'description' => 'Nudity or sexually explicit content'
                            ],
                            'nude_reason' => [
                                'type' => 'string',
                                'description' => 'Specify the content of nudity or sexually explicit content rating'
                            ],
                        ],
                        'required' => [
                            'nude',
                            'nude_reason',
                        ]
                    ]
                ]
            ],
            'function_call' => ['name' => 'rate_image'],
            'max_tokens' => 300,
            'temperature' => 0,
            'top_p' => 1
        ];

        $response = $this->sendRequest($endpoint, $data);
        $result = new \stdClass();
        $result->status = 0;
        $result->reason = "";

        if (isset($response['choices'][0]['message']['function_call']['arguments'])) {
            $arguments = json_decode($response['choices'][0]['message']['function_call']['arguments']);
            $highestValue = 0;
            $nsfwReason = '';

            $result->nude = $arguments->nude;
            if ($arguments->nude > 0) {
                $result->nude_reason = $arguments->nude_reason;
                if ($arguments->nude > $highestValue) {
                    $highestValue = $arguments->nude;
                    $nsfwReason = $arguments->nude_reason;
                }
            }

            if ($highestValue > 3) {
                $result->nsfw = true;
                $result->nsfw_reason = $nsfwReason;
            } else {
                $result->nsfw = false;
                $result->nsfw_reason = "";
            }

            return $result;
        } else {
            $result->status = 1;
            $result->reason = "Invalid API response.";
            $result->nsfw = true;
            $result->nsfw_reason = "";
            return $result;
        }
    }
}
