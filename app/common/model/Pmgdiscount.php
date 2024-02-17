<?php

namespace app\common\model;


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
 * 数据层模型
 */
class Pmgdiscount extends BaseModel {

    public function getPmgdiscountInfoByGoodsInfo($goods_info) {
        //判断店铺是否开启会员折扣
        $store = Db::name('store')->where('store_id',$goods_info['store_id'])->find();
        if($store['store_mgdiscount_state'] != 1){
            return ;
        }
        //判断套餐时间
        $mgdiscountquota_model = model('pmgdiscountquota');
		if($store['is_platform_store']!=1 && intval(config('ds_config.mgdiscount_price'))!=0){//非自营店且促销套餐价格非零就需要检查是否购买了套餐
        $current_mgdiscount_quota = $mgdiscountquota_model->getMgdiscountquotaCurrent($goods_info['store_id']);
        if(empty($current_mgdiscount_quota) || $current_mgdiscount_quota['mgdiscountquota_endtime']<TIMESTAMP){
            return ;
        }
		}
        //查看此商品是否单独设置了折扣
        if($goods_info['goods_mgdiscount'] != ''){
            return unserialize($goods_info['goods_mgdiscount']);
        }
        //当店铺设置了店铺会员等级折扣
        if($store['store_mgdiscount'] != ''){
            return unserialize($store['store_mgdiscount']);
        }
        return;
    }

}
