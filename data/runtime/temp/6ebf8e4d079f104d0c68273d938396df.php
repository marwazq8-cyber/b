<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:53:"themes/admin_simpleboot3/admin/level/level_index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_LEVEL_LIST'); ?></a></li>
        <li><a href="<?php echo url('level/add'); ?>"><?php echo lang('ADMIN_LEVEL_ADD'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" method="post" action="<?php echo url('level/level_index'); ?>">
       <?php echo lang('等级级别'); ?>:
        <input type="text" class="form-control" name="level_name" style="width: 120px;" value="<?php echo (isset($data['level_name']) && ($data['level_name'] !== '')?$data['level_name']:''); ?>" placeholder="<?php echo lang('请输入等级级别'); ?>">

         <?php echo lang('类型'); ?>:
        <select class="form-control" name="type" style="width: 140px;">
            <option value="1" <?php if($data['type'] == 1): ?> selected='selected' <?php endif; ?>><?php echo lang('财富'); ?></option>
            <option value="2" <?php if($data['type'] == 2): ?> selected='selected' <?php endif; ?>><?php echo lang('魅力'); ?></option>
        </select>
        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
        <a class="btn btn-danger" href="<?php echo url('level/level_index'); ?>"><?php echo lang('EMPTY'); ?></a>
    </form>
  
        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th>ID</th>
                <th><?php echo lang('ADMIN_LEVEL_GRADE'); ?></th>
                <th><?php echo lang('ADMIN_LEVEL_COMMISSION'); ?></th>
                <!--<th><?php echo lang('等级大图标'); ?></th>(收益)-->
                <th><?php echo lang('ADMIN_LEVEL_ICON'); ?></th>
                <th><?php echo lang('ADMIN_LEVEL_TYPE'); ?></th>
               <!-- <th><?php echo lang('颜色值'); ?></th>-->
                <th><?php echo lang('聊天背景图'); ?></th>
                <th><?php echo lang('SORT'); ?></th>
                <th><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tfoot>
                <?php  
                    $type=array('1'=> lang('财富'),'2'=>lang('魅力'));
                 if(is_array($level) || $level instanceof \think\Collection || $level instanceof \think\Paginator): if( count($level)==0 ) : echo "" ;else: foreach($level as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['levelid']; ?></td>
                    <td><?php echo $vo['level_name']; ?></td>
                    <td><?php echo $vo['level_up']; ?></td>
                    <!--<td class="gift-img">
                        <?php if($vo['level_icon']): ?>
                            <img src="<?php echo $vo['level_icon']; ?>" alt="">
                        <?php endif; ?>
                    </td>-->
                    <td class="gift-img">
                        <?php if($vo['chat_icon']): ?>
                            <img src="<?php echo $vo['chat_icon']; ?>" alt="">
                        <?php endif; ?>
                    </td>
                    <td><?php echo $type[$vo['type']]; ?></td>
                    <!--<td><?php echo $vo['colors']; ?></td>-->
                    <td class="gift-img">
                        <?php if($vo['ticon']): ?>
                            <img src="<?php echo $vo['ticon']; ?>" alt="">
                        <?php endif; ?>
                    </td>
                    <td><?php echo $vo['sort']; ?></td>
                    <td>
                        <a href="<?php echo url('level/add',array('id'=>$vo['levelid'])); ?>"><?php echo lang('EDIT'); ?></a> |
                        <a href="javescript:;" class="del" data-id="<?php echo $vo['levelid']; ?>"><?php echo lang('DELETE'); ?></a>
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
                url: "<?php echo url('level/del'); ?>",
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
</body>
</html>