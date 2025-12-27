<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:41:"themes/admin_simpleboot3/admin/login.html";i:1730282264;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title><?php echo $config_log['system_name']; ?></title>
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge"/>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta name="robots" content="noindex,nofollow">
    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->
    <link href="/themes/admin_simpleboot3/public/assets/themes/<?php echo cmf_get_admin_style(); ?>/bootstrap.min.css" rel="stylesheet">
    <link href="/static/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="/themes/admin_simpleboot3/public/assets/themes/<?php echo cmf_get_admin_style(); ?>/login.css" rel="stylesheet">
    <script>
        if (window.parent !== window.self) {
            document.write              = '';
            window.parent.location.href = window.self.location.href;
            setTimeout(function () {
                document.body.innerHTML = '';
            }, 0);
        }
    </script>
</head>
<style>
    .form-control{
        height: 55px;
        /*border:none;
        border-bottom: 1px solid #c0c0c0;*/
        /*-webkit-box-shadow: none;*/
        /*box-shadow:none;*/
        font-size: 18px;
        padding: 6px 32px;
        border-radius: 5px;
    }
    .btn{
        padding: 10px 12px;
        border-radius: 5px;
    }
    .has-error .form-control, .has-error .form-control:focus, .has-error .input-group-addon {
        /*border: none;*/
        border-bottom: 1px solid #E74C3C;
    }

</style>
<body>
<div class="login-top">
    <div class="login-top-title"><?php echo $config_log['system_name']; ?></div>
</div>
<div class="logn-box">
    <div class="logn-box-l">
        <div class="logn-box-l-title"><?php echo lang('账号登录'); ?>  <?php echo get_client_ip(); ?></div>
        <div class="logn-box-l-bottom">
            <form class="js-ajax-form" action="<?php echo url('public/doLogin'); ?>" method="post">
                <div class="form-group">
                    <img class="zh-l-img" src="/themes/admin_simpleboot3/public/assets/images/zhl.png" alt="">
                    <input type="text" id="input_username" class="form-control" name="username"
                           placeholder="<?php echo lang('USERNAME_OR_EMAIL'); ?>" title="<?php echo lang('USERNAME_OR_EMAIL'); ?>"
                           value="<?php echo cookie('admin_username'); ?>" data-rule-required="true" data-msg-required="">
                </div>

                <div class="form-group">
                    <img class="zh-l-img" src="/themes/admin_simpleboot3/public/assets/images/psl.png" alt="">
                    <input type="password" id="input_password" class="form-control" name="password"
                           placeholder="<?php echo lang('PASSWORD'); ?>" title="<?php echo lang('PASSWORD'); ?>" data-rule-required="true"
                           data-msg-required="">
                </div>

                <?php if(IS_TEST): ?>

                    <div class="form-group">
                        <label>Find the business representative who is currently in contact with you</label>
                        <div style="position: relative;">
                            <img class="zh-l-img" src="/themes/admin_simpleboot3/public/assets/images/zhl.png" alt="">
                            <input type="text" name="business_mobile" placeholder="Please enter the official business WhatsApp" class="form-control"
                                   data-rule-required="true" data-msg-required="">
                        </div>
                    </div>
                    <?php else: endif; ?>

                <div class="form-group">
                    <div style="position: relative;">
                        <img class="zh-l-img" src="/themes/admin_simpleboot3/public/assets/images/yzml.png" alt="">
                        <input type="text" name="captcha" placeholder="<?php echo lang('验证码'); ?>" class="form-control captcha" style="width:60%;">
                        <?php $__CAPTCHA_SRC=url('/captcha/new').'?height=48&width=150&font_size=18'; ?>
<img src="<?php echo $__CAPTCHA_SRC; ?>" onclick="this.src='<?php echo $__CAPTCHA_SRC; ?>&time='+Math.random();" title="换一张" class="captcha captcha-img verify_img" style="cursor: pointer;position:absolute;right:2px;top:3px;"/>
                    </div>
                </div>

                <div class="form-group">
                    <input type="hidden" name="redirect" value="">
                    <button class="btn btn-primary btn-block js-ajax-submit" type="submit" style="margin-top: 35px;margin-left: 5%;background: #5293FB;border: none;font-size: 18px;width: 90%;"
                            data-loadingmsg="<?php echo lang('LOADING'); ?>">
                        <?php echo lang('LOGIN'); ?>
                    </button>
                </div>

            </form>
        </div>

    </div>
    <div class="logn-box-r">
        <?php if(IS_OFFICIAL == 1): ?>
            <img src="/themes/admin_simpleboot3/public/assets/images/login.png" alt="">
            <?php else: ?>
            <img src="/themes/admin_simpleboot3/public/assets/images/no_official_login.png" alt="">
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    //全局变量
    var GV = {
        ROOT: "/",
        WEB_ROOT: "/",
        JS_ROOT: "static/js/",
        APP: ''/*当前应用名*/
    };
</script>
<script src="/themes/admin_simpleboot3/public/assets/js/jquery-1.10.2.min.js"></script>
<script src="/static/js/wind.js"></script>
<script src="/static/js/admin.js"></script>
<script>
    (function () {
        document.getElementById('input_username').focus();
    })();
</script>
</body>
</html>
