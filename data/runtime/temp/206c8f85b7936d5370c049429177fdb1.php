<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:60:"themes/admin_simpleboot3/admin/withdrawals_manage/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
<link href="/static/css/admin_button.css" rel="stylesheet" type="text/css">
</head>

<style>

    #status,#type{    width: 100px;
        height: 32px;
        border-color: #dce4ec;
        color: #aeb5bb;}
    .getChecked{float:left;width:80px;height:34px;line-height: 34px;text-align: center;background: #2C3E50;color:#fff;margin-right:10px;cursor:pointer;}
</style>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('withdrawals_manage/index'); ?>"><?php echo lang('提现记录'); ?></a></li>
        <li><a href="<?php echo url('withdrawals_manage/user_binding'); ?>"><?php echo lang('账号绑定列表'); ?></a></li>
    </ul>
    <div class="table-actions">
        <form class="well form-inline margin-top-20" name="form1" method="post">
            <?php echo lang('ADMIN_PHONE_NUMBER'); ?>:
            <input type="text" class="form-control" name="mobile" style="width: 120px;" value="<?php echo (isset($request['mobile']) && ($request['mobile'] !== '')?$request['mobile']:''); ?>" placeholder="<?php echo lang('请输入手机号'); ?>">
            <?php echo lang('USER_ID'); ?>:
            <input type="text" class="form-control" name="id" style="width: 120px;" value="<?php echo (isset($request['id']) && ($request['id'] !== '')?$request['id']:''); ?>" placeholder="<?php echo lang('USER_ID'); ?>">
           <?php echo lang('昵称'); ?>:
            <input type="text" class="form-control" name="name" style="width: 120px;" value="<?php echo (isset($request['name']) && ($request['name'] !== '')?$request['name']:''); ?>" placeholder="<?php echo lang('请输入用户昵称'); ?>">
           <?php echo lang('BENEFICIARY_ACCOUNT'); ?>:
            <input type="text" class="form-control" name="pay" style="width: 120px;" value="<?php echo (isset($request['pay']) && ($request['pay'] !== '')?$request['pay']:''); ?>" placeholder="<?php echo lang('BENEFICIARY_ACCOUNT'); ?>">
            <?php echo lang('ADMIN_CHECK_STATUS'); ?>：
            <select name="status" id="status">
                <option value="-1"><?php echo lang('ALL'); ?></option>
                <option value="0" <?php if($request['status'] == '0'): ?> selected="selected" <?php endif; ?>><?php echo lang('UNREVIEWED'); ?></option>
                <option value="1" <?php if($request['status'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang('AUDITED'); ?></option>
                <option value="2" <?php if($request['status'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang('REJECTED'); ?></option>
            </select>
<!--             <?php echo lang('提现类型'); ?>:-->
<!--            <select name="type" id="status">-->
<!--                <option value="0"><?php echo lang('ALL'); ?></option>-->
<!--                <option value="1" <?php if($request['type'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang('WECHAT'); ?></option>-->
<!--                <option value="2" <?php if($request['type'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang('ALIPAY'); ?></option>-->
<!--                <option value="3" <?php if($request['type'] == 3): ?> selected="selected" <?php endif; ?>><?php echo lang('银行卡'); ?></option>-->
<!--            </select>-->
            <br/>
            <input type="hidden"  name="page"  value="<?php echo (isset($request['page']) && ($request['page'] !== '')?$request['page']:'1'); ?>">
            <div style="margin-top:20px;"> <?php echo lang('TIME'); ?>:
                <input type="text" class="form-control js-bootstrap-datetime" name="start_time" value="<?php echo (isset($request['start_time']) && ($request['start_time'] !== '')?$request['start_time']:''); ?>" style="width: 140px;" autocomplete="off">-
                <input type="text" class="form-control js-bootstrap-datetime" name="end_time" value="<?php echo (isset($request['end_time']) && ($request['end_time'] !== '')?$request['end_time']:''); ?>" style="width: 140px;" autocomplete="off"> &nbsp; &nbsp;
                <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" onclick='form1.action="<?php echo url('withdrawals_manage/index'); ?>";form1.submit();'/>
                <a class="btn btn-danger" href="<?php echo url('withdrawals_manage/index'); ?>"><?php echo lang('EMPTY'); ?></a>
                <input type="button" class="btn btn-primary from_export" style="background-color: #1dccaa;" value="<?php echo lang('导出'); ?>" onclick='form1.action="<?php echo url('withdrawals_manage/export'); ?>";form1.submit();'>
                <span> <?php echo lang('Withdrawal_quantity'); ?>：<?php echo $income; ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo lang('ADMIN_WITHDRAW_MONEY'); ?>：<?php echo $money; ?> </span>
            </div>

        </form>
    </div>
    <form class="js-ajax-form" action="<?php echo url('withdrawals_manage/refuse_cash_all'); ?>" method="post">
    <div style="margin-bottom: 10px;">
        <div class="getChecked" onclick="getChecked('on')"><?php echo lang('全选'); ?></div>
        <div class="getChecked" onclick="getChecked('off')"><?php echo lang('取消全选'); ?></div>
        <div class="getChecked" onclick="getChecked('onWWoff')"><?php echo lang('反选'); ?></div>
        <button type="submit" name="type" value="1" class="btn btn-primary"><?php echo lang('批量通过'); ?></button>
        <button type="submit" name="type" value="2" class="btn btn-primary"><?php echo lang('批量拒绝'); ?></button>
    </div>
    <table class="table table-hover table-bordered table-list">
        <thead>
        <tr>
            <th></th>
            <th width="50">ID</th>
            <th><?php echo lang('USER_ID'); ?></th>
            <th><?php echo lang('USER_NICKNAME'); ?></th>
            <th><?php echo lang('ADMIN_PHONE_NUMBER'); ?></th>
            <th><?php echo lang('Withdrawal_quantity'); ?></th>
            <th><?php echo lang('ADMIN_WITHDRAW_MONEY'); ?></th>
            <th><?php echo lang('提现类型'); ?></th>
            <th><?php echo lang('Name_of_withdrawal'); ?></th>
            <th><?php echo lang('Withdrawal_payment_account_number'); ?></th>
            <th><?php echo lang('SUBMIT_TIME'); ?></th>
            <th><?php echo lang('STATUS'); ?></th>
            <th><?php echo lang('打款状态'); ?></th>
            <th width="130"><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php 
            $statuses=array('0'=> lang('UNREVIEWED'),"1"=>lang('AUDIT_BY'),"2" => lang('Refuse_to_withdraw'));
            $transfer_status=array('0'=>lang('未打款'),"1"=>lang('已打款'),"2" => "");
         if(is_array($data) || $data instanceof \think\Collection || $data instanceof \think\Paginator): if( count($data)==0 ) : echo "" ;else: foreach($data as $key=>$vo): ?>
            <tr>
                <td>
                    <input type="checkbox" name="id[]" value="<?php echo $vo['id']; ?>" class="chechbox_input">
                </td>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['user_id']; ?></td>
                <td><?php echo $vo['user_nickname']; ?></td>
                <td><?php echo $vo['mobile']; ?></td>
                <td><?php echo $vo['income']; ?></td>
                <th><?php echo $vo['money']; ?></th>
                <td>
                    <?php if($vo['type'] == 1): ?>
                        <?php echo lang('WECHAT'); elseif($vo['type'] == 2): ?>
                            <?php echo lang('ALIPAY'); else: ?>
                        <?php echo lang('银行卡'); ?>(<?php echo $vo['bank_name']; ?>)
                    <?php endif; ?>
                </td>
                <td><?php echo $vo['gathering_name']; ?></td>
                <td><?php echo $vo['gathering_number']; ?></td>

                <td><?php echo date("Y-m-d H:i:s",$vo['create_time'] ); ?></td>

                <td><?php echo $statuses[$vo['status']]; ?></td>
                <td><?php echo $transfer_status[$vo['transfer_status']]; ?></td>
                <td>
                    <button class="ipt btn btn-info" type="button"><?php echo lang('ACTIONS'); ?> <strong>+</strong></button>
                    <ul id="ul">
                        <?php if($vo['status'] == 1 && $vo['transfer_status'] == 0 && $alipay_fund_transfer_status == 1): ?>
                            <li>
                                <a class="transfer_status" data-id="<?php echo $vo['id']; ?>"><?php echo lang('打款'); ?></a>
                            </li>
                        <?php endif; if($vo['status'] == 1 && $vo['transfer_status'] == 0 && $alipay_fund_transfer_status == 0): ?>
                            <li>
                                <a class="transfer_status_open" data-id="<?php echo $vo['id']; ?>"><?php echo lang('打款'); ?></a>
                            </li>
                        <?php endif; if($vo['status'] == 0): ?>
                            <li>
                                <a href="<?php echo url('withdrawals_manage/adopt_cash',array('id'=>$vo['id'])); ?>"><?php echo lang('AUDIT_BY'); ?></a>
                            </li>
                            <li>
                                <a href="<?php echo url('withdrawals_manage/refuse_cash',array('id'=>$vo['id'])); ?>"><?php echo lang('REFUSE'); ?></a>
                            </li>
                            <?php else: ?>
                            <li>
                                <a href="<?php echo url('withdrawals_manage/del',array('id'=>$vo['id'])); ?>"><?php echo lang('DELETE'); ?></a>
                            </li>
                        <?php endif; ?>

                    </ul>



                </td>
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>

    <div class="pagination"><?php echo $page; ?></div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
<script type="text/javascript" src="/static/js/admin_button.js"></script>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
</body>
</html>
<script>
    $('.transfer_status').click(function(){
        var id = $(this).attr('data-id');

        var html = '<?php echo lang("打款备注"); ?>:<textarea style="width: 100%" type="text" name="" id="remark" value=""></textarea>';
        //自定页
        layer.open({
            title: "<?php echo lang('填写打款备注'); ?>",
            type: 1,
            skin: 'layui-layer-demo', //样式类名
            closeBtn: 0, //不显示关闭按钮
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('关闭'); ?>"],
            area: '450px;',
            anim: 2,
            shadeClose: true, //开启遮罩关闭
            content: "<div style='width:80%;margin-left: 10%;margin-top: 30px;' class='sel' name=''>"+html+"<div/>",
            success: function(layero){
                var btn = layero.find('.layui-layer-btn');

                btn.find('.layui-layer-btn0').click(function () {
                    var remark = $("#remark").val();
                    $.ajax({
                        url: "<?php echo url('withdrawals_manage/fund_transfer'); ?>",
                        type: 'post',
                        dataType: 'json',
                        data: {id: id,remark:remark},
                        success: function (data) {

                            if (data.code == '1') {
                                layer.msg(data.msg, {time: 2000, icon: 1}, function () {
                                    window.location.reload();
                                });
                            } else {
                                layer.msg(data.msg, {time: 2000, icon: 2});
                            }
                        }
                    });
                });

            }
        });
    })
    $(".transfer_status_open").click(function(){
        var id=$(this).attr('data-id');
        layer.confirm("<?php echo lang('是否已线下打款？'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function(){
            $.ajax({
                url: "<?php echo url('withdrawals_manage/fund_transfer_status'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id},
                success: function (data) {
                    if(data =='1'){
                        layer.msg("<?php echo lang('修改成功'); ?>",{time: 2000, icon:1},function(){
                            window.location.reload();
                        });
                    }else{
                        layer.msg("<?php echo lang('修改失败'); ?>",{time: 2000, icon:2});
                    }
                }
            });

        });
    })
    function getChecked(a) {
        var x = document.getElementsByClassName('chechbox_input');
        for (var i = 0; i < x.length; i++) {
            switch (a) {
                case 'on':
                    x[i].checked = true;
                    break;
                case 'off':
                    x[i].checked = false;
                    break;
                case 'onWWoff':
                    x[i].checked = !x[i].checked;
                    break;
            };
        }
    }
</script>