<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:52:"themes/admin_simpleboot3/admin/voice/room_memes.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
<style>
    .gift-img {
        width: 60px;
        height: 60px
    }

    .gift-img img {
        width: 100%;
        height: 100%;
    }

</style>
	<div class="wrap js-check-wrap">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo url('Voice/room_memes'); ?>"><?php echo lang('语音房间表情包列表'); ?></a></li>
			<li><a href="<?php echo url('Voice/add_room_memes'); ?>">增加表情包类型</a></li>
		</ul>
      
		<table class="table table-hover table-bordered">
			<thead>
				<tr>
					<th width="50">ID</th>
					<th><?php echo lang('表情包名称'); ?></th>
					<th><?php echo lang('表情包图片'); ?></th>
					<th><?php echo lang('表情分类'); ?></th>
					<th><?php echo lang('SORT'); ?></th>
					<th><?php echo lang('STATUS'); ?></th>
					<th><?php echo lang('ACTIONS'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
				<tr>
					<td><?php echo $vo['id']; ?></td>
					<td><?php echo $vo['name']; ?></td>
					<td class="gift-img"><img src="<?php echo $vo['img']; ?>" alt=""></td>
					<td><?php echo $vo['tname']; ?></td>
					<td><?php echo $vo['sort']; ?></td>
					<td>
						<?php if($vo['status'] == 1): ?>
							<?php echo lang('OPEN'); else: ?>
							<?php echo lang('CLOSE'); endif; ?>
					</td>
					<td>
						<a href="<?php echo url('Voice/add_room_memes',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
						<a href="<?php echo url('Voice/del_room_memes',array('id'=>$vo['id'])); ?>"><?php echo lang('DELETE'); ?></a>
						<?php if($vo['type'] == 1): ?>
							<a href="<?php echo url('Voice/random_room_memes',array('id'=>$vo['id'])); ?>"><?php echo lang('随机表情列表'); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; endif; else: echo "" ;endif; ?>
			</tbody>
		</table>

	</div>

</html>