<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:54:"themes/admin_simpleboot3/admin/guild_manage/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .guild-img {
        width: 50px;
        height: 50px
    }

    .js-ajax-form {
        margin-top: 30px;
    }
    .btn-info:hover,.btn-info:click{
        outline:0!important;
        color:#2a6496!important;
        background: #ecf0f1!important;
        border-color: #ecf0f1!important;

    }
    .btn-info:focus{
        outline:0!important;
        box-shadow:none;
    }

    .btn-info:hover{
        color:#2a6496!important;
        background: #ecf0f1!important;
        border-color: #ecf0f1!important;
    }

    .btn-info{
        color:#2a6496!important;
        background: #fff!important;
        border-color: #fff!important;
    }
    #ul{
        font-size: 12px;
        list-style:none;
        min-width:120px;
        border:1px solid #c1c1c1;
        display:none;
        padding-left:0px;
        position: absolute;
        right:60px;
        background-color: #ffffff
    }
    .ipt{
        /*margin:1px 0 0 0px;*/
        /*border:0px solid;*/
    }
    #ul li a{
        display:inline-block;
        float:left;
        width:100%;
        height:30px;
        line-height: 30px;
        text-decoration:none;

        color: #333;
    }
    #ul li{
        margin-left: 0;
        padding-left: 0;
        width:100%;height: 30px;
        padding-left:15px;

    }

    #ul li:hover{
        background-color:#f5f5f5;
    }
    #ul li a:hover{
        color:#333;
    }
    .ipt::-ms-input-placeholder {
        text-align: center;
    }

    .ipt::-webkit-input-placeholder {
        text-align: center;

    }
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_GUILD_LIST'); ?></a></li>
        <li><a href="<?php echo url('guild_manage/add'); ?>"><?php echo lang('ADMIN_GUILD_ADD'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" name="form1" method="post" action="<?php echo url('guild_manage/index'); ?>">
        <?php echo lang('公会长ID'); ?>:
        <input type="text" class="form-control" name="guild_uid" style="width: 120px;" value="<?php echo (isset($data['guild_uid']) && ($data['guild_uid'] !== '')?$data['guild_uid']:''); ?>" placeholder="<?php echo lang('请输入公会长ID'); ?>">
        <?php echo lang('ADMIN_GUILD_NAME'); ?>:
        <input type="text" class="form-control" name="name" style="width: 120px;" value="<?php echo (isset($data['name']) && ($data['name'] !== '')?$data['name']:''); ?>" placeholder="<?php echo lang('ADMIN_GUILD_NAME'); ?>">
        <?php echo lang('USER_ID'); ?>:
        <input type="text" class="form-control" name="uid" style="width: 120px;" value="<?php echo (isset($data['uid']) && ($data['uid'] !== '')?$data['uid']:''); ?>" placeholder="<?php echo lang('USER_ID'); ?>">

        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
        <a class="btn btn-danger" href="<?php echo url('guild_manage/index'); ?>"><?php echo lang('EMPTY'); ?></a>
    </form>
    <div style="width: 100%;height:30px;line-height: 30px;color:#f45e5e;padding-left: 20px;margin:15px 0;">
        <span>*<?php echo lang('ADMIN_GUILD_COMMISSION_INCOME'); ?></span><span style="margin-left: 20px">*<?php echo lang('ADMIN_GUILD_ADMIN_URL'); ?>：<?php echo $union; ?></span></div>
    <form class="js-ajax-form" action="<?php echo url('guild_manage/upd'); ?>" method="post" style="margin-top: 0px;">

        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th>ID</th>
                <th><?php echo lang('ADMIN_GUILD_NAME'); ?></th>
                <th><?php echo lang('公会长'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_INFO'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_LOGO'); ?></th>
<!--                <th><?php echo lang('ADMIN_GUILD_COMMISSION_TYPE'); ?></th>-->
                <th><?php echo lang('ADMIN_GUILD_ALL_USER_NUM'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_COMMISSION_RATIO'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_INCOME'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_TOTAL_INCOME'); ?></th>
                <th><?php echo lang('STATUS'); ?></th>
                <th><?php echo lang('LAST_CREATE_TIME'); ?></th>
                <th><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['id']; ?></td>
                    <td><?php echo $vo['name']; ?></td>
                    <td><?php echo $vo['user_nickname']; ?>(<?php echo $vo['user_id']; ?>)</td>
                    <td><?php echo $vo['introduce']; ?></td>
                    <td><img class="guild-img" src="<?php echo $vo['logo']; ?>" alt=""></td>
<!--                    <td>-->
<!--                        <?php if($vo['type'] == '1'): ?>-->
<!--                            平台-->
<!--                            <?php else: ?>-->
<!--                            <?php echo lang('ADMIN_ANCHOR'); ?>-->
<!--                        <?php endif; ?>-->
<!--                    </td>-->
                    <td><?php echo $vo['number']; ?></td>
                    <td><?php echo $vo['commission']; ?></td>
                    <td><?php echo $vo['earnings']; ?></td>
                    <td><?php echo $vo['total_earnings']; ?></td>
                    <td>
                        <?php if($vo['status'] == '0'): ?>
                            <?php echo lang('CHECK_LOADING'); elseif($vo['status'] == '1'): ?>
                            <?php echo lang('AUDIT_BY'); else: ?>
                            <?php echo lang('AUDIT_NO'); endif; ?>
                    </td>

                    <td><?php echo date("Y-m-d H:i:s",$vo['create_time'] ); ?></td>
                    <td>
                        <button class="ipt btn btn-info" type="button"><?php echo lang('ACTIONS'); ?> <strong>+</strong></button>
                        <ul id="ul">
                            <li>
                                <!--<a href="#" onclick="select_join_list(<?php echo $vo['id']; ?>)"><?php echo lang('查看主播列表'); ?></a>-->
                                <a href="<?php echo url('guild_manage/user_list',array('id'=>$vo['id'])); ?>"><?php echo lang('ADMIN_CHECK_ANCHOR'); ?></a>
                            </li>
                            <li>
                                <a href="#" onclick="sel_room_flow(<?php echo $vo['id']; ?>)"><?php echo lang('ADMIN_VOICE_VOICE_DETAILS'); ?></a>
<!--                                <a href="<?php echo url('guild_manage/room_flow',array('guild_uid'=>$vo['user_id'])); ?>"><?php echo lang('ADMIN_VOICE_VOICE_DETAILS'); ?></a>-->
                            </li>
                            <li>
                                <a href="<?php echo url('guild_manage/add',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
                            </li>
                            <li>
                                <a href="javescript:;" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('DELETE'); ?></a>
                            </li>
                        </ul>

                    </td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
        <div class="pagination"><?php echo $page; ?></div>
    </form>

</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script>
    function sel_room_flow(uid) {
        layer.open({
            type: 2,
            title:   "<?php echo lang('ADMIN_VOICE_VOICE_DETAILS'); ?>",
            shadeClose: true,
            area: ['80%', '90%'],   //宽高
            shade: 0.4,   //遮罩透明度
            content:  "<?php echo url('guild_manage/room_flow'); ?>?guild_uid=" + uid
        });
    }
    function select_info(id) {
        layer.open({
            type: 2,
            title: '公会信息',
            shadeClose: true,
            shade: 0.8,
            area: ['50%', '60%'],
            content: "<?php echo url('guild_manage/select_guild_info'); ?>?id=" + id //iframe的url
        });
    }

    function select_join_list(id) {
        layer.open({
            type: 2,
            title: '主播列表',
            shadeClose: true,
            shade: 0.8,
            area: ['65%', '70%'],
            content: "<?php echo url('guild_manage/join_list'); ?>?id=" + id //iframe的url
        });
    }
     $(".del").click(function(){
        var id=$(this).attr('data-id');
        layer.confirm('工会下主播自动解散,确定删除？', {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function(){
            $.ajax({
                url: "<?php echo url('guild_manage/del'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id},
                success: function (data) {
                    if(data =='1'){
                        layer.msg("<?php echo lang('DELETE_SUCCESS'); ?>",{time: 2000, icon:1},function(){
                            window.location.reload();
                        });
                    }else{
                        layer.msg("<?php echo lang('DELETE_FAILED'); ?>",{time: 2000, icon:2});
                    }
                }
            });

        });
    })

    flag = true;
    $(".ipt").click(function(){

        if(flag){
            $(this).children('strong').html(' -');
            $(this).next('ul').css('display','block');
            flag = false;
        }else{
            flag = true;
            var self = $(this);
            setTimeout(function(){
                self.children('strong').html(' +');
                self.next('ul').css('display','none');
            },200)
        }


    });

    $(".ipt").blur(function(){
        flag = true;
        var self = $(this);
        setTimeout(function(){
            self.children('strong').html('+');
            self.next('ul').css('display','none');
        },200)
    });
</script>
</body>
</html>
