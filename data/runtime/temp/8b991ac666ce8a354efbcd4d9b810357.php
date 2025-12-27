<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:51:"themes/admin_simpleboot3/admin/voice/add_index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .select{width: 150px;height: 30px;}
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('voice/index'); ?>"><?php echo lang('语音直播列表'); ?></a></li>
        <li class="active"><a href="<?php echo url('voice/add_index'); ?>"><?php echo lang('添加语音直播'); ?></a></li>
    </ul>
    <form action="<?php echo url('voice/addPost_index'); ?>" method="post">
        <div class="row gift">
            <div class="col-md-8  col-md-offset-2">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('房间标题'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[title]"
                                   required  placeholder="<?php echo lang('Please_enter_room_title'); ?>" value="<?php echo (isset($list['title']) && ($list['title'] !== '')?$list['title']:''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('房间公告'); ?><span class="form-required">*</span></th>
                        <td>
                            <textarea class="form-control" name="post[announcement]" id="" cols="10" rows="3"><?php echo (isset($list['announcement']) && ($list['announcement'] !== '')?$list['announcement']:''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('USER_ID'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[user_id]"
                                   required  placeholder="<?php echo lang('Please_enter_user_ID'); ?>" value="<?php echo (isset($list['user_id']) && ($list['user_id'] !== '')?$list['user_id']:''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('房间图片'); ?><span class="form-required">*</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[avatar]" id="thumbnail" value="<?php echo (isset($list['avatar'] ) && ($list['avatar']  !== '')?$list['avatar'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if($list['avatar']): ?>
                                        <img src="<?php echo $list['avatar']; ?>"
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
                        <th><?php echo lang('房间类型'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[room_type]" class="form-control">
                                <option value="1" <?php if($list['room_type'] == 1): ?> selected = "selected" <?php endif; ?>><?php echo lang('交友厅'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('语音频道分类'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[voice_type]" class="form-control">
                                <?php if(is_array($voice_type) || $voice_type instanceof \think\Collection || $voice_type instanceof \think\Paginator): if( count($voice_type)==0 ) : echo "" ;else: foreach($voice_type as $key=>$vo): ?>
                                    <option value="<?php echo $vo['id']; ?>" <?php if($list['voice_type'] == $vo['id']): ?> selected = "selected" <?php endif; ?>><?php echo $vo['name']; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </td>
                    </tr>
                    <!--<tr>
                       <th><?php echo lang('直播间类型'); ?><span class="form-required">*</span></th>
                       <td>
                           <select name="post[type]" class="select">
                               <option value="1" <?php if($list['type'] == 1): ?> selected = "selected" <?php endif; ?>><?php echo lang('单人直播'); ?></option>
                               <option value="2" <?php if($list['type'] == 2): ?> selected = "selected" <?php endif; ?>><?php echo lang('多人直播'); ?></option>
                           </select>
                       </td>
                   </tr>-->
                    <tr>
                        <th><?php echo lang('是否是密码房间'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[voice_status]" class="form-control">
                                <option value="0" <?php if($list['voice_status'] == '0'): ?> selected = "selected" <?php endif; ?>><?php echo lang('否'); ?></option>
                                <option value="1" <?php if($list['voice_status'] == 1): ?> selected = "selected" <?php endif; ?>><?php echo lang('是'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('房间密码'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="number" name="post[voice_psd]"
                                   placeholder="<?php echo lang('请输入房间密码'); ?>" value="<?php echo (isset($list['voice_psd']) && ($list['voice_psd'] !== '')?$list['voice_psd']:''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('房间背景图'); ?><span class="form-required">*</span></th>
                        <td>
                            <?php if(is_array($voice_bg) || $voice_bg instanceof \think\Collection || $voice_bg instanceof \think\Paginator): if( count($voice_bg)==0 ) : echo "" ;else: foreach($voice_bg as $key=>$v): ?>
                                <label>
                                    <input type="radio" name="post[voice_bg]" value="<?php echo $v['id']; ?>" <?php if($list['voice_bg'] == $v['id']): ?> checked = "checked" <?php endif; ?>>
                                    <img src="<?php echo $v['image']; ?>" style="width: 50px;height: 50px;">
                                </label>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </td>

                    </tr>
                    <tr>
                        <th><?php echo lang('排序 '); ?><span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[sort]" placeholder="<?php echo lang('排序'); ?>" value="<?php echo (isset($list['sort']) && ($list['sort'] !== '')?$list['sort']:'0'); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('STATUS'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[status]" class="form-control">
                                <option value="1" <?php if($list['status'] == '1'): ?> selected = "selected" <?php endif; ?>><?php echo lang('开启'); ?></option>
                                <option value="2" <?php if($list['status'] == '2'): ?> selected = "selected" <?php endif; ?>><?php echo lang('关闭'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <input class="form-control" type="hidden" name="id"  value="<?php echo (isset($list['id']) && ($list['id'] !== '')?$list['id']:''); ?>" />
                </table>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('voice/index'); ?>"><?php echo lang('BACK'); ?></a>
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
