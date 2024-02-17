<?php

/**
 * 卖家预售管理
 */

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
class Sellerpromotionpresell extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerpromotionpresell.lang.php');
        if (intval(config('ds_config.promotion_allow')) !== 1) {
            $this->error(lang('promotion_unavailable'), 'seller/index');
        }
    }

    public function index() {
        $presellquota_model = model('presellquota');
        $presell_model = model('presell');

        if (check_platform_store()) {
            View::assign('isPlatformStore', true);
        } else {
            $current_presell_quota = $presellquota_model->getPresellquotaCurrent($this->store_info['store_id']);
            View::assign('current_presell_quota', $current_presell_quota);
        }

        $condition = array();
        $condition[] = array('store_id', '=', $this->store_info['store_id']);
        if ((input('param.goods_name'))) {
            $condition[] = array('goods_name', 'like', '%' . input('param.goods_name') . '%');
        }
        if (input('param.state') != '' && in_array(input('param.state'), array(0, 1, 2, 3))) {
            $condition[] = array('presell_state', '=', intval(input('param.state')));
        }
        $presell_list = $presell_model->getPresellList($condition, 10, 'presell_id desc');
        foreach ($presell_list as $key => $val) {
            $presell_list[$key]['presell_state_text'] = $presell_model->getPresellStateText($val);
            $presell_list[$key] = array_merge($presell_list[$key], $presell_model->getPresellBtn($val));
        }

        View::assign('presell_list', $presell_list);
        View::assign('show_page', $presell_model->page_info->render());
        View::assign('presell_state_array', $presell_model->getPresellStateArray());

        $this->setSellerCurMenu('Sellerpromotionpresell');
        $this->setSellerCurItem('presell_list');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 添加预售活动
     * */
    public function presell_add() {
        if (!request()->isPost()) {
            if (check_platform_store()) {
                View::assign('isPlatformStore', true);
            } else {
                View::assign('isPlatformStore', false);
                $presellquota_model = model('presellquota');
                $current_presell_quota = $presellquota_model->getPresellquotaCurrent($this->store_info['store_id']);
                if (empty($current_presell_quota)) {
                    if (intval(config('ds_config.promotion_presell_price')) != 0) {
                        $this->error(lang('presell_quota_current_error1'));
                    } else {
                        $current_presell_quota = array('presellquota_starttime' => TIMESTAMP, 'presellquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                    }
                }
                View::assign('current_presell_quota', $current_presell_quota);
            }

            //输出导航
            $this->setSellerCurMenu('Sellerpromotionpresell');
            $this->setSellerCurItem('presell_add');
            return View::fetch($this->template_dir . 'presell_add');
        } else {

            $data = array(
                'goods_id' => intval(input('post.presell_goods_id')),
                'presell_type' => intval(input('post.presell_type')),
                'presell_deposit_amount' => floatval(input('post.presell_deposit_amount')),
                'presell_price' => floatval(input('post.presell_price')),
                'presell_start_time' => input('post.start_time'),
                'presell_end_time' => input('post.end_time'),
                'presell_shipping_time' => input('post.presell_shipping_time'),
            );
            $presell_validate = ds_validate('presell');
            if (!$presell_validate->scene('presell_add')->check($data)) {
                ds_json_encode(10001, $presell_validate->getError());
            }
            //获取提交的数据
            $goods_id = $data['goods_id'];

            $goods_model = model('goods');
            $goods_info = $goods_model->getGoodsInfoByID($goods_id);
            if (empty($goods_info) || $goods_info['store_id'] != $this->store_info['store_id']) {
                ds_json_encode(10001, lang('param_error'));
            }
            if ($data['presell_price'] >= $goods_info['goods_price']) {
                ds_json_encode(10001, lang('presell_price_error'));
            }
            $data['presell_start_time'] = strtotime($data['presell_start_time']);
            $data['presell_end_time'] = strtotime($data['presell_end_time']);


            if ($data['presell_end_time'] <= TIMESTAMP || $data['presell_end_time'] < $data['presell_start_time']) {
                ds_json_encode(10001, lang('greater_than_start_time'));
            }

            if ($data['presell_start_time'] <= TIMESTAMP) {
                $data['presell_state'] = 2;
            } else {
                $data['presell_state'] = 1;
            }

            $data['goods_id'] = $goods_info['goods_id'];
            $data['goods_name'] = $goods_info['goods_name'];
            $data['goods_commonid'] = $goods_info['goods_commonid'];

            if ($data['presell_type'] == 1) {//全款预售
                $data['presell_shipping_time'] = strtotime($data['presell_shipping_time']);
                if ($data['presell_shipping_time'] <= TIMESTAMP || $data['presell_shipping_time'] < $data['presell_end_time']) {
                    ds_json_encode(10001, lang('greater_than_end_time'));
                }
                $data['presell_deposit_amount'] = 0;
            } else {
                $data['presell_shipping_time'] = 0;
                if($data['presell_deposit_amount']>0.2*$data['presell_price']){
                    ds_json_encode(10001, lang('presell_deposit_amount_explain'));
                }
            }



            if (!$this->_check_allow_presell($data['goods_id'])) {
                ds_json_encode(10001, lang('sellerpromotionpresell_goods_not_allow'));
            }
            $data['member_id'] = $this->store_info['member_id'];
            $data['member_name'] = $this->store_info['member_name'];
            $data['store_id'] = $this->store_info['store_id'];
            $data['store_name'] = $this->store_info['store_name'];
            if (!check_platform_store()) {
                //获取当前套餐
                $presellquota_model = model('presellquota');
                $current_presell_quota = $presellquota_model->getPresellquotaCurrent($this->store_info['store_id']);
                if (empty($current_presell_quota)) {
                    if (intval(config('ds_config.promotion_presell_price')) != 0) {
                        ds_json_encode(10001, lang('please_buy_package_first'));
                    } else {
                        $current_presell_quota = array('presellquota_starttime' => TIMESTAMP, 'presellquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                    }
                }
                $quota_start_time = intval($current_presell_quota['presellquota_starttime']);
                $quota_end_time = intval($current_presell_quota['presellquota_endtime']);
                if ($data['presell_start_time'] < $quota_start_time) {
                    ds_json_encode(10001, sprintf(lang('presell_add_start_time_explain'), date('Y-m-d', $current_presell_quota['presellquota_starttime'])));
                }
                if ($data['presell_end_time'] > $quota_end_time) {
                    ds_json_encode(10001, sprintf(lang('presell_add_end_time_explain'), date('Y-m-d', $current_presell_quota['presellquota_endtime'])));
                }
            }

            //生成活动
            $presell_model = model('presell');
            $result = $presell_model->addPresell($data);
            if ($result) {
                $this->recordSellerlog(lang('add_group_activities') . $data['goods_name'] . lang('activity_number') . $result);
                $presell_model->_dGoodsPresellCache($data['goods_id']); #清除缓存
                ds_json_encode(10000, lang('presell_add_success'));
            } else {
                ds_json_encode(10001, lang('presell_add_fail'));
            }
        }
    }

    /**
     * 编辑预售活动
     * */
    public function presell_edit() {
        $presell_id = input('param.presell_id');
        $presell_model = model('presell');
        $presell_info = $presell_model->getPresellInfoByID($presell_id, $this->store_info['store_id']);
        $btn = $presell_model->getPresellBtn($presell_info);
        if ($btn) {
            $presell_info = array_merge($presell_info, $btn);
        }
        if (!request()->isPost()) {
            if (check_platform_store()) {
                View::assign('isPlatformStore', true);
            } else {
                View::assign('isPlatformStore', false);
            }
            if (empty($presell_info) || !$presell_info['editable']) {
                $this->error(lang('param_error'));
            }

            View::assign('presell_info', $presell_info);

            //输出导航
            $this->setSellerCurMenu('Sellerpromotionpresell');
            $this->setSellerCurItem('presell_edit');
            return View::fetch($this->template_dir . 'presell_add');
        } else {
            if (empty($presell_info) || !$presell_info['editable']) {
                ds_json_encode(10001, lang('param_error'));
            }


            $data = array(
                'presell_deposit_amount' => floatval(input('post.presell_deposit_amount')),
                'presell_price' => floatval(input('post.presell_price')),
                'presell_shipping_time' => input('post.presell_shipping_time'),
            );
            $presell_validate = ds_validate('presell');
            if (!$presell_validate->scene('presell_edit')->check($data)) {
                ds_json_encode(10001, $presell_validate->getError());
            }
            //获取提交的数据
            $goods_id = $presell_info['goods_id'];

            $goods_model = model('goods');
            $goods_info = $goods_model->getGoodsInfoByID($goods_id);
            if (empty($goods_info) || $goods_info['store_id'] != $this->store_info['store_id']) {
                ds_json_encode(10001, lang('param_error'));
            }
            if ($data['presell_price'] >= $goods_info['goods_price']) {
                ds_json_encode(10001, lang('presell_price_error'));
            }
  

            if ($presell_info['presell_type'] == 1) {//全款预售
                $data['presell_shipping_time'] = strtotime($data['presell_shipping_time']);
                if ($data['presell_shipping_time'] <= TIMESTAMP || $data['presell_shipping_time'] < $presell_info['presell_end_time']) {
                    ds_json_encode(10001, lang('greater_than_end_time'));
                }
            }else{
                if($data['presell_deposit_amount']>0.2*$data['presell_price']){
                    ds_json_encode(10001, lang('presell_deposit_amount_explain'));
                }
            }


 

            $result = $presell_model->editPresell($data, array('presell_id' => $presell_id));
            if ($result) {
                $this->recordSellerlog(lang('edit_group_activities') . $presell_info['goods_name'] . lang('activity_number') . $presell_id);
                $presell_model->_dGoodsPresellCache($goods_id); #清除缓存
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_op_fail'));
            }
        }
    }

    /**
     * 预售活动 取消
     */
    public function presell_end() {
        $presell_id = intval(input('post.presell_id'));
        $presell_model = model('presell');

        $presell_info = $presell_model->getPresellInfoByID($presell_id, $this->store_info['store_id']);
        if (!$presell_info) {
            ds_json_encode(10001, lang('param_error'));
        }
        $btn = $presell_model->getPresellBtn($presell_info);
        if (!$btn['editable']) {
            ds_json_encode(10001, lang('param_error'));
        }

        $condition = array();
        $condition[] = array('presell_id', '=', $presell_id);
        $condition[] = array('presell_state', '=', 1);
        $condition[] = array('store_id', '=', $this->store_info['store_id']);
        $result = $presell_model->cancelPresell($condition);

        if ($result) {
            $this->recordSellerlog(lang('group_activities_end_early') . $presell_info['goods_name'] . lang('activity_number') . $presell_id);
            $presell_model->_dGoodsPresellCache($presell_info['goods_id']); #清除缓存
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 预售套餐购买
     * */
    public function presell_quota_add() {
        //输出导航
        $this->setSellerCurMenu('Sellerpromotionpresell');
        $this->setSellerCurItem('presell_quota_add');
        return View::fetch($this->template_dir . 'presell_quota_add');
    }

    /**
     * 预售套餐购买保存
     * */
    public function presell_quota_add_save() {
        if (intval(config('ds_config.promotion_presell_price')) == 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $presell_quota_quantity = intval(input('post.presell_quota_quantity'));
        if ($presell_quota_quantity <= 0 || $presell_quota_quantity > 12) {
            ds_json_encode(10001, lang('presell_quota_quantity_error'));
        }
        //获取当前价格
        $current_price = intval(config('ds_config.promotion_presell_price'));
        //获取该用户已有套餐
        $presellquota_model = model('presellquota');
        $current_presell_quota = $presellquota_model->getPresellquotaCurrent($this->store_info['store_id']);
        $presell_add_time = 86400 * 30 * $presell_quota_quantity;
        if (empty($current_presell_quota)) {
            //生成套餐
            $param = array();
            $param['member_id'] = session('member_id');
            $param['member_name'] = session('member_name');
            $param['store_id'] = $this->store_info['store_id'];
            $param['store_name'] = session('store_name');
            $param['presellquota_starttime'] = TIMESTAMP;
            $param['presellquota_endtime'] = TIMESTAMP + $presell_add_time;
            $presellquota_model->addPresellquota($param);
        } else {
            $param = array();
            $param['presellquota_endtime'] = Db::raw('presellquota_endtime+' . $presell_add_time);
            $presellquota_model->editPresellquota($param, array('presellquota_id' => $current_presell_quota['presellquota_id']));
        }

        //记录店铺费用
        $this->recordStorecost($current_price * $presell_quota_quantity, lang('buy_spell_group').' ['.$presell_quota_quantity.'个月 × 单价:'.$current_price.'元]');

        $this->recordSellerlog(lang('buy') . $presell_quota_quantity . lang('combo_pack') . $current_price . lang('ds_yuan'));

        ds_json_encode(10000, lang('presell_quota_add_success'));
    }

    /**
     * 选择活动商品
     * */
    public function search_goods() {
        $goods_model = model('goods');
        $condition = array();
        $condition[] = array('goods.store_id', '=', $this->store_info['store_id']);
        $condition[] = array('goods.goods_name', 'like', '%' . input('param.goods_name') . '%');
        $goods_list = $goods_model->getGoodsListForPromotion($condition, 'goods.goods_id,goods.goods_commonid,goods.goods_name,goods.goods_image,goods.goods_price', 8, 'presell');
        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'search_goods');
        exit;
    }

    public function presell_goods_info() {
        $goods_id = intval(input('param.goods_id'));

        $data = array();
        $data['result'] = true;



        //获取商品具体信息用于显示
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsOnlineInfoByID($goods_id);

        if (empty($goods_info)) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }


        $data['goods_id'] = $goods_info['goods_id'];
        $data['goods_name'] = $goods_info['goods_name'];
        $data['goods_price'] = $goods_info['goods_price'];
        $data['goods_image'] = goods_thumb($goods_info, 240);
        $data['goods_href'] = (string) url('Goods/index', array('goods_id' => $goods_info['goods_id']));

        echo json_encode($data);
        die;
    }

    /*
     * 判断此商品是否已经参加预售
     */

    private function _check_allow_presell($goods_id, $presell_id = 0) {
        $condition = array();
        $condition[] = array('goods_id', '=', $goods_id);
        $condition[] = array('presell_state', '=', 1);
        if ($presell_id) {
            $condition[] = array('presell_id', '<>', $presell_id);
        }
        $presell = model('presell')->getPresellInfo($condition);
        if ($presell) {
            return false;
        } else {
            return true;
        }
    }

    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'presell_list', 'text' => lang('presell_active_list'),
                'url' => (string) url('Sellerpromotionpresell/index')
            ),
        );
        switch (request()->action()) {
            case 'presell_add':
                $menu_array[] = array(
                    'name' => 'presell_add', 'text' => lang('presell_add'),
                    'url' => (string) url('Sellerpromotionpresell/presell_add')
                );
                break;
            case 'presell_edit':
                $menu_array[] = array(
                    'name' => 'presell_edit', 'text' => lang('presell_edit'), 'url' => 'javascript:;'
                );
                break;
            case 'presell_quota_add':
                $menu_array[] = array(
                    'name' => 'presell_quota_add', 'text' => lang('presell_quota_add'),
                    'url' => (string) url('Sellerpromotionpresell/presell_quota_add')
                );
                break;
        }
        return $menu_array;
    }

}
