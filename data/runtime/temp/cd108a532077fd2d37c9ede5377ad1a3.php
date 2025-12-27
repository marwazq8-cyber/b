<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:61:"themes/admin_simpleboot3/admin/voice/voice_administrator.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li><a href="<?php echo url('voice/index'); ?>"><?php echo lang('语音直播列表'); ?></a></li>
        <li class="active"><a href="#"><?php echo lang('管理员列表'); ?></a></li>
        <li><a href="#" class="ban_type_btn" voice-id="<?php echo $voice_id; ?>"><?php echo lang('添加管理员'); ?></a></li>
    </ul>

    <form class="js-ajax-form" action="#" method="post">
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th><?php echo lang('USER_ID'); ?></th>
                <th><?php echo lang('头像'); ?></th>
                <th><?php echo lang('用户昵称'); ?></th>
                <th><?php echo lang('添加时间'); ?></th>
                <th><?php echo lang('操作'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['id']; ?></td>
                    <td>
                        <img src="<?php echo $vo['avatar']; ?>" style="width: 50px;height: 50px;object-fit: cover;" alt="">
                    </td>
                    <td><?php echo $vo['user_nickname']; ?></td>

                    <td><?php echo date('Y-m-d H:i:s',$vo['addtime']); ?></td>

                    <td>
                        <a href="#" class="del" data-id="<?php echo $vo['id']; ?>" voice-id="<?php echo $voice_id; ?>"><?php echo lang('删除'); ?></a>
                    </td>

                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tbody>
        </table>

    </form>

</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script>
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        var voice_id = $(this).attr('voice-id');
        layer.confirm('确定删除？', {
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('voice/del_voice_administrator'); ?>",
                type: 'post',
                dataType: 'json',
                data: {to_user_id: id,voice_id:voice_id},
                success: function (data) {
                    if (data == '1') {
                        layer.msg("<?php echo lang('删除成功'); ?>", {time: 2000, icon: 1}, function () {
                            window.location.reload();
                        });
                    } else {
                        layer.msg("<?php echo lang('删除失败'); ?>", {time: 2000, icon: 2});
                    }
                }
            });

        });
    })
    $(".btn-primary").click(function () {
        $(".js-ajax-form").submit();
    })

    $(".ban_type_btn").click(function(){

        var  voice_id=$(this).attr("voice-id");
        layer.open({
            type: 0,
            title: "<?php echo lang('添加管理员'); ?>",
            area: ['380px', '250px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"], //按钮组,
            content: '<div style="width:330px;height:100px;"><?php echo lang('管理员ID：'); ?><input type="text" id="to_user_id" value=""></div>',
            yes:function(index){   //点击确定回调
                var to_user_id=$("#to_user_id").val();

                layer.close(index);
                $.ajax({
                    url: "<?php echo url('voice/add_voice_administrator'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {voice_id: voice_id,to_user_id:to_user_id},
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
</script>
</body>
</html>

