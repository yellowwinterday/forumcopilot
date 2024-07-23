<?php

namespace ForumCopilot\Controller;

require_once \XF::getRootDirectory().'/src/addons/ForumCopilot/Lib/autoload.php';

class TextApi extends \ForumCopilot\Base\BaseApi
{
    public function callGPT4WithContent($content, $isNewUser)
    {
        $moderationEndpoint = 'https://api.openai.com/v1/moderations';
        $chatEndpoint = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'spam' => 0,
            'hate' => 0,
            'harrass' => 0,
            'harm' => 0,
            'sex' => 0,
            'violence' => 0,
            'nsfw' => 0,
            'reason' => "",
            'status' => 0
        ];
        
/*
        // Step 1: Call the Moderation API
        $moderationData = [
//            'model' => 'text-moderation-latest',
            'input' => $content,
        ];

        $moderationResponse = $this->sendRequest($moderationEndpoint, $moderationData);
        if (isset($moderationResponse['results'][0])) {
            $moderationResult = $moderationResponse['results'][0];
        } else {
            return [
                'status' => 1,
                'reason' => "Moderation API response error."
            ];
        }

     

        $violated_types = [];
        $highestValue = 0;

        foreach ($moderationResult['categories'] as $category => $violated) {
            if ($violated) {
                $value = $moderationResult['category_scores'][$category] * 100;
                switch ($category) {
                    case 'hate':
                        $data['hate'] = $value;
                        break;
                    case 'sexual':
                        $data['sex'] = $value;
                        break;
                    case 'violence':
                        $data['violence'] = $value;
                        break;
                    case 'self-harm':
                        $data['harm'] = $value;
                        break;
                    case 'harassment':
                        $data['harrass'] = $value;
                        break;
                }

                $violated_types[] = $category;
                if ($value > $highestValue) {
                    $highestValue = $value;
                }
            }
        }
        $data['nsfw'] = $highestValue;

        $moderation_bypassed = !$moderationResult['flagged'];
        if (!$moderation_bypassed) {
            $data['reason'] = "The content contains references to " . implode(',', $violated_types) . ".";
        } else {

        */
            // Step 2: Call the Chat API for spam detection
            $prompt = "You are a moderator on a forum, checking spam ads. Spam ads are unsolicited, generic, clickbait-filled, often deceptive, and frequently link to suspicious websites with too-good-to-be-true offers. You only respond with a number from 0 (least) to 4 (most) to indicate the level. Please ignore contents without contact info.";

            //Updated prompt to include pure contact information as spam, for newly registered members only.
            if ($isNewUser){
                $prompt = "You are a moderator on a forum, checking spam ads. Spam ads are unsolicited, generic, clickbait-filled, often deceptive, and frequently link to suspicious websites with too-good-to-be-true offers. Messages that only contain contact information should also be flagged as spam. You only respond with a number from 0 (least) to 4 (most) to indicate the level. Please ignore contents without contact info.";
            }

            $chatData = [

                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $prompt
                    ],
                    [
                        'role' => 'user',
                        'content' => " Content:\n" . $content
                    ]
                ],
                'temperature' => 0,
                'max_tokens' => 100,
                'top_p' => 1,
                'frequency_penalty' => 1.0,
            ];

            $chatResponse = $this->sendRequest($chatEndpoint, $chatData);
            if (isset($chatResponse['choices'][0]['message']['content'])) {
                $chatResult = $chatResponse['choices'][0]['message']['content'];

                if ((double)$chatResult > 0) {
                    $rate = (double)$chatResult * 25; // from 0 to 100
                    $data['spam'] = $rate;
                    $data['nsfw'] = 0;
                    if ($data['spam'] > 50) { // Assuming 50 is the threshold for spam
                        $data['reason'] = "The content contains references to spam.";
                    }
                }
            } else {
                return [
                    'status' => 2,
                    'reason' => "Chat API response error."
                ];
            }

        //}

        return $data;
    }
}
