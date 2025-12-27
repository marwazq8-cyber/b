<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:46:"themes/admin_simpleboot3/admin/luck/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_LUCK_LIST'); ?></a></li>
        <li><a href="<?php echo url('luck/add'); ?>"><?php echo lang('ADMIN_LUCK_ADD'); ?></a></li>
    </ul>

    <form class="js-ajax-form" action="" method="post">

        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th><?php echo lang('ADMIN_LUCK'); ?></th>
                <th><?php echo lang('ADMIN_PRICE'); ?>(<?php echo $currency_name; ?>)</th>
                <th><?php echo lang('USER'); ?>(ID)</th>
             <!--    <th><?php echo lang('时间(天)'); ?></th> -->
                <th><?php echo lang('ADMIN_WHETHER_BUY'); ?></th>
                <th><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php if(is_array($luck) || $luck instanceof \think\Collection || $luck instanceof \think\Paginator): if( count($luck)==0 ) : echo "" ;else: foreach($luck as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['nubmer']; ?></td>
                    <td><?php echo $vo['coin']; ?></td>
                    <td>
                        <?php if($vo['status'] != 0): ?>
                            <?php echo $vo['user_nickname']; ?>(<?php echo $vo['uid']; ?>)
                        <?php endif; ?>
                     </td>
                 <!--    <td><?php echo $vo['time']; ?></td> -->
                    <td>
                        <?php if($vo['status'] == 0): ?>
                            <?php echo lang('NO'); else: ?>
                            <?php echo lang('YES'); endif; ?>
                    </td>
                    <td>
                        <?php if($vo['status'] == 0): ?>
                            <a href="javescript:;" class="luck_sell"  data-id="<?php echo $vo['id']; ?>"><?php echo lang('ADMIN_SELL'); ?></a> |
                          <?php else: ?>
                            <a href="javescript:;" class="luck_back" data-id="<?php echo $vo['id']; ?>"><?php echo lang('ADMIN_WITHDRAW'); ?></a> |
                        <?php endif; ?>
                        <a href="javescript:;" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('DELETE'); ?></a>
                    </td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
    </form>
</div>
<script src="/static/js/admin.js"></script>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script>
    $(".luck_sell").click(function(){
         var id = $(this).attr('data-id');
         //prompt层
        layer.prompt({title: "<?php echo lang('请输入卖出用户id'); ?>", formType: 3, btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"]}, function(uid, index){
            if(!uid){
                layer.msg("<?php echo lang('Please_enter_user_ID'); ?>");
            }else{
                $.ajax({
                    url: "<?php echo url('luck/luck_sell'); ?>",
                    type: 'post',
                    dataType: 'json',
                    data: {id: id,uid:uid},
                    success: function (data) {
                        if (data == '1') {
                            layer.msg("<?php echo lang('Operation_successful'); ?>", {time: 2000, icon: 1}, function () {
                                window.location.reload();
                            });
                        } else {
                            layer.msg("<?php echo lang('operation_failed'); ?>", {time: 2000, icon: 2});
                        }
                    }
                });
            }
            layer.close(index);
        });
    })
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('DELETE_CONFIRM_MESSAGE'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
            , title: ''
        }, function () {
            $.ajax({
                url: "<?php echo url('luck/del'); ?>",
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
      $(".luck_back").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('确定收回？'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
            , title: ''
        }, function () {
            $.ajax({
                url: "<?php echo url('luck/back'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id},
                success: function (data) {
                    if (data == '1') {
                        layer.msg("<?php echo lang('Operation_successful'); ?>", {time: 2000, icon: 1}, function () {
                            window.location.reload();
                        });
                    } else {
                        layer.msg("<?php echo lang('operation_failed'); ?>", {time: 2000, icon: 2});
                    }
                }
            });

        });
    })
</script>
</body>
</html>
