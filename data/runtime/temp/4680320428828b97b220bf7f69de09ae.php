<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:45:"themes/admin_simpleboot3/admin/noble/add.html";i:1733921385;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li><a href="<?php echo url('noble/index'); ?>"><?php echo lang('ADMIN_NOBLE_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_NOBLE_ADD'); ?></a></li>

    </ul>
    <form action="<?php echo url('noble/addPost'); ?>" method="post">
        <div class="row gift">
            <div class="col-md-8  col-md-offset-2">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('ADMIN_NOBLE_NAME'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($data['lang_name']) || $data['lang_name'] instanceof \think\Collection || $data['lang_name'] instanceof \think\Paginator): if( count($data['lang_name'])==0 ) : echo "" ;else: foreach($data['lang_name'] as $key=>$v): ?>
                                <?php echo $v['name']; ?> :
                                <input class="form-control" type="text" name="lang_name[<?php echo $v['name']; ?>]"
                                       value="<?php echo (isset($v['value'] ) && ($v['value']  !== '')?$v['value'] :''); ?>" placeholder="<?php echo lang('请输入贵族名称'); ?>"/>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_MEAL'); ?><span class="form-required">*360*360</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[noble_img]" id="thumbnail" value="<?php echo (isset($data['noble_img'] ) && ($data['noble_img']  !== '')?$data['noble_img'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if(empty($data['noble_img']) || (($data['noble_img'] instanceof \think\Collection || $data['noble_img'] instanceof \think\Paginator ) && $data['noble_img']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['noble_img']; ?>"
                                             id="thumbnail-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Profile Icon<span class="form-required">*360*360</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[profile_icon]" id="profile-icon" value="<?php echo (isset($data['profile_icon'] ) && ($data['profile_icon']  !== '')?$data['profile_icon'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#profile-icon');">
                                    <?php if(empty($data['profile_icon']) || (($data['profile_icon'] instanceof \think\Collection || $data['profile_icon'] instanceof \think\Paginator ) && $data['profile_icon']->isEmpty())): ?>
                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="profile-icon-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>
                                        <img src="<?php echo $data['profile_icon']; ?>"
                                             id="profile-icon-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Room Image<span class="form-required">*360*360</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[room_image]" id="room-image" value="<?php echo (isset($data['room_image'] ) && ($data['room_image']  !== '')?$data['room_image'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#room-image');">
                                    <?php if(empty($data['room_image']) || (($data['room_image'] instanceof \think\Collection || $data['room_image'] instanceof \think\Paginator ) && $data['room_image']->isEmpty())): ?>
                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="room-image-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>
                                        <img src="<?php echo $data['room_image']; ?>"
                                             id="room-image-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnail" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('SORT'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[orderno]" style="width:100px;"
                                   required value="<?php echo (isset($data['orderno'] ) && ($data['orderno']  !== '')?$data['orderno'] :''); ?>" placeholder="<?php echo lang('SORT'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('STATUS'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[status]" id="gift">
                                <option value="1"><?php echo lang('OPEN'); ?></option>
                                <option value="0"><?php echo lang('CLOSE'); ?></option>

                            </select>
                        </td>
                    </tr>
                    <!--<tr>
                        <th><?php echo lang('通知方式'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[type]" id="types">
                                <option value="1"><?php echo lang('房间内'); ?></option>
                                <option value="2"><?php echo lang('全房间'); ?></option>
                                <option value="3"><?php echo lang('系统通知'); ?></option>

                            </select>
                        </td>
                    </tr>-->
                    <tr>
                        <th><?php echo lang('ADMIN_PRICE'); ?> <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[coin]" id="source"
                                   value="<?php echo (isset($data['coin'] ) && ($data['coin']  !== '')?$data['coin'] :''); ?>"
                                   placeholder="<?php echo lang('请输入心币数'); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_RENEWAL_PRICE'); ?> <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[renew_coin]" id="renew_coin"
                                   value="<?php echo (isset($data['renew_coin'] ) && ($data['renew_coin']  !== '')?$data['renew_coin'] :''); ?>"
                                   placeholder="<?php echo lang('请输入心币数'); ?>"></td>
                    </tr>

                    <tr>
                        <th><?php echo lang('续费'); ?><?php echo lang('ADMIN_SEND'); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[return_coin]" id="source"
                                   value="<?php echo (isset($data['return_coin'] ) && ($data['return_coin']  !== '')?$data['return_coin'] :''); ?>"
                                   placeholder="<?php echo lang('请输入赠送心币数'); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_TIMES_DAY'); ?> <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[noble_time]" id="noble_time"
                                   value="<?php echo (isset($data['noble_time'] ) && ($data['noble_time']  !== '')?$data['noble_time'] :''); ?>"
                                   placeholder="<?php echo lang('时长/天'); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_PRIVILEGE'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($privilege) || $privilege instanceof \think\Collection || $privilege instanceof \think\Paginator): if( count($privilege)==0 ) : echo "" ;else: foreach($privilege as $key=>$val): ?>
                                <label>
                                    <input class="privilege<?php echo $val['id']; ?>" name="privilege_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?><?php echo $val['privilege_info']; ?> &nbsp;
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('ADMIN_MEAL'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($medal) || $medal instanceof \think\Collection || $medal instanceof \think\Paginator): if( count($medal)==0 ) : echo "" ;else: foreach($medal as $key=>$val): ?>
                            <label>
                                 <input class="other<?php echo $val['id']; ?>" name="medal_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?>
                            </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_HOME_PAGE'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($home_page) || $home_page instanceof \think\Collection || $home_page instanceof \think\Paginator): if( count($home_page)==0 ) : echo "" ;else: foreach($home_page as $key=>$val): ?>
                                <label>
                                    <input class="other<?php echo $val['id']; ?>" name="home_page_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?>
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_AVATAR_FRAME'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($avatar_frame) || $avatar_frame instanceof \think\Collection || $avatar_frame instanceof \think\Paginator): if( count($avatar_frame)==0 ) : echo "" ;else: foreach($avatar_frame as $key=>$val): ?>
                                <label>
                                    <input class="other<?php echo $val['id']; ?>" name="avatar_frame_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?>
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_CHAT_BUBBLE'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($chat_bubble) || $chat_bubble instanceof \think\Collection || $chat_bubble instanceof \think\Paginator): if( count($chat_bubble)==0 ) : echo "" ;else: foreach($chat_bubble as $key=>$val): ?>
                                <label>
                                    <input class="other<?php echo $val['id']; ?>" name="chat_bubble_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?>
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_CHAT_BG'); ?> <span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($chat_bg) || $chat_bg instanceof \think\Collection || $chat_bg instanceof \think\Paginator): if( count($chat_bg)==0 ) : echo "" ;else: foreach($chat_bg as $key=>$val): ?>
                                <label>
                                    <input class="other<?php echo $val['id']; ?>" name="chat_bg_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?> &nbsp;
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('进场动画 '); ?><span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($car) || $car instanceof \think\Collection || $car instanceof \think\Paginator): if( count($car)==0 ) : echo "" ;else: foreach($car as $key=>$val): ?>
                                <label>
                                    <input class="other<?php echo $val['id']; ?>" name="car_id[]" value="<?php echo $val['id']; ?>" type="checkbox" > <?php echo $val['name']; ?> &nbsp;
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('ADMIN_NICKNAME_COLORS'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[colors]" id="colors" style="width:100px;"
                                   required value="<?php echo (isset($data['colors'] ) && ($data['colors']  !== '')?$data['colors'] :'#000000'); ?>" placeholder="<?php echo lang('颜色值默认#000000'); ?>"/>
                            <input type="color" id="colorGet" onchange="ColorGetFn(this)">&nbsp;&nbsp;
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('ADMIN_ENTRY_REMINDER'); ?><span class="form-required">*668*168</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[entry_effects]" id="entryEffects" value="<?php echo (isset($data['entry_effects'] ) && ($data['entry_effects']  !== '')?$data['entry_effects'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#entryEffects');">
                                    <?php if(empty($data['entry_effects']) || (($data['entry_effects'] instanceof \think\Collection || $data['entry_effects'] instanceof \think\Paginator ) && $data['entry_effects']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="entryEffects-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['entry_effects']; ?>"
                                             id="entryEffects-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-entryEffects" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>

                </table>
                <input type="hidden" name="id" value="<?php echo (isset($data['id'] ) && ($data['id']  !== '')?$data['id'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('noble/index'); ?>"><?php echo lang('BACK'); ?></a>
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
    var status = <?php echo (isset($data['noble_status'] ) && ($data['noble_status']  !== '')?$data['noble_status'] : 1); ?>;
    var types = <?php echo (isset($data['type'] ) && ($data['type']  !== '')?$data['type'] : 1); ?>;
    $('#gift').val(status);
    $('#types').val(types);
    $(function () {

        $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });
         $('.btn-cancel-svga').click(function () {
            $('#svga-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#svga').val('');
        });

    });
    var checkcount = <?php echo $checkcount; ?>;
    var check = [];
    var strs = '<?php echo $check; ?>';

    check = strs.split(',');
    if(checkcount>0){
        for(var i=0;i<checkcount;i++){
            $('.privilege'+check[i]).attr('checked', 'checked');
        }
    }

    var checkcount_other = <?php echo $checkcount_other; ?>;
    var other = '<?php echo $other; ?>';
    var check_other = other.split(',');
    if(checkcount_other>0){
        for(var i=0;i<checkcount_other;i++){
            $('.other'+check_other[i]).attr('checked', 'checked');
        }
    }
</script>

</body>
</html>
