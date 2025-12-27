<?php
    /**
     * Created by PhpStorm.
     * User: yang
     * Date: 2020-05-19
     * Time: 10:12
     */
    namespace app\admin\model;

    use think\Model;
    use think\Db;

    class PlayGameModel extends Model
    {
        public function get_type_list($page=10){
            if($page>50){
                $res = Db::name('play_game_type')
                    ->order('orderno asc')
                    ->select();
            }else{
                $res = Db::name('play_game_type')
                    ->order('orderno asc')
                    ->paginate($page, false, ['query' => request()->param()]);
            }

            return $res;
        }

        public function add_type($data){
            $res = Db::name('play_game_type')
                ->insertGetId($data);
            return $res;
        }

        public function update_type($where,$data){
            $res = Db::name('play_game_type')
                ->where($where)
                ->update($data);
            return $res;
        }

        public function get_type_find($where){

            $res = Db::name('play_game_type')
                ->where($where)
                ->find();
            return $res;
        }

        public function del_type($where){
            $res = Db::name('play_game_type')
                ->where($where)
                ->delete();
            return $res;
        }

        public function get_game_list($page=10){

            $res = Db::name('play_game')
                ->alias('g')
                ->join('play_game_type t','t.id=g.type_id')
                ->field('g.*,t.type_name')
                ->order('t.id,g.orderno asc')
                ->paginate($page, false, ['query' => request()->param()]);
            return $res;
        }

        public function add_game($data){
            $res = Db::name('play_game')
                ->insertGetId($data);
            return $res;
        }

        public function update_game($where,$data){
            $res = Db::name('play_game')
                ->where($where)
                ->update($data);
            return $res;
        }

        public function get_game_find($where){

            $res = Db::name('play_game')
                ->where($where)
                ->find();
            return $res;
        }

        public function del_game($where){
            $res = Db::name('play_game')
                ->where($where)
                ->delete();
            return $res;
        }

        /*
         * 游戏接单信息*/
        public function get_game_order($where,$page=10){

            $res = Db::name('play_game_order_info')
                ->where($where)
                ->order('orderno asc')
                ->paginate($page, false, ['query' => request()->param()]);
            return $res;
        }

        public function add_game_order($data){
            $res = Db::name('play_game_order_info')
                ->insertGetId($data);
            return $res;
        }

        public function update_game_order($where,$data){
            $res = Db::name('play_game_order_info')
                ->where($where)
                ->update($data);
            return $res;
        }

        public function get_game_order_find($where){

            $res = Db::name('play_game_order_info')
                ->where($where)
                ->find();
            return $res;
        }

        public function del_game_order($where){
            $res = Db::name('play_game_order_info')
                ->where($where)
                ->delete();
            return $res;
        }

        public function get_order_type_list($page=10){
            if($page>50){
                $res = Db::name('game_order_type')
                    ->order('orderno asc')
                    ->select();
            }else{
                $res = Db::name('game_order_type')
                    ->order('orderno asc')
                    ->paginate($page, false, ['query' => request()->param()]);
            }

            return $res;
        }

        public function get_order_type_find($where){

            $res = Db::name('game_order_type')
                ->where($where)
                ->find();
            return $res;
        }

        public function add_order_type($data){
            $res = Db::name('game_order_type')
                ->insertGetId($data);
            return $res;
        }

        public function update_order_type($where,$data){
            $res = Db::name('game_order_type')
                ->where($where)
                ->update($data);
            return $res;
        }

        public function del_game_order_type($where){
            $res = Db::name('game_order_type')
                ->where($where)
                ->delete();
            return $res;
        }

    }
