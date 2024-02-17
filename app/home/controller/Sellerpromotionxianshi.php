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
class Sellerpromotionxianshi extends BaseSeller {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerpromotionxianshi.lang.php');
        if (intval(config('ds_config.promotion_allow')) !== 1) {
            $this->error(lang('promotion_unavailable'), 'seller/index');
        }
    }

    public function index() {
        $xianshiquota_model = model('pxianshiquota');
        $pxianshi_model = model('pxianshi');

        if (check_platform_store()) {
            View::assign('isPlatformStore', true);
        } else {
            $current_xianshi_quota = $xianshiquota_model->getXianshiquotaCurrent(session('store_id'));
            View::assign('current_xianshi_quota', $current_xianshi_quota);
        }

        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        if ((input('param.xianshi_name'))) {
            $condition[] = array('xianshi_name', 'like', '%' . input('param.xianshi_name') . '%');
        }
        if ((input('param.state'))) {
            $condition[] = array('xianshi_state', '=', intval(input('param.state')));
        }
        $xianshi_list = $pxianshi_model->getXianshiList($condition, 10, Db::raw('FIELD(xianshi_state, 1,0,2,3), xianshi_end_time desc'));
        View::assign('xianshi_list', $xianshi_list);
        View::assign('show_page', $pxianshi_model->page_info->render());
        View::assign('xianshi_state_array', $pxianshi_model->getXianshiStateArray());

        $this->setSellerCurMenu('Sellerpromotionxianshi');
        $this->setSellerCurItem('xianshi_list');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 添加秒杀活动
     * */
    public function xianshi_add() {
        if (check_platform_store()) {
            View::assign('isPlatformStore', true);
        } else {
            View::assign('isPlatformStore', false);
            $xianshiquota_model = model('pxianshiquota');
            $current_xianshi_quota = $xianshiquota_model->getXianshiquotaCurrent(session('store_id'));
            if (empty($current_xianshi_quota)) {
                if (intval(config('ds_config.promotion_xianshi_price')) != 0) {
                    $this->error(lang('xianshi_quota_current_error1'));
                } else {
                    $current_xianshi_quota = array('xianshiquota_starttime' => TIMESTAMP, 'xianshiquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                }
            }
            View::assign('current_xianshi_quota', $current_xianshi_quota);
        }

        //输出导航
        $this->setSellerCurMenu('Sellerpromotionxianshi');
        $this->setSellerCurItem('xianshi_add');
        return View::fetch($this->template_dir . 'xianshi_add');
    }

    /**
     * 保存添加的秒杀活动
     * */
    public function xianshi_save() {
        //验证输入
        $xianshi_name = trim(input('post.xianshi_name'));
        $start_time = strtotime(input('post.start_time'));
        $end_time = strtotime(input('post.end_time'));
        $lower_limit = intval(input('post.lower_limit'));
        if ($lower_limit <= 0) {
            $lower_limit = 1;
        }
        if (empty($xianshi_name)) {
            ds_json_encode(10001, lang('xianshi_name_error'));
        }
        if ($start_time >= $end_time) {
            ds_json_encode(10001, lang('greater_than_start_time'));
        }

        if (!check_platform_store()) {
            //获取当前套餐
            $xianshiquota_model = model('pxianshiquota');
            $current_xianshi_quota = $xianshiquota_model->getXianshiquotaCurrent(session('store_id'));
            if (empty($current_xianshi_quota)) {
                if (intval(config('ds_config.promotion_xianshi_price')) != 0) {
                    ds_json_encode(10001, lang('please_buy_package_first'));
                } else {
                    $current_xianshi_quota = array('xianshiquota_starttime' => TIMESTAMP, 'xianshiquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                }
            }
            $quota_start_time = intval($current_xianshi_quota['xianshiquota_starttime']);
            $quota_end_time = intval($current_xianshi_quota['xianshiquota_endtime']);

            if ($start_time < $quota_start_time) {
                ds_json_encode(10001, sprintf(lang('xianshi_add_start_time_explain'), date('Y-m-d', $current_xianshi_quota['xianshiquota_starttime'])));
            }
            if ($end_time > $quota_end_time) {
                ds_json_encode(10001, sprintf(lang('xianshi_add_end_time_explain'), date('Y-m-d', $current_xianshi_quota['xianshiquota_endtime'])));
            }
        }
            if($end_time<TIMESTAMP){
                ds_json_encode(10001, sprintf(lang('xianshi_add_end_time_explain'), date('Y-m-d')));
            }
        //生成活动
        $pxianshi_model = model('pxianshi');
        $param = array();
        $param['xianshi_name'] = $xianshi_name;
        $param['xianshi_title'] = input('post.xianshi_title');
        $param['xianshi_explain'] = input('post.xianshi_explain');
        $param['xianshiquota_id'] = isset($current_xianshi_quota['xianshiquota_id']) ? $current_xianshi_quota['xianshiquota_id'] : 0;
        $param['xianshi_starttime'] = $start_time;
        $param['xianshi_end_time'] = $end_time;
        $param['store_id'] = session('store_id');
        $param['store_name'] = session('store_name');
        $param['member_id'] = session('member_id');
        $param['member_name'] = session('member_name');
        $param['xianshi_lower_limit'] = $lower_limit;
        $result = $pxianshi_model->addXianshi($param);
        if ($result) {
            $this->recordSellerlog(lang('add_limited_time_discount_activity') . $xianshi_name . lang('activity_number') . $result);
            // 添加计划任务
            $this->addcron(array('cron_exetime' => $param['xianshi_end_time'], 'cron_value' => serialize(intval($result)), 'cron_type' => 'editExpireXianshi'), true);
            ds_json_encode(10000, lang('xianshi_add_success'));
        } else {
            ds_json_encode(10001, lang('xianshi_add_fail'));
        }
    }

    /**
     * 编辑秒杀活动
     * */
    public function xianshi_edit() {
        if (check_platform_store()) {
            View::assign('isPlatformStore', true);
        } else {
            View::assign('isPlatformStore', false);
        }
        $pxianshi_model = model('pxianshi');

        $xianshi_info = $pxianshi_model->getXianshiInfoByID(input('param.xianshi_id'));
        if (empty($xianshi_info) || !$xianshi_info['editable']) {
            $this->error(lang('param_error'));
        }

        View::assign('xianshi_info', $xianshi_info);

        //输出导航
        $this->setSellerCurMenu('Sellerpromotionxianshi');
        $this->setSellerCurItem('xianshi_edit');
        return View::fetch($this->template_dir . 'xianshi_add');
    }

    /**
     * 编辑保存秒杀活动
     * */
    public function xianshi_edit_save() {
        $xianshi_id = input('post.xianshi_id');

        $pxianshi_model = model('pxianshi');
        $xianshigoods_model = model('pxianshigoods');

        $xianshi_info = $pxianshi_model->getXianshiInfoByID($xianshi_id, session('store_id'));
        if (empty($xianshi_info) || !$xianshi_info['editable']) {
            ds_json_encode(10001, lang('param_error'));
        }

        //验证输入
        $xianshi_name = trim(input('post.xianshi_name'));
        $lower_limit = intval(input('post.lower_limit'));
        if ($lower_limit <= 0) {
            $lower_limit = 1;
        }
        if (empty($xianshi_name)) {
            ds_json_encode(10001, lang('xianshi_name_error'));
        }

        //生成活动
        $param = array();
        $param['xianshi_name'] = $xianshi_name;
        $param['xianshi_title'] = input('post.xianshi_title');
        $param['xianshi_explain'] = input('post.xianshi_explain');
        $param['xianshi_lower_limit'] = $lower_limit;
        $param_goods = array();
        $param_goods['xianshi_name'] = $xianshi_name;
        $param_goods['xianshi_title'] = input('post.xianshi_title');
        $param_goods['xianshi_explain'] = input('post.xianshi_explain');
        $param_goods['xianshigoods_lower_limit'] = $lower_limit;
        $result = $pxianshi_model->editXianshi($param, array('xianshi_id' => $xianshi_id));
        $result1 = $xianshigoods_model->editXianshigoods($param_goods, array('xianshi_id' => $xianshi_id));
        if ($result) {
            $this->recordSellerlog(lang('edit_limited_time_discount_activity') . $xianshi_name . lang('activity_number') . $xianshi_id);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 秒杀活动删除
     * */
    public function xianshi_del() {
        $xianshi_id = intval(input('post.xianshi_id'));

        $pxianshi_model = model('pxianshi');

        $data = array();
        $data['result'] = true;

        $xianshi_info = $pxianshi_model->getXianshiInfoByID($xianshi_id, session('store_id'));
        if (!$xianshi_info) {
            ds_json_encode(10001, lang('param_error'));
        }

        $pxianshi_model = model('pxianshi');
        $result = $pxianshi_model->delXianshi(array('xianshi_id' => $xianshi_id));

        if ($result) {
            $this->recordSellerlog(lang('delete_limited_time_discount_activity') . $xianshi_info['xianshi_name'] . lang('activity_number') . $xianshi_id);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 秒杀活动管理
     * */
    public function xianshi_manage() {
        $pxianshi_model = model('pxianshi');
        $xianshigoods_model = model('pxianshigoods');

        $xianshi_id = intval(input('param.xianshi_id'));
        $xianshi_info = $pxianshi_model->getXianshiInfoByID($xianshi_id, session('store_id'));
        if (empty($xianshi_info)) {
            $this->error(lang('param_error'));
        }
        View::assign('xianshi_info', $xianshi_info);

        //获取秒杀商品列表
        $condition = array();
        $condition[] = array('xianshi_id','=',$xianshi_id);
        $xianshigoods_list = $xianshigoods_model->getXianshigoodsExtendList($condition);

        View::assign('xianshi_goods_list', $xianshigoods_list);

        //输出导航
        $this->setSellerCurMenu('Sellerpromotionxianshi');
        $this->setSellerCurItem('xianshi_manage');
        return View::fetch($this->template_dir . 'xianshi_manage');
    }

    /**
     * 秒杀套餐购买
     * */
    public function xianshi_quota_add() {
        //输出导航
        $this->setSellerCurMenu('Sellerpromotionxianshi');
        $this->setSellerCurItem('xianshi_quota_add');
        return View::fetch($this->template_dir . 'xianshi_quota_add');
    }

    /**
     * 秒杀套餐购买保存
     * */
    public function xianshi_quota_add_save() {
        if (intval(config('ds_config.promotion_xianshi_price')) == 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $xianshi_quota_quantity = intval(input('post.xianshi_quota_quantity'));

        if ($xianshi_quota_quantity <= 0 || $xianshi_quota_quantity > 12) {
            ds_json_encode(10001, lang('xianshi_quota_quantity_error'));
        }

        //获取当前价格
        $current_price = intval(config('ds_config.promotion_xianshi_price'));

        //获取该用户已有套餐
        $xianshiquota_model = model('pxianshiquota');
        $current_xianshi_quota = $xianshiquota_model->getXianshiquotaCurrent(session('store_id'));
        $xianshi_add_time = 86400 * 30 * $xianshi_quota_quantity;
        if (empty($current_xianshi_quota)) {
            //生成套餐
            $param = array();
            $param['member_id'] = session('member_id');
            $param['member_name'] = session('member_name');
            $param['store_id'] = session('store_id');
            $param['store_name'] = session('store_name');
            $param['xianshiquota_starttime'] = TIMESTAMP;
            $param['xianshiquota_endtime'] = TIMESTAMP + $xianshi_add_time;
            $xianshiquota_model->addXianshiquota($param);
        } else {
            $param = array();
            $param['xianshiquota_endtime'] = Db::raw('xianshiquota_endtime+' . $xianshi_add_time);
            $xianshiquota_model->editXianshiquota($param, array('xianshiquota_id' => $current_xianshi_quota['xianshiquota_id']));
        }

        //记录店铺费用
        $this->recordStorecost($current_price * $xianshi_quota_quantity, lang('buy_limited_time_discount').' ['.$xianshi_quota_quantity.'个月 × 单价:'.$current_price.'元]');

        $this->recordSellerlog(lang('buy') . $xianshi_quota_quantity . lang('limited_time_discount_package') . $current_price . lang('ds_yuan'));

        ds_json_encode(10000, lang('xianshi_quota_add_success'));
    }

    /**
     * 选择活动商品
     * */
    public function goods_select() {
        $goods_model = model('goods');
        $condition = array();
        $condition[] = array('goods.store_id', '=', session('store_id'));
        $condition[] = array('goods.goods_name', 'like', '%' . input('param.goods_name') . '%');
        $goods_list = $goods_model->getGoodsListForPromotion($condition, 'goods.goods_id,goods.goods_commonid,goods.goods_name,goods.goods_image,goods.goods_price', 10, 'xianshi');

        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'goods_select');
    }

    /**
     * 秒杀商品添加
     * */
    public function xianshi_goods_add() {
        $goods_id = intval(input('post.goods_id'));
        $xianshi_id = intval(input('post.xianshi_id'));
        $xianshi_price = floatval(input('post.xianshi_price'));

        $goods_model = model('goods');
        $pxianshi_model = model('pxianshi');
        $xianshigoods_model = model('pxianshigoods');

        $data = array();
        $data['result'] = true;

        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        if (empty($goods_info) || $goods_info['store_id'] != session('store_id')) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }

        $xianshi_info = $pxianshi_model->getXianshiInfoByID($xianshi_id, session('store_id'));
        if (!$xianshi_info) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }

        //检查商品是否已经参加同时段活动
        $condition = array();
        $condition[] = array('xianshigoods_end_time', '>', $xianshi_info['xianshi_starttime']);
        $condition[] = array('goods_id', '=', $goods_id);
        $xianshigoods = $xianshigoods_model->getXianshigoodsExtendList($condition);
        if (!empty($xianshigoods)) {
            $data['result'] = false;
            $data['message'] = lang('product_participated_simultaneous_activities');
            echo json_encode($data);
            die;
        }

        //添加到活动商品表
        $param = array();
        $param['xianshi_id'] = $xianshi_info['xianshi_id'];
        $param['xianshi_name'] = $xianshi_info['xianshi_name'];
        $param['xianshi_title'] = $xianshi_info['xianshi_title'];
        $param['xianshi_explain'] = $xianshi_info['xianshi_explain'];
        $param['goods_id'] = $goods_info['goods_id'];
        $param['goods_commonid'] = $goods_info['goods_commonid'];
        $param['store_id'] = $goods_info['store_id'];
        $param['goods_name'] = $goods_info['goods_name'];
        $param['goods_price'] = $goods_info['goods_price'];
        $param['xianshigoods_price'] = $xianshi_price;
        $param['goods_image'] = $goods_info['goods_image'];
        $param['xianshigoods_starttime'] = $xianshi_info['xianshi_starttime'];
        $param['xianshigoods_end_time'] = $xianshi_info['xianshi_end_time'];
        $param['xianshigoods_lower_limit'] = $xianshi_info['xianshi_lower_limit'];

        $result = array();
        $xianshigoods_info = $xianshigoods_model->addXianshigoods($param);
        if ($xianshigoods_info) {
            $result['result'] = true;
            $data['message'] = lang('add_success');
            $data['xianshi_goods'] = $xianshigoods_info;
            // 自动发布动态
            // goods_id,store_id,goods_name,goods_image,goods_price,goods_freight,xianshi_price
            $data_array = array();
            $data_array['goods_id'] = $goods_info['goods_id'];
            $data_array['store_id'] = session('store_id');
            $data_array['goods_name'] = $goods_info['goods_name'];
            $data_array['goods_image'] = $goods_info['goods_image'];
            $data_array['goods_price'] = $goods_info['goods_price'];
            $data_array['goods_freight'] = $goods_info['goods_freight'];
            $data_array['xianshigoods_price'] = $xianshi_price;
            $this->storeAutoShare($data_array, 'xianshi');
            $this->recordSellerlog(lang('add_limited_time_discount_items') . $xianshi_info['xianshi_name'] . '，' . lang('goods_name') . '：' . $goods_info['goods_name']);

            // 添加任务计划
            $this->addcron(array('cron_type' => 'updateGoodsPromotionPriceByGoodsId', 'cron_value' => serialize($goods_info['goods_id']), 'cron_exetime' => $param['xianshigoods_starttime']));
        } else {
            $data['result'] = false;
            $data['message'] = lang('param_error');
        }
        echo json_encode($data);
        die;
    }

    /**
     * 秒杀商品价格修改
     * */
    public function xianshi_goods_price_edit() {
        $xianshigoods_id = intval(input('post.xianshigoods_id'));
        $xianshi_price = floatval(input('post.xianshi_price'));

        $data = array();
        $data['result'] = true;

        $xianshigoods_model = model('pxianshigoods');

        $xianshigoods_info = $xianshigoods_model->getXianshigoodsInfoByID($xianshigoods_id, session('store_id'));
        if (!$xianshigoods_info) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }

        $update = array();
        $update['xianshigoods_price'] = $xianshi_price;
        $condition = array();
        $condition[] = array('xianshigoods_id','=',$xianshigoods_id);
        $result = $xianshigoods_model->editXianshigoods($update, $condition);

        if ($result) {
            $xianshigoods_info['xianshigoods_price'] = $xianshi_price;
            $xianshigoods_info = $xianshigoods_model->getXianshigoodsExtendInfo($xianshigoods_info);
            $data['xianshi_price'] = $xianshigoods_info['xianshigoods_price'];
            $data['xianshi_discount'] = $xianshigoods_info['xianshi_discount'];

            // 添加对列修改商品促销价格
            model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'updateGoodsPromotionPriceByGoodsId','cron_value'=>serialize($xianshigoods_info['goods_id'])));

            $this->recordSellerlog(lang('limited_time_discount_price_modified') . $xianshigoods_info['xianshigoods_price'] . '，' . lang('goods_name') . '：' . $xianshigoods_info['goods_name']);
        } else {
            $data['result'] = false;
            $data['message'] = lang('ds_common_op_succ');
        }
        echo json_encode($data);
        die;
    }

    /**
     * 秒杀商品删除
     * */
    public function xianshi_goods_delete() {
        $xianshigoods_model = model('pxianshigoods');
        $pxianshi_model = model('pxianshi');

        $data = array();
        $data['result'] = true;

        $xianshigoods_id = intval(input('post.xianshigoods_id'));
        $xianshigoods_info = $xianshigoods_model->getXianshigoodsInfoByID($xianshigoods_id);
        if (!$xianshigoods_info) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }

        $xianshi_info = $pxianshi_model->getXianshiInfoByID($xianshigoods_info['xianshi_id'], session('store_id'));
        if (!$xianshi_info) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }

        if (!$xianshigoods_model->delXianshigoods(array('xianshigoods_id' => $xianshigoods_id))) {
            $data['result'] = false;
            $data['message'] = lang('xianshi_goods_delete_fail');
            echo json_encode($data);
            die;
        }

        // 添加对列修改商品促销价格
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'updateGoodsPromotionPriceByGoodsId','cron_value'=>serialize($xianshigoods_info['goods_id'])));

        $this->recordSellerlog(lang('delete_time_limited_discount_items') . $xianshi_info['xianshi_name'] . '，' . lang('goods_name') . '：' . $xianshigoods_info['goods_name']);
        echo json_encode($data);
        die;
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $name 当前导航的name
     * @param array $array 附加菜单
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'xianshi_list', 'text' => lang('promotion_active_list'),
                'url' => (string) url('Sellerpromotionxianshi/index')
            ),
        );
        switch (request()->action()) {
            case 'xianshi_add':
                $menu_array[] = array(
                    'name' => 'xianshi_add', 'text' => lang('promotion_join_active'),
                    'url' => (string) url('Sellerpromotionxianshi/xianshi_add')
                );
                break;
            case 'xianshi_edit':
                $menu_array[] = array(
                    'name' => 'xianshi_edit', 'text' => lang('editing_activity'), 'url' => 'javascript:;'
                );
                break;
            case 'xianshi_quota_add':
                $menu_array[] = array(
                    'name' => 'xianshi_quota_add', 'text' => lang('promotion_buy_product'),
                    'url' => (string) url('Sellerpromotionxianshi/xianshi_quota_add')
                );
                break;
            case 'xianshi_manage':
                $menu_array[] = array(
                    'name' => 'xianshi_manage', 'text' => lang('promotion_goods_manage'),
                    'url' => (string) url('Sellerpromotionxianshi/xianshi_manage', ['xianshi_id' => input('param.xianshi_id')])
                );
                break;
        }
        return $menu_array;
    }

}
