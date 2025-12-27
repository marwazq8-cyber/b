<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:67:"themes/admin_simpleboot3/admin/withdrawals_manage/user_binding.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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

<style>

    #status,#type{    width: 100px;
        height: 32px;
        border-color: #dce4ec;
        color: #aeb5bb;}

</style>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('withdrawals_manage/index'); ?>"><?php echo lang('提现记录'); ?></a></li>
        <li class="active"><a href="<?php echo url('withdrawals_manage/user_binding'); ?>"><?php echo lang('账号绑定列表'); ?></a></li>
    </ul>
    <div class="table-actions">
        <form class="well form-inline margin-top-20" name="form1" method="post">
            <?php echo lang('ADMIN_PHONE_NUMBER'); ?>:
            <input type="text" class="form-control" name="mobile" style="width: 120px;" value="<?php echo (isset($request['mobile']) && ($request['mobile'] !== '')?$request['mobile']:''); ?>" placeholder="<?php echo lang('请输入手机号'); ?>">
            <?php echo lang('USER_ID'); ?>:
            <input type="text" class="form-control" name="id" style="width: 120px;" value="<?php echo (isset($request['id']) && ($request['id'] !== '')?$request['id']:''); ?>" placeholder="<?php echo lang('USER_ID'); ?>">
           <?php echo lang('昵称'); ?>:
            <input type="text" class="form-control" name="name" style="width: 120px;" value="<?php echo (isset($request['name']) && ($request['name'] !== '')?$request['name']:''); ?>" placeholder="<?php echo lang('请输入用户昵称'); ?>">
            <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" onclick='form1.action="<?php echo url('withdrawals_manage/user_binding'); ?>";form1.submit();'/>
            <a class="btn btn-danger" href="<?php echo url('withdrawals_manage/user_binding'); ?>"><?php echo lang('EMPTY'); ?></a>
        </form>
    </div>
    <table class="table table-hover table-bordered table-list">
        <thead>
        <tr>
            <th width="50">ID</th>
            <th><?php echo lang('USER_ID'); ?></th>
            <th><?php echo lang('USER_NICKNAME'); ?></th>
            <th><?php echo lang('ADMIN_PHONE_NUMBER'); ?></th>
            <th><?php echo lang('账号姓名'); ?></th>
<!--            <th><?php echo lang('支付账号'); ?></th>-->
<!--            <th><?php echo lang('微信账号'); ?></th>-->
            <th><?php echo lang('银行卡号码'); ?></th>
            <th><?php echo lang('银行卡名称'); ?></th>
            <th><?php echo lang('TIME'); ?></th>
            <th width="130"><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($data) || $data instanceof \think\Collection || $data instanceof \think\Paginator): if( count($data)==0 ) : echo "" ;else: foreach($data as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['uid']; ?></td>
                <td><?php echo $vo['user_nickname']; ?></td>
                <td><?php echo $vo['mobile']; ?></td>
                <td><?php echo $vo['name']; ?></td>
<!--                <td><?php echo $vo['pay']; ?></td>-->
<!--                <td><?php echo $vo['wx']; ?></td>-->
                <td><?php echo $vo['bank_card']; ?></td>
                <td><?php echo $vo['bank_card_name']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$vo['addtime'] ); ?></td>
                <td>
                    <a href="<?php echo url('withdrawals_manage/binding_upd',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
                </td>
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>

    <div class="pagination"><?php echo $page; ?></div>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
</body>
</html>