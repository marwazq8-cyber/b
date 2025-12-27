<?php

namespace app\common\model;

use app\common\exception\ApiException;
use think\Model;

class UserConsumeLog extends Model
{
    const TYPE_JOY_GAME = 33;
    const TYPE_LX_GAME = 34;

    public function userConsumeLog($userId, $toUserId, $type, $data)
    {
        $data['user_id'] = $userId;
        $data['to_user_id'] = $toUserId;
        $data['type'] = $type;
        $data['status'] = 1;
        $data['create_time'] = time();

        $userConsumeLog = new UserConsumeLog();
        if (!$userConsumeLog->save($data)) {
            throw new ApiException('系统错误！', 200002);
        }
    }

}