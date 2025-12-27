<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:46:"themes/admin_simpleboot3/admin/sms/config.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .js-ajax-form{margin-top:30px;}
</style>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('sms/config'); ?>"><?php echo lang('短信平台列表'); ?></a></li>
        <li><a href="<?php echo url('sms/add_config'); ?>"><?php echo lang('添加短信平台'); ?></a></li>
    </ul>

    <form class="js-ajax-form" action="<?php echo url('Voice/upd_index_sort'); ?>" method="post">
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th width="50">ID</th>
                <th><?php echo lang('平台名称'); ?></th>
                <!--<th><?php echo lang('类名'); ?></th>-->
                <th><?php echo lang('STATUS'); ?></th>
                <th><?php echo lang('是否是国际短信'); ?></th>
                <th><?php echo lang('ADD_TIME'); ?></th>
                <th><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>

                    <td><?php echo $vo['id']; ?></td>
                    <td><?php echo $vo['name']; ?></td>
                    <!--<td><?php echo $vo['val']; ?></td>-->
                    <td>
                        <?php if($vo['status'] == 1): ?>
                            <?php echo lang('OPEN'); else: ?>
                            <?php echo lang('CLOSE'); endif; ?>
                    </td>
                    <td>
                        <?php if($vo['type'] == 1): ?>
                            <?php echo lang('YES'); else: ?>
                            <?php echo lang('NO'); endif; ?>
                    </td>
                    <td><?php echo date('Y-m-d H:i:s',$vo['addtime']); ?></td>
                    <td>
                        <a href="<?php echo url('sms/add_config',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
                        <?php if($is_delete == 1): ?>
                            | <a href="#" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('DELETE'); ?></a>
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tbody>
        </table>
    </form>
    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script>
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('DELETE_CONFIRM_MESSAGE'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('sms/del'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id},
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
    $(".btn-primary").click(function () {
        $(".js-ajax-form").submit();
    })
</script>
</body>
</html>

