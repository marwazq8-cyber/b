<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:66:"themes/admin_simpleboot3/admin/withdrawals_manage/binding_upd.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
        <li><a href="<?php echo url('withdrawals_manage/index'); ?>"><?php echo lang('提现记录'); ?></a></li>
        <li><a href="<?php echo url('withdrawals_manage/user_binding'); ?>"><?php echo lang('账号绑定列表'); ?></a></li>
        <li class="active"><a href="javascript:void(0);"><?php echo lang('编辑账号列表'); ?></a></li>
    </ul>
    <form action="<?php echo url('withdrawals_manage/user_binding_post'); ?>" method="post">
        <div class="row gift">
            <div class="col-md-8  col-md-offset-2">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('USER_ID'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" readonly type="text"  value="<?php echo (isset($user['uid']) && ($user['uid'] !== '')?$user['uid']:''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('USER_NICKNAME'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" readonly type="text"  value="<?php echo (isset($user['user_nickname']) && ($user['user_nickname'] !== '')?$user['user_nickname']:''); ?>" />
                        </td>
                    </tr>

                   
                     <tr>
                        <th><?php echo lang('账号姓名'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[name]"  value="<?php echo (isset($user['name']) && ($user['name'] !== '')?$user['name']:''); ?>" />
                        </td>
                    </tr>
<!--                     <tr>-->
<!--                        <th><?php echo lang('支付宝账号'); ?></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="text" name="post[pay]"  value="<?php echo (isset($user['pay']) && ($user['pay'] !== '')?$user['pay']:''); ?>" />-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                     <tr>-->
<!--                        <th><?php echo lang('微信账号'); ?></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="text" name="post[wx]"  value="<?php echo (isset($user['wx']) && ($user['wx'] !== '')?$user['wx']:''); ?>" />-->
<!--                        </td>-->
<!--                    </tr>-->
                     <tr>
                        <th><?php echo lang('银行卡账号'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[bank_card]"  value="<?php echo (isset($user['bank_card']) && ($user['bank_card'] !== '')?$user['bank_card']:''); ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('银行分类'); ?></th>
                        <td>
                            <select name="post[bank_card_id]" id="gift">
                                <option value="0"></option>
                                <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php if($user['bank_card_id'] == $v['id']): ?> selected="selected" <?php endif; ?> ><?php echo $v['name']; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </td>
                    </tr>

                     <input class="form-control" type="hidden" name="id"  value="<?php echo (isset($user['id']) && ($user['id'] !== '')?$user['id']:''); ?>" />
                </table>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('voice/type'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>


</body>
</html>
