<?php

namespace ForumCopilot;

use ForumCopilot\Common\FCConfig;
use ForumCopilot\Controller\TextApi as TextApi;

class Listener
{
    public static function postSave(\XF\Entity\Post $post)
    {
        if(FCConfig::postEnable() ){
            \XF::app()->jobManager()->enqueue('ForumCopilot:ModeratePost', [
                'post_id' => $post->post_id
            ]);
        }
    }
}