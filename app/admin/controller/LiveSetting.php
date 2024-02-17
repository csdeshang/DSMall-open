<?php

/**
 * 商品管理
 */

namespace app\admin\controller;
use think\facade\View;
use think\facade\Lang;

/**
 * ============================================================================
 * DSKMS多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class LiveSetting extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/live_setting.lang.php');
    }

    public function index() {
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            View::assign('list_config', $list_config);
            $this->setAdminCurItem('index');
            return View::fetch();
        } else {
            $update_array=array();
            $update_array['live_type'] = input('post.live_type');
            $update_array['vod_tencent_play_key'] = input('post.vod_tencent_play_key');
            $update_array['vod_tencent_appid'] = input('post.vod_tencent_appid');
            $update_array['vod_tencent_play_domain'] = input('post.vod_tencent_play_domain');
            $update_array['vod_tencent_secret_id'] = input('post.vod_tencent_secret_id');
            $update_array['vod_tencent_secret_key'] = input('post.vod_tencent_secret_key');
            $update_array['live_push_domain'] = input('param.live_push_domain');
            $update_array['live_push_key'] = input('param.live_push_key');
            $update_array['live_play_domain'] = input('param.live_play_domain');
            $update_array['video_type'] = input('param.video_type');
            $update_array['aliyun_user_id'] = input('param.aliyun_user_id');
            $update_array['aliyun_access_key_id'] = input('param.aliyun_access_key_id');
            $update_array['aliyun_access_key_secret'] = input('param.aliyun_access_key_secret');
            $update_array['aliyun_live_push_domain'] = input('post.aliyun_live_push_domain');
            $update_array['aliyun_live_push_key'] = input('post.aliyun_live_push_key');
            $update_array['aliyun_live_play_domain'] = input('post.aliyun_live_play_domain');
            $update_array['aliyun_live_play_key'] = input('post.aliyun_live_play_key');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                dkcache('config');
                $this->log(lang('ds_edit') . lang('live_setting'), 1);
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->log(lang('ds_edit') . lang('live_setting'), 0);
            }
        }
    }
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_setting'),
                'url' => url('live_setting/index')
            ),
        );
        return $menu_array;
    }

}

?>
