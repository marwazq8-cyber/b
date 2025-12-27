<?php
/**
 * Created by PhpStorm.
 * User: yang
 * Date: 2021-01-08
 * Time: 14:39
 */

namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Config;

class ClearTable extends Command
{
    protected function configure()
    {
        $this->setName('cleartable')->setDescription('山东布谷鸟网络科技[清除表数据]');
    }

    protected function execute(Input $input, Output $output)
    {
        $d = Config::get('database')['database'];

        $s = 'Tables_in_' . $d;
        $arr = Db::query("SHOW TABLES");
        $prefix = 'bogo_';
        $table =
            [
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
                'bogo_game_box_gift_list_rate',
                'bogo_agency_menu'
            ];

        foreach ($arr as $value) {
            if (!in_array($value[$s], $table)) {
                if ($value[$s] == $prefix . 'user') {
                    Db::name('user')->where('user_type = 2')->delete();
                } else if ($value[$s] == $prefix . 'pay_menu') {
                    Db::name('pay_menu')->where('id > 0')->update(['merchant_id' => '', 'public_key' => '', 'private_key' => '']);
                } else {
                    Db::query("truncate table " . $value[$s]);
                }
            }
        }

        $output->writeln("数据表清除成功");
    }
}
