<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:50:"themes/admin_simpleboot3/admin/main/not_index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .home-info li em {
        float: left;
        width: 120px;
        font-style: normal;
        font-weight: bold;
    }

    .home-info ul {
        padding: 0;
        margin: 0;
    }

    .panel {
        margin-bottom: 0;
    }

    .grid-sizer {
        width: 10%;
    }

    .grid-item {
        margin-bottom: 5px;
        padding: 5px;
    }

    .col-xs-1, .col-sm-1, .col-md-1, .col-lg-1, .col-xs-2, .col-sm-2, .col-md-2, .col-lg-2, .col-xs-3, .col-sm-3, .col-md-3, .col-lg-3, .col-xs-4, .col-sm-4, .col-md-4, .col-lg-4, .col-xs-5, .col-sm-5, .col-md-5, .col-lg-5, .col-xs-6, .col-sm-6, .col-md-6, .col-lg-6, .col-xs-7, .col-sm-7, .col-md-7, .col-lg-7, .col-xs-8, .col-sm-8, .col-md-8, .col-lg-8, .col-xs-9, .col-sm-9, .col-md-9, .col-lg-9, .col-xs-10, .col-sm-10, .col-md-10, .col-lg-10, .col-xs-11, .col-sm-11, .col-md-11, .col-lg-11, .col-xs-12, .col-sm-12, .col-md-12, .col-lg-12 {
        padding-left: 5px;
        padding-right: 5px;
        float: none;
    }

    .main {
        width: 700px;
        height: 400px;
        border: 1px solid #ccc;
        margin-left: 20px;
        overflow: hidden;
    }

    #line {
        width: 700px;
        height: 400px;
        margin-top: -15px;
    }

    #pie {
        width: 700px;
        height: 400px;
        margin-top: -15px;
    }

    #lines {
        width: 700px;
        height: 400px;
        margin-top: -15px;
    }
    .btn-main-box{
        cursor: pointer;
        position: relative;
        padding: 20px 20px 20px 20px;
        background-color: #fff;
        color: #333;
        font-weight: 400;
        font-size: 16px;
        text-align: center;
    }
</style>
<link rel="stylesheet" href="/themes/admin_simpleboot3/public/assets/simpleboot3/css/index.css">
<?php 
    \think\Hook::listen('admin_before_head_end',$temp67b07871cb900,null,false);
 ?>
</head>
<body>
<div class="wrap">
    <?php if(empty($has_smtp_setting) || (($has_smtp_setting instanceof \think\Collection || $has_smtp_setting instanceof \think\Paginator ) && $has_smtp_setting->isEmpty())): endif; if(!extension_loaded('fileinfo')): ?>
        <div class="grid-item col-md-12">
            <div class="alert alert-danger alert-dismissible fade in" role="alert" style="margin-bottom: 0;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong><?php echo lang("提示!"); ?></strong> php_fileinfo扩展没有开启，无法正常上传文件！
            </div>
        </div>
    <?php endif; ?>

    <div class="grid-item col-md-12" id="thinkcmf-notices-grid" style="display:none;">
        <div class="dashboard-box">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo lang('SYSTEM_NOTIFICATIONS'); ?></h3>
                </div>
                <div class="panel-body home-info">
                    <ul id="thinkcmf-notices" class="list-unstyled">
                        <li>
                            <img src="/themes/admin_simpleboot3/public/assets/images/loading.gif" style="vertical-align: middle;"/>
                            <span style="display: inline-block; vertical-align: middle;"><?php echo lang('LOADING'); ?>...</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!--首页-->
    <div class="bogo_index">
        <div class="bogo_index_title"></div>

    </div>
    <div class="bogo_index_right">
        <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
            <div class="bogo_index_right_type_img bogo_index_tel1"></div>
            <div class="bogo_index_right_type_name"><?php echo lang('SYSTEM_VERSION'); ?></div>
            <div class="bogo_index_right_type_center">v1.1.0</div>
        </div>
        <?php if(IS_OFFICIAL == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img bogo_index_tel2"></div>
                <div class="bogo_index_right_type_name"><?php echo lang('COMMERCIAL_COPYRIGHT'); ?></div>
                <div class="bogo_index_right_type_center"><?php echo lang("山东布谷鸟网络科技有限公司"); ?></div>
            </div>
        <?php endif; if(IS_OFFICIAL == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img bogo_index_tel3"></div>
                <div class="bogo_index_right_type_name"><?php echo lang('OFFICIAL_WEBSITE'); ?></div>
                <div class="bogo_index_right_type_center"><a href="http://www.bogokj.com" target="_blank">http://www.bogokj.com</a>
                </div>
            </div>
        <?php endif; if(IS_GUILD == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img"><i class="fa fa-user-plus" aria-hidden="true" style="font-size: 40px"></i></div>
                <div class="bogo_index_right_type_name"><?php echo lang("公会后台"); ?></div>
                <div class="bogo_index_right_type_center copy_url" data-url="<?php echo $http; ?>/guild"><?php echo lang('Copy_address'); ?></div>

            </div>
        <?php endif; if(IS_AGENT == 1): ?>
            <div class="bogo_index_right_type bogo_index_right_type_radius bogo_index_bottom">
                <div class="bogo_index_right_type_img"><i class="fa fa-users" aria-hidden="true" style="font-size: 40px"></i></div>
                <div class="bogo_index_right_type_name">cps渠道后台</div>
                <div class="bogo_index_right_type_center copy_url" data-url="<?php echo $http; ?>/agent"><?php echo lang('Copy_address'); ?></div>
            </div>
        <?php endif; ?>
        <!--        <div class="btn-main-box bogo_index_right_type_radius bogo_index_bottom">-->
        <!--            <div class="btn-main" data-url="<?php echo $http; ?>/union"><?php echo lang('COPY_THE_UNION_BACKSTAGE_ADDRESS'); ?></div>-->
        <!--            <div class="bogo_index_right_type_center"></div>-->
        <!--        </div>-->
        <div class="btn-main-box bogo_index_right_type_radius bogo_index_bottom">
            <div class="btn-main copy_url" data-url="<?php echo $http; ?>/api/download_api/phone_index"><?php echo lang('COPY_DOWNLOAD_ADDRESS'); ?></div>
            <div class="bogo_index_right_type_center"></div>
        </div>

    </div>

</div>


<script src="/static/js/admin.js"></script>

<script src="/static/js/amcharts.js" type="text/javascript"></script>
<script src="/static/js/serial.js" type="text/javascript"></script>
<script src="/static/js/pie.js" type="text/javascript"></script>
<script src="/static/js/clipboard.min.js" type="text/javascript"></script>
<script>
    var url = '';
    $('.copy_url').click(function(){
        url = $(this).attr('data-url');
    })
    var clipboard = new ClipboardJS('.copy_url', {
        // 点击copy按钮，直接通过text直接返回复印的内容
        text: function() {
            return url;
        }
    });

    clipboard.on('success', function(e) {
        console.log(e);
        layer.msg('复制成功');
    });

    clipboard.on('error', function(e) {
        console.log(e);
    });
</script>
<?php 
    \think\Hook::listen('admin_before_body_end',$temp67b07871cb90e,null,false);
 ?>
</body>
</html>
