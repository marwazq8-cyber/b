<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:51:"themes/admin_simpleboot3/user/admin_index/edit.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
</style>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('admin_index/index'); ?>"><?php echo lang('ADMIN_USER_LIST'); ?></a></li>
        <li class="active"><a href="javascript:;"><?php echo lang("编辑信息"); ?></a></li>
    </ul>
    <form action="<?php echo url('admin_index/edit_post'); ?>" method="post">
        <div class="row gift">
            <div class="col-md-8  col-md-offset-2">
                <table class="table table-bordered">
                    <tr>
                        <th><?php echo lang('USER_NICKNAME'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="user_nickname"
                                   id="user_nickname" required value="<?php echo (isset($data['user_nickname'] ) && ($data['user_nickname']  !== '')?$data['user_nickname'] :''); ?>"
                                   placeholder="<?php echo lang('请输入用户昵称'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('区号'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="number" name="mobile_area_code"
                                   id="mobile_area_code" value="<?php echo (isset($data['mobile_area_code'] ) && ($data['mobile_area_code']  !== '')?$data['mobile_area_code'] :''); ?>"
                                   placeholder="<?php echo lang('区号'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('ADMIN_PHONE_NUMBER'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="mobile"
                                   id="mobile" value="<?php echo (isset($data['mobile'] ) && ($data['mobile']  !== '')?$data['mobile'] :''); ?>"
                                   placeholder="<?php echo lang('ADMIN_PHONE_NUMBER'); ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('GENDER'); ?></th>
                        <td>

                            <select class="form-control" name="sex">
                                <option value="1"><?php echo lang('MALE'); ?></option>
                                <option value="2"<?php if($data['sex'] == 2): ?> selected="selected"<?php endif; ?>><?php echo lang('FEMALE'); ?></option>
                            </select>
                        </td>
                    </tr>
<!--                    <tr>-->
<!--                        <th><?php echo lang("自定义价格（视频通话） "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <select name="custom_video_charging_coin" class="select">-->
<!--                                <option value="0"><?php echo lang("无"); ?></option>-->
<!--                                <?php if(is_array($fee) || $fee instanceof \think\Collection || $fee instanceof \think\Paginator): if( count($fee)==0 ) : echo "" ;else: foreach($fee as $key=>$v): ?>-->
<!--                                    <option value="<?php echo $v['coin']; ?>"-->
<!--                                    <?php if($data['custom_video_charging_coin'] == $v['coin']): ?>-->
<!--                                        selected="selected"-->
<!--                                    <?php endif; ?>-->
<!--                                    ><?php echo $v['coin']; ?> 积分</option>-->
<!--                                <?php endforeach; endif; else: echo "" ;endif; ?>-->
<!--                            </select>-->

<!--                        </td>-->
<!--                    </tr>-->
                    <tr>
                        <th><?php echo lang('USER_AVATAR'); ?><span class="form-required">*</span></th>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="avatar" id="thumbnail" value="<?php echo (isset($data['avatar'] ) && ($data['avatar']  !== '')?$data['avatar'] :''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumbnail');">
                                    <?php if($data['avatar']): ?>
                                        <img src="<?php echo $data['avatar']; ?>"
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
                        <th><?php echo lang('ADMIN_INVITE_USER_ID'); ?> <span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="invite_id"
                                   id="invite_id" required value="<?php echo (isset($data['invite_id'] ) && ($data['invite_id']  !== '')?$data['invite_id'] :''); ?>"
                                   placeholder="<?php echo lang('Please_enter_invitee_ID'); ?>"/>
                        </td>
                    </tr>
<!--                    <tr>-->
<!--                        <th><?php echo lang("视频通话比例 "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="number" name="host_one_video_proportion" step="0.01"-->
<!--                                   min="0" max="1"-->
<!--                                   id="host_one_video_proportion" required-->
<!--                                   value="<?php echo (isset($data['host_one_video_proportion'] ) && ($data['host_one_video_proportion']  !== '')?$data['host_one_video_proportion'] :'0'); ?>"-->
<!--                                   placeholder="<?php echo lang('请输入一对一视频通话比例(例如：0.1)0是后台通用比例'); ?>"/>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                        <th><?php echo lang("购买视频分成比例 "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="number" name="host_bay_video_proportion" step="0.01"-->
<!--                                   min="0" max="1"-->
<!--                                   id="host_bay_video_proportion" required-->
<!--                                   value="<?php echo (isset($data['host_bay_video_proportion'] ) && ($data['host_bay_video_proportion']  !== '')?$data['host_bay_video_proportion'] :'0'); ?>"-->
<!--                                   placeholder="<?php echo lang('请输入购买视频分成比例(例如：0.1)0是后台通用比例'); ?>"/>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                        <th><?php echo lang("赠送礼物分成比例 "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="number" name="host_bay_gift_proportion" step="0.01"-->
<!--                                   min="0" max="1"-->
<!--                                   id="host_bay_gift_proportion" required-->
<!--                                   value="<?php echo (isset($data['host_bay_gift_proportion'] ) && ($data['host_bay_gift_proportion']  !== '')?$data['host_bay_gift_proportion'] :'0'); ?>"-->
<!--                                   placeholder="<?php echo lang('请输入赠送礼物分成比例(例如：0.1)0是后台通用比例'); ?>"/>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                        <th><?php echo lang("购买私照分成比例 "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="number" name="host_bay_phone_proportion" step="0.01"-->
<!--                                   min="0" max="1"-->
<!--                                   id="host_bay_phone_proportion" required-->
<!--                                   value="<?php echo (isset($data['host_bay_phone_proportion'] ) && ($data['host_bay_phone_proportion']  !== '')?$data['host_bay_phone_proportion'] :'0'); ?>"-->
<!--                                   placeholder="<?php echo lang('请输入购买私照分成比例(例如：0.1)0是后台通用比例'); ?>"/>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                        <th><?php echo lang("私信消息分成比例 "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="number" name="host_direct_messages" step="0.01" min="0"-->
<!--                                   max="1"-->
<!--                                   id="host_direct_messages" required value="<?php echo (isset($data['host_direct_messages'] ) && ($data['host_direct_messages']  !== '')?$data['host_direct_messages'] :'0'); ?>"-->
<!--                                   placeholder="<?php echo lang('请输入私信消息分成比例(例如：0.1)0是后台通用比例'); ?>"/>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                        <th><?php echo lang("开通守护分成比例 "); ?><span class="form-required">*</span></th>-->
<!--                        <td>-->
<!--                            <input class="form-control" type="text" name="host_guardian_proportion" step="0.01" min="0"-->
<!--                                   max="1"-->
<!--                                   id="host_guardian_proportion" required-->
<!--                                   value="<?php echo (isset($data['host_guardian_proportion'] ) && ($data['host_guardian_proportion']  !== '')?$data['host_guardian_proportion'] :'0'); ?>"-->
<!--                                   placeholder="<?php echo lang('请输入守护分成比例(例如：0.1)0是后台通用比例'); ?>"/>-->
<!--                        </td>-->
<!--                    </tr>-->
                  <!--   <tr>
                        <th><?php echo lang("邀请扣量概率% "); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="invite_buckle_probability"
                                   id="invite_buckle_probability" required
                                   value="<?php echo (isset($data['invite_buckle_probability'] ) && ($data['invite_buckle_probability']  !== '')?$data['invite_buckle_probability'] :'0'); ?>"
                                   placeholder="<?php echo lang('请输入1-100的整数(例如：50代表百分之50的几率)'); ?>"/>
                        </td>
                    </tr> -->
                    <tr>
                        <th><?php echo lang('STATUS'); ?> <span class="form-required">*</span></th>
                        <td>
                            <select class="form-control" name="is_online">
                                <option value="0"><?php echo lang("下线"); ?></option>
                                <option value="1"
                                <?php if($data['is_online'] == 1): ?> selected="selected"<?php endif; ?>
                                ><?php echo lang("上线"); ?></option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('Is_it_certified'); ?> <span class="form-required">*</span></th>
                        <td>
                            <select class="form-control" name="is_auth">
                                <option value="0"><?php echo lang('NO'); ?></option>
                                <option value="1"
                                <?php if($data['is_auth'] == 1): ?> selected="selected"<?php endif; ?>
                                ><?php echo lang('YES'); ?></option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('is_robot'); ?> </th>
                        <td>
                            <select class="form-control" name="is_robot">
                                <option value="0"><?php echo lang('NO'); ?></option>
                                <option value="1"
                                <?php if($data['is_robot'] == 1): ?> selected="selected"<?php endif; ?>
                                ><?php echo lang('YES'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("音频上传"); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" readonly name="audio_file"  id="files" value="<?php echo (isset($data['audio_file']) && ($data['audio_file'] !== '')?$data['audio_file']:''); ?>" style="width:70%;">
                            <a href="javascript:uploadOne('音频上传','#files','audio');"
                               class="btn btn-sm btn-default"><?php echo lang("音频上传"); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("音频时长 "); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="audio_time"
                                   id="title" required value="<?php echo (isset($data['audio_time']) && ($data['audio_time'] !== '')?$data['audio_time']:''); ?>" placeholder="<?php echo lang('请输入音频时长 秒'); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang("country"); ?><span class="form-required">*</span></th>
                        <td>

                            <select class="form-control" name="country_code">
                                <?php if(is_array($countries) || $countries instanceof \think\Collection || $countries instanceof \think\Paginator): if( count($countries)==0 ) : echo "" ;else: foreach($countries as $key=>$vo): ?>
                                    <option value="<?php echo $vo['num_code']; ?>" <?php if($data['country_code'] == $vo['num_code']): ?> selected="selected"<?php endif; ?>><?php echo $vo['en_short_name']; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo lang('用户状态'); ?> <span class="form-required">*</span></th>
                        <td>
                            <select class="form-control" name="user_type">
                                <option value="2" <?php if($data['user_type'] == 2): ?> selected="selected"<?php endif; ?>><?php echo lang('普通会员'); ?></option>
                                <option value="1"
                                <?php if($data['user_type'] == 1): ?> selected="selected"<?php endif; ?> disabled
                                ><?php echo lang('后台账户'); ?></option>
                                <option value="3"
                                <?php if($data['user_type'] == 3): ?> selected="selected"<?php endif; ?>
                                ><?php echo lang('注销用户'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="id" value="<?php echo (isset($data['id'] ) && ($data['id']  !== '')?$data['id'] :''); ?>"/>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                        <a class="btn btn-default" href="<?php echo url('admin_index/index'); ?>"><?php echo lang('BACK'); ?></a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

</body>
<script type="text/javascript" src="/static/js/admin.js"></script>
<script>
    $(function () {
        var is_auth = "<?php echo $data['is_auth']; ?>";
        if (is_auth == 1) {
            $(".sex").attr("disabled", "disabled").css("background-color", "#EEEEEE;");
        }

        $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });

    });
</script>
</html>
