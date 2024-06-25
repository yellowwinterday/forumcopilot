<?php
namespace ForumCopilot\XF\Pub\Controller;
require_once \XF::getRootDirectory().'/src/addons/ForumCopilot/Lib/autoload.php';

use ForumCopilot\Common\FCConfig;
use ForumCopilot\Controller\TextApi as TextApi;

class Forum extends XFCP_Forum
{

	protected function finalizeThreadCreate(\XF\Service\Thread\Creator $creator)
	{



        try {

            if( FCConfig::postEnable() ){

                $post = $creator->getPost();
                $textApi = new TextApi(FCConfig::apikey());
                $result = $textApi->callGPT4WithContent($post->message);

                if($result['status'] === 0 && $result['reason']){
                    $bot = FCConfig::getBotUser();
                    //build report
                    $creator = new \XF\Service\Report\Creator($this->app, "post", $post);
                    $creator->createReport("post", $post);
                    $report = $creator->save();

                    //build report comment
                    $reportComment = $this->app->em()->create('XF:ReportComment');
                    $reportComment->report_id = $report->report_id;
                    $reportComment->comment_date = time();
                    $reportComment->user_id = $bot['id'];
                    $reportComment->username = $bot['username'];
                    $reportComment->message =  FCConfig::reasonEnable() ? $result['reason'] : 'report';
                    $reportComment->state_change = '';
                    $reportComment->is_report = 1;
                    $reportComment->save();
                }
            }

        } catch (\Exception $e) {
//            \XF::logException($e, false, 'Error creating report: ');
        }

		parent::finalizeThreadCreate( $creator );

	}

}

