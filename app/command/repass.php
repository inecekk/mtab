<?php
declare (strict_types=1);
namespace app\command;
use app\model\UserModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class repass extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('repass')
            ->setDescription('修改管理员密码 -u 用户名 -p 密码')
            ->addOption('user', '-u', Option::VALUE_REQUIRED, '管理员账户')
            ->addOption('pass', '-p', Option::VALUE_REQUIRED, '新密码');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->repass($input);
    }


    private function repass($input)
    {
        $user = $input->getOption('user');
        $pass = $input->getOption('pass');
        if ($user && $pass) {
            $info = UserModel::where('mail', $user)->find();
            if ($info) {
                $info->password = hashUserPassword($pass);
                $info->save();
                print_r("\033[1;31m账户密码重置完毕\033[0m\n\r\033[1;42m请使用新的密码登录\033[0m\n");
            } else {
                print_r("\033[1;31m账户不存在\033[0m\n");
            }
            exit();
        }
        print_r("\033[1;31m缺少用户名或密码\033[0m\n");
        exit();
    }
}
