<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-20
     * Time: 10:48
     */
    namespace app\admin\model;

    use think\Model;
    use think\Db;

    class AuthModel extends Model
    {
        //主播列表
        public function get_auth_talker($where,$page=10){
            $res = Db::name('auth_talker')
                ->alias('t')
                ->join('user u','u.id = t.uid')
                ->where($where)
                ->order('id desc')
                ->field('t.*,u.user_nickname')
                ->paginate($page, false, ['query' => request()->param()]);
            return $res;
        }

        //单条认证
        public function get_talker_find($where){
            $res = Db::name('auth_talker')
                ->where($where)
                ->select();
            return $res;
        }

        //修改主播
        public function talker_upd($id,$uid,$status,$center){
            $where = ['id'=>$id,'uid'=>$uid];
            $info = Db::name('auth_talker')
                ->where($where)
                ->find();
            if(!$info){
                $this->error(lang('operation_failed_not_log'));
                exit;
            }

            $res = Db::name('auth_talker')
                ->where($where)
                ->update(['status'=>$status,'refuse_info'=>$center]);
            if($res){
                //修改用户表
                if($status==1){
                    //is_talker is_player
                    Db::name('user')->where('id = '.$uid)->update(['is_talker'=>1]);
                }
            }
            return $res;
        }

        //删除主播认证
        public function talker_del($id){
            $where = ['id'=>$id];
            $info = Db::name('auth_talker')
                ->where($where)
                ->find();
            if(!$info){
                $this->error(lang('operation_failed_not_log'));
                exit;
            }
            $res = Db::name('auth_talker')
                ->where($where)
                ->delete();
            if($res){
                $uid = $info['uid'];
                Db::name('auth_talker_img')->where(['aid'=>$id])->delete();
                Db::name('user')->where('id = '.$uid)->update(['is_talker'=>0,'guild_id'=> 0]);
                //退出加入的工会
           //     db('guild_join')->where('user_id=' . $uid)->delete();
            }
            return $res;
        }

        //主播认证图
        public function get_talker_img($where){
            $res = Db::name('auth_talker_img')
                ->where($where)
                ->select();
            return $res;
        }

        //陪玩师列表
        public function get_auth_playerv($where,$page=10){
            $res = Db::name('auth_player')
                ->alias('p')
                ->join('user u','u.id = p.uid')
                ->join('play_game g','g.id = p.game_id')
                ->where($where)
                ->order('id desc')
                ->field('p.*,u.user_nickname,g.name as game_name')
                ->paginate($page, false, ['query' => request()->param()]);
            return $res;
        }

        //陪玩师认证图
        public function get_player_img($where){
            $res = Db::name('auth_player_img')
                ->where($where)
                ->select();
            return $res;
        }

        //获取陪玩单条
        public function get_player_find($where){
            $res = Db::name('auth_player')
                ->where($where)
                ->find();
            return $res;
        }

        //修改陪玩认证
        public function player_upd($id,$uid,$status,$center){
            $where = ['id'=>$id,'uid'=>$uid];
            $info = Db::name('auth_player')
                ->where($where)
                ->find();
            if(!$info){
                $this->error(lang('operation_failed_not_log'));
                exit;
            }

            $res = Db::name('auth_player')
                ->where($where)
                ->update(['status'=>$status,'refuse_info'=>$center]);
            if($res){
                //修改用户表
                if($status==1){
                    //is_talker is_player
                    Db::name('user')->where('id = '.$uid)->update(['is_player'=>1]);
                }
            }
            return $res;
        }

        //删除陪玩主播
        public function player_del($id){
            $where = ['id'=>$id];
            $info = Db::name('auth_player')
                ->where($where)
                ->find();
            if(!$info){
                $this->error(lang('operation_failed_not_log'));
                exit;
            }
            $res = Db::name('auth_player')
                ->where($where)
                ->delete();
            if($res){
                $uid = $info['uid'];
                $game_id = $info['game_id'];
                Db::name('auth_player_img')->where(['pid'=>$id])->delete();
                $auth = Db::name('auth_player')
                    ->where(['uid'=>$uid,'status'=>1])
                    ->find();
                if(!$auth){
                    //陪玩师认证状态
                    Db::name('user')->where(['id'=>$uid])->update(['is_player'=>0,'guild_id'=>0]);
                    //退出加入的工会
                //    db('guild_join')->where('user_id=' . $uid)->delete();
                }
                // Db::name('user')->where('id = '.$uid)->update(['is_player'=>0]);
                //是否陪玩
                $skills_info = Db::name('skills_info')
                    ->where(['uid'=>$uid,'game_id'=>$game_id])
                    ->find();
                if($skills_info){
                    Db::name('skills_info')
                        ->where(['uid'=>$uid,'game_id'=>$game_id])
                        ->update(['status'=>4]);
                }
            }
            return $res;
        }
    }
