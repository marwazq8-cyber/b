<?php
namespace app\admin\model;

use think\Db;
use think\Model;

class TripartiteGameModel extends Model
{
    /**
     * 增加三方游戏信息
     */
    public function get_list($where,$page,$limit)
    {
        $config['page'] = $page ? $page : 1;
        $field ="*";

        $List = $this->field($field)
            ->where($where)
            ->order('sort desc')
            ->paginate($limit, false, $config)
            ->toArray();
        if(is_object($List)){
            $List = $List->toArray();
        }
        foreach ($List['data'] as &$v){
            if ($v['type'] == 'kingdoms' || $v['type'] == 'kingdoms2') {
                // 扎金花 title和desc 是语言包
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_kingdoms_java_json();
                }
            }elseif($v['type'] == 'lucky77'){
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_lucky77_java_json();
                }
            }elseif($v['type'] == 'lucky99'){
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_lucky99_java_json();
                }
            }elseif($v['type'] == 'fruitLoops'){
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_fruitLoops_java_json();
                }
            }elseif($v['type'] == 'greedy' || $v['type'] == 'greedyBtec'){
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_greedy_java_json();
                }
                foreach ($v['java_json'] as $vs){
                    if ($vs['field_name'] == 'optionType') {
                        foreach ($vs['val'] as &$va){
                            $va['title'] = lang($va['field_name']);
                        }
                    }
                }
            }elseif($v['type'] == 'whackAMole'){
                // 打狗游戏
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_whackAMole_java_json();
                }
            }elseif ($v['type'] == 'dragonTigerBattle'){
                //龙虎斗游戏
                if ($v['java_json']) {
                    $v['java_json'] = json_decode($v['java_json'],true);
                }else{
                    $v['java_json'] = $this->get_dragonTigerBattle_java_json();
                }
            }
            $isLandscape = $v['is_landscape'] == 1 ? 'true' : 'false';
            $url = $v['domain_name'] . "?isLandscape=" . $isLandscape;
            if ($v['merchant']) {
                $url .= "&merchant=" . $v['merchant'];
            }
            if ($v['game_name']) {
                $url .= "&gameName=" . $v['game_name'];
            }
            $v['domain_url'] = $url;
        }
        return $List;
    }
    /*
     *
     * 龙虎斗游戏
     * [{"title":"live_count_down_title","field_name":"longTime","val":"15","type":"input","max":60,"min":10,"desc":"countdown_length_limit_description"},{"title":"number_betting_areas","field_name":"playerNum","val":"3","type":"select","list":[{"label":1,"value":1},{"label":2,"value":2},{"label":3,"value":3}],"desc":"number_betting_areas_desc"},{"title":"counter_value","field_name":"betOption","val":"10,100,1000,10000,100000","type":"input","desc":"counter_value_desc"},{"title":"Percentage_multiple_probability","field_name":"optionType","val":[{"title":"draw","field_name":"pot0","val":[{"title":"multiplier","field_name":"winMultiple","val":"800","type":"input","desc":"Percentage_multiple"}],"type":"array","desc":""},{"title":"dragon","field_name":"pot1","val":[{"title":"multiplier","field_name":"winMultiple","val":"200","type":"input","desc":"Percentage_multiple"}],"type":"array","desc":""},{"title":"tiger","field_name":"pot2","val":[{"title":"multiplier","field_name":"winMultiple","val":"200","type":"input","desc":"Percentage_multiple"}],"type":"array","desc":""}],"type":"array","desc":""},{"title":"label.platform_revenue","field_name":"commissionRate","val":"20","type":"input","desc":"platform_revenue_ratio_desc"}]
     * */
    public function get_dragonTigerBattle_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 15,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：2、3
        $playerNum_list = [];
        $playerNum_list[] = array(
            'label' => 1,
            'value' => 1,
        );
        $playerNum_list[] = array(
            'label' => 2,
            'value' => 2,
        );
        $playerNum_list[] = array(
            'label' => 3,
            'value' => 3,
        );
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 1,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000,100000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "10,100,1000,10000,100000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        //下注分类的赢取倍率和中奖概率{"pot0":{"winMultiple":800},"pot1":{"winMultiple":200},"pot2":{"winMultiple":200}}

        $winMultiple =array(
            'title' => 'multiplier',
            'field_name' => 'winMultiple',
            'val' => "200",
            'type' => 'input',
            'desc' => 'Percentage_multiple'
        );
        $draw[] = $winMultiple;

        $optionType[] = array(
            'title' => 'draw_pot0',
            'field_name' => 'pot0',
            'val' => $draw,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'draw_pot1',
            'field_name' => 'pot1',
            'val' => $draw,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'draw_pot2',
            'field_name' => 'pot2',
            'val' => $draw,
            'type' => 'array',
            'desc' => ''
        );
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );

        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );

        return $java_json;
    }
    /**
     * 获取whackAMole中的 java_json配置信息
     * optionType={
     * "luckyValue": 100,
     * "luckyValueTime": 600,
     * luckyGiftProbability: 20,
     * isCoinPlay: true, 是否可以用金币支付开奖
     * isUpdateJackpot:true|false, 是否更新奖池（未出完的奖池将会作废）
     * "times": [1, 10, 100],
     * "gameCoinValue": 20,
     * luckyGift: []
     * "jackpot": [
     * {
     * "id": 1,
     * "title": "礼物名称",
     * "coin": 10000,
     * "image": "图片地址",
     * "number": 1,
     * "probability": 1
     * },
     * {
     * "id": 2,
     * "title": "礼物名称2",
     * "coin": 1000,
     * "image": "图片地址",
     * "number": 3,
     * "probability": 10
     * }
     * ],
     * "isCoinPlay": 1
     * }
     * */
    public function get_whackAMole_java_json(){
        $optionType=[];
        // 幸运值数字
        $optionType[] = array(
            'title' => 'lucky_value_title',
            'field_name' => 'luckyValue',
            'val' => 100,
            'type' => 'input',
            'desc' => 'lucky_value_description'
        );
        // 幸运时间
        $optionType[] = array(
            'title' => 'lucky_value_time_title',
            'field_name' => 'luckyValueTime',
            'val' => 600,
            'type' => 'input',
            'desc' => 'lucky_value_time_description'
        );
        // 幸运概率-- 当达到幸运值后，是否中幸运礼物概率
        $optionType[] = array(
            'title' => 'lucky_gift_probability_title',
            'field_name' => 'luckyGiftProbability',
            'val' => 20,
            'type' => 'input',
            'desc' => 'lucky_gift_probability_description'
        );
        $switch[] = array(
            'label' => 'CLOSE',
            'value' => 0,
        );
        $switch[] = array(
            'label' => 'OPEN',
            'value' => 1,
        );

        // 是否可以用金币支付开奖
        $optionType[] = array(
            'title' => 'is_coin_play_title',
            'field_name' => 'isCoinPlay',
            'val' => 1,
            'type' => 'select',
            'list'=> $switch,
            'desc' => 'is_coin_play_desc'
        );
        // 是否更新奖池（未出完的奖池将会作废）
        $optionType[] = array(
            'title' => 'is_update_jackpot_title',
            'field_name' => 'isUpdateJackpot',
            'val' => 1,
            'type' => 'select',
            'list'=> $switch,
            'desc' => 'is_update_jackpot_desc'
        );


        //筹码值，json类型，例如:[1, 10, 100]，用JSON数组配置
        $optionType[] = array(
            'title' => 'counter_value',
            'field_name' => 'times',
            'val' => "1,10,100",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        //锤值--打狗棒消费值
        $optionType[] = array(
            'title' => 'hammer_value_title',
            'field_name' => 'gameCoinValue',
            'val' => "20",
            'type' => 'input',
            'desc' => 'hammer_value_desc'
        );
        $java_json = [];
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );
        return $java_json;
    }
    /**
     * 获取fruitMachine中的 java_json配置信息
     *  commissionRate：平台收益比例，百分比，例如收益百分之二十，传20
     *  optionType:{"pot0":{"winMultiple":4500,"winRatio":2},"pot1":{"winMultiple":2500,"winRatio":4},"pot2":{"winMultiple":1500,"winRatio":6},"pot3":{"winMultiple":1000,"winRatio":8},"pot4":{"winMultiple":500,"winRatio":20},"pot5":{"winMultiple":500,"winRatio":20},"pot6":{"winMultiple":500,"winRatio":20},"pot7":{"winMultiple":500,"winRatio":20}}
     *  playerNum:可投注区域数，可选：8
     *  betOption:筹码值，json类型，例如:[10,100,1000,10000]
     *  longTime: 10
     *  className:fruitMachine
     * */
    public function get_fruitMachine_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 15,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：8
        $playerNum_list = [];
        $optionType=[];
        //下注分类的赢取倍率和中奖概率
        $winRatio =  array(
            'title' => 'probability_winning',
            'field_name' => 'winRatio',
            'val' => "40",
            'type' => 'input',
            'desc' => 'Percentage_probability'
        );
        $winMultiple =array(
            'title' => 'multiplier',
            'field_name' => 'winMultiple',
            'val' => "200",
            'type' => 'input',
            'desc' => 'Percentage_multiple'
        );
        for($i=0;$i<=7;$i++){
            $playerNum_list[] = array(
                'label' => $i + 1,
                'value' => $i + 1,
            );
            switch ($i){
                case 0:
                    $winRatio['val'] = 2;
                    $winMultiple['val'] = 4500;
                    break;
                case 1:
                    $winRatio['val'] = 4;
                    $winMultiple['val'] = 2500;
                    break;
                case 2:
                    $winRatio['val'] = 6;
                    $winMultiple['val'] = 1500;
                    break;
                case 3:
                    $winRatio['val'] = 8;
                    $winMultiple['val'] = 1000;
                    break;
                default:
                    $winRatio['val'] = 20;
                    $winMultiple['val'] = 500;
            }
            $pot = [];
            $pot[] = $winMultiple;
            $pot[] = $winRatio;
            $optionType[] = array(
                'title' => 'fruit_pot'.$i,
                'field_name' => 'pot'.$i,
                'val' => $pot,
                'type' => 'array',
                'desc' => ''
            );
        }
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 8,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "100,1000,10000,100000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );

        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );
        return $java_json;
    }
    /**
     * 获取greedy中的 java_json配置信息
     * commissionRate：平台收益比例，百分比，例如收益百分之二十，传20
     * optionType:{"pot0":{"winMultiple":4500,"winRatio":2},"pot1":{"winMultiple":2500,"winRatio":4},"pot2":{"winMultiple":1500,"winRatio":6},"pot3":{"winMultiple":1000,"winRatio":8},"pot4":{"winMultiple":500,"winRatio":20},"pot5":{"winMultiple":500,"winRatio":20},"pot6":{"winMultiple":500,"winRatio":20},"pot7":{"winMultiple":500,"winRatio":20}}
     * playerNum:可投注区域数，可选：8
     * betOption:筹码值，json类型，例如:[10,100,1000,10000]
     * longTime: 10
     * className:greedy
     */
    public function get_greedy_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 15,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：8
        $playerNum_list = [];
        $optionType=[];
        //下注分类的赢取倍率和中奖概率
        $winRatio =  array(
            'title' => 'probability_winning',
            'field_name' => 'winRatio',
            'val' => "40",
            'type' => 'input',
            'desc' => 'Percentage_probability'
        );
        $winMultiple =array(
            'title' => 'multiplier',
            'field_name' => 'winMultiple',
            'val' => "200",
            'type' => 'input',
            'desc' => 'Percentage_multiple'
        );
        for($i=0;$i<=7;$i++){
            $playerNum_list[] = array(
                'label' => $i + 1,
                'value' => $i + 1,
            );
            switch ($i){
                case 0:
                    $winRatio['val'] = 2;
                    $winMultiple['val'] = 4500;
                    break;
                case 1:
                    $winRatio['val'] = 4;
                    $winMultiple['val'] = 2500;
                    break;
                case 2:
                    $winRatio['val'] = 6;
                    $winMultiple['val'] = 1500;
                    break;
                case 3:
                    $winRatio['val'] = 8;
                    $winMultiple['val'] = 1000;
                    break;
                default:
                    $winRatio['val'] = 20;
                    $winMultiple['val'] = 500;
            }
            $pot = [];
            $pot[] = $winMultiple;
            $pot[] = $winRatio;
            $optionType[] = array(
                'title' => 'greedy_pot'.$i,
                'field_name' => 'pot'.$i,
                'val' => $pot,
                'type' => 'array',
                'desc' => ''
            );
        }
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 1,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "100,1000,10000,100000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );

        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );
        return $java_json;
    }
    /**
     * 获取luck77中的 java_json配置信息
     *commissionRate：平台收益比例，百分比，例如收益百分之二十，传20
     * optionType:{"apple":{"winMultiple": 200, "winRatio":40},"watermelon":{"winMultiple": 200, "winRatio":40},"seven":{"winMultiple": 800, "winRatio":20}}
     * playerNum:可投注区域数，可选：2、3
     * betOption:筹码值，json类型，例如:[10,100,1000,10000]
     * longTime: 10
     * className:kingdoms
     */
    public function get_fruitLoops_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 15,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：2、3
        $playerNum_list = [];
        $playerNum_list[] = array(
            'label' => 1,
            'value' => 1,
        );
        $playerNum_list[] = array(
            'label' => 2,
            'value' => 2,
        );
        $playerNum_list[] = array(
            'label' => 3,
            'value' => 3,
        );
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 1,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "100,1000,10000,100000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        //下注分类的赢取倍率和中奖概率 {"apple":{"winMultiple": 200, "winRatio":40},"watermelon":{"winMultiple": 200, "winRatio":40},"seven":{"winMultiple": 800, "winRatio":20}}
        $winRatio =  array(
            'title' => 'probability_winning',
            'field_name' => 'winRatio',
            'val' => "40",
            'type' => 'input',
            'desc' => 'Percentage_probability'
        );
        $winMultiple =array(
            'title' => 'multiplier',
            'field_name' => 'winMultiple',
            'val' => "200",
            'type' => 'input',
            'desc' => 'Percentage_multiple'
        );
        $apple[] = $winMultiple;
        $watermelon[] = $winMultiple;
        $seven[] = $winMultiple;

        $apple[] = $winRatio;
        $watermelon[] =  $winRatio;
        $seven[] =  $winRatio;
        $optionType[] = array(
            'title' => 'mango',
            'field_name' => 'mango',
            'val' => $apple,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'watermelon',
            'field_name' => 'watermelon',
            'val' => $watermelon,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'litchi',
            'field_name' => 'litchi',
            'val' => $seven,
            'type' => 'array',
            'desc' => ''
        );
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );

        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );
        return $java_json;
    }
    /**
     * 获取luck77中的 java_json配置信息
     *commissionRate：平台收益比例，百分比，例如收益百分之二十，传20
     * optionType:{"apple":{"winMultiple": 200, "winRatio":40},"watermelon":{"winMultiple": 200, "winRatio":40},"seven":{"winMultiple": 800, "winRatio":20}}
     * playerNum:可投注区域数，可选：2、3
     * betOption:筹码值，json类型，例如:[10,100,1000,10000]
     * longTime: 10
     * className:kingdoms
     */
    public function get_lucky99_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 15,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：2、3
        $playerNum_list = [];
        $playerNum_list[] = array(
            'label' => 1,
            'value' => 1,
        );
        $playerNum_list[] = array(
            'label' => 2,
            'value' => 2,
        );
        $playerNum_list[] = array(
            'label' => 3,
            'value' => 3,
        );
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 1,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "10,100,1000,10000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        //下注分类的赢取倍率和中奖概率 {"apple":{"winMultiple": 200, "winRatio":40},"watermelon":{"winMultiple": 200, "winRatio":40},"seven":{"winMultiple": 800, "winRatio":20}}
        $winRatio =  array(
            'title' => 'probability_winning',
            'field_name' => 'winRatio',
            'val' => "40",
            'type' => 'input',
            'desc' => 'Percentage_probability'
        );
        $winMultiple =array(
            'title' => 'multiplier',
            'field_name' => 'winMultiple',
            'val' => "200",
            'type' => 'input',
            'desc' => 'Percentage_multiple'
        );
        $apple[] = $winMultiple;
        $watermelon[] = $winMultiple;
        $seven[] = $winMultiple;

        $apple[] = $winRatio;
        $watermelon[] =  $winRatio;
        $seven[] =  $winRatio;
        $optionType[] = array(
            'title' => 'apple',
            'field_name' => 'apple',
            'val' => $apple,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'lemon',
            'field_name' => 'lemon',
            'val' => $watermelon,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'nine',
            'field_name' => 'nine',
            'val' => $seven,
            'type' => 'array',
            'desc' => ''
        );
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );

        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );
        return $java_json;
    }
    /**
     * 获取luck77中的 java_json配置信息
     *commissionRate：平台收益比例，百分比，例如收益百分之二十，传20
     * optionType:{"apple":{"winMultiple": 200, "winRatio":40},"watermelon":{"winMultiple": 200, "winRatio":40},"seven":{"winMultiple": 800, "winRatio":20}}
     * playerNum:可投注区域数，可选：2、3
     * betOption:筹码值，json类型，例如:[10,100,1000,10000]
     * longTime: 10
     * className:kingdoms
     */
    public function get_lucky77_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 15,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：2、3
        $playerNum_list = [];
        $playerNum_list[] = array(
            'label' => 1,
            'value' => 1,
        );
        $playerNum_list[] = array(
            'label' => 2,
            'value' => 2,
        );
        $playerNum_list[] = array(
            'label' => 3,
            'value' => 3,
        );
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 1,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "10,100,1000,10000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        //下注分类的赢取倍率和中奖概率 {"apple":{"winMultiple": 200, "winRatio":40},"watermelon":{"winMultiple": 200, "winRatio":40},"seven":{"winMultiple": 800, "winRatio":20}}
        $winRatio =  array(
            'title' => 'probability_winning',
            'field_name' => 'winRatio',
            'val' => "40",
            'type' => 'input',
            'desc' => 'Percentage_probability'
        );
        $winMultiple =array(
            'title' => 'multiplier',
            'field_name' => 'winMultiple',
            'val' => "200",
            'type' => 'input',
            'desc' => 'Percentage_multiple'
        );
        $apple[] = $winMultiple;
        $watermelon[] = $winMultiple;
        $seven[] = $winMultiple;

        $apple[] = $winRatio;
        $watermelon[] =  $winRatio;
        $seven[] =  $winRatio;
        $optionType[] = array(
            'title' => 'apple',
            'field_name' => 'apple',
            'val' => $apple,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'watermelon',
            'field_name' => 'watermelon',
            'val' => $watermelon,
            'type' => 'array',
            'desc' => ''
        );
        $optionType[] = array(
            'title' => 'seven',
            'field_name' => 'seven',
            'val' => $seven,
            'type' => 'array',
            'desc' => ''
        );
        $java_json[] = array(
            'title' => 'Percentage_multiple_probability',
            'field_name' => 'optionType',
            'val' =>$optionType,
            'type' => 'array',
            'desc' => ''
        );

        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );
        return $java_json;
    }
    /**
    * 获取kingdoms中的 java_json配置信息
     *commissionRate：平台收益比例，百分比，例如收益百分之二十，传20
     * earningsMultiples:百分比，例如：3倍奖励传300
     * playerNum:可投注区域数，可选：2、3
     * betOption:筹码值，json类型，例如:[10,100,1000,10000]
     * longTime: 10
     * className:kingdoms
     */
    public function get_kingdoms_java_json(){
        $java_json = [];
        // 游戏倒计时时长
        $java_json[] = array(
            'title' => 'live_count_down_title',
            'field_name' => 'longTime',
            'val' => 10,
            'type' => 'input',
            'max'=> 60,
            'min' => 10,
            'desc' => 'countdown_length_limit_description'
        );
        //可投注区域数，可选：2、3
        $playerNum_list = [];
        $playerNum_list[] = array(
            'label' => 2,
            'value' => 2,
        );
        $playerNum_list[] = array(
            'label' => 3,
            'value' => 3,
        );
        $java_json[] = array(
            'title' => 'number_betting_areas',
            'field_name' => 'playerNum',
            'val' => 2,
            'type' => 'select',
            'list'=> $playerNum_list,
            'desc' => 'number_betting_areas_desc'
        );
        //筹码值，json类型，例如:[10,100,1000,10000]，一共4个盘，用JSON数组配置
        $java_json[] = array(
            'title' => 'counter_value',
            'field_name' => 'betOption',
            'val' => "10,100,1000,10000",
            'type' => 'input',
            'desc' => 'counter_value_desc'
        );
        //奖励倍数 -- 百分比，例如：3倍奖励传300
        $java_json[] = array(
            'title' => 'reward_multiple',
            'field_name' => 'earningsMultiples',
            'val' => 300,
            'type' => 'input',
            'desc' => 'reward_multiple_desc'
        );
        //平台收益比例，百分比，例如收益百分之二十，传20
        $java_json[] = array(
            'title' => 'platform_revenue',
            'field_name' => 'commissionRate',
            'val' => 20,
            'type' => 'input',
            'desc' => 'platform_revenue_ratio_desc'
        );
        return $java_json;
    }
    /**
    * 编辑三方信息
     */
    public function save_update($where,$update){
        return $this->where($where)->update($update);
    }
    /**
     * 编辑三方信息
     */
    public function sel_find($where){
         $List = $this->where($where)->find();
        if(is_object($List)){
            $List = $List->toArray();
        }
        return $List;
    }
}