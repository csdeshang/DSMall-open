<?php
/**
 * 订单打印
 */
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
class Sellerorderprint extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerorderprint.lang.php');
    }

    /**
     * 查看订单
     */
    public function index() {
        $order_id = ds_delete_param(input('param.order_id'));
        if (empty($order_id)) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id','in',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_list = $order_model->getOrderList($condition, '', '*', 'order_id desc', 0, array('order_common', 'order_goods'));
        if (empty($order_list)) {
            $this->error(lang('member_printorder_ordererror'));
        }
        

        //卖家信息
        $store_model = model('store');
        $store_info = $store_model->getStoreInfoByID(session('store_id'));
        if (!empty($store_info['store_avatar'])) {
            if (file_exists(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR .$store_info['store_id']. DIRECTORY_SEPARATOR .$store_info['store_avatar'])) {
                $store_info['store_avatar'] = UPLOAD_SITE_URL . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR .$store_info['store_id']. DIRECTORY_SEPARATOR .$store_info['store_avatar'];
            } else {
                $store_info['store_avatar'] = '';
            }
        }
        if (!empty($store_info['store_seal'])) {
            if (file_exists(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_seal'])) {
                $store_info['store_seal'] = UPLOAD_SITE_URL . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_seal'];
            } else {
                $store_info['store_seal'] = '';
            }
        }
        View::assign('store_info', $store_info);

        //订单商品
        foreach($order_list as $key =>$order_info){
            $goods_all_num = 0;
            $goods_total_price = 0;
            if (isset($order_info['extend_order_goods']) && !empty($order_info['extend_order_goods'])) {
                foreach ($order_info['extend_order_goods'] as $k => $v) {
                    $v['goods_name'] = str_cut($v['goods_name'], 100);
                    $goods_all_num += $v['goods_num'];
                    $v['goods_all_price'] = ds_price_format($v['goods_num'] * $v['goods_price']);
                    $goods_total_price += $v['goods_all_price'];
                    $order_list[$key]['extend_order_goods'][$k]=$v;
                }
                //优惠金额
                $order_list[$key]['promotion_amount'] = $goods_total_price - $order_info['goods_amount'];
                $order_list[$key]['goods_all_num'] = $goods_all_num;
                $order_list[$key]['goods_total_price'] = ds_price_format($goods_total_price);
                $order_list[$key]['total_page'] = ceil(count($order_info['extend_order_goods']) / 15);
            }
            
        }
        View::assign('order_list', $order_list);
        return View::fetch($this->template_dir.'index');
    }

}

?>
