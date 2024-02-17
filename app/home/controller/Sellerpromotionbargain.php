<?php

/**
 * 卖家砍价管理
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
class Sellerpromotionbargain extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerpromotionbargain.lang.php');
        if (intval(config('ds_config.promotion_allow')) !== 1) {
            $this->error(lang('promotion_unavailable'), 'seller/index');
        }
    }

    public function index() {
        $bargainquota_model = model('pbargainquota');
        $pbargain_model = model('pbargain');

        if (check_platform_store()) {
            View::assign('isPlatformStore', true);
        } else {
            $current_bargain_quota = $bargainquota_model->getBargainquotaCurrent($this->store_info['store_id']);
            View::assign('current_bargain_quota', $current_bargain_quota);
        }

        $condition = array();
        $condition[] = array('store_id', '=', $this->store_info['store_id']);
        if ((input('param.bargain_name'))) {
            $condition[] = array('bargain_name', 'like', '%' . input('param.bargain_name') . '%');
        }
        if (input('param.state') != '' && in_array(input('param.state'), array(0, 1, 2, 3))) {
            $condition[] = array('bargain_state', '=', intval(input('param.state')));
        }
        $bargain_list = $pbargain_model->getBargainList($condition, 10, 'bargain_id desc');
        foreach ($bargain_list as $key => $val) {
            $bargain_list[$key]['bargain_state_text'] = $pbargain_model->getBargainStateText($val);
            $bargain_list[$key] = array_merge($bargain_list[$key], $pbargain_model->getBargainBtn($val));
        }

        View::assign('bargain_list', $bargain_list);
        View::assign('show_page', $pbargain_model->page_info->render());
        View::assign('bargain_state_array', $pbargain_model->getBargainStateArray());

        $this->setSellerCurMenu('Sellerpromotionbargain');
        $this->setSellerCurItem('bargain_list');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 添加砍价活动
     * */
    public function bargain_add() {
        if (!request()->isPost()) {
            if (check_platform_store()) {
                View::assign('isPlatformStore', true);
            } else {
                View::assign('isPlatformStore', false);
                $bargainquota_model = model('pbargainquota');
                $current_bargain_quota = $bargainquota_model->getBargainquotaCurrent($this->store_info['store_id']);
                if (empty($current_bargain_quota)) {
                    if (intval(config('ds_config.promotion_bargain_price')) != 0) {
                        $this->error(lang('bargain_quota_current_error1'));
                    } else {
                        $current_bargain_quota = array('bargainquota_starttime' => TIMESTAMP, 'bargainquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                    }
                }
                View::assign('current_bargain_quota', $current_bargain_quota);
            }

            //输出导航
            $this->setSellerCurMenu('Sellerpromotionbargain');
            $this->setSellerCurItem('bargain_add');
            return View::fetch($this->template_dir . 'bargain_add');
        } else {

            $data = $this->post_data();
            if (!$this->_check_allow_bargain($data['bargain_goods_id'])) {
                ds_json_encode(10001, lang('sellerpromotionbargain_goods_not_allow'));
            }
            $data['member_id'] = $this->store_info['member_id'];
            $data['member_name'] = $this->store_info['member_name'];
            $data['store_id'] = $this->store_info['store_id'];
            $data['store_name'] = $this->store_info['store_name'];
            if (!check_platform_store()) {
                //获取当前套餐
                $bargainquota_model = model('pbargainquota');
                $current_bargain_quota = $bargainquota_model->getBargainquotaCurrent($this->store_info['store_id']);
                if (empty($current_bargain_quota)) {
                    if (intval(config('ds_config.promotion_bargain_price')) != 0) {
                        ds_json_encode(10001, lang('please_buy_package_first'));
                    } else {
                        $current_bargain_quota = array('bargainquota_starttime' => TIMESTAMP, 'bargainquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                    }
                }
                $quota_start_time = intval($current_bargain_quota['bargainquota_starttime']);
                $quota_end_time = intval($current_bargain_quota['bargainquota_endtime']);
                if ($data['bargain_begintime'] < $quota_start_time) {
                    ds_json_encode(10001, sprintf(lang('bargain_add_start_time_explain'), date('Y-m-d', $current_bargain_quota['bargainquota_starttime'])));
                }
                if ($data['bargain_endtime'] > $quota_end_time) {
                    ds_json_encode(10001, sprintf(lang('bargain_add_end_time_explain'), date('Y-m-d', $current_bargain_quota['bargainquota_endtime'])));
                }
            }

            //生成活动
            $pbargain_model = model('pbargain');
            $result = $pbargain_model->addBargain($data);
            if ($result) {
                $this->recordSellerlog(lang('add_group_activities') . $data['bargain_name'] . lang('activity_number') . $result);
                $pbargain_model->_dGoodsBargainCache($data['bargain_goods_id']); #清除缓存
                ds_json_encode(10000, lang('bargain_add_success'));
            } else {
                ds_json_encode(10001, lang('bargain_add_fail'));
            }
        }
    }

    public function post_data() {
        $data = array(
            'bargain_name' => input('post.bargain_name'),
            'bargain_limit' => input('post.bargain_limit'),
            'bargain_time' => input('post.bargain_time'),
            'bargain_floorprice' => input('post.bargain_floorprice'),
            'bargain_total' => input('post.bargain_total'),
            'bargain_max' => input('post.bargain_max'),
            'bargain_remark' => input('post.bargain_remark'),
            'bargain_begintime' => input('post.start_time'),
            'bargain_endtime' => input('post.end_time'),
        );
        $pbargain_validate = ds_validate('pbargain');
        if (!$pbargain_validate->scene('pbargin_save')->check($data)) {
            ds_json_encode(10001, $pbargain_validate->getError());
        }
        //获取提交的数据
        $goods_id = intval(input('post.bargain_goods_id'));
        if (empty($goods_id)) {
            ds_json_encode(10001, lang('param_error'));
        }
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        if (empty($goods_info) || $goods_info['store_id'] != $this->store_info['store_id']) {
            ds_json_encode(10001, lang('param_error'));
        }
        $data['bargain_begintime'] = strtotime($data['bargain_begintime']);
        $data['bargain_endtime'] = strtotime($data['bargain_endtime']);

        if ($data['bargain_endtime'] <= TIMESTAMP) {
            ds_json_encode(10001, lang('sellerpromotionbargain_bargain_endtime_error'));
        }

        if ($data['bargain_begintime'] <= TIMESTAMP) {
            $data['bargain_state'] = 2;
        } else {
            $data['bargain_state'] = 1;
        }

        $data['bargain_goods_id'] = $goods_info['goods_id'];
        $data['bargain_goods_name'] = $goods_info['goods_name'];
        $data['bargain_goods_price'] = $goods_info['goods_price'];
        $data['bargain_goods_image'] = $goods_info['goods_image'];
        $data['bargain_goods_commonid'] = $goods_info['goods_commonid'];

        //砍价差价*100必须大于底价刀数（因为1刀做少0.01元）
        if (($data['bargain_goods_price'] - $data['bargain_floorprice']) * 100 < $data['bargain_total']) {
            ds_json_encode(10001, lang('sellerpromotionbargain_bargain_total_error'));
        }
        if (($data['bargain_goods_price'] - $data['bargain_floorprice']) > ($data['bargain_total'] * $data['bargain_max'])) {
            ds_json_encode(10001, lang('sellerpromotionbargain_bargain_limit_error'));
        }

        return $data;
    }

    /**
     * 编辑砍价活动
     * */
    public function bargain_edit() {
        $bargain_id = input('param.bargain_id');
        $pbargain_model = model('pbargain');
        $bargain_info = $pbargain_model->getBargainInfoByID($bargain_id, $this->store_info['store_id']);
        $btn = $pbargain_model->getBargainBtn($bargain_info);
        if ($btn) {
            $bargain_info = array_merge($bargain_info, $btn);
        }
        if (!request()->isPost()) {
            if (check_platform_store()) {
                View::assign('isPlatformStore', true);
            } else {
                View::assign('isPlatformStore', false);
            }
            if (empty($bargain_info) || !$bargain_info['editable']) {
                $this->error(lang('param_error'));
            }

            View::assign('bargain_info', $bargain_info);

            //输出导航
            $this->setSellerCurMenu('Sellerpromotionbargain');
            $this->setSellerCurItem('bargain_edit');
            return View::fetch($this->template_dir . 'bargain_add');
        } else {
            if (empty($bargain_info) || !$bargain_info['editable']) {
                ds_json_encode(10001, lang('param_error'));
            }

            $data = $this->post_data();
            if (!$this->_check_allow_bargain($data['bargain_goods_id'], $bargain_info['bargain_id'])) {
                ds_json_encode(10001, lang('sellerpromotionbargain_goods_not_allow'));
            }

            $result = $pbargain_model->editBargain($data, array('bargain_id' => $bargain_id));
            if ($result) {
                $this->recordSellerlog(lang('edit_group_activities') . $data['bargain_name'] . lang('activity_number') . $bargain_id);
                $pbargain_model->_dGoodsBargainCache($data['bargain_goods_id']); #清除缓存
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_op_fail'));
            }
        }
    }

    /**
     * 砍价活动 取消
     */
    public function bargain_end() {
        $bargain_id = intval(input('post.bargain_id'));
        $pbargain_model = model('pbargain');

        $bargain_info = $pbargain_model->getBargainInfoByID($bargain_id, $this->store_info['store_id']);
        if (!$bargain_info) {
            ds_json_encode(10001, lang('param_error'));
        }
        $btn = $pbargain_model->getBargainBtn($bargain_info);
        if (!$btn['editable']) {
            ds_json_encode(10001, lang('param_error'));
        }
        /**
         * 指定砍价活动结束
         */
        $condition = array();
        $condition[] = array('bargain_id','=',$bargain_id);
        $condition[] = array('bargain_state','=',1);
        $result = $pbargain_model->cancelBargain($condition);

        if ($result) {
            $this->recordSellerlog(lang('group_activities_end_early') . $bargain_info['bargain_name'] . lang('activity_number') . $bargain_id);
            $pbargain_model->_dGoodsBargainCache($bargain_info['bargain_goods_id']); #清除缓存
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 商品砍价订单列表
     */
    public function bargain_order() {
        $pbargain_model = model('pbargain');
        $pbargainorder_model = model('pbargainorder');
        $bargain_id = intval(input('param.bargain_id'));
        //判断此砍价是否属于店铺
        $pbargain = $pbargain_model->getBargainInfo(array('store_id' => $this->store_info['store_id'], 'bargain_id' => $bargain_id));
        if (empty($pbargain)) {
            $this->error(lang('param_error'));
        }
        $condition = array();
        $condition[] = array('bargain_id','=',$bargain_id);
        if (input('param.bargainorder_state') != '') {
            $condition[] = array('bargainorder_state','=',intval(input('param.bargainorder_state')));
        }
        $pbargainorder_list = $pbargainorder_model->getPbargainorderList($condition, 10);

        View::assign('show_page', $pbargainorder_model->page_info->render());
        View::assign('pbargainorder_list', $pbargainorder_list);
        View::assign('bargainorder_state_array', $pbargainorder_model->getBargainorderStateArray());
        $this->setSellerCurMenu('Sellerpromotionbargain');
        $this->setSellerCurItem('bargain_order');
        return View::fetch($this->template_dir . 'bargain_order');
    }

    /**
     * 商品砍价记录列表
     */
    public function bargain_log() {
        $pbargainlog_model = model('pbargainlog');
        $bargainorder_id = intval(input('param.bargainorder_id'));
        $condition = array();
        $condition[] = array('bargainorder_id','=',$bargainorder_id);
        $pbargainlog_list = $pbargainlog_model->getPbargainlogList($condition, 10); #获取砍价记录信息
        View::assign('show_page', $pbargainlog_model->page_info->render());
        View::assign('pbargainlog_list', $pbargainlog_list);
        $this->setSellerCurMenu('Sellerpromotionbargain');
        $this->setSellerCurItem('bargain_log');
        return View::fetch($this->template_dir . 'bargain_log');
    }

    /**
     * 砍价套餐购买
     * */
    public function bargain_quota_add() {
        //输出导航
        $this->setSellerCurMenu('Sellerpromotionbargain');
        $this->setSellerCurItem('bargain_quota_add');
        return View::fetch($this->template_dir . 'bargain_quota_add');
    }

    /**
     * 砍价套餐购买保存
     * */
    public function bargain_quota_add_save() {
        if (intval(config('ds_config.promotion_bargain_price')) == 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $bargain_quota_quantity = intval(input('post.bargain_quota_quantity'));
        if ($bargain_quota_quantity <= 0 || $bargain_quota_quantity > 12) {
            ds_json_encode(10001, lang('bargain_quota_quantity_error'));
        }
        //获取当前价格
        $current_price = intval(config('ds_config.promotion_bargain_price'));
        //获取该用户已有套餐
        $bargainquota_model = model('pbargainquota');
        $current_bargain_quota = $bargainquota_model->getBargainquotaCurrent($this->store_info['store_id']);
        $bargain_add_time = 86400 * 30 * $bargain_quota_quantity;
        if (empty($current_bargain_quota)) {
            //生成套餐
            $param = array();
            $param['member_id'] = session('member_id');
            $param['member_name'] = session('member_name');
            $param['store_id'] = $this->store_info['store_id'];
            $param['store_name'] = session('store_name');
            $param['bargainquota_starttime'] = TIMESTAMP;
            $param['bargainquota_endtime'] = TIMESTAMP + $bargain_add_time;
            $bargainquota_model->addBargainquota($param);
        } else {
            $param = array();
            $param['bargainquota_endtime'] = Db::raw('bargainquota_endtime+' . $bargain_add_time);
            $bargainquota_model->editBargainquota($param, array('bargainquota_id' => $current_bargain_quota['bargainquota_id']));
        }

        //记录店铺费用
        $this->recordStorecost($current_price * $bargain_quota_quantity, lang('buy_spell_group').' ['.$bargain_quota_quantity.'个月 × 单价:'.$current_price.'元]');

        $this->recordSellerlog(lang('buy') . $bargain_quota_quantity . lang('combo_pack') . $current_price . lang('ds_yuan'));

        ds_json_encode(10000, lang('bargain_quota_add_success'));
    }

    /**
     * 选择活动商品
     * */
    public function search_goods() {
        $goods_model = model('goods');
        $condition = array();
        $condition[] = array('goods.store_id', '=', $this->store_info['store_id']);
        $condition[] = array('goods.goods_name', 'like', '%' . input('param.goods_name') . '%');
        $goods_list = $goods_model->getGoodsListForPromotion($condition, 'goods.goods_id,goods.goods_commonid,goods.goods_name,goods.goods_image,goods.goods_price', 8, 'bargain');
        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'search_goods');
        exit;
    }

    public function bargain_goods_info() {
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
     * 判断此商品是否已经参加砍价
     */

    private function _check_allow_bargain($goods_id, $bargain_id = 0) {
        $condition = array();
        $condition[] = array('bargain_goods_id','=',$goods_id);
        $condition[] = array('bargain_state','=',1);
        if ($bargain_id) {
            $condition[] = array('bargain_id', '<>', $bargain_id);
        }
        $bargain = model('pbargain')->getBargainInfo($condition);
        if ($bargain) {
            return false;
        } else {
            return true;
        }
    }

    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'bargain_list', 'text' => lang('bargain_active_list'),
                'url' => (string) url('Sellerpromotionbargain/index')
            ),
        );
        switch (request()->action()) {
            case 'bargain_add':
                $menu_array[] = array(
                    'name' => 'bargain_add', 'text' => lang('bargain_add'),
                    'url' => (string) url('Sellerpromotionbargain/bargain_add')
                );
                break;
            case 'bargain_edit':
                $menu_array[] = array(
                    'name' => 'bargain_edit', 'text' => lang('bargain_edit'), 'url' => 'javascript:;'
                );
                break;
            case 'bargain_quota_add':
                $menu_array[] = array(
                    'name' => 'bargain_quota_add', 'text' => lang('bargain_quota_add'),
                    'url' => (string) url('Sellerpromotionbargain/bargain_quota_add')
                );
                break;
            case 'bargain_order':
                $menu_array[] = array(
                    'name' => 'bargain_order', 'text' => lang('bargainorder'),
                    'url' => (string) url('Sellerpromotionbargain/bargain_order', ['bargain_id' => input('param.bargain_id')])
                );
                break;
            case 'bargain_log':
                $menu_array[] = array(
                    'name' => 'bargain_log', 'text' => lang('pbargainlog'),
                    'url' => (string) url('Sellerpromotionbargain/bargain_log', ['bargainorder_id' => input('param.bargainorder_id')])
                );
                break;
        }
        return $menu_array;
    }

}
