<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:48:"themes/admin_simpleboot3/admin/dress_up/add.html";i:1733597524;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li><a  href="<?php echo url('dress_up/index'); ?>"><?php echo lang('ADMIN_MEAL_LIST'); ?></a></li>
        <li><a href="<?php echo url('dress_up/noble_index'); ?>"><?php echo lang('ADMIN_NOBLE'); ?> <?php echo lang('ADMIN_MEAL_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_MEAL_ADD'); ?></a></li>
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
                    <input type="hidden" name="post[type]" value="1">
                    <tr>
                        <th><?php echo lang('ADMIN_ICON'); ?><span class="form-required">264*264</span></th>
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
                    <tr class="hidden">
                        <th><?php echo lang('天'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[days]"
                                   id="title" required value="<?php echo (isset($list['days'] ) && ($list['days']  !== '')?$list['days'] :'0'); ?>" placeholder="<?php echo lang('天'); ?>"/>
                        </td>
                    </tr>
                    <tr class="hidden">
                        <th><?php echo lang('价格'); ?>(<?php echo $currency_name; ?>) <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[coin]"
                                   id="title" required value="<?php echo (isset($list['coin'] ) && ($list['coin']  !== '')?$list['coin'] :'0'); ?>" placeholder="<?php echo lang('请输入数量'); ?>"/>
                        </td>
                    </tr>
                    <!--<tr>
                        <th><?php echo lang('展示背景图'); ?><span class="form-required">1500*1702</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[v_bg]" id="thumbnailvbg" value="<?php echo (isset($data['v_bg'] ) && ($data['v_bg']  !== '')?$data['v_bg'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnailvbg');">
                                    <?php if(empty($data['v_bg']) || (($data['v_bg'] instanceof \think\Collection || $data['v_bg'] instanceof \think\Paginator ) && $data['v_bg']->isEmpty())): ?>

                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumbnailvbg-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>

                                        <img src="<?php echo $data['v_bg']; ?>"
                                             id="thumbnailvbg-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>

                                </a>
                                <input type="button" class="btn btn-sm btn-cancel-thumbnailvbg" value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
-->

                    <tr>
                        <th><?php echo lang('SORT'); ?> <span class="form-required"></span></th>
                        <td>
                            <input class="form-control" type="text" name="post[orderno]"
                                   id="title" required value="<?php echo (isset($list['orderno'] ) && ($list['orderno']  !== '')?$list['orderno'] :'50'); ?>" placeholder="<?php echo lang('SORT'); ?>"/>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Type Duration <span class="form-required"></span></th>
                        <td>
                            <select class="form-control" name="post[Type_duration]" id="Type_duration" required>
                                <option value="1" <?php echo (isset($list['Type_duration']) && ($list['Type_duration'] !== '')?$list['Type_duration']:1)===1?'selected' : ''; ?>>Week</option>
                                <option value="2" <?php echo (isset($list['Type_duration']) && ($list['Type_duration'] !== '')?$list['Type_duration']:1)===2?'selected' : ''; ?>>Month</option>
                                <option value="3" <?php echo (isset($list['Type_duration']) && ($list['Type_duration'] !== '')?$list['Type_duration']:1)===3?'selected' : ''; ?>>Year</option>
                                <option value="4" <?php echo (isset($list['Type_duration']) && ($list['Type_duration'] !== '')?$list['Type_duration']:1)===4?'selected' : ''; ?>>unlimited</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Type of Medal <span class="form-required"></span></th>
                        <td>
                            <select class="form-control" name="post[type_of_medal]" id="type_of_medal" required onchange="toggleInputField()">
                                <option value="1" <?php echo (isset($list['type_of_medal']) && ($list['type_of_medal'] !== '')?$list['type_of_medal']:1)===1?'selected' : ''; ?>>for Supporter</option>
                                <option value="2" <?php echo (isset($list['type_of_medal']) && ($list['type_of_medal'] !== '')?$list['type_of_medal']:1)===2?'selected' : ''; ?>>for receiver</option>
                                <option value="3" <?php echo (isset($list['type_of_medal']) && ($list['type_of_medal'] !== '')?$list['type_of_medal']:1)===3?'selected' : ''; ?>>for gift giver</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="extra_input_row" style="display: none;">
                        <th>Gift ID <span class="form-required"></span></th>
                        <td>
                            <input class="form-control" type="text" name="post[gift_id]" value="<?php echo (isset($list['gift_id']) && ($list['gift_id'] !== '')?$list['gift_id']:NULL); ?>" id="extra_info" placeholder="Enter extra information"/>
                        </td>
                    </tr>
                    <tr>
                        <th>target <span class="form-required"></span></th>
                        <td>
                            <input class="form-control" type="text" name="post[target_coin]"
                                   id="title" required value="<?php echo (isset($list['target_coin'] ) && ($list['target_coin']  !== '')?$list['target_coin'] :'1'); ?>" placeholder="Target Coin"/>
                        </td>
                    </tr>
                    <script>
                        function toggleInputField() {
                            var typeOfMedal = document.getElementById("type_of_medal").value;
                            var extraInputRow = document.getElementById("extra_input_row");
                        
                            if (typeOfMedal === "3") {
                                extraInputRow.style.display = "table-row"; 
                            } else {
                                extraInputRow.style.display = "none";
                            }
                        }
                    </script>

                </table>
                <input type="hidden" name="id" value="<?php echo (isset($list['id'] ) && ($list['id']  !== '')?$list['id'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('dress_up/index'); ?>"><?php echo lang('BACK'); ?></a>
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
