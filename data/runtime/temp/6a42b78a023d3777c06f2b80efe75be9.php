<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:49:"themes/admin_simpleboot3/admin/gift/gift_bag.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .client-a{
        cursor: pointer;
    }
</style>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('gift/gift_bag'); ?>"><?php echo lang('ADMIN_GIFT_BAG_LIST'); ?></a></li>
        <li ><a href="<?php echo url('gift/gift_bag_add'); ?>"><?php echo lang('ADMIN_GIFT_BAG_ADD'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" method="post" action="<?php echo url('gift/gift_bag'); ?>">
        <?php echo lang('USER_ID'); ?>：
        <input class="form-control" type="text" name="uid" style="width: 100px;" value="<?php echo input('request.uid'); ?>"
               placeholder="<?php echo lang('USER_ID'); ?>">

        <?php echo lang('ADMIN_GIFT'); ?>：
        <select name="giftid" id="giftid" class="form-control">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <?php if(is_array($gift_list) || $gift_list instanceof \think\Collection || $gift_list instanceof \think\Paginator): if( count($gift_list)==0 ) : echo "" ;else: foreach($gift_list as $key=>$v): ?>
                <option value="<?php echo $v['id']; ?>" <?php if($request['giftid'] == $v['id']): ?> selected="selected" <?php endif; ?>><?php echo $v['name']; ?></option>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </select>
<!--        <?php echo lang('TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-date" name="start_time" value="<?php echo (isset($request['start_time']) && ($request['start_time'] !== '')?$request['start_time']:''); ?>" style="width: 140px;" autocomplete="off">-
        <input type="text" class="form-control js-bootstrap-date" name="end_time" value="<?php echo (isset($request['end_time']) && ($request['end_time'] !== '')?$request['end_time']:''); ?>" style="width: 140px;" autocomplete="off"> &nbsp; &nbsp;-->

        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
        <a class="btn btn-danger" href="<?php echo url('gift/gift_bag'); ?>"><?php echo lang('EMPTY'); ?></a>

    </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th width="50">ID</th>
            <th><?php echo lang('USER'); ?>（ID）</th>
            <th><?php echo lang('ADMIN_GIFT_NAME'); ?>（ID）</th>
            <th><?php echo lang('ADMIN_GIFT_NUM'); ?></th>
            <th><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['user_nickname']; ?>(<?php echo $vo['uid']; ?>)</td>
                <td><?php echo $vo['name']; ?>(<?php echo $vo['giftid']; ?>)</td>
                <td><?php echo $vo['giftnum']; ?></td>
                <td>
                    <a href="<?php echo url('gift/gift_bag_add',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a> |
                    <a href="javescript:;" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('DELETE'); ?></a>
                </td>
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/admin.js"></script>
<script src="/static/js/clipboard.min.js" type="text/javascript"></script>
</body>
</html>
<script>
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('DELETE_CONFIRM_MESSAGE'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('gift/gift_bag_del'); ?>",
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
</script>