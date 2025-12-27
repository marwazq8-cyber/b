<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:51:"themes/admin_simpleboot3/admin/main/statistics.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .home-info li em {
        float: left;
        width: 120px;
        font-style: normal;
        font-weight: bold;
    }

    .home-info ul {
        padding: 0;
        margin: 0;
    }

    .panel {
        margin-bottom: 0;
    }
    .grid-item {
        margin-bottom: 5px;
        padding: 5px;
    }

    .btn-main-box{
        cursor: pointer;
        position: relative;
        padding: 20px 20px 20px 20px;
        background-color: #fff;
        color: #333;
        font-weight: 400;
        font-size: 16px;
        text-align: center;
    }
</style>
<link rel="stylesheet" href="/themes/admin_simpleboot3/public/assets/simpleboot3/css/index.css">
<?php 
    \think\Hook::listen('admin_before_head_end',$temp67b1359457fc6,null,false);
 ?>
</head>
<body>
<div class="wrap">
    <?php if(empty($has_smtp_setting) || (($has_smtp_setting instanceof \think\Collection || $has_smtp_setting instanceof \think\Paginator ) && $has_smtp_setting->isEmpty())): ?>
        <!--<div class="grid-item col-md-12">
            <div class="alert alert-danger alert-dismissible fade in" role="alert" style="margin-bottom: 0;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong><?php echo lang('提示!'); ?></strong> 邮箱配置未完成,无法进行邮件发送!
                <a href="#" data-dismiss="alert" aria-label="Close"
                   onclick="parent.openapp('<?php echo url('Mailer/index'); ?>','admin_mailer_index','邮箱配置');"><?php echo lang('现在设置'); ?></a>
            </div>
        </div>-->
    <?php endif; if(!extension_loaded('fileinfo')): ?>
        <div class="grid-item col-md-12">
            <div class="alert alert-danger alert-dismissible fade in" role="alert" style="margin-bottom: 0;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong><?php echo lang('提示!'); ?></strong> php_fileinfo扩展没有开启，无法正常上传文件！
            </div>
        </div>
    <?php endif; ?>

    <div class="grid-item col-md-12" id="thinkcmf-notices-grid" style="display:none;">
        <div class="dashboard-box">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo lang('SYSTEM_NOTIFICATIONS'); ?></h3>
                </div>
                <div class="panel-body home-info">
                    <ul id="thinkcmf-notices" class="list-unstyled">
                        <li>
                            <img src="/themes/admin_simpleboot3/public/assets/images/loading.gif" style="vertical-align: middle;"/>
                            <span style="display: inline-block; vertical-align: middle;"><?php echo lang('LOADING'); ?>...</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!--首页-->
    <div class="bogo_index">
        <div class="bogo_index_title"><?php echo lang('ADMIN_STATISTICS'); ?></div>
        <div class="bogo_index_box">
            <div class="bogo_index_row bogo_img1">
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_DAY_PAYMENT_AMOUNT'); ?>（<?php echo lang('ADMIN_MONEY'); ?>）</div>
                    <a class="amount" href="<?php echo url('refill/log_index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['charge']['day_log']['money']) && ($admin_index_list['charge']['day_log']['money'] !== '')?$admin_index_list['charge']['day_log']['money']:"0.00"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['charge']['Yesterday_log']['money']) && ($admin_index_list['charge']['Yesterday_log']['money'] !== '')?$admin_index_list['charge']['Yesterday_log']['money']:"0.00"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('TOTAL_PAYMENT_AMOUNT'); ?>：<?php echo (isset($admin_index_list['charge']['total_log']['money']) && ($admin_index_list['charge']['total_log']['money'] !== '')?$admin_index_list['charge']['total_log']['money']:"0.00"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('NUMBER_OF_ORDERS_PAID_TODAY'); ?></div>
                    <a class="amount" href="<?php echo url('refill/log_index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['charge']['day_log']['ordersum']) && ($admin_index_list['charge']['day_log']['ordersum'] !== '')?$admin_index_list['charge']['day_log']['ordersum']:"0"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['charge']['Yesterday_log']['ordersum']) && ($admin_index_list['charge']['Yesterday_log']['ordersum'] !== '')?$admin_index_list['charge']['Yesterday_log']['ordersum']:"0"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_ALL_RECHARGE_ORDER'); ?>：<?php echo (isset($admin_index_list['charge']['total_log']['ordersum']) && ($admin_index_list['charge']['total_log']['ordersum'] !== '')?$admin_index_list['charge']['total_log']['ordersum']:"0"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_RECHARGE_TODAY'); ?></div>
                    <a class="amount" href="<?php echo url('refill/log_index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['charge']['user_day']) && ($admin_index_list['charge']['user_day'] !== '')?$admin_index_list['charge']['user_day']:"0"); ?></a>
                    <div class="amount-yesterday"><?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['charge']['user_Yesterday']) && ($admin_index_list['charge']['user_Yesterday'] !== '')?$admin_index_list['charge']['user_Yesterday']:"0"); ?></div>
                    <div class="amount-yesterday"><?php echo lang('NUMBER_OF_PAYMENTS_TODAY'); ?>：<?php echo (isset($admin_index_list['charge']['user_total']) && ($admin_index_list['charge']['user_total'] !== '')?$admin_index_list['charge']['user_total']:"0"); ?></div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="compare_title">
                        <?php echo lang('PAYMENT_AMOUNT_COMPARED_TO_THE_PREVIOUS_DAY'); ?> ：
                        <?php if($admin_index_list['charge']['day_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['charge']['day_than']['than']; ?>% </i>

                    </div>
                    <div class="compare_title">
                        <?php echo lang('PAYMENT_ORDER_COMPARED_TO_THE_DAY_BEFORE'); ?> ：
                        <?php if($admin_index_list['charge']['day_ordersum_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['charge']['day_ordersum_than']['than']; ?>% </i>
                    </div>
                    <div class="compare_title">
                        <?php echo lang('NUMBER_OF_PAYERS_COMPARED_TO_THE_PREVIOUS_DAY'); ?> ：
                        <?php if($admin_index_list['charge']['day_user_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['charge']['day_user_than']['than']; ?>% </i>
                    </div>
                </div>
            </div>

            <div class="bogo_index_row bogo_img2">
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_TODAY_REGISTER'); ?></div>
                    <a class="amount" href="<?php echo url('user/admin_index/index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['registered']['day_user']) && ($admin_index_list['registered']['day_user'] !== '')?$admin_index_list['registered']['day_user']:"0"); ?></a>
                    <div class="amount-yesterday"><?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['registered']['Yesterday_user']) && ($admin_index_list['registered']['Yesterday_user'] !== '')?$admin_index_list['registered']['Yesterday_user']:"0"); ?>
                    </div>
                    <div class="amount-yesterday"><?php echo lang('ADMIN_ALL_REGISTER'); ?>：<?php echo (isset($admin_index_list['registered']['total_user']) && ($admin_index_list['registered']['total_user'] !== '')?$admin_index_list['registered']['total_user']:"0"); ?>
                    </div>
                </div>
                <!--<div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_AGENT_TODAY_REGISTER'); ?></div>
                    <a class="amount" href="<?php echo url('agent/index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['registered']['day_agent']) && ($admin_index_list['registered']['day_agent'] !== '')?$admin_index_list['registered']['day_agent']:"0"); ?></a>
                    <div class="amount-yesterday"><?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['registered']['Yesterday_agent']) && ($admin_index_list['registered']['Yesterday_agent'] !== '')?$admin_index_list['registered']['Yesterday_agent']:"0"); ?>
                    </div>
                    <div class="amount-yesterday"><?php echo lang('ADMIN_AGENT_ALL_REGISTER'); ?>：<?php echo (isset($admin_index_list['registered']['total_agent']) && ($admin_index_list['registered']['total_agent'] !== '')?$admin_index_list['registered']['total_agent']:"0"); ?>
                    </div>
                </div>-->
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_INVITE_TODAY_REGISTER'); ?></div>
                    <a class="amount" href="<?php echo url('invite_manage/invite_record_index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['registered']['day_invitation']) && ($admin_index_list['registered']['day_invitation'] !== '')?$admin_index_list['registered']['day_invitation']:"0"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['registered']['Yesterday_invitation']) && ($admin_index_list['registered']['Yesterday_invitation'] !== '')?$admin_index_list['registered']['Yesterday_invitation']:"0"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_INVITE_ALL_REGISTER'); ?>：<?php echo (isset($admin_index_list['registered']['total_invitation']) && ($admin_index_list['registered']['total_invitation'] !== '')?$admin_index_list['registered']['total_invitation']:"0"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="compare_title">
                        <?php echo lang('ADMIN_ALL_REGISTER_YESTERDAY'); ?> ：
                        <?php if($admin_index_list['registered']['day_registered_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                         <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['registered']['day_registered_than']['than']; ?>% </i>

                    </div>
                    <!--<div class="compare_title">
                        <?php echo lang('ADMIN_ALL_AGENT_REGISTER_YESTERDAY'); ?> ：
                        <?php if($admin_index_list['registered']['agent_registered_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['registered']['agent_registered_than']['than']; ?>% </i>
                    </div>-->
                    <div class="compare_title">
                        <?php echo lang('ADMIN_ALL_INVITE_REGISTER_YESTERDAY'); ?> ：
                        <?php if($admin_index_list['registered']['invitation_registered_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['registered']['invitation_registered_than']['than']; ?>% </i>
                    </div>
                </div>
            </div>

            <div class="bogo_index_row bogo_img3">
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_TODAY_CONSUMPTION'); ?></div>
                    <a class="amount" href="<?php echo url('Consume/index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['consumption']['day_consumption']['coin']) && ($admin_index_list['consumption']['day_consumption']['coin'] !== '')?$admin_index_list['consumption']['day_consumption']['coin']:"0.00"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['consumption']['Yesterday_consumption']['coin']) && ($admin_index_list['consumption']['Yesterday_consumption']['coin'] !== '')?$admin_index_list['consumption']['Yesterday_consumption']['coin']:"0.00"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_ALL_CONSUMPTION'); ?>：<?php echo (isset($admin_index_list['consumption']['total_consumption']['coin']) && ($admin_index_list['consumption']['total_consumption']['coin'] !== '')?$admin_index_list['consumption']['total_consumption']['coin']:"0.00"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_TODAY_INCOME'); ?></div>
                    <a class="amount" href="<?php echo url('Consume/index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['consumption']['day_consumption']['profit']) && ($admin_index_list['consumption']['day_consumption']['profit'] !== '')?$admin_index_list['consumption']['day_consumption']['profit']:"0.00"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['consumption']['Yesterday_consumption']['profit']) && ($admin_index_list['consumption']['Yesterday_consumption']['profit'] !== '')?$admin_index_list['consumption']['Yesterday_consumption']['profit']:"0.00"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_ALL_INCOME'); ?>：<?php echo (isset($admin_index_list['consumption']['total_consumption']['profit']) && ($admin_index_list['consumption']['total_consumption']['profit'] !== '')?$admin_index_list['consumption']['total_consumption']['profit']:"0.00"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_TODAY_WITHDRAW'); ?></div>
                    <a class="amount" href="<?php echo url('withdrawals_manage/index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['consumption']['day_withdrawal']['income']) && ($admin_index_list['consumption']['day_withdrawal']['income'] !== '')?$admin_index_list['consumption']['day_withdrawal']['income']:"0.00"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('YESTERDAY'); ?>：<?php echo (isset($admin_index_list['consumption']['Yesterday_withdrawal']['income']) && ($admin_index_list['consumption']['Yesterday_withdrawal']['income'] !== '')?$admin_index_list['consumption']['Yesterday_withdrawal']['income']:"0.00"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_ALL_WITHDRAW'); ?>：<?php echo (isset($admin_index_list['consumption']['total_withdrawal']['income']) && ($admin_index_list['consumption']['total_withdrawal']['income'] !== '')?$admin_index_list['consumption']['total_withdrawal']['income']:"0.00"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="compare_title">
                        <?php echo lang('ADMIN_ALL_CONSUMPTION_YESTERDAY'); ?> ：
                        <?php if($admin_index_list['consumption']['day_consumption_coin_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['consumption']['day_consumption_coin_than']['than']; ?>% </i>

                    </div>
                    <div class="compare_title">
                        <?php echo lang('ADMIN_ALL_INCOME_YESTERDAY'); ?> ：
                        <?php if($admin_index_list['consumption']['day_consumption_profit_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['consumption']['day_consumption_profit_than']['than']; ?>% </i>
                    </div>
                    <div class="compare_title">
                        <?php echo lang('ADMIN_ALL_WITHDRAW_YESTERDAY'); ?> ：
                        <?php if($admin_index_list['consumption']['day_withdrawal_than']['type'] == 1): ?>
                            <i class="fa fa-long-arrow-up">
                                <?php else: ?>
                                <i class="fa fa-long-arrow-down"> -
                        <?php endif; ?>
                        <?php echo $admin_index_list['consumption']['day_withdrawal_than']['than']; ?>% </i>
                    </div>
                </div>
            </div>


            <div class="bogo_index_row bogo_img5">
                <div class="bogo_index_row_type">
                    <div class="title"> <?php echo lang('ADMIN_CERTIFICATION_AUDIT_NO'); ?></div>
                    <a class="amount" href="<?php echo url('user/identity/auth_info_list'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['audit']['auth_record']['review']) && ($admin_index_list['audit']['auth_record']['review'] !== '')?$admin_index_list['audit']['auth_record']['review']:"0"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_CERTIFICATION_AUDIT_YES'); ?>：<?php echo (isset($admin_index_list['audit']['auth_record']['through']) && ($admin_index_list['audit']['auth_record']['through'] !== '')?$admin_index_list['audit']['auth_record']['through']:"0"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_CERTIFICATION_AUDIT_PASS'); ?>：<?php echo (isset($admin_index_list['audit']['auth_record']['refused']) && ($admin_index_list['audit']['auth_record']['refused'] !== '')?$admin_index_list['audit']['auth_record']['refused']:"0"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_CERTIFICATION_AUDIT_ALL'); ?>：<?php echo (isset($admin_index_list['audit']['auth_record']['countid']) && ($admin_index_list['audit']['auth_record']['countid'] !== '')?$admin_index_list['audit']['auth_record']['countid']:"0"); ?>
                    </div>
                </div>

                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_RECHARGE_TODAY_ANDROID'); ?></div>
                    <a class="amount" href="<?php echo url('refill/log_index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['charge_type']['android_day_log']) && ($admin_index_list['charge_type']['android_day_log'] !== '')?$admin_index_list['charge_type']['android_day_log']:"0"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_RECHARGE_YESTERDAY'); ?>：<?php echo (isset($admin_index_list['charge_type']['android_Yesterday_log']) && ($admin_index_list['charge_type']['android_Yesterday_log'] !== '')?$admin_index_list['charge_type']['android_Yesterday_log']:"0"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_RECHARGE_ALL'); ?>：<?php echo (isset($admin_index_list['charge_type']['android_total_log']) && ($admin_index_list['charge_type']['android_total_log'] !== '')?$admin_index_list['charge_type']['android_total_log']:"0"); ?>
                    </div>
                </div>
                <div class="bogo_index_row_type">
                    <div class="title"><?php echo lang('ADMIN_RECHARGE_TODAY_IOS'); ?></div>
                    <a class="amount" href="<?php echo url('refill/log_index'); ?>" rel="noopener noreferrer"><?php echo (isset($admin_index_list['charge_type']['ios_day_log']) && ($admin_index_list['charge_type']['ios_day_log'] !== '')?$admin_index_list['charge_type']['ios_day_log']:"0"); ?></a>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_RECHARGE_YESTERDAY'); ?>：<?php echo (isset($admin_index_list['charge_type']['ios_Yesterday_log']) && ($admin_index_list['charge_type']['ios_Yesterday_log'] !== '')?$admin_index_list['charge_type']['ios_Yesterday_log']:"0"); ?>
                    </div>
                    <div class="amount-yesterday">
                        <?php echo lang('ADMIN_RECHARGE_ALL'); ?>：<?php echo (isset($admin_index_list['charge_type']['ios']) && ($admin_index_list['charge_type']['ios'] !== '')?$admin_index_list['charge_type']['ios']:"0"); ?>
                    </div>
                </div>



            </div>
        </div>
    </div>
    <div class="bogo_index_right">
        <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
            <div class="bogo_index_right_type_img bogo_index_tel1"></div>
            <div class="bogo_index_right_type_name"><?php echo lang('SYSTEM_VERSION'); ?></div>
            <div class="bogo_index_right_type_center">v1.1.0</div>
        </div>
        <?php if(IS_OFFICIAL == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img bogo_index_tel2"></div>
                <div class="bogo_index_right_type_name"><?php echo lang('COMMERCIAL_COPYRIGHT'); ?></div>
                <div class="bogo_index_right_type_center">Shandong Cuckoo Network Technology Co., Ltd.</div>
            </div>
        <?php endif; if(IS_OFFICIAL == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img bogo_index_tel3"></div>
                <div class="bogo_index_right_type_name"><?php echo lang('OFFICIAL_WEBSITE'); ?></div>
                <div class="bogo_index_right_type_center"><a href="http://www.bogokj.com" target="_blank">http://www.bogokj.com</a>
                </div>
            </div>
        <?php endif; if(IS_GUILD == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img"><i class="fa fa-user-plus" aria-hidden="true" style="font-size: 40px"></i></div>
                <div class="bogo_index_right_type_name"><?php echo lang('公会后台'); ?></div>
                <div class="bogo_index_right_type_center copy_url" data-url="<?php echo $http; ?>/guild"><?php echo lang('Copy_address'); ?></div>

            </div>
        <?php endif; ?>

        <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
            <div class="bogo_index_right_type_img"><i class="fa fa-btc" aria-hidden="true" style="font-size: 40px"></i></div>
            <div class="bogo_index_right_type_name"><?php echo lang('充值代理商后台'); ?></div>
            <div class="bogo_index_right_type_center copy_url" data-url="<?php echo $top_up_url; ?>"><?php echo lang('Copy_address'); ?></div>

        </div>

        <?php if(IS_AGENT == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img"><i class="fa fa-users" aria-hidden="true" style="font-size: 40px"></i></div>
                <div class="bogo_index_right_type_name"><?php echo lang('CPS 渠道后台'); ?></div>
                <div class="bogo_index_right_type_center copy_url" data-url="<?php echo $http; ?>/agent"><?php echo lang('Copy_address'); ?></div>
            </div>
        <?php endif; ?>
        <!--        <div class="btn-main-box bogo_index_right_type_radius bogo_index_bottom">-->
        <!--            <div class="btn-main" data-url="<?php echo $http; ?>/union"><?php echo lang('COPY_THE_UNION_BACKSTAGE_ADDRESS'); ?></div>-->
        <!--            <div class="bogo_index_right_type_center"></div>-->
        <!--        </div>-->
        <div class="btn-main-box bogo_index_right_type_radius bogo_index_bottom">
            <div class="btn-main copy_url" data-url="<?php echo $http; ?>/api/download_api/phone_index"><?php echo lang('COPY_DOWNLOAD_ADDRESS'); ?></div>
            <div class="bogo_index_right_type_center"></div>
        </div>

    </div>

</div>


<script src="/static/js/admin.js"></script>

<script src="/static/js/amcharts.js" type="text/javascript"></script>
<script src="/static/js/serial.js" type="text/javascript"></script>
<script src="/static/js/pie.js" type="text/javascript"></script>
<script src="/static/js/clipboard.min.js" type="text/javascript"></script>
<script>
    var url = '';
    $('.copy_url').click(function(){
        url = $(this).attr('data-url');
    })
    var clipboard = new ClipboardJS('.copy_url', {
        // 点击copy按钮，直接通过text直接返回复印的内容
        text: function() {
            return url;
        }
    });

    clipboard.on('success', function(e) {
        console.log(e);
        layer.msg('Copy successful');
    });

    clipboard.on('error', function(e) {
        console.log(e);
    });
</script>
<?php 
    \think\Hook::listen('admin_before_body_end',$temp67b1359457fdb,null,false);
 ?>
</body>
</html>
