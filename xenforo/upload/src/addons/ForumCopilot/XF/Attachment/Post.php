<?php
namespace ForumCopilot\XF\Attachment;

require_once \XF::getRootDirectory().'/src/addons/ForumCopilot/Lib/autoload.php';

use XF\Entity\Attachment as Attachment;
use ForumCopilot\Controller\ImageApi;
use XF\Pub\Controller\Attachment as AttachmentController;

class Post extends XFCP_Post
{


    public function prepareAttachmentJson(Attachment $attachment, array $context, array $json)
    {
        return parent::prepareAttachmentJson($attachment, $context, $json);
//        $ImageApi = new ImageApi(\ForumCopilot\Common\FCConfig::apikey());
//        $ImageApi->checkImage();

    }
}
