<?php
/**
 * 布谷科技商业系统
 * 三方游戏接口
 * @author 山东布谷鸟网络科技有限公司
 */

namespace app\game\controller;

use app\api\controller\Base;
use think\Db;
use think\Model;
use app\game\model\TripartiteGameLog;
use app\game\model\TripartiteGameUserLog;
use app\game\model\User;
use think\Request;

class Game extends Base
{
    private $TripartiteGameLog;
    private $TripartiteGameUserLog;
    private $User;

    protected function _initialize()
    {
        parent::_initialize();

        $TripartiteGameLog = new TripartiteGameLog();
        $this->TripartiteGameLog = $TripartiteGameLog;

        $TripartiteGameUserLog = new TripartiteGameUserLog();
        $this->TripartiteGameUserLog = $TripartiteGameUserLog;

        $User = new User();
        $this->User = $User;
    }

    /**
     * 获取用户余额
     * type ai（机器人列表）|reduceCoin（实时扣除金币）| addGameCoin（购买游戏金币） | 空是获取用户余额信息
     * gameType = 游戏类型
     * data = 实时扣币、购买金币需要传相应的参数
     *          type  reduceCoin类型才会传  扣币类型：coin(用户金币)|gameCoin(游戏金币)
     *          coin 扣除用户金币或游戏金币的额度，购买游戏金币的额度
     *          gameCoin  addGameCoin类型才会传  购买游戏币的额度
     */
    public function userInformation()
    {
        $root = array('code' => 1, 'msg' => '', 'data' => array());
        $token = input('param.token');
        $type = input('param.type');
        $gameType = input('param.gameType');
        $data = isset($_POST['data']) ? $_POST['data'] : '';
        $userInfo = '';
        if ($token) {
            $userInfo = db("user")
                ->field("id,coin,user_nickname as nickname,avatar")
                ->where("token='" . $token . "' and user_status != 0")
                ->find();
            if (!$userInfo || $userInfo == null || $token == '') {
                $root['msg'] = lang("login_timeout");
                $root['code'] = 0;
                return $this->bogokjReturnJsonData($root);
            }
        }
        bogokjLogPrint("GameUserInformation", "userInformation \n type=" . $type . "; userInfo=" . json_encode($userInfo) . " \n data=" . $data);
        if ($data) {
            $data = json_decode($data, true);
        }
        switch ($type) {
            case 'ai':
                // ai机器人
                $root['data'] = $this->get_ai();
                break;
            case 'reduceCoin':
                // 实时扣除金币
                $data['uid'] = $userInfo['id'];
                $result = $this->deducting_game_coins($gameType, $data, $userInfo);
                if ($result['status'] != 1) {
                    $root['code'] = 0;
                    $root['msg'] = "扣费失败";
                }
                $root['data'] = $result;
                break;
            case 'addGameCoin':
                // 购买游戏金币
                $data['uid'] = $userInfo['id'];
                $result = $this->buy_game_coins($gameType, $data, $userInfo);
                if ($result['status'] != 1) {
                    $root['code'] = 0;
                    $root['msg'] = "扣费失败";
                }
                $root['data'] = $result;
                break;
            default:
                $userInfo['gameCoin'] = 0;
                if (isset($gameType)) {
                    $tripartite_game_currency = db('tripartite_game_currency')->where("type='" . $gameType . "' and uid=" . $userInfo['id'])->find();
                    if ($tripartite_game_currency && $tripartite_game_currency['game_currency'] > 0) {
                        $userInfo['gameCoin'] = $tripartite_game_currency['game_currency'];
                    }
                }
                // 否则用户信息 -- 获取用户信息
                $userInfo['nickname'] = emoji_decode($userInfo['nickname']);
                $root['data'] = $userInfo;
        }
        return $this->bogokjReturnJsonData($root);
    }

    /**
     * 实时扣除游戏金币
     * */
    private function deducting_game_coins($gameType, $data, $userInfo)
    {
        $return = array(
            'coin' => 0,
            'status' => 1,
            'type' => $data['type'],
            'uid' => $data['uid'],
        );
        if ($data['type'] == 'coin') {
            $where = "id=" . $data['uid'] . " and coin >=" . $data['coin'];
            // 扣费--用户钻石
            $result = db('user')->where($where)->Dec("coin", $data['coin'])->update();
            if ($result) {
                $balance = $userInfo['coin'];
                $userInfo['coin'] = $userInfo['coin'] - $data['coin'];
                // 添加消费记录
                $content = "game:" . $gameType . "=reduceCoin";
                $this->User->add_user_log($data['uid'], $data['coin'], 1, 2, $content);
                add_charging_log($data['uid'], 0, 28, $data['coin'], 0, 0, $content);
                // 钻石变更记录
                save_coin_log($data['uid'], '-' . $data['coin'], 1, 18, $content, $balance);
            }
            $return['coin'] = intval($userInfo['coin']);
            $return['status'] = $result ? 1 : 0;
        } else {

            $where = "uid=" . $data['uid'] . " and game_currency >=" . $data['coin'] . " and type='" . $gameType . "'";
            // 扣费--用户游戏币
            $result = db('tripartite_game_currency')->where($where)->Dec("game_currency", $data['coin'])->update();
            $userInfo = db("tripartite_game_currency")->field("game_currency")->where("uid=" . $data['uid'] . " and type='" . $gameType . "'")->find();
            // 钻石变更记录
            $content = "game:" . $gameType . "=gameCoin";
            $balance = $userInfo['game_currency'] + $data['coin'];
            save_coin_log($data['uid'], '-' . $data['coin'], 3, 18, $content, $balance);
            $return['status'] = $result ? 1 : 0;
            $return['coin'] = intval($userInfo['game_currency']);
        }

        return $return;
    }

    /**
     * 购买游戏金币
     * */
    private function buy_game_coins($gameType, $data, $userInfo)
    {

        $return = array(
            'gameCoin' => 0,
            'coin' => 0,
            'status' => 0,
            'type' => '',
            'uid' => $data['uid'],
        );

        $where1 = "id=" . $data['uid'] . " and coin >=" . $data['coin'];
        // 扣费--用户钻石
        $result = db('user')->where($where1)->Dec("coin", $data['coin'])->update();
        if ($result) {
            $userInfo['coin'] = $userInfo['coin'] - $data['coin'];
            // 添加消费记录
            $content = "game:" . $gameType . "=addGameCoin;coin=" . $data['coin'] . ";game_currency=" . $data['gameCoin'];
            $this->User->add_user_log($data['uid'], $data['coin'], 1, 2, $content);
            add_charging_log($data['uid'], 0, 28, $data['coin'], 0, 0, $content);
            // 钻石变更记录
            save_coin_log($data['uid'], '-' . $data['coin'], 1, 18, $content);
            $where = "uid=" . $data['uid'] . " and type='" . $gameType . "'";
            // 增加游戏币
            $tripartite_game_currency = db('tripartite_game_currency')->where($where)->Inc("game_currency", $data['gameCoin'])->Inc("game_currency_sum", $data['gameCoin'])->Inc("coin_sum", $data['coin'])->update();
            if (!$tripartite_game_currency) {
                //添加游戏币
                $add_log = [
                    'uid' => $data['uid'],
                    'type' => $gameType,
                    'game_currency' => $data['gameCoin'],
                    'game_currency_sum' => $data['gameCoin'],
                    'coin_sum' => $data['coin'],
                ];
                db('tripartite_game_currency')->insert($add_log);
            }
            $gameCoin = db('tripartite_game_currency')->where($where)->find();

            $return['status'] = 1;
            $return['coin'] = $userInfo['coin'];
            $return['gameCoin'] = $gameCoin ? $gameCoin['game_currency'] : 0;
        }
        return $return;
    }

    /**
     * 获取机器人
     * **/
    private function get_ai()
    {
        $m_config = load_cache("config");//参数
        // game_robot_switch是否开启游戏机器人下注
        $list = array();
        if (isset($m_config['game_robot_switch']) && $m_config['game_robot_switch'] == 1) {
            $list = db("user")
                ->field("id,coin,user_nickname as nickname,avatar")
                ->where("is_robot=1")
                ->orderRaw("rand()")
                ->limit(10)
                ->select();
            foreach ($list as &$v) {
                $v['nickname'] = emoji_decode($v['nickname']);
            }
        }
        return $list;
    }

    /**
     * 中途游戏退出--需要扣除金额
     */
    public function deduction_coin($uid, $coin, $content)
    {
        if ($uid && $coin > 0) {
            $user_where = "id='" . $uid . "' and coin >=" . $coin;
            // 扣费
            $this->User->updateUserDecInc($user_where, $coin);
            // 添加消费记录
            $this->User->add_user_log($uid, $coin, 1, 2, $content);
            add_charging_log($uid, 0, 28, $coin, 0, 0, $content . " (exit)");
            // 钻石变更记录
            save_coin_log($uid, '-' . $coin, 1, 18, $content . " (exit)");
        }
    }

    /**
     * 结束三方游戏
     * @param string 三色椅子:gameType = kingdoms
     * @param string 拖拉机:gameType = kingdoms2
     * @param json gameInfo 游戏数据
     * @param json userList 用户数据
     * @param int gameId 本轮游戏唯一标识
     * @param int userTotalConsumptionCoin 用户总消费
     * @param int userTotalIncome 用户总收益
     * @param int totalIncome 平台总收入
     * @param int groupId 房间id
     * @param json gameResult 游戏结果
     * @param json type 类型
     * @return bool|mixed/game/game/
     */
    public function EndGame()
    {
        $root = array('code' => 1, 'msg' => '', 'data' => array());
        //获取body里的参数
        $request = Request::instance();
        $bodyData = $request->getContent();
        bogokjLogPrint("EndGame", $bodyData);
        if ($bodyData) {
            //将获取到的值转化为数组格式
            $bodyData = json_decode($bodyData, true);
            $Type = $bodyData['type'];
            $gameType = $bodyData['gameType'];
            if ($Type == 'gameOver') {
                // 游戏结束
                $gameInfo = isset($bodyData['gameInfo']) ? $bodyData['gameInfo'] : '';
                $userList = isset($bodyData['userList']) ? $bodyData['userList'] : '';
                $game_order_id = isset($bodyData['gameLogId']) ? $bodyData['gameLogId'] : '';
                $consumption_coin = isset($bodyData['userTotalConsumptionCoin']) ? $bodyData['userTotalConsumptionCoin'] : '';
                $total_income = isset($bodyData['userTotalIncome']) ? $bodyData['userTotalIncome'] : '';
                $platform_total = isset($bodyData['totalIncome']) ? $bodyData['totalIncome'] : '';
                $room_id = isset($bodyData['groupId']) ? $bodyData['groupId'] : '';
                $gameResult = isset($bodyData['gameResult']) ? json_encode($bodyData['gameResult']) : '';
                $time = NOW_TIME;
                $insert = array(
                    'room_id' => $room_id,
                    'game_type' => $gameType,
                    'game_order_id' => $game_order_id,
                    'consumption_coin' => $consumption_coin,
                    'total_income' => $total_income,
                    'platform_total' => $platform_total,
                    'game_result' => $gameResult,
                    'bet' => $gameInfo ? json_encode($gameInfo) : '',
                    'create_time' => $time,
                    'create_time_y' => date('Y', $time),
                    'create_time_m' => date('m', $time),
                    'create_time_d' => date('d', $time),
                );
//
                switch ($gameType) {
                    case 'kingdoms':
                    case 'kingdoms2':
                        $this->add_kingdoms($insert, $userList, $gameType);
                        break;
                    case 'lucky77':
                        $this->add_lucky77($insert, $userList, $gameType);
                        break;
                    case 'lucky99':
                        $this->add_lucky99($insert, $userList, $gameType);
                        break;
                    case 'fruitLoops':
                        $this->add_fruitLoops($insert, $userList, $gameType);
                        break;
                    case 'greedy':
                    case 'greedyBtec':
                        $this->add_greedy($insert, $userList, $gameType);
                        break;
                    case 'whackAMole':
                        $this->add_whackAMole($insert, $gameType);
                        break;
                    case 'fruitMachine':
                        $this->add_fruitMachine($insert, $userList, $gameType);
                        break;
                    case 'dragonTigerBattle':
                        $this->add_dragonTigerBattle($insert, $userList, $gameType);
                        break;
                    default:
                }
            } else if ($Type == 'deductCoin') {
                // 游戏中--用户退出 -- 提前扣除金币
                $uid = intval($bodyData['id']);
                $coin = intval($bodyData['coin']);
                $content = "game:" . $gameType;
                $this->deduction_coin($uid, $coin, $content);
            }
        }

        return $this->bogokjReturnJsonData($root);
    }

    /**
     * dragonTigerBattle: gameType = dragonTigerBattle
     * $insert 插入数据 ---- 处理结果 red红区总消费值 blue 蓝区总消费值 green绿区总消费值 winner胜利1:红,2蓝,3绿
     * $userList扣费或奖励
     * refundCoin 0不处理 存在：是和局，需要把下注的数据返还给用户
     */
    private function add_dragonTigerBattle($insert, $userList, $gameType)
    {

        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();

        if ($game_result) {
            $winner_result = array('0' => 'draw', '1' => 'dragon', '2' => 'tiger');
            $game_result['winnerName'] = $winner_result[$game_result['winner']];
            $Processing_result = "draw_pot0:" . $game_result['pot0'] . ";";
            $Processing_result .= "draw_pot1:" . $game_result['pot1'] . ";";
            $Processing_result .= "draw_pot2:" . $game_result['pot2'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $game_result['winnerName'];
            $insert['game_result_text'] = $Processing_result;
        } else {
            $game_result['winnerName'] = '';
        }
        bogokjLogPrint("EndGame_Insert", $insert);
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $m_config = load_cache("config");//参数
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $refundCoin = intval($v['refundCoin']); // 出现和局时，需要把下注的余额退回给用户
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);

                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'draw_pot0' => $v['pot0'],
                        'draw_pot1' => $v['pot1'],
                        'draw_pot2' => $v['pot2'],
                    )
                );
                $return_result = 1;// 默认扣费成功

                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin, $refundCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }

                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }

                $game_result_text = lang('draw_pot0') . ":" . $v['pot0'] . ";";
                $game_result_text .= lang('draw_pot1') . ":" . $v['pot1'] . ";";
                $game_result_text .= lang('draw_pot2') . ":" . $v['pot2'] . ";";
                $game_result_text .= lang('game_winner_result') . ":" . $game_result['winnerName'];

                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin + $refundCoin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin + $refundCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }

            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * fruitMachine: gameType = fruitMachine
     * $insert 插入数据 ---- 处理结果
     * $userList扣费或奖励
     * {"gameType":"fruitMachine","groupId":"111","gameResult":{"winnerName":"pot2","winner":2,"pot5":0,"pot6":0,"pot7":0,"pot1":0,"pot2":0,"pot3":0,"pot4":0,"pot0":0},"totalIncome":0,"userList":[],"userTotalConsumptionCoin":0,"userTotalIncome":0,"gameLogId":23679,"gameInfo":["pot3","pot3","pot5","pot3","pot5","pot1","pot4","pot6","pot4","pot5","pot4","pot3","pot5","pot6","pot7","pot3","pot6","pot6","pot1","pot4","pot5","pot4","pot7","pot5","pot1","pot7","pot6","pot6","pot2","pot2","pot5","pot7","pot5","pot5","pot4","pot6","pot7","pot7","pot4","pot2","pot5","pot2","pot5","pot7","pot5","pot7","pot6","pot7","pot4","pot7","pot6","pot6","pot6","pot7","pot6","pot6","pot4","pot7","pot2","pot4","pot7","pot7","pot3","pot7","pot4","pot5","pot6","pot6","pot4","pot6","pot4","pot5","pot4","pot5","pot4","pot6","pot2","pot7","pot4","pot7","pot0","pot7","pot3","pot5","pot7","pot4","pot3","pot5","pot5","pot6","pot5","pot6","pot0","pot4","pot1","pot4","pot4","pot7","pot5","pot6"]}
     */
    private function add_fruitMachine($insert, $userList, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        if ($game_result) {
            $Processing_result = "pot0:" . $game_result['pot0'] . ";";
            $Processing_result .= "pot1:" . $game_result['pot1'] . ";";
            $Processing_result .= "pot2:" . $game_result['pot2'] . ";";
            $Processing_result .= "pot3:" . $game_result['pot3'] . ";";
            $Processing_result .= "pot4:" . $game_result['pot4'] . ";";
            $Processing_result .= "pot5:" . $game_result['pot5'] . ";";
            $Processing_result .= "pot6:" . $game_result['pot6'] . ";";
            $Processing_result .= "pot7:" . $game_result['pot7'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $game_result['winnerName'];
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $m_config = load_cache("config");//参数
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);

                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'pot0' => $v['pot0'],
                        'pot1' => $v['pot1'],
                        'pot2' => $v['pot2'],
                        'pot3' => $v['pot3'],
                        'pot4' => $v['pot4'],
                        'pot5' => $v['pot5'],
                        'pot6' => $v['pot6'],
                        'pot7' => $v['pot7'],
                    )
                );
                $return_result = 1;// 默认扣费成功
                // 扣费
                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }
                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }
                $game_result_text = lang('fruit_pot0') . ":" . $v['pot0'] . ";";
                $game_result_text .= lang('fruit_pot1') . ":" . $v['pot1'] . ";";
                $game_result_text .= lang('fruit_pot2') . ":" . $v['pot2'] . ";";
                $game_result_text .= lang('fruit_pot3') . ":" . $v['pot3'] . ";";
                $game_result_text .= lang('fruit_pot4') . ":" . $v['pot4'] . ";";
                $game_result_text .= lang('fruit_pot5') . ":" . $v['pot5'] . ";";
                $game_result_text .= lang('fruit_pot6') . ":" . $v['pot6'] . ";";
                $game_result_text .= lang('fruit_pot7') . ":" . $v['pot7'] . ";";

                $game_result_text .= lang('game_winner_result') . ":" . lang('greedy_' . $game_result['winnerName']);
                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }
            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * whackAMole: gameType = whackAMole
     * $insert 插入数据 ---- 处理结果
     * $userList扣费或奖励
     * game_result = {"uid": 1111, "giftIds": [1,2,3]}
     * type: coin(金币类型)|gameCoin(锤子类型)
     * coin: 金币值|锤子值（根据type判断）
     * num:打的次数
     * consumptionTotalCoin:消费总价值（金币值）
     * giftTotalCoin:奖励总价值
     * incomeCoin:平台收益
     *
     */
    private function add_whackAMole($insert, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        $uid = 0;
        $gifts = [];
        $insert['game_result_text'] = '';
        bogokjLogPrint("EndGame", " game_result(add_whackAMole)=" . $insert['game_result']);
        $type = 0; // coin(金币类型)|gameCoin(锤子类型)
        $coin = 0; // 金币值|锤子值（根据type判断）
        $num = 0; // 打的次数
        if ($game_result) {
            $uid = $game_result['uid'];
            $type = $game_result['type']; // coin(金币类型)|gameCoin(锤子类型)
            $coin = $game_result['coin']; // 金币值|锤子值（根据type判断）
            $num = $game_result['num']; // 打的次数
            $consumptionTotalCoin = $game_result['consumptionTotalCoin']; // 消费总价值（金币值）
            $giftTotalCoin = $game_result['giftTotalCoin']; //奖励总价值
            $incomeCoin = $game_result['incomeTotalCoin']; //平台收益


            $reward_type_text = 'num:' . $num . '[type:' . $type . ' -' . $coin . ']';
            $insert['consumption_coin'] = $consumptionTotalCoin;
            $insert['total_income'] = $giftTotalCoin;
            $insert['platform_total'] = $incomeCoin;
            $insert['reward_type'] = 1; // 奖励类型0钻石1礼物
            $insert['reward_type_text'] = $reward_type_text; // 奖励类型说明

            $Processing_result = lang('game_winner_result') . ": ";
            $giftIds = array();
            foreach ($game_result['giftIds'] as $vs) {
                // 查询礼物
                $gift_list = Db::name('tripartite_game_gift')->alias("t")
                    ->join("gift g", "g.id = t.gift_id")
                    ->where("t.type='" . $gameType . "' and t.id=" . $vs)
                    ->field("t.gift_id,t.id,g.name as title,g.coin,t.number")
                    ->find();
                if ($gift_list) {
                    $gifts[] = $gift_list;
                    if (isset($giftIds[$gift_list['gift_id']])) {
                        $giftIds[$gift_list['gift_id']]['number'] = $gift_list['number'] + $giftIds[$gift_list['gift_id']]['number'];
                    } else {
                        $giftIds[$gift_list['gift_id']] = $gift_list;
                    }
                }
            }
            foreach ($giftIds as $v) {
                $Processing_result .= $v['title'] . "(" . $v['gift_id'] . ") x" . $v['number'] . "、 ";
                // 加入背包礼物
                $user_bag = db('user_bag')->where("uid=" . $uid . " and giftid=" . $v['gift_id'])->setInc('giftnum', intval($v['number']));
                if (!$user_bag) {  //背包中是否存在这个礼物
                    //添加背包记录
                    $gift_log = [
                        'uid' => $uid,
                        'giftid' => $v['gift_id'],
                        'giftnum' => $v['number'],
                    ];
                    db('user_bag')->insert($gift_log);
                }
            }
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);
        if ($id) {
            $reward_type_text = 'num:' . $num . '[type:' . $type . ' -' . $coin . ']'; // 奖励类型说明
            $user_bet = array(
                'uid' => $uid,
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => $consumptionTotalCoin,
                'total_income' => $giftTotalCoin,
                'exit_deduction_coin' => 0,
                'game_result' => json_encode($gifts),
                'game_result_text' => $insert['game_result_text'],
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
                'status' => 1,
                'reward_type' => 1, // 奖励类型0钻石1礼物
                'reward_type_text' => $reward_type_text,
            );

            // 用户下注记录
            $this->TripartiteGameUserLog->insert_one($user_bet);
        }
    }

    /**
     * greedy: gameType = greedy
     * $insert 插入数据 ---- 处理结果 胜利winner=8(披萨)=9(沙拉) winnerName直接就是pizza和salad
     * $userList扣费或奖励
     * {"gameType":"greedy","groupId":"111","gameResult":{"winnerName":"pot2","winner":2,"pot5":0,"pot6":0,"pot7":0,"pot1":0,"pot2":0,"pot3":0,"pot4":0,"pot0":0},"totalIncome":0,"userList":[],"userTotalConsumptionCoin":0,"userTotalIncome":0,"gameLogId":23679,"gameInfo":["pot3","pot3","pot5","pot3","pot5","pot1","pot4","pot6","pot4","pot5","pot4","pot3","pot5","pot6","pot7","pot3","pot6","pot6","pot1","pot4","pot5","pot4","pot7","pot5","pot1","pot7","pot6","pot6","pot2","pot2","pot5","pot7","pot5","pot5","pot4","pot6","pot7","pot7","pot4","pot2","pot5","pot2","pot5","pot7","pot5","pot7","pot6","pot7","pot4","pot7","pot6","pot6","pot6","pot7","pot6","pot6","pot4","pot7","pot2","pot4","pot7","pot7","pot3","pot7","pot4","pot5","pot6","pot6","pot4","pot6","pot4","pot5","pot4","pot5","pot4","pot6","pot2","pot7","pot4","pot7","pot0","pot7","pot3","pot5","pot7","pot4","pot3","pot5","pot5","pot6","pot5","pot6","pot0","pot4","pot1","pot4","pot4","pot7","pot5","pot6"]}
     */
    private function add_greedy($insert, $userList, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        if ($game_result) {
            $Processing_result = "pot0:" . $game_result['pot0'] . ";";
            $Processing_result .= "pot1:" . $game_result['pot1'] . ";";
            $Processing_result .= "pot2:" . $game_result['pot2'] . ";";
            $Processing_result .= "pot3:" . $game_result['pot3'] . ";";
            $Processing_result .= "pot4:" . $game_result['pot4'] . ";";
            $Processing_result .= "pot5:" . $game_result['pot5'] . ";";
            $Processing_result .= "pot6:" . $game_result['pot6'] . ";";
            $Processing_result .= "pot7:" . $game_result['pot7'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $game_result['winnerName'];
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);

                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'pot0' => $v['pot0'],
                        'pot1' => $v['pot1'],
                        'pot2' => $v['pot2'],
                        'pot3' => $v['pot3'],
                        'pot4' => $v['pot4'],
                        'pot5' => $v['pot5'],
                        'pot6' => $v['pot6'],
                        'pot7' => $v['pot7'],
                    )
                );
                $return_result = 1;// 默认扣费成功
                // 扣费
                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }
                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }
                $game_result_text = lang('greedy_pot0') . ":" . $v['pot0'] . ";";
                $game_result_text .= lang('greedy_pot1') . ":" . $v['pot1'] . ";";
                $game_result_text .= lang('greedy_pot2') . ":" . $v['pot2'] . ";";
                $game_result_text .= lang('greedy_pot3') . ":" . $v['pot3'] . ";";
                $game_result_text .= lang('greedy_pot4') . ":" . $v['pot4'] . ";";
                $game_result_text .= lang('greedy_pot5') . ":" . $v['pot5'] . ";";
                $game_result_text .= lang('greedy_pot6') . ":" . $v['pot6'] . ";";
                $game_result_text .= lang('greedy_pot7') . ":" . $v['pot7'] . ";";
                if ($game_result['winner'] == 8 || $game_result['winner'] == 9) {
                    $game_result_text .= lang('game_winner_result') . ":" . $game_result['winnerName'];
                } else {
                    $game_result_text .= lang('game_winner_result') . ":" . lang('greedy_' . $game_result['winnerName']);
                }

                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }
            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * fruitLoops: gameType = fruitLoops
     * $insert 插入数据 ---- 处理结果 red红区总消费值 blue 蓝区总消费值 green绿区总消费值 winner胜利1:红,2蓝,3绿
     * $userList扣费或奖励
     */
    private function add_fruitLoops($insert, $userList, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        if ($game_result) {
            $Processing_result = "mango:" . $game_result['mango'] . ";";
            $Processing_result .= "litchi:" . $game_result['litchi'] . ";";
            $Processing_result .= "watermelon:" . $game_result['watermelon'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $game_result['winnerName'];
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $m_config = load_cache("config");//参数
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);
                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'mango' => $v['mango'],
                        'litchi' => $v['litchi'],
                        'watermelon' => $v['watermelon'],
                    )
                );
                $return_result = 1;// 默认扣费成功
                // 扣费
                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }
                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }
                $game_result_text = "mango:" . $v['mango'] . ";";
                $game_result_text .= "litchi:" . $v['litchi'] . ";";
                $game_result_text .= "watermelon:" . $v['watermelon'] . ";";
                $game_result_text .= lang('game_winner_result') . ":" . $game_result['winnerName'];

                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }
            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * luck99: gameType = luck99
     * $userList扣费或奖励
     */
    private function add_lucky99($insert, $userList, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        if ($game_result) {
            $Processing_result = "apple:" . $game_result['apple'] . ";";
            $Processing_result .= "nine:" . $game_result['nine'] . ";";
            $Processing_result .= "lemon:" . $game_result['lemon'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $game_result['winnerName'];
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $m_config = load_cache("config");//参数
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);
                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'apple' => $v['apple'],
                        'nine' => $v['nine'],
                        'lemon' => $v['lemon'],
                    )
                );
                $return_result = 1;// 默认扣费成功
                // 扣费
                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }
                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }
                $game_result_text = "apple:" . $v['apple'] . ";";
                $game_result_text .= "nine:" . $v['nine'] . ";";
                $game_result_text .= "lemon:" . $v['lemon'] . ";";
                $game_result_text .= lang('game_winner_result') . ":" . $game_result['winnerName'];

                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }
            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * luck77: gameType = luck77
     * $insert 插入数据 ---- 处理结果 red红区总消费值 blue 蓝区总消费值 green绿区总消费值 winner胜利1:红,2蓝,3绿
     * $userList扣费或奖励
     */
    private function add_lucky77($insert, $userList, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        if ($game_result) {
            $Processing_result = "apple:" . $game_result['apple'] . ";";
            $Processing_result .= "seven:" . $game_result['seven'] . ";";
            $Processing_result .= "watermelon:" . $game_result['watermelon'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $game_result['winnerName'];
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $m_config = load_cache("config");//参数
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);
                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'apple' => $v['apple'],
                        'seven' => $v['seven'],
                        'watermelon' => $v['watermelon'],
                    )
                );
                $return_result = 1;// 默认扣费成功
                // 扣费
                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }
                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }
                $game_result_text = "apple:" . $v['apple'] . ";";
                $game_result_text .= "seven:" . $v['seven'] . ";";
                $game_result_text .= "watermelon:" . $v['watermelon'] . ";";
                $game_result_text .= lang('game_winner_result') . ":" . $game_result['winnerName'];

                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }
            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * 三色椅子:gameType = kingdoms
     * 拖拉机:gameType = kingdoms2
     * $insert 插入数据 ---- 处理结果 red红区总消费值 blue 蓝区总消费值 green绿区总消费值 winner胜利1:红,2蓝,3绿
     * $userList扣费或奖励
     *
     */
    private function add_kingdoms($insert, $userList, $gameType)
    {
        $game_result = $insert['game_result'] ? json_decode($insert['game_result'], true) : array();
        $winner_result = array('1' => 'red', '2' => 'blue', '3' => 'green');
        $winner = 1;
        if ($game_result) {
            $winner = $game_result['winner'];
            $Processing_result = lang('game_red_consumption') . ":" . $game_result['red'] . ";";
            $Processing_result .= lang('game_blue_consumption') . ":" . $game_result['blue'] . ";";
            $Processing_result .= lang('game_green_consumption') . ":" . $game_result['green'] . ";";
            $Processing_result .= lang('game_winner_result') . ":" . $winner_result[$winner];
            $insert['game_result_text'] = $Processing_result;
        }
        // 本轮信息记录
        $id = $this->TripartiteGameLog->insert_one($insert);

        if ($userList) {
            $bet_insert = array();
            $consumption_add = array();
            $user_consume_log = array();
            $user_bet = array(
                'room_id' => $insert['room_id'],
                'tripartite_game_log_id' => $id,
                'game_type' => $insert['game_type'],
                'game_order_id' => $insert['game_order_id'],
                'consumption_coin' => 0,
                'total_income' => 0,
                'game_result' => '',
                'game_result_text' => '',
                'create_time' => $insert['create_time'],
                'create_time_y' => $insert['create_time_y'],
                'create_time_m' => $insert['create_time_m'],
                'create_time_d' => $insert['create_time_d'],
            );
            $content = "game:" . $gameType;
            $im_user = [];
            $m_config = load_cache("config");//参数
            $saveAll = [];
            $coin_log = [];
            foreach ($userList as $v) {
                $consumptionCoin = intval($v['consumptionCoin']);
                $rewardCoin = intval($v['rewardCoin']);
                $deductCoin = intval($v['deductCoin']);
                $user_bet['uid'] = $v['uid'];
                $user_bet['consumption_coin'] = $consumptionCoin;
                $user_bet['total_income'] = $rewardCoin;
                $user_bet['game_result'] = json_encode(
                    array(
                        'red' => $v['red'],
                        'blue' => $v['blue'],
                        'green' => $v['green'],
                    )
                );
                $return_result = 1;// 默认扣费成功
                // 获取用户信息
                $userInfo = Db::name("user")->field("id,user_nickname as nick_name,coin")->where("id='" . $v['uid'] . "'")->find();
                // 处理账号余额
                $saveAllVal = $this->save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin);
                if ($saveAllVal['id']) {
                    $saveAll[] = $saveAllVal;
                    $coin_val = $consumptionCoin - $deductCoin;
                    if ($coin_val) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => '-' . $coin_val,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                    if ($rewardCoin) {
                        $coin_log[] = [
                            'uid' => $v['uid'],
                            'coin' => $rewardCoin,
                            'coin_type' => 1,
                            'type' => 18,
                            'balance' => $userInfo['coin'],
                            'create_time' => NOW_TIME,
                            'notes' => $content
                        ];
                    }
                } else {
                    // 扣费失败处理
                    bogokjLogPrint("EndGameDeductionFailed", "用户余额 = " . $userInfo['coin'] . " ; 没有扣费=" . json_encode($v) . ";");
                }
                if ($rewardCoin > $consumptionCoin) {
                    // 中奖发送im消息
                    $im_user[] = array(
                        'uid' => $v['uid'],
                        'nick_name' => emoji_decode($userInfo['nick_name']),
                        'coin' => $rewardCoin,
                    );
                }
                $game_result_text = lang('game_red_consumption') . ":" . $v['red'] . ";";
                $game_result_text .= lang('game_blue_consumption') . ":" . $v['blue'] . ";";
                $game_result_text .= lang('game_green_consumption') . ":" . $v['green'] . ";";
                $game_result_text .= lang('game_winner_result') . ":" . $winner_result[$winner];
                $user_bet['game_result_text'] = $game_result_text;
                $user_bet['exit_deduction_coin'] = $deductCoin;
                $user_bet['status'] = $return_result == 1 ? 1 : 2;
                $bet_insert[] = $user_bet;
                if ($return_result == 1) {
                    // 处理用户消费或收益
                    $coin = $rewardCoin - $consumptionCoin > 0 ? $rewardCoin - $consumptionCoin : $consumptionCoin - $rewardCoin;
                    $consumption_add[] = array(
                        'uid' => $v['uid'],
                        'center' => $content,
                        'type' => 1,
                        'genre' => $rewardCoin - $consumptionCoin > 0 ? 1 : 2,
                        'buy_type' => 21,
                        'coin' => $coin,
                        'addtime' => $insert['create_time']
                    );
                    $user_consume_content = $rewardCoin > 0 ? $content . " + " . $rewardCoin : $content;
                    $user_consume_log[] = array(
                        'user_id' => $v['uid'],
                        'to_user_id' => $v['uid'],
                        'coin' => $consumptionCoin - $deductCoin,
                        'table_id' => 0,
                        'type' => 28,
                        'create_time' => $insert['create_time'],
                        'host_coin' => $rewardCoin,
                        'status' => 1,
                        'content' => $user_consume_content,
                    );
                }
            }
            $this->extracted($saveAll, $consumption_add, $im_user, $gameType, $user_consume_log, $bet_insert, $coin_log);
        }
    }

    /**
     * im消息
     * @param $uid
     */
    public function im_send_massage($im_user, $game_id)
    {
        $status = false;
        $tripartite_game = db('tripartite_game')->field("*")->where("status = 1 and type='" . $game_id . "'")->find();
        if ($tripartite_game) {
            $isLandscape = $tripartite_game['is_landscape'] == 1 ? 'true' : 'false';
            $url = $tripartite_game['domain_name'] . "?isLandscape=" . $isLandscape;
            if ($tripartite_game['merchant']) {
                $url .= "&merchant=" . $tripartite_game['merchant'];
            }
            if ($tripartite_game['game_name']) {
                $url .= "&gameName=" . $tripartite_game['game_name'];
            }

            $m_config = load_cache("config");//参数
            $system_user_id = $m_config['acquire_group_id'];// im大群
            $system_admin_id = $m_config['create_group_idf'];// im大群
            $ext = array();
            $ext['type'] = 770;
            $ext['user_list'] = $im_user;
            $ext['game_id'] = $tripartite_game['id'];
            $ext['game_name'] = $tripartite_game['title'];
            $ext['game_type'] = $tripartite_game['type'];
            $ext['game_img'] = $tripartite_game['icon'];
            $ext['winning_time'] = NOW_TIME;
            $ext['game_url'] = $url;
            // **** 在《游戏名称》赢得1000豆
            $ext['text'] = "";
            #构造高级接口所需参数
            $msg_content = array();
            //创建array 所需元素
            $msg_content_elem = array(
                'MsgType' => 'TIMCustomElem',       //自定义类型
                'MsgContent' => array(
                    'Data' => json_encode($ext),
                    'Desc' => '',
                )
            );
            sleep(8);
            //将创建的元素$msg_content_elem, 加入array $msg_content
            array_push($msg_content, $msg_content_elem);
            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();
            $ret = $api->group_send_group_msg2(strval($system_admin_id), $system_user_id, $msg_content);
            $status = $ret['ActionStatus'] == 'OK' ? true : false;

        }
        return $status;
    }

    /**
     * 返回接口信息
     * @param array $data
     * @return \think\response\Json
     */
    protected function bogokjReturnJsonData(array $data)
    {
        return json($data);
        exit;
    }

    /**
     * 加减金处理
     * */
    public function save_all_coin($userInfo, $consumptionCoin, $deductCoin, $rewardCoin, $refundCoin = 0)
    {
        $saveAll = array(
            'id' => 0,
            'coin' => 0
        );
        // 扣费
        $is_save_status = 1;
        $sum = 0;
        if ($consumptionCoin > $deductCoin) {
            $sum = $consumptionCoin - $deductCoin;
            if ($sum > 0) {
                if ($userInfo['coin'] >= $sum) {
                    $saveAll = array(
                        'id' => $userInfo['id'],
                        'coin' => Db::raw('coin-' . $sum),
                    );
                } else {
                    $is_save_status = 0;
                }
            }
        }
        if ($is_save_status == 0) {
            $users = array(
                'consumptionCoin' => $consumptionCoin,
                'deductCoin' => $deductCoin,
                'rewardCoin' => $rewardCoin,
                'user' => $userInfo
            );
            // 获取当时游戏请求的余额
            $game_user_balance = redis_get("game_user_balance_" . $userInfo['id']);
            // 扣费失败处理
            bogokjLogPrint("EndGameDeductionFailed", "扣费余额不足=" . json_encode($users) . "; 游戏请求时的余额=" . $game_user_balance);
        }
        bogokjLogPrint("EndGame", " is_save_status=" . $is_save_status);
        if ($rewardCoin > 0 && $is_save_status == 1) {
            $diamonds = $rewardCoin - $sum + $refundCoin;
            $saveAll['coin'] = Db::raw('coin+' . $diamonds);
            $saveAll['id'] = $userInfo['id'];
        }
        return $saveAll;
    }

    /**
     * @param array $saveAll
     * @param array $consumption_add
     * @param array $im_user
     * @param       $gameType
     * @param array $user_consume_log
     * @param array $bet_insert
     * @return void
     */
    private function extracted(array $saveAll, array $consumption_add, array $im_user, $gameType, array $user_consume_log, array $bet_insert, array $coin_log)
    {
        // 启动事务
        db()->startTrans();
        try {
            if (count($saveAll)) {
                $this->User->save_all($saveAll);
            }
            if (count($consumption_add)) {
                // 添加消费记录
                $this->User->add_user_log_all($consumption_add);
                // 添加消费记录
                $this->User->add_user_consume_log_all($user_consume_log);
            }
            if (count($bet_insert)) {
                // 用户下注记录
                $this->TripartiteGameUserLog->insert_all($bet_insert);
            }
            if (count($coin_log)) {
                // 插入用户钻石日志
                save_coin_all_log($coin_log);
            }
            db()->commit();      // 提交事务
            if (count($consumption_add)) {
                if (count($im_user)) {
                    $this->im_send_massage($im_user, $gameType);
                }
            }
        } catch (\Exception $e) {
            $data = array(
                'Exception_e' => $e,
                'saveAll' => $saveAll,
                'consumption_add' => $consumption_add,
                'user_consume_log' => $user_consume_log,
                'bet_insert' => $bet_insert,
                'coin_log' => $coin_log
            );
            bogokjLogPrint("EndGame_extracted", $data);
            db()->rollback();    // 回滚事务
        }
    }
}