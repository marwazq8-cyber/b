# 布谷海外语音系统

> 核心框架基于ThinkPHP 5.0
> 
## 配置文件

数据库配置文件：`/public/db.php`

`Redis` 配置文件：`/system/RedisPackage.php`、`/application/config.php`

部署后设置禁止直接访问目录：`/public/system` 目录禁止访问

部署根据发送的部署文档进行部署即可

## 定时任务

`/api/crontab_api/minute_crontab` 每分钟定时任务

`/api/crontab_api/online_status` 定时修改登录状态

`/api/crontab_api/remove_pull_black` 解除拉黑状态 - 每3秒执行一次

`/api/crontab_api/crontab_tree` 浇树游戏 - 每3秒执行一次

`/api/crontab_api/crontab_user_game_box` 开宝箱游戏 - 每1秒执行一次

`/api/crontab_api/lucky_jackpot` 幸运礼物头奖 - 每1分钟执行一次

---

## 需要迁移的资源表字段

- **bogo_noble**：`noble_img`、`entry_effects`
- **bogo_noble_privilege**：`privilege_img`、`no_img`
- **bogo_game_list**：`img`、`game_coin_picture`、`game_bg`
- **bogo_gift**：`img`、`svga`
- **bogo_dress_up**：`icon`、`ios_icon`、`img_bg`