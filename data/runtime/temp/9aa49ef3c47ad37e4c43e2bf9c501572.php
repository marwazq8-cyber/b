<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:56:"themes/admin_simpleboot3/admin/refill/edit_pay_menu.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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

    .js-ajax-form {
        margin-top: 30px;
    }
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('refill/pay_menu'); ?>"><?php echo lang('支付渠道列表'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('添加支付渠道'); ?></a></li>
    </ul>
    <form class="js-ajax-form" action="<?php echo url('refill/edit_pay_menu_post'); ?>" method="post" >
        <div class="row">
            <div class="col-md-9">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('ADMIN_NAME'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[pay_name]"
                                   id="title" required value="<?php echo (isset($data['pay_name'] ) && ($data['pay_name']  !== '')?$data['pay_name'] :''); ?>" placeholder="<?php echo lang('Please_enter_name'); ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('支付图标'); ?><span class="form-required">*</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[icon]" id="thumbnail" value="<?php echo (isset($data['icon'] ) && ($data['icon']  !== '')?$data['icon'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if($data['icon']): ?>
                                        <img src="<?php echo $data['icon']; ?>"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>
                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('商户号 '); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[merchant_id]" id="merchant_id" value="<?php echo (isset($data['merchant_id'] ) && ($data['merchant_id']  !== '')?$data['merchant_id'] :''); ?>"
                                 ></td>
                    </tr>

                    <tr>
                        <th>APP_ID <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[app_id]" id="app_id" value="<?php echo (isset($data['app_id'] ) && ($data['app_id']  !== '')?$data['app_id'] :''); ?>"
                                   placeholder="<?php echo lang('APP ID'); ?>"></td>
                    </tr>


                    <tr>
                        <th><?php echo lang('类名 '); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[class_name]" id="class_name" value="<?php echo (isset($data['class_name'] ) && ($data['class_name']  !== '')?$data['class_name'] :''); ?>"
                                  ></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('STATUS'); ?><span class="form-required">*</span></th>
                        <td>
                            <input  type="radio" name="post[status]" <?php if($data['status'] == 1): ?>checked="checked"<?php endif; ?>  value="1"/>&nbsp;&nbsp;<?php echo lang('OPEN'); ?> &nbsp;&nbsp;&nbsp;&nbsp;
                            <input  type="radio" name="post[status]" <?php if($data['status'] == 0): ?>checked="checked"<?php endif; ?> value="0"/>&nbsp;&nbsp;<?php echo lang('CLOSE'); ?>
                        </td>
                    </tr>
<!--                    <tr>
                        <th>IOS是否显示<span class="form-required">*</span></th>
                        <td>
                            <input  type="radio" name="post[ios_on]" <?php if($data['ios_on'] == 1): ?>checked="checked"<?php endif; ?>  value="1"/>&nbsp;&nbsp;是 &nbsp;&nbsp;&nbsp;&nbsp;
                            <input  type="radio" name="post[ios_on]" <?php if($data['ios_on'] == 0): ?>checked="checked"<?php endif; ?> value="0"/>&nbsp;&nbsp;否
                        </td>
                    </tr>-->

                    <tr>
                        <th><?php echo lang('公钥 '); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[public_key]" id="public_key" value="<?php echo (isset($data['public_key'] ) && ($data['public_key']  !== '')?$data['public_key'] :''); ?>"
                                   placeholder="<?php echo lang('公钥'); ?>"></td>
                    </tr>

                    <tr>
                        <th><?php echo lang('私钥'); ?><span class="form-required">*</span></th>

                        <td><input class="form-control" type="text" name="post[private_key]" id="private_key" value="<?php echo (isset($data['private_key'] ) && ($data['private_key']  !== '')?$data['private_key'] :''); ?>"
                                   ></td>
                    </tr>
                </table>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('refill/pay_menu'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>


                <input type="hidden" name="id" value="<?php echo (isset($data['id'] ) && ($data['id']  !== '')?$data['id'] :''); ?>"/>
            </div>

        </div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>


</body>
</html>
