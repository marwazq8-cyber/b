<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:46:"themes/admin_simpleboot3/admin/refill/add.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
<style type="text/css">
    .pic-list li {
        margin-bottom: 5px;
    }
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('refill/index'); ?>"><?php echo lang('充值列表'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('添加充值分类'); ?></a></li>
    </ul>
    <form action="<?php echo url('refill/addPost'); ?>" method="post" >
        <div class="row">
            <div class="col-md-9">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('充值金额(元) '); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[money]"
                                   id="title" required value="<?php echo (isset($rule['money'] ) && ($rule['money']  !== '')?$rule['money'] :''); ?>" placeholder="<?php echo lang('请输入金额'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('谷歌支付唯一标识'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[google_pay_id]"
                                   value="<?php echo (isset($rule['google_pay_id'] ) && ($rule['google_pay_id']  !== '')?$rule['google_pay_id'] :''); ?>" placeholder="<?php echo lang('谷歌支付唯一标识'); ?>"/>
                        </td>
                    </tr>

                   <tr>
                        <th><?php echo lang('苹果内购名称，留空时苹果内购列表不显示'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[apple_pay_name]"
                                   id="apple_pay_name" value="<?php echo (isset($rule['apple_pay_name'] ) && ($rule['apple_pay_name']  !== '')?$rule['apple_pay_name'] :''); ?>" placeholder="<?php echo lang('Please_enter_name'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('苹果内购金额(元)'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[ios_money]"
                                   id="ios_money" value="<?php echo (isset($rule['ios_money'] ) && ($rule['ios_money']  !== '')?$rule['ios_money'] :''); ?>" placeholder="<?php echo lang('请输入金额'); ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('苹果内购获得钻石数量'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[apple_pay_coin]"
                                   id="apple_pay_coin" value="<?php echo (isset($rule['apple_pay_coin'] ) && ($rule['apple_pay_coin']  !== '')?$rule['apple_pay_coin'] :'0'); ?>" placeholder="<?php echo lang('钻石数量'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('需要充值的金币数 '); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[coin]" id="source" value="<?php echo (isset($rule['coin'] ) && ($rule['coin']  !== '')?$rule['coin'] :''); ?>"
                                   placeholder="<?php echo lang('请输入金币数'); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('STATUS'); ?><span class="form-required">*</span></th>
                        <td>
                            <input  type="radio" name="post[type]" <?php if($rule['type'] == 1): ?>checked="checked"<?php endif; ?>  value="1"/>&nbsp;&nbsp;<?php echo lang('OPEN'); ?> &nbsp;&nbsp;&nbsp;&nbsp;
                            <input  type="radio" name="post[type]" <?php if($rule['type'] == 0): ?>checked="checked"<?php endif; ?> value="0"/>&nbsp;&nbsp;<?php echo lang('CLOSE'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('赠送金额 '); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[give]" id="money2" value="<?php echo (isset($rule['give'] ) && ($rule['give']  !== '')?$rule['give'] :''); ?>"
                                   placeholder="<?php echo lang('赠送金额'); ?>"></td>
                    </tr>


                    <tr>
                        <th><?php echo lang('SORT'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[orderno]" style="width:100px;"
                                   required value="<?php echo (isset($rule['orderno'] ) && ($rule['orderno']  !== '')?$rule['orderno'] :'100'); ?>" placeholder="<?php echo lang('SORT'); ?>"/>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="id" value="<?php echo (isset($rule['id'] ) && ($rule['id']  !== '')?$rule['id'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('refill/index'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>


</body>
</html>
