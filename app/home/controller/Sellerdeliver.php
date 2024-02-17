<?php

/**
 * 发货
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
class Sellerdeliver extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerdeliver.lang.php');
    }

    /**
     * 发货列表
     *
     */
    public function index() {
        $order_model = model('order');
        $state = input('state');
        if (!in_array($state, array('deliverno', 'delivering', 'delivered'))) {
            $state = 'deliverno';
        }

        $order_state = str_replace(array('deliverno', 'delivering', 'delivered'), array(ORDER_STATE_PAY, ORDER_STATE_SEND, ORDER_STATE_SUCCESS), $state);
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('order_state', '=', $order_state);


        $buyer_name = input('buyer_name');
        if ($buyer_name != '') {
            $condition[] = array('buyer_name', '=', $buyer_name);
        }
        $order_sn = input('order_sn');
        if ($order_sn != '') {
            $condition[] = array('order_sn', '=', $order_sn);
        }
        $query_start_date = input('query_start_date');
        $query_end_date = input('query_end_date');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_date);
        $start_unixtime = $if_start_date ? strtotime($query_start_date) : null;
        $end_unixtime = $if_end_date ? (strtotime($query_end_date) + 86399) : null;
        if ($start_unixtime) {
            $condition[] = array('add_time', '>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('add_time', '<=', $end_unixtime);
        }
        $order_list = $order_model->getOrderList($condition, '15', '*', 'order_id desc', 0, array('order_goods', 'order_common', 'ppintuanorder', 'member'));

        foreach ($order_list as $key => $order_info) {
            if (isset($order_info['extend_order_goods'])) {
                foreach ($order_info['extend_order_goods'] as $value) {
                    $value['image_240_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
                    $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
                    $value['goods_url'] = (string) url('Goods/index', ['goods_id' => $value['goods_id']]);
                    if ($value['goods_type'] == 5) {
                        $order_info['zengpin_list'][] = $value;
                    } else {
                        $order_info['goods_list'][] = $value;
                    }
                }

                if (empty($order_info['zengpin_list'])) {
                    $order_info['goods_count'] = count($order_info['goods_list']);
                } else {
                    $order_info['goods_count'] = count($order_info['goods_list']) + 1;
                }
            }
            $order_list[$key] = $order_info;
        }
        View::assign('order_list', $order_list);
        View::assign('show_page', $order_model->page_info->render());
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerdeliver');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem(input('param.state'));
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 批量发货
     */
    public function batch_send() {
        $order_id = ds_delete_param(input('param.order_id'));
        $order_model = model('order');
        $daddress_model = model('daddress');
        $condition = array();
        $condition[] = array('order_id', 'in', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('lock_state', '=', 0);
        $condition[] = array('order_state', 'in', array(ORDER_STATE_PAY));
        $order_list = $order_model->getOrderList($condition, '', '*', 'order_id desc', 0, array('order_common'));
        if (request()->isPost()) {
            if (empty($order_list)) {
                ds_json_encode(10001, lang('order_send_message'));
            }
            $send = input('param.send/a');
            $daddress_id = input('param.daddress_id');
            $express_id = input('param.express_id');
            $shipping_type = input('param.shipping_type');
            $logic_order = model('order', 'logic');
            if ($shipping_type == 3) {
                $express_id = 0;
            }
            $express_model = model('express');
            $expresscf_kdn_config_model = model('expresscf_kdn_config');
            $daddress_list = array();
            $express_list = array();
            $expresscf_kdn_config_list = array();
            foreach ($order_list as $order_info) {
                if (empty($send[$order_info['order_id']])) {
                    ds_json_encode(10001, lang('param_error'));
                }
                if (!$daddress_id) {
                    ds_json_encode(10001, lang('store_order_order_sn') . $order_info['order_sn'] . ':' . lang('store_deliver_confirm_daddress'));
                }
                if ($shipping_type != 3 && !$express_id) {
                    ds_json_encode(10001, lang('store_order_order_sn') . $order_info['order_sn'] . ':' . lang('store_deliver_express_select'));
                }
                if ($shipping_type == 2 && !$send[$order_info['order_id']]['shipping_code']) {
                    ds_json_encode(10001, lang('store_order_order_sn') . $order_info['order_sn'] . ':' . lang('store_deliver_shipping_code_pl'));
                }

                if ($shipping_type == 1) {
                    if (!isset($express_list[$express_id])) {
                        $express_list[$express_id] = $express_model->getExpressInfo($express_id);
                    }
                    $express_info = $express_list[$express_id];
                    if (!$express_info) {
                        ds_json_encode(10001, '快递公司不存在');
                    }

                    if (!isset($expresscf_kdn_config_list[$express_info['express_code']])) {
                        $condition = array();
                        $condition[] = array('express_code', '=', $express_info['express_code']);
                        $condition[] = array('store_id', '=', $this->store_info['store_id']);
                        $expresscf_kdn_config_list[$express_info['express_code']] = $expresscf_kdn_config_model->getExpresscfKdnConfigInfo($condition);
                    }

                    $expresscf_kdn_config_info = $expresscf_kdn_config_list[$express_info['express_code']];
                    if (!$expresscf_kdn_config_info) {
                        ds_json_encode(10001, '电子面单不存在');
                    }
                    $area_array1 = preg_split("/\s+/", $order_info['extend_order_common']['reciver_info']['area']);
                    if (count($area_array1) < 3) {
                        ds_json_encode(10001, '收货地区必须选到3级');
                    }
                    if (!isset($daddress_list[$daddress_id])) {
                        $condition = array();
                        $condition[] = array('store_id', '=', $this->store_info['store_id']);
                        $condition[] = array('daddress_id', '=', $daddress_id);
                        $daddress_list[$daddress_id] = $daddress_model->getAddressInfo($condition);
                    }
                    $daddress_info = $daddress_list[$daddress_id];
                    if (empty($daddress_info)) {
                        ds_json_encode(10001, '发货地址必填');
                    }
                    $area_array2 = preg_split("/\s+/", $daddress_info['area_info']);
                    if (count($area_array2) < 3) {
                        ds_json_encode(10001, '发货地区必须选到3级');
                    }
                    if(in_array($area_array1[0],['北京市','天津市','上海市','重庆市'])){
                        $area_array1[1]=$area_array1[0];
                        $area_array1[0]=str_replace('市','',$area_array1[0]);
                    }
                    if(in_array($area_array2[0],['北京市','天津市','上海市','重庆市'])){
                        $area_array2[1]=$area_array2[0];
                        $area_array2[0]=str_replace('市','',$area_array2[0]);
                    }
                    $goods_count = Db::name('ordergoods')->where(array(array('order_id', '=', $order_info['order_id'])))->count();
                    $requestData = array(
                        'MemberID' => (String)$this->store_info['store_id'],
                        'CustomerName' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_name'],
                        'CustomerPwd' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_pwd'],
                        'SendSite' => $expresscf_kdn_config_info['expresscf_kdn_config_send_site'],
                        'SendStaff' => $expresscf_kdn_config_info['expresscf_kdn_config_send_staff'],
                        'MonthCode' => $expresscf_kdn_config_info['expresscf_kdn_config_month_code'],
                        'ShipperCode' => $express_info['express_code'],
                        'OrderCode' => $order_info['order_sn'],
                        'PayType' => $expresscf_kdn_config_info['expresscf_kdn_config_pay_type'],
                        'ExpType' => '1',
                        'Receiver' => array(
                            'Name' => $order_info['extend_order_common']['reciver_name'],
                            'Tel' => $order_info['extend_order_common']['reciver_info']['tel_phone'],
                            'Mobile' => $order_info['extend_order_common']['reciver_info']['mob_phone'],
                            'ProvinceName' => $area_array1[0],
                            'CityName' => $area_array1[1],
                            'ExpAreaName' => $area_array1[2],
                            'Address' => $order_info['extend_order_common']['reciver_info']['street'],
                        ),
                        'Sender' => array(
                            'Name' => $daddress_info['seller_name'],
                            'Mobile' => $daddress_info['daddress_telphone'],
                            'ProvinceName' => $area_array2[0],
                            'CityName' => $area_array2[1],
                            'ExpAreaName' => $area_array2[2],
                            'Address' => $daddress_info['daddress_detail'],
                        ),
                        'Quantity' => 1,
                        'Commodity' => array(array(
                            'GoodsName' => Db::name('ordergoods')->where(array(array('order_id', '=', $order_info['order_id'])))->value('goods_name') . ($goods_count > 1 ? '等' : '')
                        ))
                    );
                    $result = $expresscf_kdn_config_model->requestExpresscfKdnApi($requestData, '1007', $this->store_info['expresscf_kdn_id'], $this->store_info['expresscf_kdn_key']);
                    if ($result['Success'] != true) {
                        ds_json_encode(10001, $result['Reason']);
                    }
                    $send[$order_info['order_id']]['shipping_code'] = $result['Order']['LogisticCode'];
                }
                $result = $logic_order->changeOrderSend($order_info, 'seller', session('seller_name'), array_merge($send[$order_info['order_id']], array(
                    'reciver_info' => serialize($order_info['extend_order_common']['reciver_info']),
                    'daddress_id' => $daddress_id,
                    'shipping_express_id' => $express_id,
                    'reciver_name' => $order_info['extend_order_common']['reciver_name'],
                    'reciver_area' => $order_info['extend_order_common']['reciver_info']['area'],
                    'reciver_street' => $order_info['extend_order_common']['reciver_info']['street'],
                    'reciver_mob_phone' => $order_info['extend_order_common']['reciver_info']['mob_phone'],
                    'reciver_tel_phone' => $order_info['extend_order_common']['reciver_info']['tel_phone'],
                    'deliver_explain' => $order_info['extend_order_common']['deliver_explain'],
                )));
                if (!$result['code']) {
                    if ($shipping_type == 1) {
                        $requestData = array(
                            'ShipperCode' => $express_info['express_code'],
                            'OrderCode' => $order_info['order_sn'],
                            'ExpNo' => $send[$order_info['order_id']]['shipping_code'],
                            'CustomerName' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_name'],
                            'CustomerPwd' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_pwd'],
                        );
                        $result = $expresscf_kdn_config_model->requestExpresscfKdnApi($requestData, '1147', $this->store_info['expresscf_kdn_id'], $this->store_info['expresscf_kdn_key']);
                        if ($result['Success'] != true) {
                            ds_json_encode(10001, $result['Reason']);
                        }
                    }
                    ds_json_encode(10001, $result['msg']);
                }
            }

            ds_json_encode(10000, $result['msg']);
        } else {
            if (!empty($order_list)) {
                $daddress_list = $daddress_model->getAddressList(array('store_id' => session('store_id')), '*', 'daddress_isdefault desc');
                View::assign('daddress_list', $daddress_list);
                $express_list = rkcache('express', true);
                //快递公司
                $my_express_list = ds_getvalue_byname('storeextend', 'store_id', session('store_id'), 'express');
                if (!empty($my_express_list)) {
                    $my_express_list = explode(',', $my_express_list);
                    foreach ($express_list as $k => $v) {
                        if (!in_array($v['express_id'], $my_express_list))
                            unset($express_list[$k]);
                    }
                }else {
                    $express_list = array();
                }
                View::assign('my_express_list', array_values($express_list));
            }
            View::assign('order_list', $order_list);
            View::assign('expresscf_kdn_if_open', $this->store_info['expresscf_kdn_if_open']);
            return View::fetch($this->template_dir . 'batch_send');
        }
    }

    /**
     * 发货
     */
    public function send() {
        $order_id = input('param.order_id');
        if ($order_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common', 'order_goods'));
        $if_allow_send = intval($order_info['lock_state']) || !in_array($order_info['order_state'], array(ORDER_STATE_PAY, ORDER_STATE_SEND));
        if ($if_allow_send) {
            ds_json_encode(10001, lang('param_error'));
        }
        //取发货地址
        $daddress_model = model('daddress');
        $daddress_info = array();
        if ($order_info['extend_order_common']['daddress_id'] > 0) {
            $condition=array();
            $condition[]=array('store_id', '=', session('store_id'));
            $condition[]=array('daddress_id', '=', $order_info['extend_order_common']['daddress_id']);
            $daddress_info = $daddress_model->getAddressInfo($condition);
        }
        if (empty($daddress_info)) {
            //取默认地址
            $condition=array();
            $condition[]=array('store_id', '=', session('store_id'));
            $condition[]=array('daddress_isdefault', '=', 1);
            $daddress_info = $daddress_model->getAddressInfo($condition);
        }
        if (!request()->isPost()) {
            View::assign('expresscf_kdn_if_open', $this->store_info['expresscf_kdn_if_open']);
            View::assign('order_info', $order_info);

            if (!empty($daddress_info)) {
                //写入发货地址编号
                $this->_edit_order_daddress($daddress_info['daddress_id'], $order_id);
            } else {
                //写入发货地址编号
                $this->_edit_order_daddress(0, $order_id);
            }
            View::assign('daddress_info', $daddress_info);

            $express_list = rkcache('express', true);

            //快递公司
            $my_express_list = ds_getvalue_byname('storeextend', 'store_id', session('store_id'), 'express');
            if (!empty($my_express_list)) {
                $my_express_list = explode(',', $my_express_list);
            }

            View::assign('my_express_list', $my_express_list);
            View::assign('express_list', $express_list);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliver');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('');
            return View::fetch($this->template_dir . 'send');
        } else {
            $logic_order = model('order', 'logic');
            $post = input('post.');
            $post['reciver_info'] = $this->_get_reciver_info();
            $express_model = model('express');
            $expresscf_kdn_config_model = model('expresscf_kdn_config');
            if (input('param.shipping_type') == 'eorder') {
                if ($post['shipping_express_id'] == 0) {
                    ds_json_encode(10001, '快递公司必填');
                }
                $express_info = $express_model->getExpressInfo(intval($post['shipping_express_id']));
                if (!$express_info) {
                    ds_json_encode(10001, '快递公司不存在');
                }
                $condition = array();
                $condition[] = array('express_code', '=', $express_info['express_code']);
                $condition[] = array('store_id', '=', $this->store_info['store_id']);
                $expresscf_kdn_config_info = $expresscf_kdn_config_model->getExpresscfKdnConfigInfo($condition);
                if (!$expresscf_kdn_config_info) {
                    ds_json_encode(10001, '电子面单不存在');
                }
                $area_array1 = preg_split("/\s+/", input('post.reciver_area'));
                if (count($area_array1) < 3) {
                    ds_json_encode(10001, '收货地区必须选到3级');
                }
                if (empty($daddress_info)) {
                    ds_json_encode(10001, '发货地址必填');
                }
                $area_array2 = preg_split("/\s+/", $daddress_info['area_info']);
                if (count($area_array2) < 3) {
                    ds_json_encode(10001, '发货地区必须选到3级');
                }
                if(in_array($area_array1[0],['北京市','天津市','上海市','重庆市'])){
                    $area_array1[1]=$area_array1[0];
                    $area_array1[0]=str_replace('市','',$area_array1[0]);
                }
                if(in_array($area_array2[0],['北京市','天津市','上海市','重庆市'])){
                    $area_array2[1]=$area_array2[0];
                    $area_array2[0]=str_replace('市','',$area_array2[0]);
                }
                $goods_count = Db::name('ordergoods')->where(array(array('order_id', '=', $order_info['order_id'])))->count();
                $requestData = array(
                    'MemberID' => (String)$this->store_info['store_id'],
                    'CustomerName' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_name'],
                    'CustomerPwd' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_pwd'],
                    'SendSite' => $expresscf_kdn_config_info['expresscf_kdn_config_send_site'],
                    'SendStaff' => $expresscf_kdn_config_info['expresscf_kdn_config_send_staff'],
                    'MonthCode' => $expresscf_kdn_config_info['expresscf_kdn_config_month_code'],
                    'ShipperCode' => $express_info['express_code'],
                    'OrderCode' => $order_info['order_sn'],
                    'PayType' => $expresscf_kdn_config_info['expresscf_kdn_config_pay_type'],
                    'ExpType' => '1',
                    'Receiver' => array(
                        'Name' => input('post.reciver_name'),
                        'Tel' => input('post.reciver_tel_phone'),
                        'Mobile' => input('post.reciver_mob_phone'),
                        'ProvinceName' => $area_array1[0],
                        'CityName' => $area_array1[1],
                        'ExpAreaName' => $area_array1[2],
                        'Address' => input('post.reciver_street'),
                    ),
                    'Sender' => array(
                        'Name' => $daddress_info['seller_name'],
                        'Mobile' => $daddress_info['daddress_telphone'],
                        'ProvinceName' => $area_array2[0],
                        'CityName' => $area_array2[1],
                        'ExpAreaName' => $area_array2[2],
                        'Address' => $daddress_info['daddress_detail'],
                    ),
                    'Quantity' => 1,
                    'Commodity' => array(array(
                        'GoodsName' => Db::name('ordergoods')->where(array(array('order_id', '=', $order_info['order_id'])))->value('goods_name') . ($goods_count > 1 ? '等' : '')
                    ))
                );
                $result = $expresscf_kdn_config_model->requestExpresscfKdnApi($requestData, '1007', $this->store_info['expresscf_kdn_id'], $this->store_info['expresscf_kdn_key']);
                if ($result['Success'] != true) {
                    ds_json_encode(10001, $result['Reason']);
                }
                $post['shipping_code'] = $result['Order']['LogisticCode'];
            }
            $result = $logic_order->changeOrderSend($order_info, 'seller', session('seller_name'), $post);
            if (!$result['code']) {
                if (input('param.shipping_type') == 'eorder') {
                    $requestData = array(
                        'ShipperCode' => $express_info['express_code'],
                        'OrderCode' => $order_info['order_sn'],
                        'ExpNo' => $post['shipping_code'],
                        'CustomerName' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_name'],
                        'CustomerPwd' => $expresscf_kdn_config_info['expresscf_kdn_config_customer_pwd'],
                    );
                    $result = $expresscf_kdn_config_model->requestExpresscfKdnApi($requestData, '1147', $this->store_info['expresscf_kdn_id'], $this->store_info['expresscf_kdn_key']);
                    if ($result['Success'] != true) {
                        ds_json_encode(10001, $result['Reason']);
                    }
                }
                ds_json_encode(10001, $result['msg']);
            } else {
                ds_json_encode(10000, $result['msg']);
            }
        }
    }

    /**
     * 编辑收货地址
     * @return boolean
     */
    public function buyer_address_edit() {
        $order_id = input('param.order_id');
        if ($order_id <= 0) {
            return false;
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_common_info = $order_model->getOrdercommonInfo($condition);
        if (!$order_common_info) {
            return false;
        }
        $order_common_info['reciver_info'] = @unserialize($order_common_info['reciver_info']);
        View::assign('address_info', $order_common_info);
        return View::fetch($this->template_dir . 'buyer_address_edit');
    }

    /**
     * 收货地址保存
     */
    public function buyer_address_save() {
        $order_model = model('order');
        $data = array();
        $data['reciver_name'] = input('post.reciver_name');
        $data['reciver_info'] = $this->_get_reciver_info();
        $condition = array();
        $condition[] = array('order_id', '=', intval(input('post.order_id')));
        $condition[] = array('store_id', '=', session('store_id'));
        $result = $order_model->editOrdercommon($data, $condition);
        if ($result) {
            echo 'true';
        } else {
            echo 'flase';
        }
    }

    /**
     * 组合reciver_info
     */
    private function _get_reciver_info() {
        $reciver_info = array(
            'address' => input('post.reciver_area') . ' ' . input('post.reciver_street'),
            'phone' => trim(input('post.reciver_mob_phone') . ',' . input('post.reciver_tel_phone'), ','),
            'area' => input('post.reciver_area'),
            'street' => input('post.reciver_street'),
            'mob_phone' => input('post.reciver_mob_phone'),
            'tel_phone' => input('post.reciver_tel_phone'),
            'chain' => input('post.reciver_chain'),
        );
        return serialize($reciver_info);
    }

    /**
     * 选择发货地址
     * @return boolean
     */
    public function send_address_select() {
        $address_list = model('daddress')->getAddressList(array('store_id' => session('store_id')));
        View::assign('address_list', $address_list);
        View::assign('order_id', input('param.order_id'));
        return View::fetch($this->template_dir . 'send_address_select');
    }

    /**
     * 保存发货地址修改
     */
    public function send_address_save() {
        $result = $this->_edit_order_daddress(input('post.daddress_id'), input('post.order_id'));
        if ($result >= 0) {
            echo 'true';
        } else {
            echo 'flase';
        }
    }

    /**
     * 修改发货地址
     */
    private function _edit_order_daddress($daddress_id, $order_id) {
        $order_model = model('order');
        $data = array();
        $data['daddress_id'] = intval($daddress_id);
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        return $order_model->editOrdercommon($data, $condition);
    }

    /**
     * 物流跟踪
     */
    public function search_deliver() {
        $order_sn = input('param.order_sn');
        if (!is_numeric($order_sn)) {
            $this->error(lang('param_error'));
        }

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_sn', '=', $order_sn);
        $condition[] = array('store_id', '=', session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common', 'order_goods'));
        if (empty($order_info) || $order_info['shipping_code'] == '') {
            $this->error(lang('no_information_found'));
        }
        $order_info['state_info'] = get_order_state($order_info);
        View::assign('order_info', $order_info);
        //卖家发货信息
        $daddress_info = model('daddress')->getAddressInfo(array('daddress_id' => $order_info['extend_order_common']['daddress_id']));
        View::assign('daddress_info', $daddress_info);

        //取得配送公司代码
        $express = rkcache('express', true);
        View::assign('express_code', isset($express[$order_info['extend_order_common']['shipping_express_id']]) ? $express[$order_info['extend_order_common']['shipping_express_id']]['express_code'] : '');
        View::assign('express_name', isset($express[$order_info['extend_order_common']['shipping_express_id']]) ? $express[$order_info['extend_order_common']['shipping_express_id']]['express_name'] : '');
        View::assign('express_url', isset($express[$order_info['extend_order_common']['shipping_express_id']]) ? $express[$order_info['extend_order_common']['shipping_express_id']]['express_url'] : '');
        View::assign('shipping_code', $order_info['shipping_code']);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerdeliver');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('');
        return View::fetch($this->template_dir . 'search_deliver');
    }

    /**
     * 延迟收货
     */
    public function delay_receive() {
        $order_id = input('param.order_id');
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('lock_state', '=', 0);
        $order_info = $order_model->getOrderInfo($condition);

        //取目前系统最晚收货时间
        $delay_time = $order_info['delay_time'] + config('ds_config.order_auto_receive_day') * 3600 * 24;
        if (request()->isPost()) {
            $delay_date = intval(input('post.delay_date'));
            if (!in_array($delay_date, array(5, 10, 15))) {
                ds_json_encode(10001, lang('param_error'));
            }
            $update = $order_model->editOrder(array('delay_time' => Db::raw('delay_time+' . $delay_date * 3600 * 24)), $condition);
            if ($update) {
                //新的最晚收货时间
                $dalay_date = date('Y-m-d H:i:s', $delay_time + $delay_date * 3600 * 24);
                ds_json_encode(10000, lang('successful_delivery_deadline') . $dalay_date . '&emsp;');
            } else {
                ds_json_encode(10000, lang('delayed_failure'));
            }
        } else {
            $order_info['delay_time'] = $delay_time;
            View::assign('order_info', $order_info);
            return View::fetch($this->template_dir . 'delay_receive');
        }
    }

    /**
     * 从第三方取快递信息
     *
     */
    public function get_express() {
        $result = model('express')->queryExpress(input('param.express_code'), input('param.shipping_code'), input('param.phone'));

        if ($result['Success'] != true) {
            exit(json_encode(false));
        }
        $content['Traces'] = array_reverse($result['Traces']);
        $output = array();
        if (is_array($content['Traces'])) {
            foreach ($content['Traces'] as $k => $v) {
                if ($v['AcceptTime'] == '') {
                    continue;
                }
                $output[] = '<li>' . $v['AcceptTime'] . '&nbsp;&nbsp;' . $v['AcceptStation'] . '</li>';
            }
        }
        if ($output == '')
            exit(json_encode(false));
        echo json_encode($output);
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$name	当前导航的name
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array();
        $menu_type = request()->action();
        switch ($menu_type) {
            case 'index':
                $menu_array = array(
                    array('name' => 'deliverno', 'text' => lang('ds_member_path_deliverno'), 'url' => (string) url('Sellerdeliver/index', ['state' => 'deliverno'])),
                    array('name' => 'delivering', 'text' => lang('ds_member_path_delivering'), 'url' => (string) url('Sellerdeliver/index', ['state' => 'delivering'])),
                    array('name' => 'delivered', 'text' => lang('ds_member_path_delivered'), 'url' => (string) url('Sellerdeliver/index', ['state' => 'delivered'])),
                );
                break;
            case 'search':
                $menu_array = array(
                    array('name' => 'nodeliver', 'text' => lang('ds_member_path_deliverno'), 'url' => (string) url('Sellerdeliver/index/state/nodeliver')),
                    array('name' => 'delivering', 'text' => lang('ds_member_path_delivering'), 'url' => (string) url('Sellerdeliver/index/state/delivering')),
                    array('name' => 'delivered', 'text' => lang('ds_member_path_delivered'), 'url' => (string) url('Sellerdeliver/index/state/delivered')),
                    array('name' => 'search', 'text' => lang('ds_member_path_deliver_info'), 'url' => '###'),
                );
                break;
        }
        return $menu_array;
    }

}
