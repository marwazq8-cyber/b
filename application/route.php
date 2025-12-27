<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

Route::rule('/game_lx/getUserInfo', 'game/LXGameApi/getUserInfo');
Route::rule('/game_lx/submitFlow', 'game/LXGameApi/submitFlow');

Route::group('v2/api', function () {
    Route::get('GetAllMedals', 'api/V2ApiController/GetAllMedals');
    Route::post('CreateFamily', 'api/FamilyController/CreateFamily');
    Route::get('GetFamilyByUserID','api/FamilyController/GetFamilyByUserID');
    Route::get('GetFamilyByFamilyId','api/FamilyController/GetFamilyByFamilyId');
    Route::get('GetAllFamiles','api/FamilyController/GetAllFamiles');
    Route::Post('RequestToJoinToFamily','api/FamilyController/RequestToJoinToFamily');
    Route::Post('LeaveFamily','api/FamilyController/LeaveFamily');
    Route::get('ListofUserRequestToJoin','api/FamilyController/ListofUserRequestToJoin');
    Route::get('AddToMedalList', 'api/V2ApiController/AddToMedalList');
    Route::get('DeletFromMyList', 'api/V2ApiController/DeletFromMyList');
    Route::get('VoiceTrace/JoinToRoom', 'api/VoiceDataController/JoinToRoom');
    Route::get('VoiceTrace/GetVoiceUserRooms', 'api/VoiceDataController/GetVoiceUserRooms');
    
});
return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

    // favicon.ico路由规则
    'favicon.ico' => function () {
        // 这里不需要做任何操作
    },

];
