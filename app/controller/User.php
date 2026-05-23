<?php


namespace app\controller;

use app\BaseController;
use app\model\ConfigModel;
use app\model\LinkFolderModel;
use app\model\SettingModel;
use app\model\TokenModel;
use app\model\UserGroupModel;
use app\model\UserModel;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\facade\View;

class User extends BaseController
{
    protected $qq_bind_mode = false;

    public function login(): \think\response\Json
    {
        $user = $this->request->post('username', '0');
        $pass = $this->request->post('password', '0');
        $user = trim($user);
        $pass = trim($pass);
        $info = UserModel::where('mail', $user)->find();

        if (Cache::get('login.' . $user)) {
            return $this->error('账号已被安全锁定,您可以修改密码然后登录');
        }
        if (!$info) {
            return $this->error('账号不存在');
        }
        if ($info['login_fail_count'] == 10) {
            Cache::set('login.' . $user, 'lock', 7200);
            $info->login_fail_count = 0;
            $info->save();
            return $this->error('账号已被锁定2小时');
        }
        if (!verifyUserPassword($pass, (string)$info['password'])) {
            $info->login_fail_count += 1;
            $info->save();
            return $this->error('账号不存在或密码错误');
        }
        if (shouldRehashUserPassword((string)$info['password'])) {
            $info->password = hashUserPassword($pass);
        }
        if ($info['status'] === 1) {
            return $this->error('账号已被冻结');
        }
        $auth = $this->refreshToken($info);
        $info->login_ip = getRealIp();
        $info->login_time = date('Y-m-d H:i:s');
        $info->login_fail_count = 0; //登陆成功将失败次数归零
        $info->save();
        return $this->success('登录成功', $auth);
    }


    private function refreshToken($info): array
    {
        $token = renderToken($info['id']);
        $agent = $this->request->header('User-Agent');
        $agent = mb_substr($agent, 0, 250);
        $auth = ['user_id' => $info['id'], 'token' => $token, 'create_time' => time(), 'ip' => getRealIp(), 'user_agent' => $agent];
        if (isset($info['access_token'])) {
            $auth['access_token'] = $info['access_token'];
        }
        TokenModel::insert($auth);
        unset($auth['user_agent']);
        unset($auth['access_token']);
        unset($auth['ip']);
        return $auth;
    }

    function register(): \think\response\Json
    {
        if ($this->systemSetting("user_register", '0', true) === '1') {
            return $this->error('管理员已关闭用户注册功能');
        }
        $user = $this->request->post('username', false);
        $pass = $this->request->post('password', false);
        $code = $this->request->post('code', '0000');
        if ($user && $pass) {
            $user = trim($user);
            $pass = trim($pass);
            if (!validateEmail($user)) {
                return $this->error('邮箱格式错误');
            }
            if (strlen($pass) < 6) {
                return $this->error('密码过短');
            }
            $cacheCode = Cache::get('code' . $user);
            if (!$cacheCode || $cacheCode != $code) {
                return $this->error('验证码错误');
            }
            if (UserModel::where('mail', $user)->field('id,mail')->find()) {
                return $this->error('账号已存在');
            }
            $add = UserModel::insert(['mail' => $user, 'password' => hashUserPassword($pass), 'create_time' => date('Y-m-d H:i:s'), 'register_ip' => getRealIp()]);
            if ($add) {
                Cache::delete('code' . $user);
                return $this->success('ok');
            }
        }
        return $this->error('注册失败');
    }

    public function forgetPass(): \think\response\Json
    {
        $user = $this->request->post('username', false);
        $pass = $this->request->post('password', false);
        $code = $this->request->post('code', '0000');
        if ($user && $pass) {
            $user = trim($user);
            $pass = trim($pass);
            if (!validateEmail($user)) {
                return $this->error('邮箱格式错误');
            }
            if (strlen($pass) < 6) {
                return $this->error('密码过短');
            }
            $info = UserModel::where('mail', $user)->field('id,mail')->find();
            if (!$info) {
                return $this->error('账号不存在');
            }
            $cacheCode = Cache::get('code' . $user);
            if ($cacheCode && $cacheCode == $code) {
                $info->password = hashUserPassword($pass);
                $add = $info->save();
                if ($add) {
                    TokenModel::where('user_id', $info['id'])->delete(); //删除所有登录记录
                    Cache::delete('login.' . $user);
                    return $this->success('ok');
                }
            } else {
                return $this->error('验证码错误');
            }
        }
        return $this->error('修改失败');
    }

    function newMail(): \think\response\Json
    {
        $userinfo = $this->getUser(true);
        $user = $this->request->post('mail', false);
        $code = $this->request->post('code', false);
        if ($user && $code) {
            $user = trim($user);
            if (!validateEmail($user)) {
                return $this->error('邮箱格式错误');
            }
            $cacheCode = Cache::get('code' . $user);
            if ($cacheCode && $cacheCode == $code) {
                $info = UserModel::where('mail', $user)->field('id,mail')->find();
                if ($info) {
                    return $this->error('该邮箱已被使用！');
                }
                $info = UserModel::where('id', $userinfo['user_id'])->field('id,mail')->find();
                $info->mail = $user;
                $info->save();
                Cache::delete('code' . $user);
                return $this->success('修改成功');
            } else {
                return $this->error('验证码错误');
            }
        }
        return $this->error('请认真填写表单');
    }

    function loginOut(): \think\response\Json
    {
        $user = $this->getUser();
        if ($user) {
            TokenModel::where('user_id', $user['user_id'])->where('token', $user['token'])->delete();
        }
        return $this->success('ok');
    }

    public function get(): \think\response\Json
    {
        $info = $this->getUser(true);
        if ($info) {
            $info = UserModel::field('id,mail,manager,nickname,avatar,qq_open_id,active')->find($info['user_id']);
            if ($info['qq_open_id']) {
                $info['qqBind'] = true;
                unset($info['qq_open_id']);
            }
            if ($info['active'] !== date("Y-m-d")) {
                $info['active'] = date("Y-m-d");
                $info->save();
            }
            return $this->success('ok', $info);
        }
        return $this->error('获取失败');
    }

    public function unbindQQ(): \think\response\Json
    {
        $info = $this->getUser(true);
        if ($info) {
            $info = UserModel::field('id,mail,manager,nickname,avatar,qq_open_id')->find($info['user_id']);
            if (empty($info->mail)) {
                return $this->error("请先绑定邮箱后再解绑");
            }
            $info->qq_open_id = "";
            $info->save();
        }
        return $this->success('解绑成功', $info);
    }

    public function updateInfo(): \think\response\Json
    {
        $info = $this->getUser(true);
        $field = $this->request->post('field', false);
        $value = $this->request->post('value', false);
        //允许修改的字段
        $allow = ['nickname', 'avatar'];
        if ($info && $field && $value && in_array($field, $allow)) {
            UserModel::where('id', $info['user_id'])->update([$field => $value]);
        }
        return $this->success('修改成功');
    }

    function qLogin(): \think\response\Redirect
    {
        $appId = SettingModel::Config('qq_login_appid', false);
        $callback = 'https://' . $this->request->host() . '/qq_login';
        $type = $this->request->get('type', '');
        $query = [
            'redirect_uri' => $callback,
            'state' => renderToken(),
            'response_type' => 'code',
            'scope' => 'get_user_info,list_album,upload_pic',
            'client_id' => $appId
        ];
        if ($type === 'bind') {
            $query['state'] = $query['state'] . 'bind';
        }
        $http = http_build_query($query);
        return redirect('https://graph.qq.com/oauth2.0/authorize?' . $http);
    }

    function qq_login(): string
    {
        $appId = SettingModel::Config('qq_login_appid', false);
        $code = $this->request->get('code', false);
        $state = $this->request->get('state');
        if (strpos($state, 'bind') !== false) {
            //绑定模式
            $this->qq_bind_mode = true;
        }
        $callback = 'https://' . $this->request->host() . '/qq_login';
        $result = \Axios::http()->get('https://graph.qq.com/oauth2.0/token', [
            'query' => [
                'grant_type' => 'authorization_code',
                'client_id' => $appId,
                'client_secret' => SettingModel::Config('qq_login_appkey', false),
                'code' => $code,
                'redirect_uri' => $callback,
                'fmt' => 'json'
            ]
        ]);
        if ($result->getStatusCode() === 200) {
            $content = $result->getBody()->getContents();
            $js = \Axios::toJson($content);
            if (isset($js['access_token'])) {
                $access_token = $js['access_token'];
                return $this->getOpenId($access_token);
            }
        }
        return View::fetch('/qq_login_error');
    }

    //此方法禁止网络访问
    private function getOpenId($access_token): string
    {
        $result = \Axios::http()->get('https://graph.qq.com/oauth2.0/me', [
            'query' => [
                'access_token' => $access_token,
                'fmt' => 'json'
            ]
        ]);
        if ($result->getStatusCode() === 200) {
            $content = $result->getBody()->getContents();
            $js = \Axios::toJson($content);
            if (isset($js['openid'])) {
                $openid = $js['openid'];
                if ($this->qq_bind_mode) {
                    //绑定模式
                    if (UserModel::where('qq_open_id', $openid)->field('id,qq_open_id')->find()) {
                        return View::fetch('/qq_login_error');
                    }
                    //如果openid数据库不存在说明QQ没有被绑定过，可以绑定
                    $this->BindQQ($openid); //绑定后需要替换Token，不然之前的QQ登录会失效
                }
                $info = UserModel::where('qq_open_id', $openid)->find();
                if (!$info) { //不存在就创建一个新用户,如果上一个步骤绑定成功的话，是不可能进入此步骤的
                    UserModel::insert(['mail' => '', 'password' => hashUserPassword(renderToken()), 'create_time' => date('Y-m-d H:i:s'), 'register_ip' => getRealIp(), 'qq_open_id' => $openid]);
                    $info = UserModel::where('qq_open_id', $openid)->find();
                    $this->getUserOpenInfo($access_token, $openid); //获取一些用户的默认信息
                }
                if ($info) { //如果用户存在
                    $info->login_ip = getRealIp();
                    $info->login_time = date('Y-m-d H:i:s');
                    $info->login_fail_count = 0; //登陆成功将失败次数归零
                    $info->save();
                    $info['access_token'] = $access_token;
                    $auth = $this->refreshToken($info);
                    if ($info['status'] === 1) {
                        return View::fetch('/qq_login_error');
                    }
                    return View::fetch('/qq_login', ['info' => $auth]);
                }
            }
        }
        return View::fetch('/qq_login_error');
    }

    private function BindQQ($qq_open_id)
    {
        $user = $this->getUser();
        if ($user) {
            $info = UserModel::where('id', $user['user_id'])->field('id,mail,qq_open_id,password,login_fail_count,login_ip,login_time')->find();
            if ($info) {
                $info->qq_open_id = $qq_open_id;
                $info->save();
            }
        }
    }

    private function getUserOpenInfo($access_token, $openid)
    {
        $result = \Axios::http()->get('https://graph.qq.com/user/get_user_info', [
            'query' => [
                'openid' => $openid,
                'oauth_consumer_key' => SettingModel::Config('qq_login_appid', false),
                'access_token' => $access_token
            ]
        ]);
        if ($result->getStatusCode() === 200) {
            $content = $result->getBody()->getContents();
            $js = \Axios::toJson($content);
            if ($js['ret'] === 0) {
                UserModel::where('qq_open_id', $openid)->update(['nickname' => $js['nickname'], 'avatar' => $js['figureurl_qq_1']]);
            }
        }
    }

    function UserGroup(): \think\response\Json
    {
        is_demo_mode(true);
        $this->getAdmin();
        return $this->success("ok", UserGroupModel::order("sort", 'desc')->select()->toArray());
    }

    function createGroup(): \think\response\Json
    {
        is_demo_mode(true);
        $type = $this->request->post('type', false);
        $this->getAdmin();
        if ($type === 'edit') {
            $form = $this->request->post('info');
            $id = $this->request->post('info.id', false);
            if ($id && $id > 0) {
                $model = UserGroupModel::find($id);
                $model->update($form);
            } else {
                $model = new UserGroupModel();
                $model->insert($form);
            }
        } else if ($type === 'del') {
            $id = $this->request->post('id');
            $result = UserGroupModel::where('id', $id)->find();
            if ($result) {
                $result->delete();
                UserModel::where('group_id', $id)->update(['group_id' => 0]);
            }
        }
        return $this->success('处理完毕！');
    }

    function sortGroup(): \think\response\Json
    {
        $sort = (array)$this->request->post();
        foreach ($sort as $key => $value) {
            UserGroupModel::where('id', $value['id'])->update(['sort' => $value['sort']]);
        }
        return $this->success('ok');
    }
}
