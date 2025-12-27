<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:61:"themes/admin_simpleboot3/admin/guild_manage/earnings_log.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
<style>
    .guild-img {
        width: 50px;
        height: 50px
    }

    .js-ajax-form {
        margin-top: 30px;
    }
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('ADMIN_GUILD_INCOME_LOG'); ?></a></li>
    </ul>

    <form class="well form-inline margin-top-20" name="form1" method="post" action="<?php echo url('guild_manage/earnings_log'); ?>">
       <?php echo lang('公会长ID'); ?>:
        <input type="text" class="form-control" name="id" style="width: 120px;" value="<?php echo (isset($data['id']) && ($data['id'] !== '')?$data['id']:''); ?>" placeholder="<?php echo lang('请输入公会长ID'); ?>">

        <?php echo lang('ADMIN_ANCHOR_ID'); ?>:
        <input type="text" class="form-control" name="hid" style="width: 120px;" value="<?php echo (isset($data['hid']) && ($data['hid'] !== '')?$data['hid']:''); ?>" placeholder="<?php echo lang('请输入主播ID'); ?>">
        <?php echo lang('USER_ID'); ?>:
        <input type="text" class="form-control" name="uid" style="width: 120px;" value="<?php echo (isset($data['uid']) && ($data['uid'] !== '')?$data['uid']:''); ?>" placeholder="<?php echo lang('USER_ID'); ?>">
        <?php echo lang('STATUS'); ?>:
        <select name="status" class="form-control">
            <option value="-1"><?php echo lang('ALL'); ?></option>
            <option value="0" <?php if($data['status'] == '0'): ?> selected='selected' <?php endif; ?> ><?php echo lang('发放中'); ?></option>
            <option value="1" <?php if($data['status'] == 1): ?> selected='selected' <?php endif; ?> ><?php echo lang('已到账'); ?></option>
            <option value="2" <?php if($data['status'] == 2): ?> selected='selected' <?php endif; ?> ><?php echo lang('已取消'); ?></option>
        </select>
       <?php echo lang('类型'); ?>:
        <select name="classification" class="form-control">
            <option value="0"><?php echo lang('ALL'); ?></option>
            <option value="1" <?php if($data['classification'] == 1): ?> selected='selected' <?php endif; ?> ><?php echo lang('Voice_room'); ?></option>
            <option value="2" <?php if($data['classification'] == 2): ?> selected='selected' <?php endif; ?> ><?php echo lang('短视频'); ?></option>
            <option value="4" <?php if($data['classification'] == 2): ?> selected='selected' <?php endif; ?> ><?php echo lang('私信'); ?></option>
        </select>
        <?php echo lang('TIME'); ?>:
        <input type="text" class="form-control js-bootstrap-date" name="start_time" value="<?php echo (isset($data['start_time']) && ($data['start_time'] !== '')?$data['start_time']:''); ?>" style="width: 140px;" autocomplete="off">-
        <input type="text" class="form-control js-bootstrap-date" name="end_time" value="<?php echo (isset($data['end_time']) && ($data['end_time'] !== '')?$data['end_time']:''); ?>" style="width: 140px;" autocomplete="off"> &nbsp; &nbsp;

        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
        <a class="btn btn-danger" href="<?php echo url('guild_manage/earnings_log'); ?>"><?php echo lang('EMPTY'); ?></a>
        <input type="button" class="btn btn-primary from_export" style="background-color: #1dccaa;" value="<?php echo lang('导出'); ?>" onclick='form1.action="<?php echo url('guild_manage/export_earnings_log'); ?>";form1.submit();'>

        <?php if($is_show_total == 1): ?>
            <div style="margin-top: 15px;">
                <span><?php echo lang('总流水'); ?>:<?php echo $count['coin']; ?></span>
                <span><?php echo lang('主播总收益'); ?>:<?php echo $count['profit']; ?></span>
                <span><?php echo lang('公会总收益'); ?>:<?php echo $count['guild_earnings']; ?></span>
            </div>
        <?php else: endif; ?>

    </form>

        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th><?php echo lang('ADMIN_CONSUME_USER'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_ANCHOR'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_GUILD'); ?>(ID)</th>
                <th><?php echo lang('ADMIN_COMMISSION_COIN'); ?></th>
                <th><?php echo lang('ADMIN_ANCHOR_INCOME'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_INCOME'); ?></th>
                <th><?php echo lang('ADMIN_GUILD_COMMISSION_RATIO'); ?></th>
                <th><?php echo lang('ADMIN_COMMISSION_INFO'); ?></th>
                <th><?php echo lang('STATUS'); ?></th>
                <th><?php echo lang('TIME'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['uname']; ?>(<?php echo $vo['uid']; ?>)</td>
                    <td><?php echo $vo['hname']; ?>(<?php echo $vo['to_user_id']; ?>)</td>
                    <td><?php echo $vo['gname']; ?>(<?php echo $vo['guild_uid'] ."/". $vo['guild_id']; ?>)</td>
                    <td><?php echo $vo['ucoin']; ?></td>
                    <td><?php echo $vo['profit']; ?></td>
                    <td><?php echo $vo['guild_earnings']; ?></td>
                    <td><?php echo $vo['guild_commission']; ?></td>
                    <td><?php echo $vo['content']; ?></td>

                    <td>
                        <?php if($vo['status'] == '1'): ?>
                            <?php echo lang('已到账'); else: ?>
                            <?php echo $vo['status']==2?lang('已取消') : lang('发放中'); endif; ?>
                       </td>
                    <td><?php echo date("Y-m-d H:i",$vo['create_time'] ); ?></td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
         <div class="pagination"><?php echo $page; ?></div>

</div>
<script src="/static/js/admin.js"></script>
</body>
</html>