<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:49:"themes/admin_simpleboot3/admin/consume/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .gift-img{width:50px;height:50px}
    .gift-img img{width:100%;height:100%;}
    .gift-in input{width:25px;}
    .js-ajax-form{margin-top:30px;}
    .identity img{width:30px;height:30px;border-radius: 50%;}
    .details{cursor: pointer;}
    .layui-layer-demo .layui-layer-title{
        background: #e0e0e0!important;
    }
    .form-control{width:110px!important;}
    #status,#type{    width: 100px;
        height: 32px;
        border-color: #dce4ec;
        color: #aeb5bb;}
    .table-list{font-size:14px!important;}
    .consume-col{color:#ff41ee;}
    .layui-layer{width: 1000px!important;}
    a{text-decoration: none;}
    .details_type td{text-align: center;}
    .consume_count{width:100%;height:40px;line-height: 40px}
    .consume_count span{margin-left:30px;}
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_CONSUME_LIST'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" name="form1" method="post">
        <?php echo lang('ADMIN_CONSUME_USER_ID'); ?>：
        <input class="form-control" type="text" name="uid" style="width: 200px;" value="<?php echo input('request.uid'); ?>"
               placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('ADMIN_CONSUME_INCOME_USER'); ?>：
        <input class="form-control" type="text" name="touid" style="width: 200px;" value="<?php echo input('request.touid'); ?>"
               placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('ADMIN_CONSUME_TYPE'); ?>：
        <select name="type" id="type">
            <option value="-1"><?php echo lang('全部消费'); ?></option>
            <?php if(is_array($Consumer_classification) || $Consumer_classification instanceof \think\Collection || $Consumer_classification instanceof \think\Paginator): if( count($Consumer_classification)==0 ) : echo "" ;else: foreach($Consumer_classification as $key=>$c): ?>
                <option value="<?php echo $c['id']; ?>" <?php if($request['type'] == $c['id']): ?> selected="selected" <?php endif; ?> ><?php echo $c['title']; ?></option>
            <?php endforeach; endif; else: echo "" ;endif; ?>

        </select>
        <?php echo lang('ADMIN_GUILD_ID'); ?>：
        <input class="form-control" type="text" name="guild_uid" style="width: 200px;" value="<?php echo input('request.guild_uid'); ?>"
               placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('agent_level1'); ?>ID：
        <input class="form-control" type="text" name="agent_company" style="width: 200px;" value="<?php echo input('request.agent_company'); ?>"
               placeholder="<?php echo lang('agent_level1'); ?>ID">

        <?php echo lang('TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-datetime" name="start_time"
               value="<?php echo (isset($request['start_time']) && ($request['start_time'] !== '')?$request['start_time']:''); ?>"
               style="width: 200px;" autocomplete="off">-
        <input type="text" class="form-control js-bootstrap-datetime" name="end_time"
               value="<?php echo (isset($request['end_time']) && ($request['end_time'] !== '')?$request['end_time']:''); ?>"
               style="width: 200px;" autocomplete="off"> &nbsp; &nbsp;

        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" onclick='form1.action="<?php echo url('Consume/index'); ?>";form1.submit();'/>
        <a class="btn btn-danger" href="<?php echo url('Consume/index'); ?>"><?php echo lang('EMPTY'); ?></a>

        <input type="button" class="btn btn-primary from_export" style="background-color: #1dccaa;" value="<?php echo lang('导出'); ?>" onclick='form1.action="<?php echo url('Consume/export'); ?>";form1.submit();'>


    </form>

    <form class="js-ajax-form" action="<?php echo url('Consume/upd'); ?>" method="post">

        <?php if($is_show_total == 1): ?>
         <h4><?php echo lang('ADMIN_ALL_CONSUMPTION'); ?>: <?php echo $coin; ?>(<?php echo $currency_name; ?>)<span style="margin-left: 20px"><?php echo $system_coin; ?>(<?php echo $system_currency_name; ?>)</span><span style="margin-left: 60px"><?php echo lang('ADMIN_ALL_INCOME'); ?>：<?php echo $total; ?>(<?php echo $profit_name; ?>)</span></h4>
        <?php else: endif; ?>
        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th>ID</th>
                <th><?php echo lang('ADMIN_CONSUME_USER'); ?>（ID）</th>
                <th><?php echo lang('ADMIN_INCOME_USER'); ?>（ID）</th>
                <th><?php echo lang('ADMIN_CONSUME_NUM'); ?></th>
                <th><?php echo lang('ADMIN_ANCHOR_INCOME'); ?>(<?php echo $profit_name; ?>)</th>
                <th><?php echo lang('ADMIN_CONSUME_TYPE'); ?></th>
                <th><?php echo lang('ADMIN_CONSUME_CONTENT'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_GUILD'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_GUILD_INCOME'); ?></th>
                <th>{:<?php echo lang('agent_level1'); ?>}(<?php echo lang('公司ID/员工ID/推广员ID'); ?>)</th>
                <th><?php echo lang('ADMIN_CONSUME_TIME'); ?></th>

            </tr>
            </thead>
            <tfoot>

            <?php if(is_array($data) || $data instanceof \think\Collection || $data instanceof \think\Paginator): if( count($data)==0 ) : echo "" ;else: foreach($data as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['id']; ?></td>
                    <td><?php echo $vo['uname']; ?>(<?php echo $vo['user_id']; ?>)</td>
                    <td>
                        <?php if($vo['to_user_id'] == 0): ?>
                           <?php echo lang('平台'); else: ?>
                            <?php echo $vo['toname']; ?>(<?php echo $vo['to_user_id']; ?>)
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $vo['coin']; ?> <?php echo $vo['coin_type']==1?$currency_name : $system_currency_name; ?>
                    </td>
                    <td><?php echo $vo['profit']; ?></td>
                    <th>
                        <?php if($vo['type'] == 4): ?>
                            <a href="javascript:void(0);" class="details" data-id="<?php echo $vo['table_id']; ?>"> <?php echo $type[$vo['type']]; ?></a>
                        <?php else: ?>
                             <?php echo $type[$vo['type']]; endif; ?>

                    </th>
                    <th><?php echo $vo['content']; ?></th>
                    <td><?php echo $vo['guild_name']; ?>(<?php echo $vo['guild_uid']; ?>)</td>
                    <td><?php echo $vo['guild_earnings']; ?></td>
                    <td><?php echo $vo['agent_company_name']; ?>（<?php echo $vo['agent_company']; ?> /<?php echo $vo['agent_staff']; ?> /<?php echo $vo['agent_id']; ?>）</td>
                    <th><?php echo date("Y-m-d H:i:s",$vo['create_time'] ); ?></th>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
        <ul class="pagination"><?php echo $page; ?></ul>

    </form>

</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script src="/static/js/admin.js"></script>
<script>
    const currency_name = '<?php echo $currency_name; ?>';
    $(".details").click(function(){
        var id=$(this).attr("data-id");
        $.ajax({
            url: "<?php echo url('consume/select_call'); ?>",
            type: 'get',
            dataType: 'json',
            offset: 'rb', //具体配置参考：offset参数项
            area: ['1000px', '500px'],
            data: {id: id},
            success: function (data) {

                var html='<div style="width:1000px!important;height:500px;"><div class="consume_count"><span><?php echo lang("总消费"); ?>('+ currency_name +')：'+data.coin+'</span><span><?php echo lang("主播总收益"); ?>：'+data.profit+' </span><span><?php echo lang("邀请总收益 ："); ?> '+data.money+'<span><span><?php echo lang("总时长 ："); ?> '+data.time+'<span></div><table class="table table-hover table-bordered details_type"><thead><tr><th style="text-align: center;background: #f7f7f7;"><?php echo lang('ADMIN_CONSUME_USER'); ?>（ID）</th><th style="text-align: center;background: #f7f7f7;"><?php echo lang('ADMIN_INCOME_USER'); ?>（ID）</th><th style="text-align: center;background: #f7f7f7;"><?php echo lang('ADMIN_CONSUMPTION_NUMBER'); ?>（'+ currency_name +'）</th><th style="text-align: center;background: #f7f7f7;"><?php echo lang('ADMIN_ANCHOR_INCOME'); ?></th><th style="text-align: center;background: #f7f7f7;"><?php echo lang("主播邀请人(ID)"); ?></th><th style="text-align: center;background: #f7f7f7;"><?php echo lang("邀请收益(元)"); ?></th><th style="text-align: center;background: #f7f7f7;"><?php echo lang('ADMIN_CONSUME_CONTENT'); ?></th><th style="text-align: center;background: #f7f7f7;"><?php echo lang('ADMIN_CONSUME_TIME'); ?></th></tr></thead><tbody>';
               var user=data.user;
                for(var i=0;i<user.length;i++){
                    html+='<tr><td>'+user[i]['uname']+'('+user[i]['user_id']+')</td><td>'+user[i]['toname']+'('+user[i]['to_user_id']+')</td><td>'+user[i]['coin']+'</td><td>'+user[i]['profit']+'</td><td>';
                    if(user[i]['cid']){
                         html+=user[i]['cname']+'('+user[i]['cid']+')</td><td>'+user[i]['money'];
                    }else{
                         html+='</td><td>';
                    }
                    html+='</td><td>'+user[i]['content']+'</td><td>'+user[i]['create_time']+'</td></tr>';

                }
                html+='</tbody></table></div>';
                 //自定页
                 layer.open({
                    type: 1,
                    title: "<?php echo lang('Video_call'); ?>",
                    closeBtn: 0,
                    shadeClose: true,
                    skin: 'yourclass',
                    content: html
                });

            }
        });



    })


</script>
</body>
</html>
