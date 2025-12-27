<?php
namespace app\guild_api\controller;

use app\guild_api\model\GuildModel;
use think\Controller;
use think\Db;
use think\config;
use think\Model;

class LoginApi extends Base{

    public function do_login(){
        $result = array('code' => 1, 'msg' => '');
        $uid = trim(input('username'));
        $psd = trim(input('password'));
        if(IS_GUILD != 1){
            $result['code'] = 0;
            $result['msg'] = lang('Login_mode_is_turned_off');
        }
        $guild = db('guild')->where('login = "'.$uid.'"')->find();
        $is_admin = 1;
        if(!$guild){
            $guild = db('guild_admin')->where('status = 1 and login = "'.$uid.'"')->find();
            if($guild){
                $is_admin = 2;
                //$guild_info = db('guild')->where('id = "'.$guild['guild_id'].'"')->find();
                //$guild['name'] = $guild_info['name'];
                //$guild['avatar'] = $guild_info['logo'];
                $menu = db('guild_admin_menu_user')
                    ->alias('m')
                    ->join('guild_admin_menu g','g.id=m.menu_id')
                    ->field('g.title,g.id,g.name,g.path')
                    ->where('m.is_half = 0 and m.guild_id = '.$guild['id'])
                    ->order('g.id')
                    ->find();
                $result['data']['path'] = $menu['path'];
            }
        }else{
            $guild['guild_id'] = $guild['id'];
            $result['data']['path'] = '/';
        }


        if($guild){
            $password = cmf_password($psd);
            if($password==$guild['psd']){
                $microTime = $this->getMicroTime();
                $result['data']['guild_id'] = $guild['guild_id'];
                $result['data']['id'] = $guild['id'];
                $result['data']['name'] = $guild['name'];
                $result['data']['avatar'] = $guild['logo'];
                $token = md5($guild['id'].$microTime.mt_rand(1000,9999));
                $result['data']['token'] = $token;
                $data = [
                    'token'=>$token,
                    'login_time'=>NOW_TIME
                ];
                if($is_admin==1){
                    db('guild')->where('id = '.$guild['id'])->update($data);
                }else{
                    db('guild_admin')->where('id = '.$guild['id'])->update($data);
                }
            }else{
                $result['code'] = 0;
                $result['msg'] = lang('PASSWORD_NOT_RIGHT');
            }
        } else{
            $result['code'] = 0;
            $result['msg'] = lang('Login_account_does_not_exist');
        }

        return_json_encode($result);
    }

    public function get_info(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('id'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        //$user_info['id'] = $user_info['guild_id'];
        $roles = [];
        if($user_info['is_admin']==1){
            $list = db('guild_admin_menu')->field('id,title,name')->where("id !=12 and id !=14")->select();
        }else{
            $list = db('guild_admin_menu_user')
                ->alias('m')
                ->join('guild_admin_menu g','g.id=m.menu_id')
                ->field('g.title,g.id,g.name')
                ->where('m.is_half = 0 and m.guild_id = '.$user_info['id']." and id !=12 and id !=14")
                ->select();
        }
        if($list){
            foreach ($list as $v){
                $roles[] = ['name'=>$v['name']];
            }
        }
        $result['data']['roles'] = $roles;
        $result['data']['name'] = $user_info['name'];
        $result['data']['avatar'] = $user_info['logo'];
        $result['data']['introduction'] = '12345678';
        return_json_encode($result);
    }

    public function get_user_info(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        if($user_info){
            $result['data']['name'] = $user_info['name'];
            $result['data']['avatar'] = $user_info['logo'];
            $result['data']['id'] = $user_info['id'];
            $result['data']['user_id'] = $user_info['user_id'];
            $result['data']['login'] = $user_info['login'];
            $result['data']['introduce'] = $user_info['introduce'];
            $result['data']['notice'] = $user_info['notice'];
            // 获取会员用户
            $user = db('user')->field("user_nickname")->where('id = '.intval($user_info['user_id']))->find();
            $result['data']['user_nickname'] = '';
            if($user){
                $result['data']['user_nickname'] = $user['user_nickname'];
            }
            // 获取绑定的账号
            $user_cash_account = db('user_cash_account')->field("pay,name")->where('uid = '.intval($user_info['user_id']))->find();
            $result['data']['cash_account'] = '';
            $result['data']['account_name'] = '';
            if ($user_cash_account) {
                $result['data']['cash_account'] = $user_cash_account['pay'];
                $result['data']['account_name'] = $user_cash_account['name'];
            }
            $result['data']['format_time'] = date('Y-m-d H:i:s',$user_info['create_time']);
            $result['data']['introduction'] = '12345678';
        }else{
            $result['code'] = 0;
            $result['msg'] = lang('Guild_does_not_exist');
        }

        return_json_encode($result);
    }

    public function logout(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('id'));
        $token = trim(input('token'));
        $user_info = $this->check_token($uid,$token);
        //db('guild')->where('id = '.$uid.' and token = "'.$token.'"')->update(['token'=>'']);
        return_json_encode($result);
    }

    //修改头像/密码
    public function update_info(){
        $result = array('code' => 1, 'msg' => lang('Modified_successfully'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $avatar = trim(input('avatar'));
        $old_psd = trim(input('old_psd'));
        $psd = trim(input('psd'));
        $user_info = $this->check_token($uid,$token);
        if($avatar){
            if($user_info['is_admin']==1){
                db('guild')->where('id = '.$uid.' and token = "'.$token.'"')->update(['logo'=>$avatar]);
            }else{
                db('guild_admin')->where('id = '.$uid.' and token = "'.$token.'"')->update(['logo'=>$avatar]);
            }
        }else{
            //dump(cmf_password($old_psd));
            //dump($user_info);
            if($old_psd && $psd){
                if(cmf_compare_password($old_psd,$user_info['psd'])){
                    $psd = cmf_password($psd);
                    if($user_info['is_admin']==1){
                        db('guild')->where('id = '.$uid.' and token = "'.$token.'"')->update(['psd'=>$psd]);
                    }else{
                        db('guild_admin')->where('id = '.$uid.' and token = "'.$token.'"')->update(['psd'=>$psd]);
                    }
                }else{
                    $result = array('code' => 0, 'msg' => lang('Original_password_verification_error'));
                }
            }
        }

        return_json_encode($result);
    }
    // 修改公会信息
    public function save_guild(){
        $result = array('code' => 1, 'msg' => lang('Modified_successfully'));
        $uid = intval(input('uid'));
        $token = trim(input('token'));
        $introduce = trim(input('introduce'));
        $name = trim(input('name'));
        $notice = trim(input('notice'));
        $this->check_token($uid,$token);

        $data = array(
            'introduce' => $introduce,
            'name' => $name,
            'notice' => $notice
        );
        if (empty($data['name'])) {
            $result['code'] = 0;
            $result['msg'] = lang('Please_enter_guild_name');
            return_json_encode($result);
        }
        db('guild')->where('id = '.$uid.' and token = "'.$token.'"')->update($data);
        return_json_encode($result);
    }
}