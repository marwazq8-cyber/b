<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:60:"themes/admin_simpleboot3/admin/dress_up/chat_bubble_add.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li><a  href="<?php echo url('dress_up/chat_bubble'); ?>"><?php echo lang('ADMIN_CHAT_BUBBLE_LIST'); ?></a></li>
        <li><a href="<?php echo url('Dress_up/noble_chat_bubble'); ?>"><?php echo lang('ADMIN_NOBLE'); ?> <?php echo lang('ADMIN_CHAT_BUBBLE_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_CHAT_BUBBLE_ADD'); ?></a></li>
    </ul>
    <form action="<?php echo url('dress_up/addPost'); ?>" method="post" >
        <div class="row">
            <div class="col-md-9">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('ADMIN_NAME'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[name]"
                                   id="title" required value="<?php echo (isset($list['name'] ) && ($list['name']  !== '')?$list['name'] :''); ?>" placeholder="<?php echo lang('Please_enter_name'); ?>"/>
                        </td>
                    </tr>
                    <input type="hidden" name="post[type]" value="4">
                    <tr>
                        <th><?php echo lang('ADMIN_CHAT_BUBBLE'); ?><span class="form-required">630*186 <?php echo lang('提示：安卓需要.9图'); ?></span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[icon]" id="thumbnail" value="<?php echo (isset($data['icon'] ) && ($data['icon']  !== '')?$data['icon'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if(empty($data['icon']) || (($data['icon'] instanceof \think\Collection || $data['icon'] instanceof \think\Paginator ) && $data['icon']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['icon']; ?>"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_CHAT_BUBBLE'); ?><span class="form-required"><?php echo lang('提示：苹果气泡图'); ?></span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[ios_icon]" id="thumbnail1" value="<?php echo (isset($data['ios_icon'] ) && ($data['ios_icon']  !== '')?$data['ios_icon'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail1');">
                                    <?php if(empty($data['ios_icon']) || (($data['ios_icon'] instanceof \think\Collection || $data['ios_icon'] instanceof \think\Paginator ) && $data['ios_icon']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnail1-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['ios_icon']; ?>"
                                             id="thumbnail1-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail1" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_CHAT_BUBBLE_DEMO'); ?><span class="form-required">138*122 </span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[img_bg]" id="thumbnailan" value="<?php echo (isset($data['img_bg'] ) && ($data['img_bg']  !== '')?$data['img_bg'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnailan');">
                                    <?php if(empty($data['img_bg']) || (($data['img_bg'] instanceof \think\Collection || $data['img_bg'] instanceof \think\Paginator ) && $data['img_bg']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnailan-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['img_bg']; ?>"
                                             id="thumbnailan-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnailan" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                    <!--<tr>
                        <th>IOS聊天气泡<span class="form-required">138*122</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[v_bg]" id="thumbnailios" value="<?php echo (isset($data['v_bg'] ) && ($data['v_bg']  !== '')?$data['v_bg'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnailios');">
                                    <?php if(empty($data['v_bg']) || (($data['v_bg'] instanceof \think\Collection || $data['v_bg'] instanceof \think\Paginator ) && $data['v_bg']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnailios-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['v_bg']; ?>"
                                             id="thumbnailios-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnailios" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>-->
                    <tr>
                        <th><?php echo lang('ADMIN_TEXT_COLOR'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[colors]" id="colors" style="width:100px;"
                                   required value="<?php echo (isset($list['colors'] ) && ($list['colors']  !== '')?$list['colors'] :'#000000'); ?>" placeholder="<?php echo lang('颜色值默认#000000'); ?>"/>
                            <input type="color" id="colorGet" onchange="ColorGetFn(this)">&nbsp;&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('天'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[days]"
                                  value="<?php echo (isset($list['days'] ) && ($list['days']  !== '')?$list['days'] :''); ?>" placeholder="<?php echo lang('天'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('价格'); ?>(<?php echo $currency_name; ?>) <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[coin]"
                                   value="<?php echo (isset($list['coin'] ) && ($list['coin']  !== '')?$list['coin'] :''); ?>" placeholder="<?php echo lang('请输入数量'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('SORT'); ?> <span class="form-required"></span></th>
                        <td>
                            <input class="form-control" type="text" name="post[orderno]"
                                   id="title" required value="<?php echo (isset($list['orderno'] ) && ($list['orderno']  !== '')?$list['orderno'] :'50'); ?>" placeholder="<?php echo lang('SORT'); ?>"/>
                        </td>
                    </tr>

                </table>
                <input type="hidden" name="id" value="<?php echo (isset($list['id'] ) && ($list['id']  !== '')?$list['id'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('dress_up/chat_bubble'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
<script>
    function ColorGetFn(dom){
        document.getElementById('colors').value = dom.value;
    }
 $('.btn-level-thumbnail').click(function () {
            $('#level-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#level').val('');
        });
  $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });
</script>
</body>
</html>
