<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
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
class BaseStoreSns extends BaseHome {

    const MAX_RECORDNUM = 20; // 允许插入新记录的最大次数，sns页面该常量是一样的。

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/baseseller.lang.php');
        $this->template_dir = 'default/store/default/' . strtolower(request()->controller()) . '/';
        View::assign('store_theme','default');
        
        View::assign('max_recordnum', self::MAX_RECORDNUM);

        // 自定义导航条
        $this->getStorenavigation();

        //查询会员信息
        $this->getMemberAndGradeInfo(false);
    }

    // 自定义导航条
    protected function getStorenavigation() {
        $storenavigation_model = model('storenavigation');
        $store_navigation_list = $storenavigation_model->getStorenavigationList(array('storenav_store_id' => intval(input('param.sid'))));
        View::assign('store_navigation_list', $store_navigation_list);
    }

    protected function getStoreInfo($store_id) {
        //得到店铺等级信息
        $store_info = model('store')->getStoreInfoByID($store_id);
        if (empty($store_info)) {
            $this->error(lang('store_sns_store_not_exists'));
        }
        //处理地区信息
        $area_array = array();
        $area_array = explode("\t", $store_info["area_info"]);
        $map_city = lang('store_sns_city');
        $city = '';
        if (strpos($area_array[0], $map_city) !== false) {
            $city = $area_array[0];
        } else {
            $city = isset($area_array[1])?$area_array[1]:'';
        }
        $store_info['city'] = $city;
        
        $storejoinin_model=model('storejoinin');
            if(!$store_info['is_platform_store']){
                $storejoinin_info=$storejoinin_model->getOneStorejoinin(array('member_id'=>$store_info['member_id']));
                //营业执照
            if($storejoinin_info){
                $store_info['business_licence_number_electronic']=($storejoinin_info['business_licence_number_electronic'] && $storejoinin_info['store_type']==0)?get_store_joinin_imageurl($storejoinin_info['business_licence_number_electronic']):'';
            }  
        }
        
        View::assign('store_theme', $store_info['store_theme']);
        View::assign('store_info', $store_info);
    }

}

?>
