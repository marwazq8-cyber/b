<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:51:"themes/admin_simpleboot3/admin/refill/recharge.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('手动充值记录'); ?></a></li>
        <li><a href="<?php echo url('refill/add_recharge'); ?>"><?php echo lang('手动充值'); ?></a></li>
    </ul>

    <form class="well form-inline margin-top-20" method="post" action="<?php echo url('Refill/recharge'); ?>">
        <?php echo lang('USER_ID'); ?>:
        <input type="text" class="form-control" name="uid" style="width: 130px;"
               value="<?php echo (isset($recharge['uid']) && ($recharge['uid'] !== '')?$recharge['uid']:''); ?>" placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('TYPE'); ?>：
        <select name="type" id="sex" style="width:130px;height:30px;margin-right:10px;">
            <option value="0"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($recharge['type'] == 1): ?> selected='selected' <?php endif; ?> ><?php echo lang('增加'); ?></option>
            <option value="2" <?php if($recharge['type'] == 2): ?> selected='selected' <?php endif; ?> ><?php echo lang('减少'); ?></option>
        </select>
        <?php echo lang('ADMIN_USER_TYPE'); ?>：
        <select name="user_type" id="user_type" style="width:130px;height:30px;margin-right:10px;">
            <option value="0"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($recharge['user_type'] == 1): ?> selected='selected' <?php endif; ?> ><?php echo $currency_name; ?></option>
            <option value="2" <?php if($recharge['user_type'] == 2): ?> selected='selected' <?php endif; ?> ><?php echo $profit_name; ?></option>
            <option value="3" <?php if($recharge['user_type'] == 3): ?> selected='selected' <?php endif; ?> ><?php echo lang('邀请收益'); ?></option>
            <option value="4" <?php if($recharge['user_type'] == 4): ?> selected='selected' <?php endif; ?> ><?php echo $system_currency_name; ?></option>
            <option value="5" <?php if($recharge['user_type'] == 5): ?> selected='selected' <?php endif; ?> ><?php echo lang('cps收益'); ?></option>
        </select>
        <?php echo lang('TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-datetime" name="start_time" value="<?php echo input('request.start_time'); ?>" style="width: 140px;" autocomplete="off">-
        <input type="text" class="form-control js-bootstrap-datetime" name="end_time" value="<?php echo input('request.end_time'); ?>" style="width: 140px;" autocomplete="off"> &nbsp; &nbsp;
        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>"/>
        <span style="margin-left: 15px;"><?php echo lang('总数'); ?>: <?php echo $coin; ?></span>
    </form>

    <form class="js-ajax-form" method="post">

        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th>ID</th>
                <th><?php echo lang('USER'); ?>(ID)</th>
                <th><?php echo lang('变动金额'); ?></th>
                <th><?php echo lang('TYPE'); ?></th>
                <th><?php echo lang('ADMIN_USER_TYPE'); ?></th>
                <th>IP</th>
                <th><?php echo lang('ACTION_TIME'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php 
                $type=array(1=>lang('增加'),2=>lang('减少'));
                $user=array(1=> $currency_name,2=> $profit_name,3=>lang('邀请收益'),4=> $system_currency_name,5=>lang('cps收益'));
             if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['id']; ?></td>
                    <td>
                        <?php echo $vo['user_nickname']; ?>
                        (<?php echo $vo['uid']; ?>)
                    </td>
                    <td><?php echo $vo['coin']; ?></td>
                    <td><?php echo $type[$vo['type']]; ?></td>
                    <td><?php echo $user[$vo['user_type']]; ?></td>
                    <td><?php echo $vo['ip']; ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$vo['addtime'] ); ?></td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
        <ul class="pagination"><?php echo (isset($page) && ($page !== '')?$page:''); ?></ul>
    </form>
</div>
<script src="/static/js/admin.js"></script>
</body>
</html>
