<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:49:"themes/admin_simpleboot3/admin/country/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
</head>
<style type="text/css">
    select{width:130px;height:35px;line-height: 35px;border: 1px solid #dce4ec;}
</style>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;"><?php echo lang('国家列表'); ?></a></li>
        <li><a href="<?php echo url('Country/country_add'); ?>"> <?php echo lang('ADD'); ?></a></li>
    </ul>
    <form class="well form-inline margin-top-20" name="form1" method="post">
       <?php echo lang('名称'); ?>:
        <input class="form-control" type="text" name="name" style="width: 200px;" value="<?php echo input('request.name'); ?>" >
        <?php echo lang('STATUS'); ?>:
        <select name="status" id="status">
            <option value="0"><?php echo lang('ALL'); ?></option>
            <option value="1"<?php if($request['status'] == 1): ?> selected="selected"<?php endif; ?>><?php echo lang('OPEN'); ?></option>
            <option value="2"<?php if($request['status'] == 2): ?> selected="selected"<?php endif; ?>><?php echo lang('CLOSE'); ?></option>
        </select>
        <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" onclick='form1.action="<?php echo url('Country/index'); ?>";form1.submit();'/>
        <a class="btn btn-danger" href="<?php echo url('Country/index'); ?>"><?php echo lang('EMPTY'); ?></a>
    </form>
    <?php 
        $status = array(1 => lang('OPEN'),2 => lang('CLOSE'))
     ?>
    <form class="js-ajax-form">
        <table class="table table-hover table-bordered table-list">
            <thead>
            <tr>
                <th>ID</th>
                <th><?php echo lang('名称'); ?></th>
                <th><?php echo lang('图标'); ?></th>
                <th><?php echo lang('国家编码'); ?></th>
                <th><?php echo lang('国家编号'); ?></th>
                <th><?php echo lang('STATUS'); ?></th>
                <th><?php echo lang('SORT'); ?></th>
                <th><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <?php if(is_array($data) || $data instanceof \think\Collection || $data instanceof \think\Paginator): if( count($data)==0 ) : echo "" ;else: foreach($data as $key=>$vo): ?>
                <tr>
                    <td><?php echo $vo['id']; ?></td>
                    <td><?php echo $vo['name']; ?></td>
                    <td><img src="<?php echo $vo['img']; ?>" style="width:50px;"/></td>
                    <td><?php echo $vo['alpha_2_code']; ?></td>
                    <td><?php echo $vo['num_code']; ?></td>
                    <th><?php echo $status[$vo['status']]; ?></th>
                    <td><?php echo $vo['sort']; ?></td>
                    <th>
                        <a href="<?php echo url('Country/country_add',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
                    </th>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tfoot>
        </table>
        <ul class="pagination"><?php echo $page; ?></ul>
    </form>
</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script src="/static/js/admin.js"></script>
</body>
</html>
