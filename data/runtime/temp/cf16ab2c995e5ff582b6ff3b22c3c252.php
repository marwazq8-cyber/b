<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:55:"themes/admin_simpleboot3/user/admin_index/close_ip.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li class="active"><a href="<?php echo url('user/adminIndex/close_ip'); ?>"><?php echo lang("禁封IP记录"); ?></a></li>
        <li><a href="<?php echo url('admin/Anchor/equipment_closures'); ?>"><?php echo lang("禁封设备列表"); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" method="post" action="<?php echo url('User/adminIndex/close_ip'); ?>">
        <?php echo lang('用户ID'); ?>:
        <input type="text" class="form-control" name="uid" style="width: 120px;" value="<?php echo (isset($data['uid'] ) && ($data['uid']  !== '')?$data['uid'] :''); ?>" placeholder="<?php echo lang('请输入ID'); ?>">
        IP:
        <input type="text" class="form-control" name="ip" style="width: 200px;" value="<?php echo (isset($data['ip'] ) && ($data['ip']  !== '')?$data['ip'] :''); ?>" placeholder="<?php echo lang('请输入IP'); ?>">
        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
        <a class="btn btn-danger" href="<?php echo url('User/adminIndex/close_ip'); ?>"><?php echo lang('EMPTY'); ?></a>
        <a class="btn add_ip" style="margin-left: 10px;color: #fff; background-color: #18BC9C;border-color: #18BC9C;"><?php echo lang("添加IP"); ?></a>
    </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>IP</th>
            <th><?php echo lang('LAST_LOGIN_TIME'); ?></th>
            <th><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['ip']; ?></td>
                <td>
                    <?php if($vo['addtime'] == 0): ?>
                        <?php echo lang('USER_HAVE_NOT_LOGIN'); else: ?>
                        <?php echo date('Y-m-d H:i:s',$vo['addtime']); endif; ?>
                </td>
                <td>
                    <a href="javescript:void(0);" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('DELETE'); ?></a>
                </td>
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/admin.js"></script>
<script>
    $(".add_ip").click(function(){
        layer.open({
            type: 0,
            title:   "<?php echo lang('禁封ip'); ?>",
            area: ['380px', '250px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('禁封'); ?>","<?php echo lang('取消'); ?>"], //按钮组,
            content: '<div style="width:330px;height:100px;">' +
                '<table class="table table-hover table-bordered">' +
                '<tbody><' +
                'tr>' +
                '<td>' +
                '<div class="ban_type">' +
               "<?php echo lang('禁封的IP'); ?>" + '<input type="text" id="ip" style="width: 100%;height:40px;padding: 10px;"/>' +
                '</div>' +
                '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>',
            yes:function(index){   //点击确定回调
                var ip=$("#ip").val();

                layer.close(index);
                $.ajax({
                    url: "<?php echo url('User/adminIndex/add_closures_ip'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {ip: ip},
                    success: function (data) {
                        if(data['code'] == 1){
                            layer.msg(data['msg'],{time: 2000, icon:1},function(){
                                window.location.reload();
                            });
                        }else{
                            layer.msg(data['msg'],{time: 2000, icon:2});
                        }
                    }
                });
            }
        });


    })
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('DELETE_CONFIRM_MESSAGE'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('User/adminIndex/delete_close_ip'); ?>",
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