<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:47:"themes/admin_simpleboot3/admin/voice/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
<link href="/static/css/admin_acction.css" rel="stylesheet">
</head>
<body>
	<div class="wrap js-check-wrap">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo url('voice/index'); ?>"><?php echo lang('语音直播列表'); ?></a></li>
			<li><a href="<?php echo url('voice/add_index'); ?>"><?php echo lang('添加语音直播'); ?></a></li>
		</ul>
        <form class="well form-inline margin-top-20" method="post" action="<?php echo url('Voice/index'); ?>">
			<?php echo lang('USER_ID'); ?>:
            <input type="text" class="form-control" name="user_id" style="width: 120px;" value="<?php echo input('request.user_id/s',''); ?>" placeholder="<?php echo lang('Please_enter_user_ID'); ?>">
           <?php echo lang('房间ID'); ?>:
            <input type="text" class="form-control" name="voice_id" style="width: 120px;" value="<?php echo input('request.voice_id/s',''); ?>" placeholder="<?php echo lang('请输入房间ID'); ?>">
<!--            <?php echo lang('房间直播状态'); ?>:-->
<!--	        <select name="status" class="form-control">-->
<!--	            <option value="0"><?php echo lang('ALL'); ?></option>-->
<!--	            <option value="1" <?php if(input('request.status/s','') == '1'): ?> selected='selected' <?php endif; ?> ><?php echo lang('正在语音直播'); ?></option>-->
<!--	        </select>-->
	        <?php echo lang('房间类型'); ?>:
	        <select name="type" class="form-control">
	            <option value="-1"><?php echo lang('ALL'); ?></option>
	            <option value="1" <?php if(input('request.type/s','') == '1'): ?> selected='selected' <?php endif; ?> ><?php echo lang('交友厅'); ?></option>
	            <option value="2" <?php if(input('request.type/s','') == '2'): ?> selected='selected' <?php endif; ?> ><?php echo lang('派单厅'); ?></option>
	        </select>
	         <?php echo lang('语音频道类型'); ?>:
	        <select name="voice_type" class="form-control">
	            <option value="0"><?php echo lang('ALL'); ?></option>
	            <?php if(is_array($voice_type) || $voice_type instanceof \think\Collection || $voice_type instanceof \think\Paginator): if( count($voice_type)==0 ) : echo "" ;else: foreach($voice_type as $key=>$v): ?>
	            	<option value="<?php echo $v['id']; ?>" <?php if($v['id'] == input('request.voice_type/s','')): ?> selected='selected' <?php endif; ?> ><?php echo $v['name']; ?></option>
	        	<?php endforeach; endif; else: echo "" ;endif; ?>
	        </select>
            <input type="submit" class="btn btn-primary" value="<?php echo lang('SEARCH'); ?>" />
            <a class="btn btn-danger" href="<?php echo url('Voice/index'); ?>"><?php echo lang('EMPTY'); ?></a>
        </form>
        <form class="js-ajax-form" action="<?php echo url('Voice/upd_index_sort'); ?>" method="post">
			<table class="table table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo lang('SORT'); ?></th>
						<th width="50">ID</th>
						<th><?php echo lang('语音标题'); ?></th>
						<th><?php echo lang('房间封面'); ?></th>
						<th><?php echo lang('USER_ID'); ?></th>
						<th><?php echo lang('USER_NICKNAME'); ?></th>
						<th><?php echo lang('TIME'); ?></th>
						<th><?php echo lang('语音频道'); ?></th>
						<th><?php echo lang('房间类型'); ?></th>
						<th><?php echo lang('密码'); ?></th>
						<th><?php echo lang('观看人数'); ?></th>
						<th><?php echo lang('群组ID'); ?></th>
						<th><?php echo lang('ACTIONS'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
					<tr>
						<td ><input type="text" name="sort[<?php echo $vo['id']; ?>]" value="<?php echo $vo['sort']; ?>" style="width: 30px;"></td>
						<td><?php echo $vo['id']; ?></td>
						<td><?php echo $vo['title']; ?></td>
						<td>
							<img style="width: 50px;height: 50px;object-fit: cover;" src="<?php echo $vo['avatar']; ?>" alt="">
						</td>
						<td><?php echo $vo['user_id']; ?></td>
						<td><?php echo $vo['user_nickname']; ?></td>

						<td><?php echo date('Y-m-d H:i:s',$vo['create_time']); ?></td>
						<td><?php echo $vo['name']; ?></td>
						<td>
							<?php if($vo['room_type'] == 1): ?>
								<?php echo lang('交友厅'); else: ?>
								<?php echo lang('派单厅'); endif; ?>
						</td>
						<td><?php echo $vo['voice_psd_show']; ?></td>
						<td><?php echo $vo['online_number']; ?></td>
						<td><?php echo $vo['group_id']; ?></td>
						<td>
							<button class="ipt btn btn-info" type="button"><?php echo lang('ACTIONS'); ?> <strong>+</strong></button>
							<ul id="ul">
								<li>
									<a href="<?php echo url('voice/add_index',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
								</li>
								<li>
									<a href="<?php echo url('voice/voice_administrator',array('voice_id'=>$vo['user_id'])); ?>"><?php echo lang('管理员列表'); ?></a>
								</li>
								<li>
									<a href="<?php echo url('voice/voice_host',array('voice_id'=>$vo['user_id'])); ?>"><?php echo lang('主持人列表'); ?></a>
								</li>
								<li>
									<a href="<?php echo url('voice/voice_wheat',array('voice_id'=>$vo['user_id'])); ?>"><?php echo lang('麦位列表'); ?></a>
								</li>
								<li>
									<a href="javascript:void(0);" onclick="get_voice_audience('<?php echo $vo['user_id']; ?>')"><?php echo lang('观众列表'); ?></a>
								</li>
								<li>
									<?php if($vo['reference'] == 0): ?>
										<a style="text-decoration:none;"  href="<?php echo url('user/adminIndex/reference',array('id'=>$vo['user_id'],'type'=>1)); ?>"><?php echo lang('推荐房间'); ?></a>
										<?php else: ?>
										<a style="text-decoration:none;"  data-value="<?php echo url('adminIndex/reference',array('id'=>$vo['user_id'],'type'=>0)); ?>" href="<?php echo url('user/adminIndex/reference',array('id'=>$vo['user_id'],'type'=>0)); ?>"><?php echo lang('取消推荐'); ?></a>
									<?php endif; ?>
								</li>
								<li>
									<a href="#" class="del" data-id="<?php echo $vo['id']; ?>"><?php echo lang('关闭房间'); ?></a>
								</li>
							</ul>

						</td>

					</tr>
					<?php endforeach; endif; else: echo "" ;endif; ?>
				</tbody>
			</table>
			 <button type="button" class="btn btn-primary" style="margin-top:20px;"><?php echo lang('排序'); ?></button>
		</form>
		<div class="pagination"><?php echo $page; ?></div>
	</div>
<script src="/static/js/layer/layer.js" rel="stylesheet"></script>
<script>
	function get_voice_audience(id) {
		layer.open({
			type: 2,
			title: "<?php echo lang('观众列表'); ?>",
			shadeClose: true,
			area: ['80%', '80%'],   //宽高
			shade: 0.4,   //遮罩透明度
			content:  "<?php echo url('voice/voice_audience'); ?>?voice_id=" + id
		});
	}
    $(".del").click(function () {
        var id = $(this).attr('data-id');
        layer.confirm("<?php echo lang('您是否要关闭房间？'); ?>", {
			title: "<?php echo lang('操作'); ?>",
            btn: ["<?php echo lang('确定'); ?>","<?php echo lang('取消'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('voice/voice_del'); ?>",
                type: 'post',
                dataType: 'json',
                data: {id: id},
                success: function (data) {
                    if (data == '1') {
                        layer.msg("<?php echo lang('DELETE_SUCCESS'); ?>", {time: 2000, icon: 1}, function () {
                            window.location.reload();
                        });
                    } else {
                        layer.msg("<?php echo lang('DELETE_FAILED'); ?>", {time: 2000, icon: 2});
                    }
                }
            });

        });
    })
    $(".btn-primary").click(function () {
        $(".js-ajax-form").submit();
    })

	flag = true;
	$(".ipt").click(function(){

		if(flag){
			$(this).children('strong').html(' -');
			$(this).next('ul').css('display','block');
			flag = false;
		}else{
			flag = true;
			var self = $(this);
			setTimeout(function(){
				self.children('strong').html(' +');
				self.next('ul').css('display','none');
			},200)
		}


	});

	$(".ipt").blur(function(){
		flag = true;
		var self = $(this);
		setTimeout(function(){
			self.children('strong').html('+');
			self.next('ul').css('display','none');
		},200)
	});
</script>
</body>
</html>

