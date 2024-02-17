<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
use think\facade\Db;
/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class Live extends BaseMall {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/live.lang.php');
    }

    public function index() {
        
//        View::assign('floor_block', $floor_block);
        return View::fetch($this->template_dir . 'index');
    }
    
    public function get_live_list() {
        $state=input('param.state');
        $goodsclass_model = model('goodsclass');
        //获取分类
        $cache_key = 'api-member-live';
        $temp = rcache($cache_key);
        if (empty($temp)) {
            $gc_id_array = Db::name('live_apply_goods')->distinct(true)->where('1=1')->column('gc_id_1');
            $goodsclass_list = array();
            $live_apply_ids = array();
            foreach ($gc_id_array as $v) {
                $goodsclass_list[] = $goodsclass_model->getGoodsclassInfoById($v);
                $live_apply_ids[$v] = Db::name('live_apply_goods')->distinct(true)->where('gc_id_1', $v)->column('live_apply_id');
            }
            $temp = array('goodsclass_list' => $goodsclass_list, 'live_apply_ids' => $live_apply_ids);
            wcache($cache_key, $temp);
        }
        $goodsclass_list = $temp['goodsclass_list'];
        $live_apply_ids = $temp['live_apply_ids'];

        $gc_id = intval(input('param.gc_id'));
        $keyword = input('param.keyword');
        $goods_model = model('goods');
        $live_apply_model = model('live_apply');
        $condition = array();
        $condition[] = array('live_apply_state', '=', 1);
//        $condition[] = array('live_apply_end_time', '>', TIMESTAMP);
        switch($state){
            case 2://未开播
                $condition[] = array('live_apply_play_time', '>', TIMESTAMP);
                break;
            case 3://已结束
                $condition[] = array('live_apply_end_time', '<', TIMESTAMP);
                $condition[] = array('live_apply_video','<>','');
                break;
            default://直播中
                $condition[] = array('live_apply_play_time', '<', TIMESTAMP);
                $condition[] = array('live_apply_end_time', '>', TIMESTAMP);
                break;
        }
        if ($gc_id > 0) {
            $condition[] = array('live_apply_id', 'in', isset($live_apply_ids[$gc_id]) ? $live_apply_ids[$gc_id] : array());
        }
        if ($keyword) {
            $condition[] = array('live_apply_id', 'in', Db::name('live_apply_goods')->distinct(true)->where(array(array('store_name|goods_name|gc_name', 'like', $keyword)))->column('live_apply_id'));
        }
        $live_apply_list = $live_apply_model->getLiveApplyList($condition);
        $store_model = model('store');
        foreach ($live_apply_list as $key => $val) {
            $live_apply_list[$key]['live_apply_play_time_text'] = date('Y-m-d H:i',$val['live_apply_play_time']);
            $live_apply_list[$key]['state'] = 1;
            if($val['live_apply_play_time']>TIMESTAMP){
                $live_apply_list[$key]['state'] = 2;
            }elseif($val['live_apply_end_time']<TIMESTAMP){
                $live_apply_list[$key]['state'] = 3;
            }
            if ($val['live_apply_user_type'] == 2) {
                $store_info = $store_model->getStoreInfoByID($val['live_apply_user_id']);
                if (!$store_info) {
                    unset($live_apply_list[$key]);
                    continue;
                }
                $live_apply_list[$key]['store_name'] = $store_info['store_name'];
                $live_apply_list[$key]['store_avatar'] = get_store_logo($store_info['store_avatar']);
                $live_apply_list[$key]['area_info'] = $store_info['area_info'];
            }

            $live_apply_list[$key]['live_apply_cover_image_url'] = ds_get_pic(ATTACH_COMMON,config('ds_config.default_goods_image'));
            if ($val['live_apply_cover_video']) {
                $live_apply_list[$key]['live_apply_cover_video_url'] = ds_get_pic( ATTACH_LIVE_APPLY . '/' . $val['live_apply_user_id'] , $val['live_apply_cover_video']);
            } elseif ($val['live_apply_cover_image']) {
                $live_apply_list[$key]['live_apply_cover_image_url'] = ds_get_pic( ATTACH_LIVE_APPLY . '/' . $val['live_apply_user_id'] , $val['live_apply_cover_image']);
            }

            $live_apply_goods_list = $live_apply_model->getLiveApplyGoodsList(array(array('live_apply_id', '=', $val['live_apply_id'])));
            $live_apply_list[$key]['goods_list'] = array();
            foreach ($live_apply_goods_list as $v) {
                    $goods_info = $goods_model->getGoodsCommonInfoByID($v['goods_commonid']);
                    if ($goods_info && $goods_info['goods_state'] == 1 && $goods_info['goods_verify'] == 1) {
                        $goods_info['goods_image'] = goods_cthumb($goods_info['goods_image']);
                        $live_apply_list[$key]['goods_list'][] = $goods_info;
                    }
            }
        }
        $result = array('goodsclass_list' => $goodsclass_list, 'live_apply_list' => $live_apply_list);
        $extend_data=array();
        $extend_data['hasmore'] = false;
        
        $current_page = $live_apply_model->page_info->currentPage();
        if ($current_page <= 0) {
            $current_page = 1;
        }
        if ($current_page >= $live_apply_model->page_info->lastPage()) {
            $extend_data['hasmore'] = false;
        }
        else {
            $extend_data['hasmore'] = true;
        }
        $result = array_merge($result, $extend_data);
        ds_json_encode(10000, '', $result);
    }
}
