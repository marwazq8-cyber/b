<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:55:"themes/admin_simpleboot3/user/admin_index/add_user.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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

    .gift {
        margin-top: 40px;
    }

    #gift {
        width: 30%;
        height: 35px;
        border-color: #dce4ec;
        color: #a5b6c6;
    }
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
          <li><a href="<?php echo url('user/AdminIndex/index'); ?>"><?php echo lang('USER_INDEXADMIN_INDEX'); ?></a></li>
         <li  class="active"><a><?php echo lang("添加用户"); ?></a></li>
    </ul>
    <form action="<?php echo url('AdminIndex/addUserPost'); ?>" class="js-ajax-form" method="post">
        <div class="row gift">
            <div class="col-md-8  col-md-offset-2">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('USERNAME'); ?>（登录账号）<span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="user_login"
                                  required value="" placeholder="<?php echo lang('请输入用户名称'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("USER_NICKNAME"); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="user_nickname"
                                    required value="" placeholder="<?php echo lang('请输入昵称'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('区号'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="number" name="mobile_area_code"
                                   id="mobile_area_code" value="" placeholder="<?php echo lang('区号'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_PHONE_NUMBER'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="mobile"
                                   id="mobile" value=""  placeholder="<?php echo lang('ADMIN_PHONE_NUMBER'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('GENDER'); ?></th>
                        <td>
                            <select class="form-control" name="sex">
                                <option value="1"><?php echo lang('MALE'); ?></option>
                                <option value="2"><?php echo lang('FEMALE'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('AVATAR'); ?><span class="form-required">*</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="avatar" id="thumbnail">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('STATUS'); ?> <span class="form-required">*</span></th>
                        <td>
                            <select class="form-control" name="is_online">
                                <option value="0"><?php echo lang("下线"); ?></option>
                                <option value="1"><?php echo lang("上线"); ?></option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('Is_it_certified'); ?> <span class="form-required">*</span></th>
                        <td>
                            <select class="form-control" name="is_auth">
                                <option value="0"><?php echo lang('NO'); ?></option>
                                <option value="1"><?php echo lang('YES'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("音频上传"); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" readonly name="audio_file"  id="files" value="" style="width:70%;">
                            <a href="javascript:uploadOne('音频上传','#files','audio');"
                               class="btn btn-sm btn-default"><?php echo lang("音频上传"); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("音频时长 "); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="audio_time"
                                    value="" placeholder="<?php echo lang('请输入音频时长 秒'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("country"); ?><span class="form-required">*</span></th>
                        <td>

                            <select class="form-control" name="num_code">
                                <?php if(is_array($countries) || $countries instanceof \think\Collection || $countries instanceof \think\Paginator): if( count($countries)==0 ) : echo "" ;else: foreach($countries as $key=>$vo): ?>
                                    <option value="<?php echo $vo['num_code']; ?>"><?php echo $vo['en_short_name']; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('gift/index'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
<script>
    $(function () {

        $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });

    });
</script>

</body>
</html>
<script type="text/javascript">

</script>
