<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:51:"themes/admin_simpleboot3/admin/slide_item/edit.html";i:1730282264;s:94:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/themes/admin_simpleboot3/public/header.html";i:1730282264;}*/ ?>
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
    .link_url{display: none;}
    .article_title{display: none;}
</style>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('SlideItem/index',['slide_id'=>$slide_id]); ?>"><?php echo lang('幻灯片页面列表'); ?></a></li>
        <?php if($slide_id != 3): ?>
            <li><a href="<?php echo url('SlideItem/add',['slide_id'=>$slide_id]); ?>"><?php echo lang('添加幻灯片页面'); ?></a></li>
        <?php endif; ?>
        <li class="active"><a><?php echo lang('编辑幻灯片页面'); ?></a></li>
    </ul>
    <form action="<?php echo url('SlideItem/editPost'); ?>" method="post" class="form-horizontal js-ajax-form margin-top-20">
        <div class="row">
            <div class="col-md-9">
                <table class="table table-bordered">
                    <tr>
                        <th><span class="form-required">*</span><?php echo lang('标题'); ?></th>
                        <td>
                            <input class="form-control" type="text" style="width:400px;" name="post[title]" id="title"
                                   required value="<?php echo $result['title']; ?>" placeholder="<?php echo lang('Please_enter_title'); ?>"/>
                        </td>
                    </tr>
                     <tr>
                        <th><?php echo lang('URL链接类型'); ?></th>
                        <td>
                            <select class="form-control link_type" name="post[type]" >
                                <option value="0"><?php echo lang('外连接'); ?></option>
                                <option value="1" <?php if($result['type'] == 1){ ?> selected='selected'<?php }?>><?php echo lang('邀请链接'); ?></option>
                                <option value="2" <?php if($result['type'] == 2){ ?> selected='selected'<?php }?>><?php echo lang('文章链接'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr  class="link_url">
                        <th><?php echo lang('链接'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post[url]" id="keywords" value="<?php echo $result['url']; ?>"
                                   style="width: 400px" placeholder="<?php echo lang('请输入链接'); ?>">
                        </td>
                    </tr>
                  
                    <tr>
                        <th><?php echo lang('是否传用户ID TOKEN 信息'); ?></th>
                        <td>
                            <select class="form-control" name="post[is_auth_info]">
                                <option value="0" selected='selected'><?php echo lang('否'); ?></option>
                                <option value="1" <?php if($result['is_auth_info'] == 1){ ?> selected='selected'<?php }?>><?php echo lang('YES'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><?php echo lang('是否使用外部浏览器打开'); ?></th>
                        <td>
                            <select class="form-control" name="post[is_out_webview_open]">
                                <option value="0" selected='selected'><?php echo lang('否'); ?></option>
                                <option value="1" <?php if($result['is_out_webview_open'] == 1){ ?> selected='selected'<?php }?>><?php echo lang('YES'); ?></option>
                            </select>
                        </td>
                    </tr>

                     <tr class="article_title">
                        <th><?php echo lang('文章标题'); ?><span class="form-required">*</span></th>
                        <td>
                            <input class="form-control" type="text" name="post_title"
                                   id="title" required value="<?php echo (isset($article['post_title']) && ($article['post_title'] !== '')?$article['post_title']:''); ?>" placeholder="<?php echo lang('Please_enter_title'); ?>"/>
                        </td>
                    </tr>
                     <tr  class="article_title">
                        <th><?php echo lang('关键词'); ?></th>
                        <td>
                            <input class="form-control" type="text" name="post_keywords" id="keywords" value="<?php echo (isset($article['post_keywords']) && ($article['post_keywords'] !== '')?$article['post_keywords']:''); ?>"
                                   placeholder="<?php echo lang('请输入关键字'); ?>">
                            <p class="help-block"><?php echo lang('多关键词之间用英文逗号隔开'); ?></p>
                        </td>
                    </tr>
                    <tr class="article_title">
                        <th><?php echo lang('文章内容'); ?></th>
                        <td>
                            <script type="text/plain" id="content" name="post_content"><?php echo (isset($article['post_content']) && ($article['post_content'] !== '')?$article['post_content']:''); ?></script>
                        </td>
                    </tr>

                </table>
            </div>
            <div class="col-md-3">
                <table class="table table-bordered">
                    <tr>
                        <th><b><?php echo lang('缩略图'); ?></b></th>
                    </tr>
                    <tr>
                        <td>
                            <div style="text-align: center;">
                                <input type="hidden" name="post[image]" id="thumb" value="<?php echo (isset($result['image']) && ($result['image'] !== '')?$result['image']:''); ?>">
                                <a href="javascript:uploadOneImage('<?php echo lang("图片上传"); ?>','#thumb');">
                                    <?php if(empty($result['image'])): ?>
                                        <img src="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png"
                                             id="thumb-preview" width="135" style="cursor: hand"/>
                                        <?php else: ?>
                                        <img src="<?php echo cmf_get_image_preview_url($result['image']); ?>" id="thumb-preview"
                                             width="135" style="cursor: hand"/>
                                    <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm"
                                       onclick="$('#thumb-preview').attr('src','/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');$('#thumb').val('');return false;"
                                       value="<?php echo lang('取消图片'); ?>">
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <input type="hidden" name="post[id]" value="<?php echo $result['id']; ?>"/>
                <input type="hidden" name="post[slide_id]" value="<?php echo $slide_id; ?>">
                <input type="hidden" name="article_id" value="<?php echo (isset($article['id']) && ($article['id'] !== '')?$article['id']:''); ?>">
                <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                <a class="btn btn-default" href="<?php echo url('SlideItem/index',['slide_id'=>$slide_id]); ?>"><?php echo lang('BACK'); ?></a>
            </div>
        </div>
    </form>
</div>
<script type="text/javascript" src="/static/js/admin.js"></script>
<script type="text/javascript">
    //编辑器路径定义
    var editorURL = GV.WEB_ROOT;
</script>
<script type="text/javascript" src="/static/js/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/static/js/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript">
    $(function () {
        var type="<?php echo $result['type']; ?>";
        if(type == '0'){
            $(".link_url").show();
        }else if(type == '2'){
            $(".article_title").show();
        }
        editorcontent = new baidu.editor.ui.Editor();
        editorcontent.render('content');
        try {
            editorcontent.sync();
        } catch (err) {
        }

        $('.btn-cancel-thumbnail').click(function () {
            $('#thumbnail-preview').attr('src', '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png');
            $('#thumbnail').val('');
        });

        $(".link_type").change(function(){
             var label_id=($(this).val());
             if(label_id ==1){
                $(".article_title").hide();
                $(".link_url").hide();
              }else if(label_id ==2){
                    $(".article_title").show();
                    $(".link_url").hide();
              }else{
                $(".link_url").show();
                $(".article_title").hide();
              }
        });


    });

   
</script>
</body>
</html>