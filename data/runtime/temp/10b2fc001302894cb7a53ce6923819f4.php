<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:45:"themes/admin_simpleboot3/admin/sms/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li class="active"><a href="#"><?php echo lang('短信发送记录'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" method="post" action="<?php echo url('sms/index'); ?>">
        <?php echo lang('ADMIN_PHONE_NUMBER'); ?>:
        <input type="text" class="form-control" name="account" style="width: 120px;" value="<?php echo (isset($data['account']) && ($data['account'] !== '')?$data['account']:''); ?>" placeholder="<?php echo lang('请输入账号'); ?>">
      
        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
        <a class="btn btn-danger" href="<?php echo url('sms/index'); ?>"><?php echo lang('EMPTY'); ?></a>
        
    </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th width="50"><?php echo lang('编号'); ?></th>
            <th><?php echo lang('手机区号'); ?></th>
            <th><?php echo lang('ADMIN_PHONE_NUMBER'); ?></th>
            <th><?php echo lang('验证码'); ?></th>
            <th><?php echo lang('截止当天发送次数'); ?></th>
            <th><?php echo lang('发送时间'); ?></th>
            <th><?php echo lang('过期时间'); ?></th>
            <th><?php echo lang('STATUS'); ?></th>
            <th><?php echo lang('返回信息'); ?></th>
        
        </tr>
        </thead>
        <tbody>
        <?php $user_statuses=array("1"=>lang('成功'),"2"=>lang('失败')); if(is_array($users) || $users instanceof \think\Collection || $users instanceof \think\Paginator): if( count($users)==0 ) : echo "" ;else: foreach($users as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['phone_area_code']; ?></td>
                <td><?php echo $vo['account']; ?></td>
                <td><?php echo $vo['code']; ?></td>
                 <td><?php echo $vo['count']; ?></td>
                <td><?php echo date('Y-m-d H:i:s',$vo['send_time']); ?> </td>
                <td><?php echo date('Y-m-d H:i:s',$vo['expire_time']); ?> </td>
                <td><?php echo $user_statuses[$vo['status']]; ?></td>
                <td><?php echo $vo['msg']; ?></td>
               
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/admin.js"></script>
</body>
</html>