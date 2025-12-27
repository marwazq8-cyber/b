<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:104:"/www/wwwroot/admin.xivolive.com/TomChat-PHP/public/../application/api/view/novice_guide_api/content.html";i:1730282264;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo lang("内容"); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/static/css/index.css" rel="stylesheet">
    <link href="/static/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
        body{
            overflow-y: scroll;
        }
    </style>
</head>
<body>
<div class="col-xs-12 guide-center text-center"><?php echo $portal['post_title']; ?></div>

<div class="col-xs-12  text-center">
   <?php echo $portal['post_content']; ?>
</div>
</body>
</html>