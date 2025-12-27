<?php
/**
 * 布谷科技商业系统
 * 文章
 * @author 山东布谷鸟网络科技有限公司
 * @create 2020-08-05 00:02
 */


namespace app\agency\model;

use app\agency\model\Role;
use think\Db;
use think\Model;

class Agency extends Model
{
    /**
    * 获取代理下代理账号列表
     */
    public function subordinate_agency_list($limit,$page,$where){
        $config['page'] = $page ? $page : 1;
        $field ="t.name as tname,f.name as fname,g.id,g.login,g.name,g.tel,g.coin,g.coin_total,g.consumption_coin,g.create_time,g.login_time,g.status,g.platform_level,g.first_superior_id,g.two_superior_id";

        $list = $this->alias('g') ->field($field)
            ->join("agency f","f.id = g.first_superior_id","left")
            ->join("agency t","t.id = g.two_superior_id","left")
            ->where($where)
            ->order('g.create_time', 'desc')
            ->paginate($limit, false, $config);
        if(is_object($list)){
            $list = $list->toArray();
        }
        return $list;
    }
    /**
     * 当前管理员信息
     * @param $where
     * @param string $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function select_Agency_one($where,$field="*"){
        $List = $this->where($where)->field($field)->find();
        if(is_object($List)){
            $List = $List->toArray();
        }
        return $List;

    }
    /**
     * 获取所有充值账户数量
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function select_Agency_count($where){
        return $this->where($where)->count();
    }
    /**
     * 减少收益
     * @param $where
     * @return bool
     */
    public function dec_agency($where,$field,$val){
        return $this->where($where)-> Dec($field,$val) -> update();
    }
    /**
     * 增加收益
     * @param $where
     * @return bool
     */
    public function inc_agency($where,$field,$val){
        return $this->where($where)-> Inc($field,$val) -> update();
    }
    /**
     * 删除管理员
     * @param $where
     * @return bool
     */
    public function delete_admin($where){
        return $this->where($where)->delete();
    }
    /**
     * 添加管理员
     * @param $insert
     * @return int|string
     */
    public function add_admin($insert){
        return $this->insertGetId($insert);
    }
    /**
     * 修改管理员信息
     * @param $where
     * @param $update
     * @return bool
     */
    public function save_admin($where,$update){
        return $this ->where($where)->update($update);
    }

    /**
     * 后台权限用户列表
     */
    public function get_admin_list($where,$field){
        return $this->where($where)->field($field)->select();
    }
    /**
     * 检查登录IP 222.132.157.159
     * @return array
     */
    public function check_account_ip()
    {
        $m_config = load_cache('config');
        $ip = get_client_ip();
        if($m_config['sign_in_is_ip'] != 1){
            return 1;
        }
        //备用域名 列表
        $account_ip = array();
        $account_ip_arr = explode("<br />", nl2br($m_config['account_ip']));
        foreach ($account_ip_arr as $k => $v) {
            $v = ltrim(rtrim(trim($v)));
            if ($v != '') {
                $account_ip[] = $v;
            }
        }
        $status = 0;
        if (in_array($ip, $account_ip) && count($account_ip) > 0) {
            $status = 1;
        }
        return $status;
    }
    /**
     * 用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param bool $rememberme 记住登录
     * @return bool|mixed
     */
    public function Login($username,$password,$rememberme = false){

        $root = array('code' => 0, 'message' => apiLang("Password_error"));

        if(!$username){
            // 登录失败,账户不存在
            $root['message'] = apiLang("Login_failed_not_exist");
            return $root;exit;
        }
        $login = $this->where('login = "'.$username.'"')->find();

        if (!$login) {
            $root['message'] = apiLang("Login_failed_not_exist");
        } else {
            if($login['status'] != 1){
                //登录账户错误,账户不存在; 账户已删除,请联系管理员
                $root['data'] = $login;
                $root['message'] = apiLang("Account_is_invalid");
            }
            if ($login['psd'] != cmf_password($password)){
                // 密码错误
                $root['message'] = apiLang("Password_error");
            } else {
                //登录成功 -- 生成用户token
                $token = get_token($login['login'] . $login['id']);
                // 更新登录信息
                $update =array(
                    'login_ip' => get_client_ip(),
                    'login_time' => NOW_TIME,
                    'token' => $token
                );
                //重新保存记录
                $this->where('id = '.$login['id'])->update($update);

                redis_set($token, json_encode($login), get_d());
                redis_set(get_platform_agency_token_prefix() . $login['id'], $token, get_d());
                redis_set(get_platform_agency_token_prefix(), $login['id'], get_d());

                // 记住登录 - 自动登录
                $this->auto_login($login, $rememberme);
                $m_config = load_cache('config');
                $is_agent_authority = 0;
                if ($login['platform_level'] < $m_config['recharge_background_level']) {
                    // 是否有权限开启下级代理
                    $is_agent_authority = 1;
                }
                //日志
                $insert = array(
                    'agency_id' =>$login['id'],
                    'login_name' => $login,
                    'ip' => get_client_ip(),
                    'addtime' =>$update['login_time'],
                    'admin_headers' => json_encode(getallheaders())
                );
                db('agency_login_log')->insert($insert);

                $root['code'] = 200;
                $root['data']['token'] = $token;
                $root['data']['name'] = $login['name'];
                $root['data']['platform_level'] = $login['platform_level'];
                $root['data']['is_agent_authority'] = $is_agent_authority;
                $root['data']['id'] = $login['id'];
            }
        }
        return $root;
    }
    /**
     * 自动登录
     * @param mixed $user 用户对象
     * @param bool $rememberme 是否记住登录，默认7天
     */
    public function auto_login($user, $rememberme = false){
        // 记住登录
        if ($rememberme) {
            $signin_token = $user['nick_name'].$user['id'];
            cookie('id', $user['id'], 24 * 3600 * 7);
            cookie('signin_token', $signin_token, 24 * 3600 * 7);
        }
    }
    /**
     * 退出
     * @param $token
     * @return array
     */
    public function logout($token) {
        $root = array('code' => 200, 'message' => apiLang("Account_exit_succeeded"),'data'=>array());
        $userInfo = redis_get_token($token);

        if($userInfo){
           // save_log($userInfo['name'].apiLang("Account_exit_succeeded"),1);
            redis_rm(get_platform_agency_token_prefix() . $userInfo['id']);
            redis_rm($token);
        }
        return $root;
    }

























    /**
     * 修改用户密码
     * @param $id
     * @param $password
     * @return bool
     */
    public function upd_password($id,$password){
        return $this->where("id=".$id)->update(array('password'=>$password));
    }

    /**
     * 获取用户信息
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_user($where){
        return $this->where($where)->find();
    }


    /**
     *  修改用户信息
     * @param $id
     * @param $data
     * @return bool
     */
    public function upd_user($id,$data){
        return $this->where("id=".$id)->update($data);
    }


    /**
     *   删除后台账号
     * @param $id
     * @return bool
     */
    public function delete_user($id){
        return $this->where("id=".$id)->delete();
    }

    /**
     * 增加管理员
     * @param $data
     * @return int|string
     */
    public static function add($data){
        return db::name("sys_user")->insertGetId($data);
    }

    /**
     * 修改管理员
     * @param $id
     * @param $data
     * @return bool
     */
    public static function edit($id,$data){
        return db::name("sys_user")->where("id=".$id)->update($data);
    }
    /**
     *  获取前端用户信息
     * @param $id
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_reception_user($id){
        return db::name("user")->where("id=".$id)->find();
    }

    /**
     * 获取用户权限
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sys_user_role($where){
        return db::name("sys_user_role")->where($where)->find();
    }

    /**
     * 修改权限
     * @param $id
     * @param $data
     * @return int|string
     */
    public function update_sys_user_role($id,$data){
        return db::name("sys_user_role")->where("id=".$id)->update($data);
    }
    /**
     * 添加权限
     */
    public function add_sys_user_role($data){
        return db::name("sys_user_role")->insertGetId($data);
    }

    /**
     * 清理数据库
     * @param $name
     * @param $where
     * @throws \think\db\exception\DbException
     */
    public function clear_database_one($name,$where){
        Db::table($name)->where($where)->delete();
    }
}