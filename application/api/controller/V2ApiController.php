<?php
namespace app\api\controller;
use think\Model;
use think\Db;
class V2ApiController extends Base
{
    protected function _initialize()
    {
        parent::_initialize();

        header('Access-Control-Allow-Origin:*');
    }
    public function GetAllMedals() {
        $user_info['id'] = input('param.uid');
        $user_giftes = Db::table('bogo_user_gift_log')
        ->where('user_id', $user_info['id'])
        ->order('gift_id')
        ->select();
        $gift_medal_lsit = Db::table('bogo_dress_up')
        ->where('type', 1)
        ->where('type_of_medal', 3)
        ->order('gift_id')
        ->select();
        $FinalMedalList = [];
        foreach ($gift_medal_lsit as $gift) {
            $counter = 0;
            for ($i = 0; $i < count($user_giftes); $i++) { 
                if($gift['gift_id'] < $user_giftes[$i]['gift_id']){
                    break;
                }else if($gift['gift_id'] == $user_giftes[$i]['gift_id']){
                    if($gift['Type_duration'] == 4){
                        $counter++;
                    }else if($gift['Type_duration'] == 3){
                        $withus = date('Y-m-d', strtotime( $user_giftes[$i]['date_y_m_d']. ' + 365 days'));
                        if(date('Y-m-d') <= $withus)
                            $counter++;
                    }else if($gift['Type_duration'] == 2){
                        $withus = date('Y-m-d', strtotime( $user_giftes[$i]['date_y_m_d']. ' + 30 days'));
                        if(date('Y-m-d') <= $withus)
                            $counter++;
                    }else if($gift['Type_duration'] == 1){
                        $withus = date('Y-m-d', strtotime( $user_giftes[$i]['date_y_m_d']. ' + 7 days'));
                        if(date('Y-m-d') <= $withus)
                            $counter++;
                    }
                }
                
            }
            $gift["IsAchived"] = 0;
            $gift["YourAchive"] = $counter;
            if($counter >= $gift['target_coin']){
                    $gift["IsAchived"] = 1;
            }

            $FinalMedalList[] =  $gift;
        }
        $d = time();
        $week_worth = Db::table('bogo_user_consume_log')
        ->where('user_id', $user_info['id'])
        ->where('create_time', ">=" ,($d - (7 *24*60*60)))
        ->sum('coin');

        $month_worth = Db::table('bogo_user_consume_log')
        ->where('user_id', $user_info['id'])
        ->where('create_time', ">=" ,$d - 30 *24*60*60)
        ->sum('coin');
        $year_worth = Db::table('bogo_user_consume_log')
        ->where('user_id', $user_info['id'])
        ->where('create_time', ">=" ,$d - 365 *24*60*60)
        ->sum('coin');
        $all_worth = Db::table('bogo_user_consume_log')
        ->where('user_id', $user_info['id'])
        ->sum('coin');
        $FinalMedalListWorth = [];
        $medalslist = Db::table('bogo_dress_up')
        ->where('type', 1)
        ->where('type_of_medal', 1)
        ->select();
        foreach ($medalslist as $medal ) {
            $medal['IsAchived'] = 0;
            if($medal['Type_duration'] == 4){
                if($all_worth >= $medal['target_coin']){
                    $medal['IsAchived'] = 1;
                }
                $medal['Achived'] = $all_worth;
            }
            else if($medal['Type_duration'] == 3){
                if($year_worth >= $medal['target_coin'])
                    $medal['IsAchived'] = 1;
                $medal['Achived'] = $year_worth;
            }
            else if($medal['Type_duration'] == 2){
                if($month_worth >= $medal['target_coin'])
                    $medal['IsAchived'] = 1;

                $medal['Achived'] = $month_worth;
            }
            else {
                if($week_worth >= $medal['target_coin']){
                    $medal['IsAchived'] = 1;
                }
                $medal['Achived'] = $week_worth;
            }

            $FinalMedalListWorth[] = $medal;
        }
        $FinalMedalListWorth2 = [];
        $week_worth = Db::table('bogo_user_consume_log')
        ->where('to_user_id', $user_info['id'])
        ->where('create_time', ">=" ,$d - 7 *24*60*60)
        ->sum('coin');
        $month_worth = Db::table('bogo_user_consume_log')
        ->where('to_user_id', $user_info['id'])
        ->where('create_time', ">=" ,$d - 30 *24*60*60)
        ->sum('coin');
        $year_worth = Db::table('bogo_user_consume_log')
        ->where('to_user_id', $user_info['id'])
        ->where('create_time', ">=" ,$d - 365 *24*60*60)
        ->sum('coin');
        $all_worth = Db::table('bogo_user_consume_log')
        ->where('to_user_id', $user_info['id'])
        ->sum('coin');
        $medalslist = Db::table('bogo_dress_up')
        ->where('type', 1)
        ->where('type_of_medal', 2)
        ->select();
        foreach ($medalslist as $medal ) {
            if($medal['Type_duration'] == 4){
                if($all_worth >= $medal['target_coin'])
                    $FinalMedalListWorth2[] = $medal;
            }
            else if($medal['Type_duration'] == 3){
                if($year_worth >= $medal['target_coin'])
                    $FinalMedalListWorth2[] = $medal;
            }
            else if($medal['Type_duration'] == 2){
                if($month_worth >= $medal['target_coin'])
                    $FinalMedalListWorth2[] = $medal;
            }
            else {
                if($week_worth >= $medal['target_coin'])
                    $FinalMedalListWorth2[] = $medal;
            }
        }

        $medalslist = array_merge($FinalMedalList, $FinalMedalListWorth,$FinalMedalListWorth2);
        
        $result['data'] = $medalslist;
        return_json_encode($result);
        
    }
    public function AddToMedalList(){
        $user_id = input('param.uid');
        $medal_id = input('param.MedalId');
        $medal_index = input('param.MedalIndex');
        $medal_lsit = Db::table('bogo_dress_up')
        ->where('id', $medal_id)
        ->Find();
        $d = time();
        if($medal_lsit['type_of_medal'] == 3){
            $user_giftes = []; 
            if($medal_lsit['Type_duration'] == 1){
                $user_giftes = Db::table('bogo_user_gift_log')
                ->where('user_id', $user_id)
                ->where('gift_id', $medal_lsit['gift_id'])
                ->where('create_time', ">=" ,($d - (7 *24*60*60)))
                ->select();
            }
            if($medal_lsit['Type_duration'] == 2){
                $user_giftes = Db::table('bogo_user_gift_log')
                ->where('user_id', $user_id)
                ->where('gift_id', $medal_lsit['gift_id'])
                ->where('create_time', ">=" ,($d - (30 *24*60*60)))
                ->select();
            }
            if($medal_lsit['Type_duration'] == 3){
                $user_giftes = Db::table('bogo_user_gift_log')
                ->where('user_id', $user_id)
                ->where('gift_id', $medal_lsit['gift_id'])
                ->where('create_time', ">=" ,($d - (365 *24*60*60)))
                ->select();
            }
            if($medal_lsit['Type_duration'] == 4){
                $user_giftes = Db::table('bogo_user_gift_log')
                ->where('user_id', $user_id)
                ->where('gift_id', $medal_lsit['gift_id'])
                ->select();
            }
            if(count($user_giftes) < $medal_lsit['target_coin'] ){
                $result['code'] = 400;
                $result['msg'] = 'You have reached the Target limit';
                return_json_encode($result);
            }
        }
        else if($medal_lsit['type_of_medal'] == 2){
            $worth = 0;
            if($medal_lsit['Type_duration'] == 4){
                $worth = Db::table('bogo_user_consume_log')
                ->where('to_user_id', $user_info['id'])
                ->sum('coin');
            }
            if($medal_lsit['Type_duration'] == 3){
                $worth = Db::table('bogo_user_consume_log')
                ->where('to_user_id', $user_info['id'])
                ->where('create_time', ">=" ,$d - 365 *24*60*60)
                ->sum('coin');
            }
            if($medal_lsit['Type_duration'] == 2){
                $worth = Db::table('bogo_user_consume_log')
                ->where('to_user_id', $user_info['id'])
                ->where('create_time', ">=" ,$d - 30 *24*60*60)
                ->sum('coin');
            }
            if($medal_lsit['Type_duration'] == 1){
                $worth = Db::table('bogo_user_consume_log')
                ->where('to_user_id', $user_info['id'])
                ->where('create_time', ">=" ,$d - 7 *24*60*60)
                ->sum('coin');
            }
            if($worth < $medal_lsit['target_coin']){
                $result['code'] = 400;
                $result['msg'] = 'You have reached the Target limit';
                return_json_encode($result);
            }
        }
        else if($medal_lsit['type_of_medal'] == 1){
            $worth = 0;
            if($medal_lsit['Type_duration'] == 4){
                $worth = Db::table('bogo_user_consume_log')
                ->where('user_id', $user_id)
                ->sum('coin');
            }
            if($medal_lsit['Type_duration'] == 3){
                $worth = Db::table('bogo_user_consume_log')
                ->where('user_id', $user_id)
                ->where('create_time', ">=" ,$d - 365 *24*60*60)
                ->sum('coin');
            }
            if($medal_lsit['Type_duration'] == 2){
                $worth = Db::table('bogo_user_consume_log')
                ->where('user_id', $user_id)
                ->where('create_time', ">=" ,$d - 30 *24*60*60)
                ->sum('coin');
            }
            if($medal_lsit['Type_duration'] == 1){
                $worth = Db::table('bogo_user_consume_log')
                ->where('user_id', $user_id)
                ->where('create_time', ">=" ,$d - 7 *24*60*60)
                ->sum('coin');
            }
            if($worth < $medal_lsit['target_coin']){
                $result['code'] = 400;
                $result['msg'] = 'You have reached the Target limit';
                return_json_encode($result);
            }
        }
        $user_medal = Db::table('bogo_user_medals')
        ->where('user_id', $user_id)
        ->where('medal_id', $medal_id)
        ->find();
        if($user_medal){
            $result['code'] = 400;
            $result['msg'] = 'You already Added this';
            return_json_encode($result);
        }
        $user_medal = Db::table('bogo_user_medals')
        ->insert(
            ["user_id" => $user_id,"medal_id" => $medal_id,"Medal_index" => $medal_index]);
        $result['code'] = 202;
        $result['msg'] = 'Added Successfully';
        return_json_encode($result);
    }
    public function DeletFromMyList(){
        $user_id = input('param.uid');
        $medal_id = input('param.MedalId');
        $user_medal = Db::table('bogo_user_medals')
        ->where('user_id', $user_id)
        ->where('medal_id', $medal_id)
        ->delete();
        
        $result['code'] = 202;
        $result['msg'] = 'Deleted Successfully';
        return_json_encode($result);
    }
}