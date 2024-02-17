<?php

/*
 * 发货设置
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
class Sellerdeliverset extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerdeliver.lang.php');
    }

    /**
     * 发货地址列表
     */
    public function index() {
        $daddress_model = model('daddress');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $address_list = $daddress_model->getAddressList($condition, '*', '', 20);
        View::assign('address_list', $address_list);
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerdeliverset');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('daddress');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 新增/编辑发货地址
     */
    public function daddress_add() {
        $address_id = intval(input('param.address_id'));
        if ($address_id > 0) {
            $daddress_mod = model('daddress');
            //编辑
            if (!request()->isPost()) {
                $address_info = $daddress_mod->getAddressInfo(array('daddress_id' => $address_id, 'store_id' => session('store_id')));
                View::assign('address_info', $address_info);
                return View::fetch($this->template_dir . 'daddress_add');
            } else {
                $data = array(
                    'seller_name' => input('post.seller_name'),
                    'area_id' => input('post.area_id'),
                    'city_id' => input('post.city_id'),
                    'area_info' => input('post.region'),
                    'daddress_detail' => input('post.address'),
                    'daddress_telphone' => input('post.telphone'),
                    'daddress_company' => input('post.company'),
                );
                //验证数据  BEGIN
                $sellerdeliverset_validate = ds_validate('sellerdeliverset');
                if (!$sellerdeliverset_validate->scene('daddress_add')->check($data)) {
                    ds_json_encode(10001, $sellerdeliverset_validate->getError());
                }
                //验证数据  END
                $result = $daddress_mod->editDaddress($data, array('daddress_id' => $address_id, 'store_id' => session('store_id')));
                if ($result) {
                    ds_json_encode(10000, lang('ds_common_op_succ'));
                } else {
                    ds_json_encode(10001, lang('store_daddress_modify_fail'));
                }
            }
        } else {
            //新增
            if (!request()->isPost()) {
                $address_info = array(
                    'daddress_id' => '', 'city_id' => '1', 'area_id' => '1', 'seller_name' => '',
                    'area_info' => '', 'daddress_detail' => '', 'daddress_telphone' => '', 'daddress_company' => '',
                );
                View::assign('address_info', $address_info);
                return View::fetch($this->template_dir . 'daddress_add');
            } else {
                $data = array(
                    'store_id' => session('store_id'),
                    'seller_name' => input('post.seller_name'),
                    'area_id' => input('post.area_id'),
                    'city_id' => input('post.city_id'),
                    'area_info' => input('post.region'),
                    'daddress_detail' => input('post.address'),
                    'daddress_telphone' => input('post.telphone'),
                    'daddress_company' => input('post.company'),
                );
                //验证数据  BEGIN
                $sellerdeliverset_validate = ds_validate('sellerdeliverset');
                if (!$sellerdeliverset_validate->scene('daddress_add')->check($data)) {
                    ds_json_encode(10001, $sellerdeliverset_validate->getError());
                }
                //验证数据  END
                $result = Db::name('daddress')->insertGetId($data);
                if ($result) {
                    ds_json_encode(10000, lang('ds_common_op_succ'));
                } else {
                    ds_json_encode(10001, lang('store_daddress_add_fail'));
                }
            }
        }
    }

    /**
     * 删除发货地址
     */
    public function daddress_del() {
        $address_id = intval(input('param.address_id'));
        if ($address_id <= 0) {
            ds_json_encode(10001, lang('store_daddress_del_fail'));
        }
        $condition = array();
        $condition[] = array('daddress_id', '=', $address_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $delete = model('daddress')->delDaddress($condition);
        if ($delete) {
            ds_json_encode(10000, lang('store_daddress_del_succ'));
        } else {
            ds_json_encode(10001, lang('store_daddress_del_fail'));
        }
    }

    /**
     * 设置默认发货地址
     */
    public function daddress_default_set() {
        $address_id = intval(input('get.address_id'));
        if ($address_id <= 0)
            return false;
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $update = model('daddress')->editDaddress(array('daddress_isdefault' => 0), $condition);
        $condition[] = array('daddress_id', '=', $address_id);
        $update = model('daddress')->editDaddress(array('daddress_isdefault' => 1), $condition);
    }

    public function express() {
        $storeextend_model = model('storeextend');
        if (!request()->isPost()) {
            $express_list = rkcache('express', true);

            //取得店铺启用的快递公司ID
            $express_select = ds_getvalue_byname('storeextend', 'store_id', session('store_id'), 'express');

            if (!is_null($express_select)) {
                $express_select = explode(',', $express_select);
            } else {
                $express_select = array();
            }
            View::assign('express_select', $express_select);
            //页面输出
            View::assign('express_list', $express_list);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('express');
            return View::fetch($this->template_dir . 'express');
        } else {
            $data['store_id'] = session('store_id');
            $cexpress_array = input('post.cexpress/a'); #获取数组
            if (!empty($cexpress_array)) {
                $data['express'] = implode(',', $cexpress_array);
            } else {
                $data['express'] = '';
            }
            $condition = array();
            $condition[] = array('store_id', '=', session('store_id'));
            if (!$storeextend_model->getStoreextendInfo($condition)) {
                $result = $storeextend_model->addStoreextend($data);
            } else {
                $result = $storeextend_model->editStoreextend($data, $condition);
            }
            if ($result) {
                ds_json_encode('10000', lang('ds_common_save_succ'));
            } else {
                ds_json_encode('10001', lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 免运费额度设置
     */
    public function free_freight() {
        if (!request()->isPost()) {
            View::assign('store_free_price', $this->store_info['store_free_price']);
            View::assign('store_free_time', $this->store_info['store_free_time']);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('free_freight');
            return View::fetch($this->template_dir . 'free_freight');
        } else {
            $store_model = model('store');
            $store_free_price = floatval(abs(input('post.store_free_price')));
            $store_free_time = input('post.store_free_time');
            $store_model->editStore(array(
                'store_free_price' => $store_free_price,
                'store_free_time' => $store_free_time
                    ), array('store_id' => session('store_id')));
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    /**
     * 电子面单
     */
    public function eorder_set() {
        if (!request()->isPost()) {
            View::assign('store_info', $this->store_info);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('eorder_set');
            return View::fetch($this->template_dir . 'eorder_set');
        } else {
            $store_model = model('store');
            $store_model->editStore(array(
                'expresscf_kdn_if_open' => input('post.expresscf_kdn_if_open'),
                'expresscf_kdn_id' => input('post.expresscf_kdn_id'),
                'expresscf_kdn_key' => input('post.expresscf_kdn_key'),
                'expresscf_kdn_printer' => input('post.expresscf_kdn_printer'),
                    ), array('store_id' => session('store_id')));
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    /**
     * 电子面单
     */
    public function eorder_list() {
        $expresscf_kdn_config_model = model('expresscf_kdn_config');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $expresscf_kdn_config_list = $expresscf_kdn_config_model->getExpresscfKdnConfigList($condition, '*', 10);

        foreach ($expresscf_kdn_config_list as $key => $val) {
            $condition = array();
            $condition[] = array('express_code', '=', $val['express_code']);
            $expresscf_kdn_config_list[$key]['express_name'] = Db::name('express')->where($condition)->value('express_name');
            $expresscf_kdn_config_list[$key]['expresscf_kdn_config_pay_type_text'] = lang('expresscf_kdn_config_pay_type')[$val['expresscf_kdn_config_pay_type']];
        }
        View::assign('expresscf_kdn_config_list', $expresscf_kdn_config_list);
        View::assign('show_page', $expresscf_kdn_config_model->page_info->render());
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerdeliverset');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('eorder_list');
        return View::fetch($this->template_dir . 'eorder_list');
    }

    /**
     * 电子面单
     */
    public function eorder_add() {
        if (!request()->isPost()) {
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
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('eorder_add');
            return View::fetch($this->template_dir . 'eorder_form');
        } else {
            $express_code = input('post.express_code');
            $data = array(
                'store_id' => session('store_id'),
                'express_code' => $express_code,
                'expresscf_kdn_config_customer_name' => input('post.expresscf_kdn_config_customer_name'),
                'expresscf_kdn_config_customer_pwd' => input('post.expresscf_kdn_config_customer_pwd'),
                'expresscf_kdn_config_send_site' => input('post.expresscf_kdn_config_send_site'),
                'expresscf_kdn_config_send_staff' => input('post.expresscf_kdn_config_send_staff'),
                'expresscf_kdn_config_month_code' => input('post.expresscf_kdn_config_month_code'),
                'expresscf_kdn_config_pay_type' => input('post.expresscf_kdn_config_pay_type'),
            );
            $expresscf_kdn_config_validate = ds_validate('expresscf_kdn_config');
            if (!$expresscf_kdn_config_validate->scene('expresscf_kdn_config_add')->check($data)) {
                ds_json_encode(10000, $expresscf_kdn_config_validate->getError());
            }
            $expresscf_kdn_config_model = model('expresscf_kdn_config');
            $condition = array();
            $condition[] = array('store_id', '=', session('store_id'));
            $condition[] = array('express_code', '=', $express_code);
            $expresscf_kdn_config_info = $expresscf_kdn_config_model->getExpresscfKdnConfigInfo($condition);
            if ($expresscf_kdn_config_info) {
                ds_json_encode(10000, '电子面单已存在');
            }
            $flag = $expresscf_kdn_config_model->addExpresscfKdnConfig($data);
            if (!$flag) {
                ds_json_encode(10000, lang('ds_common_op_fail'));
            }
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    /**
     * 电子面单
     */
    public function eorder_edit() {
        $expresscf_kdn_config_id = input('get.expresscf_kdn_config_id');
        $expresscf_kdn_config_model = model('expresscf_kdn_config');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('expresscf_kdn_config_id', '=', $expresscf_kdn_config_id);
        $expresscf_kdn_config_info = $expresscf_kdn_config_model->getExpresscfKdnConfigInfo($condition);
        if (!request()->isPost()) {
            View::assign('expresscf_kdn_config_info', $expresscf_kdn_config_info);
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
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('eorder_edit');
            return View::fetch($this->template_dir . 'eorder_form');
        } else {
            if (!$expresscf_kdn_config_info) {
                ds_json_encode(10000, '电子面单不存在');
            }
            $data = array(
                'expresscf_kdn_config_customer_name' => input('post.expresscf_kdn_config_customer_name'),
                'expresscf_kdn_config_customer_pwd' => input('post.expresscf_kdn_config_customer_pwd'),
                'expresscf_kdn_config_send_site' => input('post.expresscf_kdn_config_send_site'),
                'expresscf_kdn_config_send_staff' => input('post.expresscf_kdn_config_send_staff'),
                'expresscf_kdn_config_month_code' => input('post.expresscf_kdn_config_month_code'),
                'expresscf_kdn_config_pay_type' => input('post.expresscf_kdn_config_pay_type'),
            );
            $expresscf_kdn_config_validate = ds_validate('expresscf_kdn_config');
            if (!$expresscf_kdn_config_validate->scene('expresscf_kdn_config_edit')->check($data)) {
                ds_json_encode(10000, $expresscf_kdn_config_validate->getError());
            }
            $flag = $expresscf_kdn_config_model->editExpresscfKdnConfig($data, $condition);
            if (!$flag) {
                ds_json_encode(10000, lang('ds_common_op_fail'));
            }
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    /**
     * 电子面单
     */
    public function eorder_del() {
        $expresscf_kdn_config_id = intval(input('param.expresscf_kdn_config_id'));
        if ($expresscf_kdn_config_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $expresscf_kdn_config_model = model('expresscf_kdn_config');
        $condition = array();
        $condition[] = array('expresscf_kdn_config_id', '=', $expresscf_kdn_config_id);
        $condition[] = array('store_id', '=', session('store_id'));
        $delete = $expresscf_kdn_config_model->delExpresscfKdnConfig($condition);
        if ($delete) {
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 默认配送区域设置
     */
    public function deliver_region() {
        if (!request()->isPost()) {
            $deliver_region = array(
                '', ''
            );
            if (strpos($this->store_info['deliver_region'], '|')) {
                $deliver_region = explode('|', $this->store_info['deliver_region']);
            }
            View::assign('deliver_region', $deliver_region);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('deliver_region');
            return View::fetch($this->template_dir . 'deliver_region');
        } else {
            model('store')->editStore(array('deliver_region' => input('post.area_ids') . '|' . input('post.region')), array('store_id' => session('store_id')));
            ds_json_encode(10000, lang('ds_common_save_succ'));
        }
    }

    /**
     * 发货单打印设置
     */
    public function print_set() {
        $store_info = $this->store_info;

        if (!request()->isPost()) {
            View::assign('store_info', $store_info);
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellerdeliverset');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('print_set');
            return View::fetch($this->template_dir . 'print_set');
        } else {
            $data = array(
                'store_printexplain' => input('store_printexplain')
            );

            $sellerdeliverset_validate = ds_validate('sellerdeliverset');
            if (!$sellerdeliverset_validate->scene('print_set')->check($data)) {
                $this->error($sellerdeliverset_validate->getError());
            }
            $update_arr = array();
            //上传认证文件
            if ($_FILES['store_seal']['name'] != '') {
                $file_name = session('store_id') . '_' . date('YmdHis') . rand(10000, 99999) . '.png';
                $res = ds_upload_pic(ATTACH_STORE, 'store_seal');
                if ($res['code']) {
                    $file_name = $res['data']['file_name'];
                    $update_arr['store_seal'] = $file_name;
                    //删除旧认证图片
                    if (!empty($store_info['store_seal'])) {
                        @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_STORE . DIRECTORY_SEPARATOR . $store_info['store_seal']);
                    }
                } else {
                    $this->error($res['msg']);
                }
            }
            $update_arr['store_printexplain'] = input('post.store_printexplain');

            $rs = model('store')->editStore($update_arr, array('store_id' => session('store_id')));
            if ($rs) {
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'daddress',
                'text' => lang('store_deliver_daddress_list'),
                'url' => (string) url('Sellerdeliverset/index')
            ),
            array(
                'name' => 'express',
                'text' => lang('store_deliver_default_express'),
                'url' => (string) url('Sellerdeliverset/express')
            ),
            array(
                'name' => 'eorder_set',
                'text' => lang('eorder_set'),
                'url' => (string) url('Sellerdeliverset/eorder_set')
            ),
            array(
                'name' => 'eorder_list',
                'text' => lang('eorder_list'),
                'url' => (string) url('Sellerdeliverset/eorder_list')
            ),
            array(
                'name' => 'free_freight',
                'text' => lang('free_freight'),
                'url' => (string) url('Sellerdeliverset/free_freight')
            ),
            array(
                'name' => 'deliver_region',
                'text' => lang('default_delivery_area'),
                'url' => (string) url('Sellerdeliverset/deliver_region')
            ),
            array(
                'name' => 'print_set',
                'text' => lang('print_set'),
                'url' => (string) url('Sellerdeliverset/print_set')
            )
        );
        return $menu_array;
    }

}

?>
