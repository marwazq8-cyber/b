<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:53:"themes/admin_simpleboot3/admin/exchange/user_log.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .js-ajax-form {
        margin-top: 30px;
    }
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_EXCHANGE_RULES_LOG'); ?></a></li>
    </ul>
     <form class="well form-inline margin-top-20" method="post" action="<?php echo url('exchange/user_log'); ?>">
            <?php echo lang('ADMIN_EXCHANGE_USER_ID'); ?>:
           <input type="text" class="form-control" name="uid" style="width: 120px;" value="<?php echo (isset($data['uid']) && ($data['uid'] !== '')?$data['uid']:''); ?>" placeholder="<?php echo lang('请输入兑换ID'); ?>">
            <?php echo lang('ADMIN_INCOME_USER_ID'); ?>:
           <input type="text" class="form-control" name="touid" style="width: 120px;" value="<?php echo (isset($data['touid']) && ($data['touid'] !== '')?$data['touid']:''); ?>" placeholder="<?php echo lang('请输入收益人ID'); ?>">
         <?php echo lang('TIME'); ?>:
            <input type="text" class="form-control js-bootstrap-date" name="start_time" value="<?php echo (isset($data['start_time']) && ($data['start_time'] !== '')?$data['start_time']:''); ?>" style="width: 140px;" autocomplete="off">-
            <input type="text" class="form-control js-bootstrap-date" name="end_time" value="<?php echo (isset($data['end_time']) && ($data['end_time'] !== '')?$data['end_time']:''); ?>" style="width: 140px;" autocomplete="off"> &nbsp; &nbsp;

            <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
            <a class="btn btn-danger" href="<?php echo url('exchange/user_log'); ?>"><?php echo lang('EMPTY'); ?></a>
         <span style="margin-left: 15px"><?php echo lang('总扣除数'); ?>: <?php echo $sum['earnings']; ?></span>
         <span style="margin-left: 15px"><?php echo lang('总到账数'); ?>: <?php echo $sum['coin']; ?></span>
        </form>
   
        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th><?php echo lang('ADMIN_EXCHANGE_USER'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_INCOME_PEOPLE'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_DEDUCT_INCOME_NUM'); ?></th>
                <th><?php echo lang('ADMIN_GET_INCOME'); ?></th>
                <th><?php echo lang('TIME'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['uname']; ?>(<?php echo $vo['uid']; ?>)</td>
                    <td><?php echo $vo['toname']; ?>(<?php echo $vo['touid']; ?>)</td>
                    <td><?php echo $vo['earnings']; ?></td>
                    <td><?php echo $vo['coin']; ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$vo['addtime'] ); ?></td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
         <div class="pagination"><?php echo $page; ?></div>
 
</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script src="/static/js/admin.js"></script>
</body>
</html>