<?php
namespace ForumCopilot\XF\Attachment;

require_once \XF::getRootDirectory().'/src/addons/ForumCopilot/Lib/autoload.php';

use ForumCopilot\Common\FCConfig;
use ForumCopilot\Controller\ImageApi;
use ForumCopilot\Common\ForumCopilotLog;


class Manipulator extends XFCP_Manipulator
{


    public function insertAttachmentFromUpload(\XF\Http\Upload $upload, &$error = null)
    {
        try {

            if( FCConfig::imageEnable() ){
                $ImageApi = new ImageApi(\ForumCopilot\Common\FCConfig::apikey());
                $res = $ImageApi->checkImage($upload);
                if($res['nsfw_reason'] ){

//                    $reasons = [
//                        $res['nude_reason'] ?? '',
//                        $res['nsfw_reason'] ?? '',
//                        $res['off_reason'] ?? '',
//                        $res['prof_reason']
//                    ];

//                    foreach ($reasons as $reason) {
//                        if ($reason) {
//                            $error = $reason;
//                            break;
//                        }
//                    }

                    ForumCopilotLog::logMessage('ForumCopilot banned image upload:' . $res['nsfw_reason'],\XF::getRootDirectory().'/forumCopilotLog/checkImage.log');
                    return;
                }

            }
        } catch (\Exception $e){

        }



        return parent::insertAttachmentFromUpload($upload, $error);

    }
}
