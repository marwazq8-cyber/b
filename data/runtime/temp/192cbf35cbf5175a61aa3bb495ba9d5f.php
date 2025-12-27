<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:53:"themes/admin_simpleboot3/admin/chat_record/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .gift-img{width:120px;height:40px;}
     .gift-img img{height:100%};
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_CHAT_LOG'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" name="form1" method="post">
        <?php echo lang('USER_ID'); ?>：
        <input class="form-control" type="text" name="uid" style="width: 100px;" value="<?php echo $request['uid']; ?>"
               placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('ADMIN_CHAT_USER_ID'); ?>：
        <input class="form-control" type="text" name="receive_uid" style="width: 120px;" value="<?php echo $request['receive_uid']; ?>"
               placeholder="<?php echo lang('请输入聊天用户ID'); ?>">
        <?php echo lang('ADMIN_ACTIVITY_MSG_START_TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-datetime" name="start_time" value="<?php echo $request['start_time']; ?>" style="width: 130px;" autocomplete="off">
        <?php echo lang('ADMIN_ACTIVITY_MSG_END_TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-datetime" name="end_time" value="<?php echo $request['end_time']; ?>" style="width: 130px;" autocomplete="off"> &nbsp; &nbsp;
        <?php echo lang('聊天信息'); ?>:
        <input class="form-control" type="text" name="keyword" style="width: 120px;" value="<?php echo input('request.keyword'); ?>"
               placeholder="">
        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" onclick='form1.action="<?php echo url('chat_record/index'); ?>";form1.submit();'/>
        <a class="btn btn-danger" href="<?php echo url('chat_record/index'); ?>"><?php echo lang('EMPTY'); ?></a>
    </form>

    <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th><?php echo lang('USER'); ?></th>
                <th><?php echo lang('ADMIN_CHAT_USER'); ?></th>
                <th><?php echo lang('ADMIN_CHAT_INFO'); ?></th>
                <th><?php echo lang('ADMIN_SEND_TIME'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['uname']; ?>(<?php echo $vo['uid']; ?>)</td>
                    <td><?php echo $vo['tname']; ?>(<?php echo $vo['receive_uid']; ?>)</td>
                    <td>
                        <?php if($vo['type'] ==1): ?>
                            <?php echo $vo['information']; endif; if($vo['type'] ==2): ?>
                            <audio src="<?php echo $vo['url']; ?>" controls="controls"></audio>
                        <?php endif; if($vo['type'] ==3): ?>
                            <img src="<?php echo $vo['url']; ?>" style="max-width: 50px;max-height: 80px">
                        <?php endif; ?>
                    </td>
                    <td><?php echo date("Y-m-d H:i:s",$vo['create_time']); ?></td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/admin.js"></script>
</body>
</html>
