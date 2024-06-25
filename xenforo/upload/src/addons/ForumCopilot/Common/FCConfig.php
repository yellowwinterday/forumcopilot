<?php

namespace ForumCopilot\Common;

use XF\Entity\User;

class FCConfig
{

    public static function apiKey(){
        return \XF::options()['fc_apikey'] ?? '';
    }

    public static function postEnable(){
        return \XF::options()['fc_moderate_post_content'] ?? false;
    }

    public static function imageEnable(){
        return \XF::options()['fc_moderate_nsfw_image'] ?? false;
    }

    public static function reasonEnable(){
        return \XF::options()['fc_explain_flagged_reason'] ?? false;
    }

    public static function recommend(){
        return \XF::options()['fc_recommend_forumcopilot'] ?? false;
    }

    public static function getBotUser(){
        $em = \XF::app()->em();
        $user = $em->getFinder('XF:User')
            ->where('username', 'ForumCopilot')
            ->fetchOne();

        $res = $user ? [
            'id' =>  $user->user_id,
            'username' =>  'ForumCopilot',
        ] :
            [
                'id' =>  0,
                'username' =>  'ForumCopilot',
            ];

        return $res;
    }
}
?>
