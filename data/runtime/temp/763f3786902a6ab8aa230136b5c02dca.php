<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:62:"themes/admin_simpleboot3/admin/dress_up/noble_chat_bubble.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li><a href="<?php echo url('Dress_up/noble_chat_bubble'); ?>"><?php echo lang('ADMIN_CHAT_BUBBLE_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_NOBLE'); ?> <?php echo lang('ADMIN_CHAT_BUBBLE_LIST'); ?></a></li>
        <li><a href="<?php echo url('Dress_up/chat_bubble_add'); ?>"><?php echo lang('ADMIN_CHAT_BUBBLE_ADD'); ?></a></li>
    </ul>

    <table class="table table-hover table-bordered table-list">
        <thead>
        <tr>
            <th>ID</th>
            <th><?php echo lang('ADMIN_NAME'); ?></th>
            <th><?php echo lang('ADMIN_CHAT_BUBBLE_ADD'); ?></th>
            <th><?php echo lang('ADMIN_CHAT_BUBBLE_DEMO'); ?></th>
            <th><?php echo lang('ADD_TIME'); ?></th>
            <th><?php echo lang('SORT'); ?></th>
            <th><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tfoot>
        <?php 
            $type=array('1'=>lang('OPEN'),'0'=>lang('CLOSE'));
            $vip=array('1'=>lang('YES'),'0'=>lang('NO'));
         if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['name']; ?></td>
                <td class="gift-img"><img src="<?php echo $vo['icon']; ?>" alt=""></td>
                <td class="gift-img"><img src="<?php echo $vo['img_bg']; ?>" alt=""></td>
                <td><?php echo date("Y-m-d H:i:s",$vo['addtime']); ?></td>
                <td><?php echo $vo['orderno']; ?></td>
                <td>
                    <a href="<?php echo url('dress_up/chat_bubble_add',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a> |
                    <a href="javescript:;" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('DELETE'); ?></a>
                </td>
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tfoot>
    </table>
    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/admin.js"></script>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script>
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('DELETE_CONFIRM_MESSAGE'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('dress_up/del'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id,type:3},
                success: function (data) {
                    if (data == '1') {
                        layer.msg("<?php echo lang('DELETE_SUCCESS'); ?>", {time: 2000, icon: 1}, function () {
                            window.location.reload();
                        });
                    } else {
                        layer.msg("<?php echo lang('DELETE_FAILED'); ?>", {time: 2000, icon: 2});
                    }
                }
            });

        });
    })
</script>
</body>
</html>
