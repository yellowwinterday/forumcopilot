<?php

namespace ForumCopilot;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Entity\User;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function install(array $stepParams = [])
    {
        try {
            $this->createUser('ForumCopilot', 'ForumCopilot@example.com', 'password123');

        }catch (\Exception $exception ){

        }

       }

    protected function createUser($username, $email, $password)
    {
        $em = $this->app()->em();

        /** @var User $user */
        $user = $em->create('XF:User');
        $user->username = $username;
        $user->email = $email;
        $user->user_group_id = 2; // 用户组 ID，例如 2 为注册用户
        $user->timezone = 'Europe/London';
        $user->visible = true;
        $user->activity_visible = true;
        $user->user_state = 'valid';
        $user->save(true, false); // 保存基本用户数据

        // 设置用户密码
        $auth = $em->create('XF:UserAuth');
        $auth->user_id = $user->user_id;
        $auth->setPassword($password);
        $auth->save(true, false);

        // 创建用户选项数据
        $userOption = $em->create('XF:UserOption');
        $userOption->user_id = $user->user_id;
        $userOption->content_show_signature = true;
        $userOption->email_on_conversation = true;
        $userOption->creation_watch_state = 'watch_no_email';
        $userOption->interaction_watch_state = 'watch_no_email';
        $userOption->receive_admin_email = true;
        $userOption->show_dob_date = false;
        $userOption->show_dob_year = false;
        $userOption->save(true, false);

        // 创建用户配置文件数据
        $userProfile = $em->create('XF:UserProfile');
        $userProfile->user_id = $user->user_id;
        $userProfile->dob_day = 0;
        $userProfile->dob_month = 0;
        $userProfile->dob_year = 0;
        $userProfile->signature = '';
        $userProfile->about = '';
        $userProfile->location = '';
        $userProfile->website = '';
        $userProfile->save(true, false);

        // 返回用户ID
        return $user->user_id;
    }

}
