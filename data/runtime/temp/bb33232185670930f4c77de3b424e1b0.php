<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:55:"themes/admin_simpleboot3/admin/dress_up/user_dress.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    select {
        width: 100px;
        height: 35px;
        line-height: 35px;
    }
    .gift-img{width:120px;height:40px;}
    .gift-img img{height:100%};
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('用户装饰列表'); ?></a></li>
        <li><a href="#" onclick="vipset();"><?php echo lang('添加装饰'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" name="form1" method="post">
        <?php echo lang('USER_ID'); ?>：
        <input class="form-control" type="text" name="uid" style="width: 100px;" value="<?php echo input('request.uid'); ?>"
               placeholder="<?php echo lang('Please_enter_user_ID'); ?>">

        <?php echo lang('装饰类型'); ?>:
        <select name="type" id="user_status">
            <option value="-1"><?php echo lang('全部'); ?></option>
            <option value="1" <?php if($request['type'] == '1'): ?> selected="selected" <?php endif; ?>><?php echo lang('勋章'); ?></option>
            <option value="2" <?php if($request['type'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang('主页特效'); ?></option>
            <option value="3" <?php if($request['type'] == 3): ?> selected="selected" <?php endif; ?>><?php echo lang('头像框'); ?></option>
            <option value="4" <?php if($request['type'] == 4): ?> selected="selected" <?php endif; ?>><?php echo lang('聊天气泡'); ?></option>
            <option value="5" <?php if($request['type'] == 5): ?> selected="selected" <?php endif; ?>><?php echo lang('聊天背景'); ?></option>
            <option value="7" <?php if($request['type'] == 7): ?> selected="selected" <?php endif; ?>><?php echo lang('进场动画'); ?></option>
            <option value="11" <?php if($request['type'] == 11): ?> selected="selected" <?php endif; ?>><?php echo lang('座驾'); ?></option>
        </select>
        &nbsp; &nbsp;
        <input type="submit" class="btn btn-primary" value="<?php echo lang('提交'); ?>" onclick='form1.action="<?php echo url('Dress_up/user_dress'); ?>";form1.submit();'/>
        <a class="btn btn-danger" href="<?php echo url('Dress_up/user_dress'); ?>"><?php echo lang('EMPTY'); ?></a>

    </form>
    <table class="table table-hover table-bordered table-list">
        <thead>
        <tr>
            <th>ID</th>
            <th><?php echo lang('USER_NAME'); ?>(ID)</th>
            <th><?php echo lang('装饰类型'); ?></th>
            <th><?php echo lang('装饰名称(ID)'); ?></th>
            <th><?php echo lang('到期时间'); ?></th>
<!--            <th><?php echo lang('时长(天)'); ?></th>-->
            <th><?php echo lang('添加时间'); ?></th>
<!--            <th><?php echo lang('排序'); ?></th>-->
            <th><?php echo lang('操作'); ?></th>
        </tr>
        </thead>
        <tfoot>
        <?php 
            $type=array('1'=>lang('勋章'),'2'=>lang('主页特效'),'3'=>lang('头像框'),'4'=>lang('聊天气泡'),'5'=>lang('聊天背景'),'6'=>lang('徽章'),'7'=>lang('进场动画'),'8'=>lang('麦克风'),'9'=>lang('昵称铭牌'),'10'=>lang('定制名片'),'11'=>lang('座驾'));
         if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['user_nickname']; ?>(<?php echo $vo['uid']; ?>)</td>
                <td>
                    <?php echo $type[$vo['type']]; ?>
                </td>
                <td><?php echo $vo['name']; ?>(<?php echo $vo['dress_id']; ?>)</td>

                <td><?php echo date("Y-m-d H:i:s",$vo['endtime']); ?></td>
                <td><?php echo date("Y-m-d H:i:s",$vo['addtime']); ?></td>
                <td>

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
        layer.confirm("<?php echo lang('确定删除？'); ?>", {
            btn: ["<?php echo lang('确定'); ?>", "<?php echo lang('取消'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('dress_up/user_dress_del'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id,type:1},
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
    function vipset(id,time_end) {
        /*if(time_end.length<=1){
            var time_end = '';
        }*/
        const currentLanguage = navigator.language || navigator.userLanguage;
        console.log(currentLanguage);
        time_end = '<?php echo $time; ?>';
        layer.open({
            type: 0,
            title: "<?php echo lang('装饰设置'); ?>",
            area: ['400px', '300px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('确定'); ?>", "<?php echo lang('取消'); ?>"], //按钮
            content: '<div class="layui-form"><label class="layui-form-label"><?php echo lang('USER_ID'); ?></label><input  type="text" id="uid" class="form-control"><label class="layui-form-label"><?php echo lang('装饰ID'); ?></label><input  type="text" id="dress_id" class="form-control" name="dress_id"><label class="layui-form-label"><?php echo lang('结束时间'); ?></label><input  type="text" id="vip_end_time" class="form-control" name="vip_end_time" value="'+time_end+'" style="width: 140px;" autocomplete="off" oninput = "value=value.replace(/[^\\d]/g,\'\')"></div>',
            success:function(){
                Wind.css('bootstrapDatetimePicker');
                Wind.use('bootstrapDatetimePicker', function () {
                    $("#vip_end_time").datetimepicker({
                        language: currentLanguage,
                        format: 'yyyy-mm-dd hh:ii',
                        todayBtn: 1,
                        autoclose: true
                    });
                });
            },
            yes:function(index){   //点击确定回调
                //alert($('#vip_end_time').val());
                layer.close(index);
                var time = $('#vip_end_time').val();
                var dress_id = $('#dress_id').val();
                var uid = $('#uid').val();

                $.ajax({
                    url: "<?php echo url('DressUp/dress_set'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {id: id,time:time,dress_id:dress_id,uid:uid},
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
    }
</script>
</body>
</html>
