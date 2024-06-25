<?php
namespace ForumCopilot\XF\Pub\Controller;

require_once \XF::getRootDirectory().'/src/addons/ForumCopilot/Lib/autoload.php';

use ForumCopilot\Controller\TextApi as TextApi;
use \ForumCopilot\Common\FCConfig;

class Thread extends XFCP_Thread
{

	protected function finalizeThreadReply(\XF\Service\Thread\Replier $replier)
	{

        try {

            if( FCConfig::postEnable() ){


                $post = $replier->getPost();
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

		parent::finalizeThreadReply( $replier );

	}

    public function plugin($name)
    {
        if (substr_count($name, ':') == 2)
        {
            $class = \XF::stringToClass($name, '%s\%s\ControllerPlugin\%s', $this->app->container('app.classType'));
        }
        else
        {
            $class = \XF::stringToClass($name, '%s\ControllerPlugin\%s');
        }

        $class = $this->app->extendClass($class);

        return new $class($this);
    }

}