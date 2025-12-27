<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:52:"themes/admin_simpleboot3/user/admin_index/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <!-- Set render engine for 360 browser -->
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->


    <link href="/themes/admin_simpleboot3/public/assets/themes/<?php echo cmf_get_admin_style(); ?>/bootstrap.min.css" rel="stylesheet">
    <link href="/themes/admin_simpleboot3/public/assets/simpleboot3/css/simplebootadmin.css" rel="stylesheet">
    <link href="/static/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <style>
        form .input-order {
            margin-bottom: 0px;
            padding: 0 2px;
            width: 42px;
            font-size: 12px;
        }

        form .input-order:focus {
            outline: none;
        }

        .table-actions {
            margin-top: 5px;
            margin-bottom: 5px;
            padding: 0px;
        }

        .table-list {
            margin-bottom: 0px;
        }

        .form-required {
            color: red;
        }
        .table{margin-top: 20px;}
    </style>
    <script type="text/javascript">
        //全局变量
        var GV = {
            ROOT: "/",
            WEB_ROOT: "/",
            JS_ROOT: "static/js/",
            APP: '<?php echo \think\Request::instance()->module(); ?>'/*当前应用名*/
        };
    </script>
    <script src="/themes/admin_simpleboot3/public/assets/js/jquery-1.10.2.min.js"></script>
    <script src="/static/js/layer/layer.js" rel="stylesheet"></script>
    <script src="/static/js/wind.js"></script>
    <script src="/themes/admin_simpleboot3/public/assets/js/bootstrap.min.js"></script>
    <script>
        Wind.css('artDialog');
        Wind.css('layer');
        $(function () {
            $("[data-toggle='tooltip']").tooltip();
            $("li.dropdown").hover(function () {
                $(this).addClass("open");
            }, function () {
                $(this).removeClass("open");
            });
        });
    </script>
    <?php if(APP_DEBUG): ?>
        <style>
            #think_page_trace_open {
                z-index: 9999;
            }
        </style>
    <?php endif; ?>
<style>

    #sex,#is_online,#reference,.user_status,#order,#is_exchange{
        width: 80px;
        height: 32px;
        border-color: #dce4ec;
        color: #aeb5bb;}

    .gift-in input{width:25px;}
    .user_details_type{float:left;width:50%;text-align: left;}

    .btn-info:hover,.btn-info:click{
        outline:0!important;
        color:#2a6496!important;
        background: #ecf0f1!important;
        border-color: #ecf0f1!important;

    }
    .btn-info:focus{
        outline:0!important;
        box-shadow:none;
    }

    tr:hover .btn-info{
        color:#2a6496!important;
        background: #ecf0f1!important;
        border-color: #ecf0f1!important;
    }

    .btn-info{
        color:#2a6496!important;
        background: #fff!important;
        border-color: #fff!important;
    }
    #ul{
        font-size: 12px;
        list-style:none;
        min-width:120px;
        border:1px solid #c1c1c1;
        display:none;
        padding-left:0px;
        position: absolute;
        right:60px;
        background-color: #ffffff
    }
    .ipt{
        /*margin:1px 0 0 0px;*/
        /*border:0px solid;*/
    }
   #ul li a{
        display:inline-block;
        float:left;
        width:100%;
        height:30px;
        line-height: 30px;
        text-decoration:none;

        color: #333;
    }
   #ul li{
        margin-left: 0;
        padding-right: 15px;
        width:100%;height: 30px;
         padding-left:15px;

    }

   #ul li:hover{
        background-color:#f5f5f5;
    }
    #ul li a:hover{
        color:#333;
    }
    .ipt::-ms-input-placeholder {
        text-align: center;
    }

    .ipt::-webkit-input-placeholder {
        text-align: center;

    }


</style>
</head>
<body>
<div class="wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a><?php echo lang('USER_INDEXADMIN_INDEX'); ?></a></li>
        <li ><a href="<?php echo url('user/AdminIndex/add_user'); ?>"><?php echo lang("添加用户"); ?></a></li>
    </ul>

    <form class="well form-inline margin-top-20" name="form1" method="post">
        <?php echo lang('USER_ID'); ?>：
        <input class="form-control" type="text" name="uid" style="width: 100px;" value="<?php echo input('request.uid'); ?>"
               placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('ADMIN_KEYWORDS'); ?>：
        <input class="form-control" type="text" name="keyword" style="width: 120px;" value="<?php echo input('request.keyword'); ?>"
               placeholder="<?php echo lang('用户名/昵称/邮箱/手机号'); ?>">
        <?php echo lang('用户状态'); ?>：
        <select name="user_type" class="user_status">
            <option value="0"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($request['user_type'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang('后台账户'); ?></option>
            <option value="2" <?php if($request['user_type'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang('普通会员'); ?></option>
            <option value="3" <?php if($request['user_type'] == 3): ?> selected="selected" <?php endif; ?>><?php echo lang('注销用户'); ?></option>
        </select>
        <?php echo lang('ADMIN_ACCOUNT_STATUS'); ?>：
        <select name="user_status" class="user_status">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="0" <?php if($request['user_status'] == '0'): ?> selected="selected" <?php endif; ?>><?php echo lang("禁用"); ?></option>
            <option value="1" <?php if($request['user_status'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang("正常"); ?></option>
        </select>
        <?php echo lang('ADMIN_ONLINE_STATUS'); ?>：
        <select name="is_online" id="is_online">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($request['is_online'] == '1'): ?> selected="selected" <?php endif; ?>><?php echo lang("在线"); ?></option>
            <option value="0" <?php if($request['is_online'] == '0'): ?> selected="selected" <?php endif; ?>><?php echo lang("离线"); ?></option>
        </select>
        <?php echo lang('GENDER'); ?>：
        <select name="sex" id="sex">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($request['sex'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang('MALE'); ?></option>
            <option value="2" <?php if($request['sex'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang('FEMALE'); ?></option>
        </select>

        <?php echo lang('SORT'); ?>：
        <select name="order" id="order">
            <option value="-1"><?php echo lang("默认"); ?></option>
            <option value="1" <?php if($request['order'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang('ADMIN_INCOME'); ?></option>
            <option value="2" <?php if($request['order'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang("余额"); ?></option>
            <option value="3" <?php if($request['order'] == 3): ?> selected="selected" <?php endif; ?>><?php echo lang("级别"); ?></option>
        </select>

        <?php echo lang('ADMIN_RECOMMEND_STATUS'); ?>：
        <select name="reference" id="reference" style="margin-top:10px;">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($request['reference'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang("推荐"); ?></option>
            <option value="0" <?php if($request['reference'] == '0'): ?> selected="selected" <?php endif; ?>><?php echo lang("未推荐"); ?></option>
        </select>
        <?php echo lang('好友兑换'); ?>：
        <select name="is_exchange" id="is_exchange" style="margin-top:10px;">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($request['is_exchange'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang("开启"); ?></option>
            <option value="0" <?php if($request['is_exchange'] == '0'): ?> selected="selected" <?php endif; ?>><?php echo lang("关闭"); ?></option>
        </select>
        <br/>
        <?php echo lang('ADMIN_AUTH_STATUS'); ?>：
        <select name="is_auth" id="is_auth" style="margin-top:10px;width: 100px;height: 32px;border-color: #dce4ec;color: #aeb5bb;">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="0" <?php if($request['is_auth'] == '0'): ?> selected="selected" <?php endif; ?>><?php echo lang("未认证"); ?></option>
            <option value="1" <?php if($request['is_auth'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang("已认证"); ?></option>
        </select>
        <?php echo lang('country'); ?>：
        <select name="country_code" class="user_status">
            <option value="0"><?php echo lang('ALL'); ?></option>
            <?php if(is_array($countries) || $countries instanceof \think\Collection || $countries instanceof \think\Paginator): if( count($countries)==0 ) : echo "" ;else: foreach($countries as $key=>$c): ?>
                <option value="<?php echo $c['num_code']; ?>" <?php if($request['country_code'] == $c['num_code']): ?> selected="selected" <?php endif; ?>><?php echo $c['en_short_name']; ?></option>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </select>
        <?php echo lang('REGISTRATION_TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-datetime" name="start_time" value="<?php echo input('request.start_time'); ?>" style="width: 130px;" autocomplete="off">-
        <input type="text" class="form-control js-bootstrap-datetime" name="end_time" value="<?php echo input('request.end_time'); ?>" style="width: 130px;" autocomplete="off"> &nbsp; &nbsp;
        <?php echo lang('LOGIN_TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-datetime" name="start_time2" value="<?php echo input('request.start_time2'); ?>" style="width: 130px;" autocomplete="off">-
        <input type="text" class="form-control js-bootstrap-datetime" name="end_time2" value="<?php echo input('request.end_time2'); ?>" style="width: 130px;" autocomplete="off"> &nbsp; &nbsp;
        <?php echo lang('Registered_equipment_number'); ?>：
        <input class="form-control" type="text" name="device_uuid" style="width: 200px;" value="<?php echo input('request.device_uuid'); ?>"
               placeholder="<?php echo lang('Registered_equipment_number'); ?>">

        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" onclick='form1.action="<?php echo url('user/adminIndex/index'); ?>";form1.submit();'/>
        <a class="btn btn-danger" href="<?php echo url('user/adminIndex/index'); ?>"><?php echo lang('EMPTY'); ?></a>
        <input type="button" class="btn btn-primary from_export" style="background-color: #1dccaa;" value="<?php echo lang('导出'); ?>" onclick='form1.action="<?php echo url('user/adminIndex/export'); ?>";form1.submit();'>


    </form>
    <form method="post" action="<?php echo url('adminIndex/upd'); ?>" class="js-ajax-form1">
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th>ID</th>
                <th><?php echo lang('ADMIN_LUCK'); ?></th>
                <th><?php echo lang('ADMIN_ACCOUNT'); ?></th>
                <th><?php echo lang('NICENAME'); ?></th>
                <th><?php echo lang('GENDER'); ?></th>
                <th><?php echo lang('Grade'); ?></th>
                <th><?php echo lang('AVATAR'); ?></th>
                <th><?php echo lang('MOBILE'); ?></th>
                <th><?php echo lang('country'); ?></th>

                <th><?php echo lang('email'); ?></th>
              <!--   <th><?php echo lang("设备号"); ?></th> -->
                <th><?php echo lang('STATUS'); ?></th>
<!--                <th><?php echo lang("是否推荐"); ?></th>-->
                <th><?php echo lang('ADMIN_ONLINE_STATUS'); ?></th>
<!--                <th><?php echo lang('ADMIN_AUTH'); ?></th>-->
                <th><?php echo lang("邀请人"); ?></th>
<!--                <th>VIP</th>-->
                <th><?php echo lang('贵族'); ?></th>

                <?php if(IS_AGENT == 1): ?>
                    <th><?php echo lang('ADMIN_CHANNEL_ID'); ?></th>
                <?php endif; ?>
<!--                <th><?php echo lang('ADMIN_PAY_RATE'); ?></th>-->
                <th><?php echo $currency_name; ?></th>
                <th><?php echo $system_currency_name; ?></th>
                <th><?php echo lang('ADMIN_INCOME'); ?></th>
                <th><?php echo lang('ADMIN_LOGIN_TYPE'); ?></th>
<!--                <th><?php echo lang('REGISTRATION_TIME'); ?></th>-->
<!--                <th><?php echo lang('LAST_LOGIN_TIME'); ?></th>-->
                <th><?php echo lang('LAST_LOGIN_IP'); ?></th>
                <th><?php echo lang('用户状态'); ?></th>
                <th align="center"><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php 

                $is_online=array("0"=>'lang("ADMIN_LINE")',"1"=>lang('在线'));
                $is_auth=array('0'=>lang('未认证'),"1"=>lang('认证'));
                $reference=array('0'=>lang('否'),"1"=>lang('是'));
             if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
<!--                    <td class="gift-in"><input type="text" name="listorders[<?php echo $vo['id']; ?>]" value="<?php echo $vo['sort']; ?>"></td>-->
                    <td><?php echo $vo['id']; ?></td>
                    <td><?php echo !empty($vo['luck'])?$vo['luck'] : ''; ?></td>
                    <?php if(IS_TEST == 1): ?><td><?php echo lang("测试模式，敏感数据不予显示！"); ?></td><?php else: ?><td><?php echo !empty($vo['user_login'])?$vo['user_login']:($vo['mobile']?$vo['mobile']:lang('THIRD_PARTY_USER')); ?></td><?php endif; ?>

                    <td><?php echo !empty($vo['user_nickname'])?$vo['user_nickname']:lang('NOT_FILLED'); ?></td>
                    <td><?php echo $vo['sex']==2?lang('女') : lang('男'); ?></td>
                    <td><?php echo (isset($vo['level'] ) && ($vo['level']  !== '')?$vo['level'] :"1"); ?></td>

                    <td class="head_img" data-img='<img  src="<?php echo url('user/public/avatar',array('id'=>$vo['id'])); ?>" style="max-width:100%;max-height:700px;"/>'><img width="25" height="25" src="<?php echo url('user/public/avatar',array('id'=>$vo['id'])); ?>"/></td>
                 <!--    <td><a href="<?php echo url('admin/audit/index'); ?>?uid=<?php echo $vo['id']; ?>"><?php echo lang("详情"); ?></a></td>
 -->

                    <?php if(IS_TEST == 1): ?><td><?php echo lang("测试模式，敏感数据不予显示！"); ?></td><?php else: ?><td>(<?php echo (isset($vo['mobile_area_code'] ) && ($vo['mobile_area_code']  !== '')?$vo['mobile_area_code'] :""); ?>)<?php echo $vo['mobile']; ?></td><?php endif; ?>
                    <td><?php echo (isset($vo['country_name'] ) && ($vo['country_name']  !== '')?$vo['country_name'] :""); ?></td>
                    <td><?php echo (isset($vo['user_email'] ) && ($vo['user_email']  !== '')?$vo['user_email'] :""); ?></td>
                 <!--    <?php if(IS_TEST == 1): ?><td><?php echo lang("测试模式，敏感数据不予显示！"); ?></td><?php else: ?><td><?php echo $vo['device_uuid']; ?></td><?php endif; ?> -->

                    <td>
                        <?php if($vo['user_status'] !='0'): ?>
                            <?php echo lang('USER_STATUS_ACTIVATED'); else: ?>
                            <?php echo lang('BLOCK_USER'); endif; ?>
                    </td>
<!--                    <td>-->
<!--                        <?php echo $reference[$vo['reference']]; ?>-->
<!--                    </td>-->
                    <td>
                        <?php if($vo['is_online'] == 1): ?>
                            <span style="color:#18BC9C;"><?php echo lang("ADMIN_ONLINE"); ?></span>
                            <?php else: ?>
                            <span style="color:#f84444;"><?php echo lang("ADMIN_LINE"); ?></span>
                        <?php endif; ?>
                    </td>
<!--                    <td><?php echo $is_auth[$vo['is_auth']]; ?></td>-->

                    <td>
                        <?php if($vo['invite_user_id']): ?>
                                <?php echo $vo['invite_user_name']; ?>(<?php echo $vo['invite_user_id']; ?>)
                            <?php else: ?>
                            <?php echo lang("无"); endif; ?>
                    </td>
<!--                    <td>-->
<!--                        <?php if($vo['vip_name']): ?>-->
<!--                            <?php echo $vo['vip_name']; ?>:<?php echo $vo['vip_end_time']; ?>-->
<!--                            <?php else: ?>-->
<!--                            <?php echo lang("无"); ?>-->
<!--                        <?php endif; ?>-->
<!--                    </td>-->

                    <td>
                        <?php if($vo['noble_end_time']): ?>
                            <?php echo $vo['noble_name']; ?>:<?php echo $vo['noble_end_time']; ?>
                            <div onclick="nobleSet(<?php echo $vo['id']; ?>)" style="cursor: pointer;color: #4885cd;">取消</div>
                            <?php else: ?>
                            <?php echo lang("无"); endif; ?>
                    </td>

                    <?php if(IS_AGENT == 1): ?>
                        <td>
                            <a style="text-decoration:none;"  href="javascript:void(0);"  onclick="link_set(<?php echo $vo['id']; ?>,'<?php echo $vo['link_id']; ?>')">
                                <?php if($vo['link_id']): ?>
                                    <?php echo $vo['link_id']; else: ?>
                                    <?php echo lang("无"); endif; ?>
                            </a>
                        </td>
                    <?php endif; ?>
<!--                    <td><?php echo $vo['recharge_probability']; ?>%</td>-->
                    <td><?php echo $vo['coin']; ?></td>
                    <td><?php echo $vo['friend_coin']; ?></td>
                    <td><?php echo (isset($vo['income']) && ($vo['income'] !== '')?$vo['income']:0); ?></td>


                    <td>
                        <?php if($vo['login_way'] == 1): ?>
                            <?php echo lang('手机'); elseif($vo['login_way'] == 2): ?>
                            QQ
                            <?php elseif($vo['login_way'] == 4): ?>
                            Emai Login
                            <?php elseif($vo['login_way'] == 5): ?>
                            Google Login
                            <?php elseif($vo['login_way'] == 6): ?>
                            Facebook Login
                            <?php else: ?>
                            <?php echo lang('WECHAT'); endif; ?>
                    </td>
<!--                    <td><?php echo date('Y-m-d H:i:s',$vo['create_time']); ?></td>-->
<!--                    <td><?php echo date('Y-m-d H:i:s',$vo['last_login_time']); ?></td>-->
                    <td><?php echo $vo['last_login_ip']; ?></td>
                <td>
                    <?php if($vo['user_type'] == 3): ?>
                        <?php echo lang('注销用户'); else: ?>
                        <?php echo $vo['user_type']==1?lang('后台账户') : lang('普通会员'); endif; ?>
                </td>
                    <td>
                        <button class="ipt btn btn-info" type="button"><?php echo lang('ACTIONS'); ?> <strong>+</strong></button>
                        <!--<input class="ipt" type="button"  placeholder="<?php echo lang('操作'); ?>"/>-->
                        <ul id="ul">
                            <li><a style="text-decoration:none;"  href="<?php echo url('admin_index/edit',array('id'=>$vo['id'])); ?>"><?php echo lang("编辑资料"); ?></a></li>
                            <?php if($vo['is_test'] == 1): ?>
                                <li><a style="text-decoration:none;" href="javascript:void(0);" onclick="set_test('<?php echo $vo['id']; ?>',2)"><?php echo lang("取消测试号"); ?></a></li>
                                <?php else: ?>
                                <li><a style="text-decoration:none;" href="javascript:void(0);"  onclick="set_test('<?php echo $vo['id']; ?>',1)"><?php echo lang("设为测试号"); ?></a></li>
                            <?php endif; if($vo['is_exchange'] == 1): ?>
                                <li><a style="text-decoration:none;" href="javascript:void(0);" onclick="set_exchange('<?php echo $vo['id']; ?>',0)"><?php echo lang("关闭好友兑换"); ?></a></li>
                                <?php else: ?>
                                <li><a style="text-decoration:none;" href="javascript:void(0);" onclick="set_exchange('<?php echo $vo['id']; ?>',1)"><?php echo lang("开启好友兑换"); ?></a></li>
                            <?php endif; if($vo['id'] != '1'): if(empty($vo['user_status']) || (($vo['user_status'] instanceof \think\Collection || $vo['user_status'] instanceof \think\Paginator ) && $vo['user_status']->isEmpty())): ?>
                                    <li>
                                        <a  style="text-decoration:none;" class="js-ajax-dialog-btn" href="<?php echo url('adminIndex/cancelban',array('id'=>$vo['id'])); ?>" data-msg="<?php echo lang('ACTIVATE_USER_CONFIRM_MESSAGE'); ?>"><?php echo lang('ACTIVATE_USER'); ?></a>
                                    </li>
                                    <?php else: ?>
                                    <li>
                                        <a style="text-decoration:none;" href="javascript:void(0);"  class="ban_type_btn" data-uid="<?php echo $vo['id']; ?>"><?php echo lang('BLOCK_USER'); ?></a>
                                    </li>
                                <?php endif; else: ?>
                                <li>
                                    <a style="color: #ccc;text-decoration:none;"><?php echo lang('BLOCK_USER'); ?></a>
                                </li>
                            <?php endif; if($vo['id'] != 1): if($vo['reference'] == 0): ?>

                                    <li>
                                        <a style="text-decoration:none;"  href="<?php echo url('adminIndex/reference',array('id'=>$vo['id'],'type'=>1)); ?>"><?php echo lang("推荐"); ?></a>
                                    </li>

                                    <?php else: ?>

                                    <li>
                                        <a style="text-decoration:none;"  data-value="<?php echo url('adminIndex/reference',array('id'=>$vo['id'],'type'=>0)); ?>" href="<?php echo url('adminIndex/reference',array('id'=>$vo['id'],'type'=>0)); ?>"><?php echo lang("取消推荐"); ?></a>
                                    </li>
                                <?php endif; endif; if($vo['is_auth'] == 1): ?>
                                <li>
                                    <a style="text-decoration:none;"  href="javascript:void(0);"  onclick="cancel_auth(<?php echo $vo['id']; ?>)"><?php echo lang('ADMIN_AUTH_CANCEL'); ?></a>
                                </li>
                            <?php endif; ?>
                            <!--<li><a style="text-decoration:none;"  href="javascript:void(0);"  onclick="vipset(<?php echo $vo['id']; ?>,'<?php echo $vo['vip_end_time']; ?>')">VIP设置</a></li>-->
                            <li><a style="text-decoration:none;"  href="javascript:void(0);"  onclick="addCoin(<?php echo $vo['id']; ?>)"><?php echo lang('ADMIN_ACCOUNT_MANAGE'); ?></a></li>
                            <!--<li><a style="text-decoration:none;"  href="<?php echo url('admin_index/invitation',array('id'=>$vo['id'])); ?>"><?php echo lang("邀请信息"); ?></a></li> -->

                            <li><a style="text-decoration:none;"  href="javascript:void(0);"  onclick="sel_consumption_records(<?php echo $vo['id']; ?>)"><?php echo lang('钻石记录'); ?></a></li>
                            <li><a style="text-decoration:none;"  href="javascript:void(0);"  onclick="sel_revenue_records(<?php echo $vo['id']; ?>)"><?php echo lang('收入记录'); ?></a></li>

                            <li><a style="text-decoration:none;"  href="<?php echo url('/admin/user/receive_gift_log',array('id'=>$vo['id'])); ?>"><?php echo lang('ADMIN_GIFT_LOG'); ?></a></li>
                            <li><a style="text-decoration:none;"  href="<?php echo url('admin_index/edit_img',array('id'=>$vo['id'])); ?>"><?php echo lang('ADMIN_BAN_AVATAR'); ?></a></li>

                            <li><a style="text-decoration:none;"  href="javascript:void(0);"  class="device_info" data-id="<?php echo $vo['id']; ?>" data-os="<?php echo (isset($vo['device_info']['os']) && ($vo['device_info']['os'] !== '')?$vo['device_info']['os']:''); ?>" data-sdk_version="<?php echo (isset($vo['device_info']['sdk_version']) && ($vo['device_info']['sdk_version'] !== '')?$vo['device_info']['sdk_version']:''); ?>" data-app_version="<?php echo (isset($vo['device_info']['app_version']) && ($vo['device_info']['app_version'] !== '')?$vo['device_info']['app_version']:''); ?>" data-brand="<?php echo (isset($vo['device_info']['brand']) && ($vo['device_info']['brand'] !== '')?$vo['device_info']['brand']:''); ?>" data-model="<?php echo (isset($vo['device_info']['model']) && ($vo['device_info']['model'] !== '')?$vo['device_info']['model']:''); ?>"  data-addtime="<?php echo empty($vo['device_info']['addtime'])?'':date('Y-m-d H:i:s',$vo['device_info']['addtime'])?>" data-device="<?php echo (isset($vo['device_uuid']) && ($vo['device_uuid'] !== '')?$vo['device_uuid']:''); ?>"><?php echo lang('ADMIN_DEVICE_INFO'); ?></a></li>

                            <li><a style="text-decoration:none;"  href="<?php echo url('admin_index/add_closures',array('uid'=>$vo['id'],'device_uuid'=>$vo['device_uuid'])); ?>"> <?php if($vo['is_device'] == 1): ?><?php echo lang('ADMIN_BAN_DEVICE'); else: ?><?php echo lang('ADMIN_BAN_DEVICE_CANCEL'); endif; ?></a></li>
                            <li><a style="text-decoration:none;" href="javascript:void(0);"  onclick="clearCoin(<?php echo $vo['id']; ?>)"><?php echo lang('账户一键清空'); ?></a></li>
                             <li><a style="text-decoration:none;"  href="javascript:void(0);"  class="user_details"
                                    data-create_time="<?php echo date('Y-m-d H:i:s',$vo['create_time']); ?>" data-last_login_time="<?php echo date('Y-m-d H:i:s',$vo['last_login_time']); ?>"  data-last_login_ip="<?php echo $vo['last_login_ip']; ?>" data-id="<?php echo $vo['id']; ?>" data-coin="<?php echo $vo['coin']; ?>" data-custom="<?php echo $vo['custom_video_charging_coin']; ?>" data-invite-withdrawal="<?php echo $vo['invite_withdrawal']; ?>" data-invite="<?php echo $vo['invitation_coin']; ?>" data-total="<?php echo $vo['income_total']; ?>" data-income="<?php echo $vo['income']; ?>" data-perfect="<?php echo $vo['is_reg_perfect']; ?>" data-attention="<?php echo $vo['attention']; ?>" data-fans="<?php echo $vo['fans']; ?>" data-money="<?php echo $vo['money']; ?>"  data-vip="<?php echo $vo['vip_end_time']; ?>" data-longitude="<?php echo $vo['longitude']; ?>" data-latitude="<?php echo $vo['latitude']; ?>"><?php echo lang('ADMIN_INFO'); ?></a></li>
                        </ul>

                    </td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tbody>
        </table>
        <div class="pagination"><?php echo $page; ?></div>
    </form>
</div>
<script src="/static/js/admin.js"></script>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<style>
    .ban_type{width:calc(100% - 40px);height:40px;margin:20px 20px 10px;}
    .ban_time{width:40px;height:30px;line-height: 30px;}
    .btn_click{float:left;width:30%;height:40px;line-height: 40px;background-color: #000;color:#fff;text-align: center;margin:0 10%;}
</style>
<script type="text/javascript">
    function sel_revenue_records(uid) {
        layer.open({
            type: 2,
            title:   "<?php echo lang('收入记录'); ?>",
            shadeClose: true,
            area: ['80%', '90%'],   //宽高
            shade: 0.4,   //遮罩透明度
            content:  "<?php echo url('user/adminIndex/revenue_records'); ?>?uid=" + uid
        });
    }
    function sel_consumption_records(uid) {
        layer.open({
            type: 2,
            title:   "<?php echo lang('钻石记录'); ?>",
            shadeClose: true,
            area: ['80%', '90%'],   //宽高
            shade: 0.4,   //遮罩透明度
            content:  "<?php echo url('user/adminIndex/consumption_records'); ?>?uid=" + uid
        });
    }
    function link_set(id,link_id){
        const link_num = link_id > 0 ? link_id : '';
        layer.open({
            type: 0,
            title:   "<?php echo lang('设置渠道绑定'); ?>",
            area: ['300px', '200px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"], //按钮组,
            content: '<div class="layui-form"><label class="layui-form-label"><?php echo lang('ADMIN_CHANNEL_ID'); ?></label><input  type="text" id="link_id" class="form-control" name="vip_end_time" value="'+link_num+'" style="width: 100%;"></div>',
            yes:function(index){   //点击确定回调
                //alert($('#vip_end_time').val());
                layer.close(index);
                $.ajax({
                    url: "<?php echo url('admin_index/linkSet'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {id: id,link_id:$('#link_id').val()},
                    success: function (data) {
                        if(data['code'] == 1){
                            layer.msg(data['msg'],{time: 2000, icon:1},function(){
                                window.location.reload();
                            });
                        }else{
                            layer.msg(data['msg'],{time: 2000, icon:2});
                        }
                    }
                });
            }
        });
    }
      //l拉黑
    $(".ban_type_btn").click(function(){

        var  uid=$(this).attr("data-uid");
         layer.open({
            type: 0,
            title:   "<?php echo lang('拉黑的时间'); ?>",
            area: ['380px', '250px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"], //按钮组,
           content: '<div style="width:330px;height:100px;"><table class="table table-hover table-bordered"><tbody><tr><td><div class="ban_type"><input type="text" id="day" class="ban_time"/> <?php echo lang("ADMIN_DAY"); ?> <input type="text" id="hours" class="ban_time"/> <?php echo lang("ADMIN_HOUR"); ?> <input type="text" id="minutes" class="ban_time"/> <?php echo lang("ADMIN_MINUTE"); ?> <input type="text" id="seconds" class="ban_time"/> <?php echo lang("ADMIN_SECOND"); ?></div></td></tr></tbody></table></div>',
            yes:function(index){   //点击确定回调
                var day=$("#day").val();
                var hours=$("#hours").val();
                var minutes=$("#minutes").val();
                var seconds=$("#seconds").val();

                layer.close(index);
                $.ajax({
                    url: "<?php echo url('adminIndex/ban_type'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {id: uid,day:day,hours:hours,minutes:minutes,seconds:seconds},
                    success: function (data) {
                        if(data['code'] == 1){
                            layer.msg(data['msg'],{time: 2000, icon:1},function(){
                                window.location.reload();
                            });
                        }else{
                            layer.msg(data['msg'],{time: 2000, icon:2});
                        }
                    }
                });
            }
        });


    })
    $(".device_info").click(function(){
        var id=$(this).attr("data-id");
        var os=$(this).attr("data-os");
        var sdk_version=$(this).attr("data-sdk_version");
        var app_version=$(this).attr("data-app_version");
        var brand=$(this).attr("data-brand");
        var model=$(this).attr("data-model");
        var addtime=$(this).attr("data-addtime");
        var device=$(this).attr("data-device");

        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            shadeClose: true,
            skin: 'yourclass',
            content: '<div style="width:330px;height:350px;"><table class="table table-hover table-bordered"><thead><tr><th style="text-align: center;background: #f7f7f7;"><?php echo lang("ADMIN_DEVICE_CONTENT"); ?></th></tr></thead><tbody><tr><td><div  class="user_details_type"><?php echo lang("ADMIN_OPERATING_SYSTEM"); ?></div><div class="user_details_type">'+os+'</div></td></tr><tr><td><div  class="user_details_type">sdk <?php echo lang("ADMIN_VERSION"); ?></div><div class="user_details_type">'+sdk_version+'</div></td></tr><tr><tr><td><div  class="user_details_type">app<?php echo lang("ADMIN_VERSION"); ?></div><div class="user_details_type">'+app_version+'</div></td></tr><tr><td><div  class="user_details_type"><?php echo lang("ADMIN_PHONE_BRAND"); ?></div><div class="user_details_type">'+brand+'</div></td></tr><tr><td><div  class="user_details_type"><?php echo lang("ADMIN_PHONE_MODEL"); ?></div><div class="user_details_type">'+model+'</div></td></tr><tr><td><div  class="user_details_type"><?php echo lang("ADMIN_DEVICE_NUMBER"); ?></div><div class="user_details_type">'+device+'</div></td></tr><tr><td><div  class="user_details_type"><?php echo lang("ADD_TIME"); ?></div><div class="user_details_type">'+addtime+'</div></td></tr></tbody></table></div>'
        });

    });

    flag = true;
    $(".ipt").click(function(){

        if(flag){
            $(this).children('strong').html(' -');
            $(this).next('ul').css('display','block');
            flag = false;
        }else{
            flag = true;
            var self = $(this);
            setTimeout(function(){
                self.children('strong').html(' +');
                self.next('ul').css('display','none');
            },200)
        }


    });

    $(".ipt").blur(function(){
        flag = true;
        var self = $(this);
        setTimeout(function(){
            self.children('strong').html('+');
            self.next('ul').css('display','none');
        },200)
    });
    // $('li').onclick(function(){
    //     var selfli = $(this);
    //     alert(selfli.val());
    //     selfli.sibling('.ipt').text(selfli.val());
    //
    // });


    $(".user_details").click(function(){
        var id=$(this).attr("data-id");
        var custom=$(this).attr("data-custom");
        var invite=$(this).attr("data-invite");
        var total=$(this).attr("data-total");
        var fans=$(this).attr("data-fans");
        var attention=$(this).attr("data-attention");
        var income=$(this).attr("data-income");
        var perfect=$(this).attr("data-perfect");
        var coin=$(this).attr("data-coin");
        var money=$(this).attr("data-money");
        var vip=$(this).attr("data-vip");
        var invite_withdrawal=$(this).attr("data-invite-withdrawal");
        var create_time=$(this).attr("data-create_time");
        var last_login_time=$(this).attr("data-last_login_time");
        var last_login_ip=$(this).attr("data-last_login_ip");
        var longitude=$(this).attr("data-longitude");
        var latitude=$(this).attr("data-latitude");

        if(perfect =='1'){
            var reg_perfect='<a href="<?php echo url('admin/consume/index'); ?>?touid='+id+'">'+income+'</a>';
        }else{
            var reg_perfect=income;
        }

        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            shadeClose: true,
            content: '<div style="width:330px;height:470px;"><table class="table table-hover table-bordered"><thead>' +
                '<tr><th style="text-align: center;background: #f7f7f7;"><?php echo lang("ADMIN_INFO"); ?></th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_DIAMOND_BALANCE"); ?></div><div class="user_details_type">'+coin+'</div></td></tr>' +
/*                '<tr><td><div  class="user_details_type"><?php echo lang("邀请收益余额"); ?></div><div class="user_details_type"><a href="<?php echo url('admin/InviteManage/income_index'); ?>?user_id='+id+'">'+invite+'</a></div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("邀请提现"); ?></div><div class="user_details_type">'+invite_withdrawal+'</div></td></tr>' +*/
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_INCOME_BALANCE"); ?></div><div class="user_details_type">'+reg_perfect+'</div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_TOTAL_INCOME"); ?></div><div class="user_details_type">'+total+'</div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_WITHDRAW_BALANCE"); ?></div><div class="user_details_type">'+money+'</div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_WITHDRAW_INCOME"); ?></div><div class="user_details_type">'+income+'</div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_ATT_NUMBER"); ?></div><div class="user_details_type " data-type="1"><a href="javascript:void(0);" class="attention" data-type="2" data-id="'+id+'">'+attention+'</a></div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("ADMIN_FANS_NUMBER"); ?></div><div class="user_details_type" ><a href="javascript:void(0);" class="attention" data-type="1" data-id="'+id+'">'+fans+'</a></div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("LAST_CREATE_TIME"); ?></div><div class="user_details_type" >'+create_time+'</div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("LAST_LOGIN_TIME"); ?></div><div class="user_details_type" >'+last_login_time+'</div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("封禁设备列表"); ?></div><div class="user_details_type" ><a style="text-decoration:none;"  href="javascript:void(0);" onclick="clearEquipmentClosures('+id+')"><?php echo lang("查看"); ?></a></div></td></tr>' +
                '<tr><td><div  class="user_details_type"><?php echo lang("LAST_LOGIN_IP"); ?></div><div class="user_details_type" >'+last_login_ip+'</div></td></tr><tr><td><div  class="user_details_type"><?php echo lang("ADMIN_LONGITUDE"); ?></div><div class="user_details_type">'+longitude+'</div></td></tr><tr><td><div  class="user_details_type"><?php echo lang("ADMIN_LATITUDE"); ?></div><div class="user_details_type">'+latitude+'</div></td></tr></tbody></table></div>'
        });
    })


    function cancel_auth(id) {
        $.ajax({
            url: "<?php echo url('admin_index/cancel_auth'); ?>",
            type: 'get',
            dataType: 'json',
            data: {id: id},
            success: function (data) {
                if(data['status'] == 1){
                    layer.msg(lang('Operation_successful'),{time: 2000, icon:2});
                }else{
                    layer.msg(data['msg'],{time: 2000, icon:2});
                }
            }
        });
    }

    function account(id) {
        layer.prompt({title:"<?php echo lang('请输入充值金额'); ?>", formType: 0}, function(coin, index){
            $.ajax({
                url: "<?php echo url('admin_index/account'); ?>",
                type: 'get',
                dataType: 'json',
                data: {id: id,coin:coin},
                success: function (data) {
                    if(data['code'] == 1){
                        layer.msg("<?php echo lang('Operation_successful'); ?>",{time: 2000, icon:1},function(){
                            window.location.reload();
                        });
                    }else{
                        layer.msg("<?php echo lang('operation_failed'); ?>",{time: 2000, icon:2});
                    }
                }
            });
            layer.close(index);
        });
    }

    function addCoin(id) {
        layer.open({
            type: 2,
            title:   "<?php echo lang('手动充值'); ?>",
            shadeClose: true,
            area: ['80%', '80%'],   //宽高
            shade: 0.4,   //遮罩透明度
            content:  "<?php echo url('admin/refill/add_recharge'); ?>?id=" + id
        });
      }
    function nobleSet(id) {
        layer.confirm("<?php echo lang('cancel_nobility'); ?>", {
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"] //按钮
        }, function(){
            $.ajax({
                url: "<?php echo url('admin_index/clear_noble'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id},
                success: function (data) {
                    if(data.status === 1){
                        layer.msg(data.msg,{time: 2000, icon:1},function(){
                            window.location.reload();
                        });
                    }else{
                        layer.msg(data.msg,{time: 2000, icon:2});
                    }
                }
            });
        });
    }

    function vipset(id,time_end) {
        if(time_end.length<=1){
            var time_end = '';
        }
        layer.open({
            type: 0,
            title: "VIP设置",
            area: ['300px', '200px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"], //按钮组,
            content: '<div class="layui-form"><label class="layui-form-label">VIP结束时间</label><input  type="text" id="vip_end_time" class="form-control" name="vip_end_time" value="'+time_end+'" style="width: 140px;" autocomplete="off" oninput = "value=value.replace(/[^\\d]/g,\'\')"></div>',
            success:function(){
                Wind.css('bootstrapDatetimePicker');
                Wind.use('bootstrapDatetimePicker', function () {
                    $("#vip_end_time").datetimepicker({
                        language: 'zh-CN',
                        format: 'yyyy-mm-dd hh:ii',
                        todayBtn: 1,
                        autoclose: true
                    });
                });
            },
            yes:function(index){   //点击确定回调
                //alert($('#vip_end_time').val());
                layer.close(index);
                $.ajax({
                    url: "<?php echo url('admin_index/vipSet'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {id: id,vip_end_time:$('#vip_end_time').val()},
                    success: function (data) {
                        if(data['code'] == 1){
                            layer.msg(data['msg'],{time: 2000, icon:1},function(){
                                window.location.reload();
                            });
                        }else{
                            layer.msg(data['msg'],{time: 2000, icon:2});
                        }
                    }
                });
            }
        });
    }

    $(".head_img").click(function(){
        var img=$(this).attr("data-img");
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            area: ['auto', 'auto'],
            skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content:img
        });
    })
    $(".get_sort").click(function(){
        $(".js-ajax-form1").submit();
    })
    $(".from_export").click(function(){

    })
    $("body").on("click",".attention",function(){

        var id=$(this).attr("data-id");
        var type=$(this).attr("data-type");

        $.ajax({
            url: "<?php echo url('admin_index/attention'); ?>",
            type: 'get',
            dataType: 'json',
            data: {id: id,type:type},
            success: function (data) {
                if(data['status'] == 1){
                    var name=data['data'];
                    layer.open({
                        type: 1,
                        title: false,
                        closeBtn: 0,
                        shadeClose: true,
                        skin: 'yourclass',
                        content: '<div style="width:360px;height:300px;"><table class="table table-hover table-bordered"><thead><tr><th><?php echo lang('USER_ID'); ?></th><th><?php echo lang('NICENAME'); ?></th><th><?php echo lang("关注时间"); ?></th></tr></thead><tbody>'+name+'</tbody></table></div>'
                    });
                }else{
                    layer.msg(data['msg'],{time: 2000, icon:2});
                }
            }
        });
    })
    function set_exchange(id,type) {
        $.ajax({
            url: "<?php echo url('admin_index/set_exchange'); ?>",
            type: 'get',
            dataType: 'json',
            data: {id: id,type:type},
            success: function (data) {
                if(data['status'] == 1){
                    layer.msg("<?php echo lang('Operation_successful'); ?>",{time: 2000, icon:1},function(){
                        window.location.reload();
                    });
                }else{
                    layer.msg("<?php echo lang('operation_failed'); ?>",{time: 2000, icon:2});
                }
            }
        });
    }
      function set_test(id,type) {
          //layer.prompt({title:"<?php echo lang('请输入充值金额'); ?>", formType: 0}, function(coin, index){
              $.ajax({
                  url: "<?php echo url('admin_index/set_test'); ?>",
                  type: 'get',
                  dataType: 'json',
                  data: {id: id,type:type},
                  success: function (data) {
                      if(data['status'] == 1){
                          layer.msg("<?php echo lang('Operation_successful'); ?>",{time: 2000, icon:1},function(){
                              window.location.reload();
                          });
                      }else{
                          layer.msg("<?php echo lang('operation_failed'); ?>",{time: 2000, icon:2});
                      }
                  }
              });
              //layer.close(index);
          //});
      }

      function clearCoin(id){
          layer.confirm('确定清零 余额、收益、邀请收益等账户？', {
              btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"] //按钮
          }, function(){
              $.ajax({
                  url: "<?php echo url('admin_index/clear_coin'); ?>",
                  type: 'post',
                  dataType: 'json',
                  data: {id: id},
                  success: function (data) {
                      if(data =='1'){
                          layer.msg("<?php echo lang('清空成功'); ?>",{time: 2000, icon:1},function(){
                              window.location.reload();
                          });
                      }else{
                          layer.msg("<?php echo lang('清空失败'); ?>",{time: 2000, icon:2});
                      }
                  }
              });

          });
      }
    function clearEquipmentClosures(uid) {
        layer.open({
            type: 2,
            title:   "<?php echo lang('禁封设备表'); ?>",
            shadeClose: true,
            area: ['80%', '80%'],   //宽高
            shade: 0.4,   //遮罩透明度
            content:  "<?php echo url('admin/Anchor/equipment_closures'); ?>?uid=" + uid
        });
    }
</script>
</body>
</html>
