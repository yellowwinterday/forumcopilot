<?php

namespace ForumCopilot\Job;

use ForumCopilot\Common\FCConfig;
use ForumCopilot\Controller\TextApi as TextApi;

class ModeratePost extends \XF\Job\AbstractJob
{
    protected $defaultData = [
        'post_id' => null
    ];

    public function run($maxRunTime)
    {
        $post = \XF::em()->find('XF:Post', $this->data['post_id']);
        if (!$post)
        {
            return $this->complete();
        }

        if ($post->message_state == 'visible')
        {
            $moderationReason = self::shouldModeratePost($post);
            if ($moderationReason) {

                self::sendToModerationQueue($post, $moderationReason);
            }
        }

        return $this->complete();
    }
    private static function shouldModeratePost(\XF\Entity\Post $post)
    {
        $textApi = new TextApi(FCConfig::apikey());
        $result = $textApi->callGPT4WithContent($post->message);
        if($result['status'] === 0 && $result['reason']){
            return $result['reason'];
        }
        return false;
    }

    private static function sendToModerationQueue(\XF\Entity\Post $post, $reason)
    {
        if ($post->message_state == 'visible') {
            $post->setOption('log_moderator', false);
            $post->message_state = 'moderated';
            $post->save(false, false);

            \XF::app()->logger()->logModeratorAction('post', $post, 'edit', ['reason' => $reason]);

            $thread = $post->Thread;
            $thread->draft_reply = true;
            $thread->save(false, false);
        }
    }
    public function getStatusMessage()
    {
        return 'Moderating post';
    }

    public function canCancel()
    {
        return false;
    }

    public function canTriggerByChoice()
    {
        return false;
    }
}