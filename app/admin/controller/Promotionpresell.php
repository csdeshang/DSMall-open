<?php

/**
 * 预售管理
 */

namespace app\admin\controller;

use think\facade\View;
use think\facade\Db;
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
class Promotionpresell extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/promotionpresell.lang.php');
    }

    /**
     * 预售列表
     */
    public function index() {
        $presell_model = model('presell');
        $condition = array();
        if (!empty(input('param.goods_name'))) {
            $condition[] = array('goods_name', 'like', '%' . input('param.goods_name') . '%');
        }
        if (!empty(input('param.store_name'))) {
            $condition[] = array('store_name', 'like', '%' . input('param.store_name') . '%');
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

        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件

        $this->setAdminCurItem('presell_list');
        return View::fetch();
    }

    /**
     * 预售活动 取消
     */
    public function presell_end() {
        $presell_id = intval(input('param.presell_id'));
        $presell_model = model('presell');

        $presell_info = $presell_model->getPresellInfoByID($presell_id);
        if (!$presell_info) {
            ds_json_encode(10001, lang('param_error'));
        }
        if (!in_array($presell_info['presell_state'], array(1, 2))) {//只有未开始、进行中的活动可以取消
            ds_json_encode(10001, lang('presell_cant_cancel'));
        }
        try {
            Db::startTrans();
            //取消用户发起的活动
            $condition = array();
            $condition[] = array('goods_type', '=', 10);
            $condition[] = array('promotions_id', '=', $presell_id);
            $order_ids = Db::name('ordergoods')->where($condition)->column('order_id');
            if (!empty($order_ids)) {
                $order_model = model('order');
                $logic_order = model('order', 'logic');
                $condition = array();
                $condition[] = array('order_id', 'in', $order_ids);
                $condition[] = array('order_state', 'in', [ORDER_STATE_NEW,ORDER_STATE_DEPOSIT, ORDER_STATE_REST,ORDER_STATE_PAY]);
                $order_list = $order_model->getOrderList($condition);
                if (!empty($order_list)) {
                    foreach ($order_list as $order_info) {
                        $logic_order->changeOrderStateCancel($order_info, 'admin', $this->admin_info['admin_name'], '管理员取消预售活动');
                    }
                }
            }
            if (!$presell_model->cancelPresell(array('presell_id' => $presell_id))) {
                throw new \think\Exception(lang('presell_edit_fail'), 10006);
            }
        } catch (\Exception $e) {
            Db::rollback();
            ds_json_encode(10001, $e->getMessage());
        }
        Db::commit();

        $this->log('预售活动取消，商品名称：' . $presell_info['goods_name'] . '活动编号：' . $presell_id, 1);
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }
    /**
     * 拼团活动 删除
     */
    public function presell_del() {
        $presell_id = intval(input('param.presell_id'));
        $presell_model = model('presell');
        $presell_info = $presell_model->getPresellInfoByID($presell_id);
        if (!$presell_info) {
            ds_json_encode(10001, lang('param_error'));
        }
        /**
         * 指定拼团活动删除
         */
        $result = $presell_model->delPresell(array('presell_id' => $presell_id));

        if ($result) {
            $this->log('预售活动删除，商品名称：' . $presell_info['goods_name'] . '活动编号：' . $presell_id, 1);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }
    /**
     * 预售套餐管理
     */
    public function presell_quota() {
        $presellquota_model = model('presellquota');

        $condition = array();
        $condition[] = array('store_name', 'like', '%' . input('param.store_name') . '%');
        $presellquota_list = $presellquota_model->getPresellquotaList($condition, 10, 'presellquota_endtime desc');
        View::assign('presellquota_list', $presellquota_list);
        View::assign('show_page', $presellquota_model->page_info->render());

        $this->setAdminCurItem('presell_quota');
        return View::fetch();
    }

    /**
     * 预售设置
     */
    public function presell_setting() {
        if (!(request()->isPost())) {
            $setting = rkcache('config', true);
            View::assign('setting', $setting);
            return View::fetch();
        } else {
            $promotion_presell_price = intval(input('post.promotion_presell_price'));
            if ($promotion_presell_price < 0) {
                $this->error(lang('param_error'));
            }

            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_presell_price'] = $promotion_presell_price;

            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log('修改预售套餐价格为' . $promotion_presell_price . '元');
                dsLayerOpenSuccess(lang('setting_save_success'));
            } else {
                $this->error(lang('setting_save_fail'));
            }
        }
    }

    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'presell_list', 'text' => lang('presell_list'), 'url' => (string) url('Promotionpresell/index')
            ), array(
                'name' => 'presell_quota', 'text' => lang('presell_quota'),
                'url' => (string) url('Promotionpresell/presell_quota')
            ), array(
                'name' => 'presell_setting',
                'text' => lang('presell_setting'),
                'url' => "javascript:dsLayerOpen('" . (string) url('Promotionpresell/presell_setting') . "','" . lang('presell_setting') . "')"
            ),
        );

        return $menu_array;
    }

}
