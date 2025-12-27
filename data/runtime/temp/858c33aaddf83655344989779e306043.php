<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:45:"themes/admin_simpleboot3/admin/level/add.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .select{width:130px;height:35px;line-height: 35px;border: 1px solid #dce4ec;}
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('level/level_index'); ?>"><?php echo lang('ADMIN_LEVEL_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_LEVEL_ADD'); ?></a></li>
    </ul>
    <form action="<?php echo url('level/addPost'); ?>" method="post" >
        <div class="row">
            <div class="col-md-9">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('ADMIN_LEVEL_NAME'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="number" name="post[level_name]"
                                   id="title" required value="<?php echo (isset($level['level_name'] ) && ($level['level_name']  !== '')?$level['level_name'] :''); ?>" placeholder="<?php echo lang('请输入等级'); ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('ADMIN_LEVEL_COMMISSION'); ?> <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[level_up]" id="source" value="<?php echo (isset($level['level_up'] ) && ($level['level_up']  !== '')?$level['level_up'] :''); ?>"
                                   placeholder="<?php echo lang('请输入消费(收益)总数 '); ?>"></td>
                    </tr>

                    <tr>
                        <th><?php echo lang('ADMIN_LEVEL_ICON'); ?><span class="form-required">*</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[chat_icon]" id="thumbnail" value="<?php echo (isset($level['chat_icon'] ) && ($level['chat_icon']  !== '')?$level['chat_icon'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if($level['chat_icon']): ?>
                                        <img src="<?php echo $level['chat_icon']; ?>"
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
                        <th><?php echo lang('聊天背景图 '); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[level_type_id]" class="select">
                                <?php if(is_array($level_type) || $level_type instanceof \think\Collection || $level_type instanceof \think\Paginator): if( count($level_type)==0 ) : echo "" ;else: foreach($level_type as $key=>$vo): ?>
                                <option value="<?php echo $vo['id']; ?>" <?php if($vo['id'] == $level['level_type_id']): ?> selected="selected" <?php endif; ?>><?php echo $vo['level_type_name']; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('等级类型 '); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[type]" class="select">
                                <option value="1" <?php if($level['type'] == 1): ?> selected="selected" <?php endif; ?>><?php echo lang('财富'); ?></option>
                                <option value="2" <?php if($level['type'] == 2): ?> selected="selected" <?php endif; ?>><?php echo lang('魅力'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('SORT'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[sort]" style="width:100px;"
                                   required value="<?php echo (isset($level['sort'] ) && ($level['sort']  !== '')?$level['sort'] :''); ?>" placeholder="<?php echo lang('SORT'); ?>"/>
                        </td>
                    </tr>

                </table>
                <input type="hidden" name="levelid" value="<?php echo (isset($level['levelid'] ) && ($level['levelid']  !== '')?$level['levelid'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('level/level_index'); ?>"><?php echo lang('BACK'); ?></a>
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
