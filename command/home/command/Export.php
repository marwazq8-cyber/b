<?php

namespace app\home\command;

use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Export extends Command
{
    protected function configure()
    {
        // 这里描述这个任务做什么的
        $this->setName('ExportTable')->setDescription('Export tables structure or structure and data');
    }

    protected function execute(Input $input, Output $output)
    {
        // 这里放你的需要导出结构和数据的表名数组
        $tablesToExportData = $this->getTablesToExportData();

        // 提示用户输入数据库连接信息
        $output->writeln('Please enter your database credentials：');

        $db = Config::get('database');

        $host = $db['hostname'];
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'];

        // 临时文件存储MySQL凭据
        $temp = tempnam(sys_get_temp_dir(), 'cnf');
        $cnf = <<<EOT
[mysqldump]
user={$username}
password={$password}
EOT;
        file_put_contents($temp, $cnf);

        $directory = "./sql";

        if (!file_exists($directory)) {
            //如果目录不存在,使用 mkdir() 函数创建
            if (mkdir($directory, 0777, true)) {
                $output->info('Directory created successfully.');
            } else {
                $output->error('Failed to create directory.');
            }
        } else {
            $output->info('Directory already exists.');
        }

        $fileName = './sql/database_backup_' . date('Y-m-d-H-i-s') . '.sql'; // 导出文件名

        // 先删除之前的备份文件，确保新的备份不会附加在旧的备份之后
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        // 获取数据库中所有的表名
        $allTables = Db::query('SHOW TABLES');

        // 对于每一个表，如果表名在需要导出数据的数组中，则导出结构和数据，否则只导出结构
        foreach ($allTables as $tableArr) {
            foreach ($tableArr as $table) {
                $command = in_array($table, $tablesToExportData)
                    ? "mysqldump --defaults-extra-file={$temp} --host={$host} {$database} {$table} >> {$fileName}"
                    : "mysqldump --defaults-extra-file={$temp} --host={$host} --no-data {$database} {$table} >> {$fileName}";

                // 执行命令
                exec($command, $shellOutput, $returnVar);

                $output->info($table);

                // 检查返回状态码
                if ($returnVar !== 0) {
                    $output->error('Error executing the command.');
                } else {
                    $output->info('Command successfully executed.');
                }
            }
        }

        // 输出结果
        $output->writeln("Export complete! File saved as {$fileName}");
    }

    private function getTablesToExportData()
    {
        return [
            'bogo_cloud_sms_config',//短信配置
            'bogo_upload_set',
            'bogo_game_list',
            'bogo_playing_bubble_list',
            'bogo_game_box_list',
            'bogo_bubble_pool',
            'bogo_game_box_type',
            'bogo_admin_menu',
            'bogo_asset',
            'bogo_auth_access',
            'bogo_auth_rule',
            'bogo_auth_talker_label',
            'bogo_auto_talking_skill',
            'bogo_bubble_type',
            'bogo_buckle_invite_recharge_rule',
            'bogo_buckle_invite_rule',
            'bogo_cash_card_name',
            'bogo_cash_rule',
            'bogo_comment',
            'bogo_config',
            'bogo_device_info',
            'bogo_dress_up',
            'bogo_equipment_closures',
            'bogo_evaluate_label',
            'bogo_feedback',
            'bogo_friendship_level',
            'bogo_gacha_coupon_rule',
            'bogo_gacha_gift_list',
            'bogo_gacha_type',
            'bogo_game_box_gift_list',
            'bogo_game_box_pool',
            'bogo_game_box_type',
            'bogo_game_order_type',
            'bogo_gift',
            'bogo_gift_sum',
            'bogo_guardian',
            'bogo_guild_rule',
            'bogo_guild_admin_menu',
            'bogo_guild_admin_menu_user',
            'bogo_home_contact',
            'bogo_home_img',
            'bogo_home_index',
            'bogo_hook',
            'bogo_hook_plugin',
            'bogo_host_fee',
            'bogo_invite_cash_record',
            'bogo_invite_code',
            'bogo_invite_profit_record',
            'bogo_invite_receive_log',
            'bogo_invite_recharge_deduction_record',
            'bogo_invite_record',
            'bogo_invite_redbag',
            'bogo_invite_reg_deduction_record',
            'bogo_invited_record_log',
            'bogo_join_in',
            'bogo_level',
            'bogo_level_type',
            'bogo_level_log',
            'bogo_link',
            'bogo_live',
            'bogo_live_gift',
            'bogo_live_pk',
            'bogo_live_pk_time',
            'bogo_luck_box',
            'bogo_magic_wand',
            'bogo_mb_user',
            'bogo_medal',
            'bogo_mission',
            'bogo_monitor',
            'bogo_music',
            'bogo_music_download',
            'bogo_music_type',
            'bogo_nav',
            'bogo_nav_menu',
            'bogo_noble',
            'bogo_noble_log',
            'bogo_noble_privilege',
            'bogo_notice',
            'bogo_online_record',
            'bogo_option',
            'bogo_platform_auth',
            'bogo_platform_auth_img',
            'bogo_platform_auth_type',
            'bogo_play_game',
            'bogo_play_game_order_info',
            'bogo_play_game_type',
            'bogo_player_level',
            'bogo_player_price_rule',
            'bogo_playing_bubble_list',
            'bogo_plugin',
            'bogo_portal_category',
            'bogo_portal_category_post',
            'bogo_portal_post',
            'bogo_portal_tag',
            'bogo_portal_tag_post',
            'bogo_recycle_bin',
            'bogo_refuse',
            'bogo_reward_coin_rule',
            'bogo_role',
            'bogo_role_user',
            'bogo_room_memes',
            'bogo_route',
            'bogo_shop',
            'bogo_shop_price',
            'bogo_sign_in',
            'bogo_skills_comment_label',
            'bogo_skills_info_label',
            'bogo_skills_list',
            'bogo_skills_price',
            'bogo_skills_recommend_label',
            'bogo_skills_search_price',
            'bogo_slide',
            'bogo_slide_item',
            'bogo_sys_menu',
            'bogo_sys_role',
            'bogo_sys_role_menu',
            'bogo_sys_user',
            'bogo_sys_user_role',
            'bogo_talker_level',
            'bogo_task',
            'bogo_theme',
            'bogo_theme_file',
            'bogo_turntable',
            'bogo_user_charge_rule',
            'bogo_user_exchange_list',
            'bogo_user_earnings_withdrawal',
            'bogo_user_report_type',
            'bogo_user_occupation',
            'bogo_user_message',
            'bogo_user_message_all',
            'bogo_user_video_buy',
            'bogo_vip_rule',
            'bogo_vip',
            'bogo_vip_rule_details',
            'bogo_visualize_table',
            'bogo_voice_bank',
            'bogo_voice_bg',
            'bogo_voice_img',
            'bogo_voice_label',
            'bogo_voice_release',
            'bogo_voice_sound',
            'bogo_voice_type',
            'bogo_gift_type',
            'bogo_country',
            'bogo_game_tree_coin',
            'bogo_game_tree_gift',
            'bogo_game_box_gift_list_rate'
        ];
    }
}