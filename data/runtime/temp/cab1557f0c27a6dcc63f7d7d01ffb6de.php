<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:52:"themes/admin_simpleboot3/admin/guild_manage/add.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .title-img{
        width: 15px;
    }
    .title-content{
        position: absolute;
        background: rgba(1,1,1,0.1);
        padding: 2px 8px;
        font-size: 12px;
        color: #999999;
        border-radius: 10px;
        display: none;
    }
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('guild_manage/index'); ?>"><?php echo lang('ADMIN_GUILD_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_GUILD_ADD'); ?></a></li>
    </ul>
    <form action="<?php echo url('guild_manage/addPost'); ?>" method="post">
        <div class="row gift">
            <div class="col-md-8  col-md-offset-2">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('ADMIN_GUILD_LOGIN_ACCOUNT'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[login]"
                                   id="title" required value="<?php echo (isset($list['login'] ) && ($list['login']  !== '')?$list['login'] :''); ?>" placeholder="<?php echo lang('请输入公会登录账号'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_GUILD_LOGIN_PASSWORD'); ?><span class="form-required">*(<?php echo lang('6位以上的密码'); ?>)</span></th>
                        <td>
                            <input class="form-control" type="text" name="psd" id="psd" placeholder="<?php echo lang('请输入密码6位以上的密码'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_GUILD_LOGIN_NICKNAME'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[name]"
                                   id="title" required value="<?php echo (isset($list['name'] ) && ($list['name']  !== '')?$list['name'] :''); ?>" placeholder="<?php echo lang('请输入公会登录昵称'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('公会长ID'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[user_id]"
                                   id="user_id" required value="<?php echo (isset($list['user_id'] ) && ($list['user_id']  !== '')?$list['user_id'] :''); ?>" placeholder="<?php echo lang('请输入绑定的用户ID'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_IMG'); ?><span class="form-required">*</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[logo]" id="thumbnail" value="<?php echo (isset($list['logo'] ) && ($list['logo']  !== '')?$list['logo'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if($list['logo']): ?>
                                        <img src="<?php echo $list['logo']; ?>"
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
                        <th><?php echo lang('ADMIN_PHONE_NUMBER'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post[tel]"
                                   id="tel" required value="<?php echo (isset($list['tel'] ) && ($list['tel']  !== '')?$list['tel'] :''); ?>" placeholder="<?php echo lang('请输入公会手机号'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_GUILD_INFO'); ?> <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[introduce]" id="source"
                                   value="<?php echo (isset($list['introduce'] ) && ($list['introduce']  !== '')?$list['introduce'] :''); ?>"
                                   placeholder="<?php echo lang('请输入公会介绍'); ?>"></td>
                    </tr>

                    <tr>
                        <th><?php echo lang('STATUS'); ?><span class="form-required">*</span></th>
                        <td>
                            <select name="post[status]" id="gift">
                                <option value="1"><?php echo lang('OPEN'); ?></option>
                                <option value="0" <?php if($list['status'] == '0'): ?> selected="selected" <?php endif; ?> ><?php echo lang('CLOSE'); ?></option>

                            </select>
                        </td>
                    </tr>

<!--                    <tr>-->
<!--                        <th><?php echo lang('ADMIN_GUILD_TYPE'); ?><span class="form-required">*</span>-->
<!--                            <img class="title-img" src="/static/image/ww.png" alt="">-->
<!--                            <div class="title-content"><?php echo lang('平台:消费数计算，主播:实际收益计算'); ?></div>-->
<!--                        </th>-->
<!--                        <td>-->
<!--                            <select name="post[type]" id="gift">-->
<!--                                <option value="1"><?php echo lang('平台'); ?></option>-->
<!--                                <option value="2" <?php if($list['type'] == '2'): ?> selected="selected" <?php endif; ?> ><?php echo lang('主播'); ?></option>-->
<!--                            </select>-->
<!--                        </td>-->
<!--                    </tr>-->
                    <tr>
                        <th><?php echo lang('ADMIN_GUILD_COMMISSION_RATIO'); ?> <span class="form-required">*</span></th>
                        <td><input class="form-control" type="text" name="post[commission]" id="commission"
                                   value="<?php echo (isset($list['commission'] ) && ($list['commission']  !== '')?$list['commission'] :''); ?>"
                                   placeholder="<?php echo lang('请输入0-1的数值'); ?>"></td>
                    </tr>



                    <!--<tr>
                       <th><?php echo lang('权限'); ?><span class="form-required">*</span></th>
                       <td>
                           <?php if(is_array($rule) || $rule instanceof \think\Collection || $rule instanceof \think\Paginator): if( count($rule)==0 ) : echo "" ;else: foreach($rule as $key=>$v): ?>
                               <label>
                                   <input type="checkbox" name="rule[]" value="<?php echo $v['id']; ?>" <?php if($v['type'] == 1): ?> checked="checked" <?php endif; ?>>  <?php echo $v['name']; ?>
                               </label>&nbsp;&nbsp;
                          <?php endforeach; endif; else: echo "" ;endif; ?>
                       </td>
                   </tr>-->

                </table>
                <input type="hidden" name="id" value="<?php echo (isset($list['id'] ) && ($list['id']  !== '')?$list['id'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('ADD'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('guild_manage/index'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
</body>
</html>
<script>
    var title_status = 1;
    $('.title-img').click(function(){
        //var title = $(this).attr('title');
        if(title_status==1){
            $('.title-content').show(500);
            title_status = 2;
            setTimeout(function(){
                if(title_status==2){
                    $('.title-content').hide(500);
                    title_status = 1;
                }
            },6000);
        }/*else{
            $('.title-content').hide(500);
            title_status = 1;
        }*/

    })
    $('#psd').on("blur",function(){
        var qty_data = $(this).val();
        if (qty_data.length < 6) {
            layer.msg("<?php echo lang('密码必须大于6位数'); ?>", {time: 2000, icon: 2});
        }
    })
</script>
