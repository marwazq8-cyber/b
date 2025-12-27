<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:49:"themes/admin_simpleboot3/admin/systems/index.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .form-control_sum{width: 100px;height:35px;line-height:35px;float: left; margin-right: 10px;margin-bottom: 10px;}
    .form-control_sum_btn{background: #2c3e50;color:#fff;    line-height: 24px!important;  width: 54px!important;}
    /*.config_left{width: 150px;float:left;border:1px solid #ccc;min-height: 850px;background: #4e4e4e;}*/
    #div_content{float:left;width: calc(100% - 160px);margin-left:10px;padding:10px;}
    /*.config_left li{width: 100%;}*/
    .config_left li a{color:#7b8a8b;background-color: #d8e0e6;border-top-left-radius: 5px;border-top-right-radius: 5px;}
    .config_left li a:hover{color:#7b8a8b;}
    .config_left .active a{background: #ffffff!important;}
    .config_left_type{width: 100%;background: #2c3e50;color:#fff;height: 40px;line-height: 40px;text-align: center;}
    .wrap .config_red{float:left;width: 100%;height: 50px;line-height: 50px; background: #2c3e50;padding-left: 40px;margin-bottom: 10px;color: #fff;}
    .config_left_box{
        width: 100%;
        /*height: 50px;
    overflow-y: hidden;
    overflow-x: scroll;*/
    }
    .config_left{
        /*position: fixed;
    top: 0;*/
        z-index: +999;
        min-width: 100%;
        height: 100%;
        /*border:1px solid #ccc;*/
        background: #e8edf0;
        white-space:normal;
        overflow: hidden;
    }
    .config_left li{
        list-style: none;
        float: left;
        overflow: hidden;
        padding: 2px 5px;
        margin-top: 5px;
    }
    .config_left li a{
        text-decoration: none;
        /*padding: 15px 5px;*/
        margin: 0;
    }
    .config_left li a:hover{
        /*color: #7b8a8b;*/
        background-color: #ffffff;
        color: #2C3E50;
    }
    .config_left .active a:hover{
        color: #7b8a8b;
    }
    .config_left .active a {
        color: #7b8a8b;
        margin: 0;
    }
    .tab-pane:nth-child(even) .form-group{
        background-color: #ffffff;
    }
    .tab-pane{
        width: 100%;
        display: none;
        overflow: auto;
    }
    .active{
        /*display: revert;*/
        display: block;
    }
    .desc-text{
        font-size: 12px;
        color: #0cb007;
    }
    .table-th{
        width: 100%;
        overflow: hidden;
        font-size: 15px;
        font-weight: 600;
        padding: 10px 0;
        border-top: 1px solid #EDF0F1;
        background-color: #f9f9f9;
    }
    .table-th-l{
        width: 40%;
        float: left;
    }
    .table-th-r{
        width: 60%;
        float: left;
    }
    .li-table{
        width: 100%;
        overflow: hidden;
        list-style: none;
        border-top: 1px solid #EDF0F1;
        padding: 10px 0;

    }
    .li-table-l{
        width: 40%;
        float: left;
        overflow: hidden;
    }
    .li-table-r{
        width: 60%;
        float: left;
        overflow: hidden;
    }
    .li-table:nth-of-type(even){
        background-color: #f9f9f9;
    }
    .li-bottom{
        margin-top: 10px;
    }
    .btn-bj{
        text-decoration: none;
        cursor: pointer;
    }
</style>
<!-- 颜色值 -->
<link href="/themes/admin_simpleboot3/public/assets/simpleboot3/css/colpick.css" rel="stylesheet">

</head>
<body>
<div class="wrap js-check-wrap" style="padding:0px;">
    <!--nav nav-tabs-->
    <div class="config_left_box">
        <ul class="nav nav-tabs config_left">
            <?php if(is_array($type) || $type instanceof \think\Collection || $type instanceof \think\Paginator): if( count($type)==0 ) : echo "" ;else: foreach($type as $key=>$vo): ?>
                <li <?php if($key == 0): ?> class="active" <?php endif; ?>>
                <a href="#<?php echo $key; ?>" data-toggle="tab" class="group" data-id="<?php echo lang($vo['group_id']); ?>"><?php echo lang($vo['group_id']); ?></a>
                </li>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            <li>
                <a href="<?php echo url('systems/add_sys'); ?>" ><?php echo lang("添加配置"); ?></a>
            </li>
        </ul>
    </div>
    <input type="hidden" class="group_id_val" name="group_id" value="<?php echo (isset($type[0]['group_id']) && ($type[0]['group_id'] !== '')?$type[0]['group_id']:''); ?>">

    <div id="div_content">
    <form class="form-horizontal margin-top-20" role="form" action="<?php echo url('systems/upd_post'); ?>" method="post">

        <!--        <table class="table table-striped">-->

        <div class="table-th">
            <div class="table-th-l">
                <?php echo lang('变量标题'); ?>
            </div>
            <div class="table-th-r">
                <?php echo lang('变量值'); ?>
            </div>
        </div>
        <!--            <ul>-->
        <?php if(is_array($type) || $type instanceof \think\Collection || $type instanceof \think\Paginator): if( count($type)==0 ) : echo "" ;else: foreach($type as $k=>$vo): ?>
            <div style="width: 100%" id="<?php echo $k; ?>" <?php if($k == 0): ?> class="tab-pane active" <?php else: ?>  class="tab-pane" <?php endif; ?> >
            <?php if(is_array($config) || $config instanceof \think\Collection || $config instanceof \think\Paginator): if( count($config)==0 ) : echo "" ;else: foreach($config as $key=>$v): if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 0)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>" data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <?php if($v['code'] == 'acquire_group_id'): ?>
                                <input type="text" class="form-control" disabled="disabled" name="<?php echo $v['code']; ?>" value="<?php echo (isset($v['val']) && ($v['val'] !== '')?$v['val']:''); ?>">
                                <?php else: ?>
                                <input type="text" class="form-control" id="input-site-name" name="<?php echo $v['code']; ?>" value="<?php echo (isset($v['val']) && ($v['val'] !== '')?$v['val']:''); ?>">
                            <?php endif; ?>
                        </div>
                    </li>

                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 1)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <textarea rows="3" class="form-control" name="<?php echo $v['code']; ?>"><?php echo $v['val']; ?></textarea>
                        </div>
                    </li>

                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 2)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <div style="text-align: center;">
                                <input type="hidden" name="<?php echo $v['code']; ?>" id="<?php echo $v['code']; ?>" value="<?php echo (isset($v['val']) && ($v['val'] !== '')?$v['val']:''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#<?php echo $v['code']; ?>');">
                                <?php if(empty($v['val'])): ?>
                                    <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                         id="<?php echo $v['code']; ?>-preview" width="135" style="cursor: hand"/>
                                    <?php else: ?>
                                    <img src="<?php echo cmf_get_image_preview_url($v['val']); ?>" id="<?php echo $v['code']; ?>-preview"
                                         width="135" style="cursor: hand"/>
                                <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm"
                                       onclick="$('#<?php echo $v['code']; ?>-preview').attr('src','/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');$('#<?php echo $v['code']; ?>').val('');return false;"
                                       value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </div>
                    </li>

                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 3)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <div class="checkbox">
                                <?php if(is_array($v['checkbox_val']) || $v['checkbox_val'] instanceof \think\Collection || $v['checkbox_val'] instanceof \think\Paginator): if( count($v['checkbox_val'])==0 ) : echo "" ;else: foreach($v['checkbox_val'] as $i=>$tv): ?>
                                    <label>
                                        <input type="checkbox" name="<?php echo $v['code']; ?>[]" value="<?php echo $i; ?>" <?php if(is_array($v['checkbox_check']) || $v['checkbox_check'] instanceof \think\Collection || $v['checkbox_check'] instanceof \think\Paginator): if( count($v['checkbox_check'])==0 ) : echo "" ;else: foreach($v['checkbox_check'] as $key=>$cv): if($i == $cv): ?> checked <?php endif; endforeach; endif; else: echo "" ;endif; ?> > <?php echo lang($tv); ?>
                                    </label>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </div>
                        </div>
                    </li>

                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 4)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <div class="checkbox">
                                <?php if(is_array($v['type_val']) || $v['type_val'] instanceof \think\Collection || $v['type_val'] instanceof \think\Paginator): if( count($v['type_val'])==0 ) : echo "" ;else: foreach($v['type_val'] as $key=>$tv): ?>
                                    <label>
                                        <input type="radio" name="<?php echo $v['code']; ?>" value="<?php echo $key; ?>" <?php if($v['val'] == $key): ?> checked <?php endif; ?> > <?php echo lang($tv); ?>
                                    </label>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </div>
                        </div>
                    </li>

                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 5)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <span class="form-required">*</span><?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <input type="text" class="form-control js-bootstrap-datetime" name="<?php echo $v['code']; ?>"
                                   value="<?php echo $v['val']; ?>" style="width: 140px;" autocomplete="off">
                        </div>
                    </li>

                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 6)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <!--                                <span class="form-required">*</span>-->
                            <?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r form-control_sum_val">
                            <input type="button" class="form-control form-control_sum form-control_sum_btn" data-code="<?php echo $v['code']; ?>" value="<?php echo lang('增加'); ?>">
                            <?php if(is_array($v['list']) || $v['list'] instanceof \think\Collection || $v['list'] instanceof \think\Paginator): if( count($v['list'])==0 ) : echo "" ;else: foreach($v['list'] as $key=>$lv): ?>
                                <input type="text" class="form-control form-control_sum"  name="<?php echo $v['code']; ?>[]" value="<?php echo (isset($lv) && ($lv !== '')?$lv:''); ?>"  placeholder="<?php echo lang('输入内容'); ?>">
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </div>
                    </li>
                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 7)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <span class="form-required">*</span><?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <input type="text" class="form-control picker" name="<?php echo $v['code']; ?>" value="<?php echo (isset($v['val']) && ($v['val'] !== '')?$v['val']:''); ?>" style="width: 150px;">
                        </div>
                    </li>
                <?php endif; if(($v['group_id'] == $vo['group_id']) and ($v['type'] == 8)): ?>
                    <li class="li-table">
                        <div class="li-table-l">
                            <span class="form-required"></span><?php echo lang($v['title']); ?>
                            <div class="desc-text">
                                <?php if($v['desc']): ?>
                                    <?php echo lang($v['desc']); endif; ?>
                            </div>
                            <?php if($debug == 1 && $admin_id == 1): ?>
                                <a class="btn-bj" data-id="<?php echo $v['code']; ?>" data-val="<?php echo $v['desc']; ?>"  data-title="<?php echo $v['title']; ?>" data-sort="<?php echo $v['sort']; ?>"><?php echo lang('EDIT'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="li-table-r">
                            <select class="form-control" name="<?php echo $v['code']; ?>" id="">
                                <?php if(is_array($v['list']) || $v['list'] instanceof \think\Collection || $v['list'] instanceof \think\Paginator): if( count($v['list'])==0 ) : echo "" ;else: foreach($v['list'] as $ki=>$lva): ?>
                                    <option value="<?php echo $ki; ?>" <?php if($ki == $v['val']): ?>selected<?php endif; ?> ><?php echo $lva; ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>

                            </select>
                        </div>
                    </li>
                <?php endif; endforeach; endif; else: echo "" ;endif; ?>
</div>
<?php endforeach; endif; else: echo "" ;endif; ?>
<!--            </ul>-->
<!--        </table>-->
<div class="form-group li-bottom">
    <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-primary js-ajax-submit" data-refresh="1"><?php echo lang('SAVE'); ?></button>
        <div class="btn btn-primary" data-refresh="3" onclick="export_group()"><?php echo lang("导出"); ?></div>
    </div>
</div>
</form>
</div>


</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
<!-- 颜色值 -->
<script src="/themes/admin_simpleboot3/public/assets/simpleboot3/js/colpick.js"></script>
<script>
    $(document).ready(function(){

        $(".form-control_sum_btn").click(function(){
            var code=$(this).attr("data-code");
            html='<input type="text" class="form-control form-control_sum"  name="'+code+'[]" placeholder="<?php echo lang('输入内容'); ?>">';
            $(".form-control_sum_val").append(html);
        });

        /*<!-- 颜色值 -->*/
        $('.picker').colpick({
            layout:'hex',
            submit:0,
            colorScheme:'dark',
            onChange:function(hsb,hex,rgb,el,bySetColor) {
                $(el).css('border-color','#'+hex);
                if(!bySetColor) $(el).val('#'+hex);
            }
        }).keyup(function(){
            $(this).colpickSetColor(this.value);
        });

    })
    $('.group').click(function(){
        var a= $(this).attr('data-id');
        //console.log(a);
        $('.group_id_val').val(a);
    })

    function export_group(){
        var id = $('.group_id_val').val();
        //console.log(id);return;
        layer.confirm("<?php echo lang('确定导出？'); ?>", {
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"] //按钮
        }, function () {
            $.ajax({
                url: "<?php echo url('systems/export_group_json'); ?>",
                type: 'post',
                dataType: 'json',
                data: {group_id: id},
                success: function (data) {
                    if (data.code == '1') {
                        var fileName = id;
                        var datastr = `data:text/json;charset=utf-8,${data.href}`;
                        var downloadAnchorNode = document.createElement("a");
                        downloadAnchorNode.setAttribute("href", datastr);
                        downloadAnchorNode.setAttribute("download", fileName + ".json");
                        downloadAnchorNode.click();
                        downloadAnchorNode.remove();
                        layer.msg( "<?php echo lang('导出成功'); ?>", {time: 2000, icon: 1}, function () {
                            //window.location.reload();
                        });
                    } else {
                        layer.msg( "<?php echo lang('导出失败'); ?>", {time: 2000, icon: 2});
                    }
                }
            });

        });
    }
    $('.btn-bj').click(function (){
        var code = $(this).attr('data-id');
        var val = $(this).attr('data-val');
        var title = $(this).attr('data-title');
        var sort = $(this).attr('data-sort');
        layer.open({
            type: 0,
            title:    "<?php echo lang('编辑备注'); ?>",
            area: ['500px', '300px'],   //宽高
            shade: 0.4,   //遮罩透明度
            btn: ["<?php echo lang('OK'); ?>", "<?php echo lang('OFF'); ?>"], //按钮组,
            content: '<div class="layui-form"><label class="layui-form-label"><?php echo lang("标题："); ?></label><input  type="text"  class="form-control" id="title" name="title" value="'+title+'" style="width: 450px;"><label class="layui-form-label"><?php echo lang("备注："); ?></label><input  type="text"  class="form-control" id="desc" name="desc" value="'+val+'" style="width: 450px;"><label class="layui-form-label"><?php echo lang("排序："); ?></label><input  type="text"  class="form-control" id="sort" name="sort" value="'+sort+'" style="width: 450px;"></div>',
            success:function(){

            },
            yes:function(index){   //点击确定回调
                //alert($('#vip_end_time').val());
                layer.close(index);
                $.ajax({
                    url: "<?php echo url('systems/desc_set'); ?>",
                    type: 'get',
                    dataType: 'json',
                    data: {code: code,desc:$('#desc').val(),title:$('#title').val(),sort:$('#sort').val()},
                    success: function (data) {
                        if(data['code'] == 1){
                            layer.msg(data['msg'],{time: 2000, icon:1},function(){
                                window.location.reload();
                            });
                        }else{
                            layer.msg(data['msg'],{time: 2000, icon:2});
                        }
                    }
                });
            }
        });
    });
</script>
</body>
</html>
