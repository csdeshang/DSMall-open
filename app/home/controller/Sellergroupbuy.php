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
class Sellergroupbuy extends BaseSeller {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellergroupbuy.lang.php');
        //检查抢购功能是否开启
        if (intval(config('ds_config.groupbuy_allow')) !== 1) {
            $this->error(lang('groupbuy_unavailable'), 'seller/index');
        }
    }

    /**
     * 默认显示抢购列表
     * */
    public function index() {
        $this->groupbuy_list();
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 抢购套餐购买
     * */
    public function groupbuy_quota_add() {
        //输出导航
        $this->setSellerCurMenu('Sellergroupbuy');
        $this->setSellerCurItem('groupbuy_quota_add');
        return View::fetch($this->template_dir . 'groupbuy_quota_add');
    }

    /**
     * 抢购套餐购买保存
     * */
    public function groupbuy_quota_add_save() {
        if (intval(config('ds_config.groupbuy_price')) == 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $groupbuy_quota_quantity = intval(input('param.groupbuy_quota_quantity'));
        if ($groupbuy_quota_quantity <= 0) {
            ds_json_encode(10001, lang('purchase_quantity_cannot_empty'));
        }

        $groupbuyquota_model = model('groupbuyquota');

        //获取当前价格
        $current_price = intval(config('ds_config.groupbuy_price'));

        //获取该用户已有套餐
        $current_groupbuy_quota = $groupbuyquota_model->getGroupbuyquotaCurrent(session('store_id'));
        $add_time = 86400 * 30 * $groupbuy_quota_quantity;
        if (empty($current_groupbuy_quota)) {
            //生成套餐
            $param = array();
            $param['member_id'] = session('member_id');
            $param['member_name'] = session('member_name');
            $param['store_id'] = session('store_id');
            $param['store_name'] = session('store_name');
            $param['groupbuyquota_starttime'] = TIMESTAMP;
            $param['groupbuyquota_endtime'] = TIMESTAMP + $add_time;
            $groupbuyquota_model->addGroupbuyquota($param);
        } else {
            $param = array();
            $param['groupbuyquota_endtime'] = Db::raw('groupbuyquota_endtime+' . $add_time);
            $groupbuyquota_model->editGroupbuyquota($param, array('groupbuyquota_id' => $current_groupbuy_quota['groupbuyquota_id']));
        }

        //记录店铺费用
        $this->recordStorecost($current_price * $groupbuy_quota_quantity, lang('buy_to_snap_up').' ['.$groupbuy_quota_quantity.'个月 × 单价:'.$current_price.'元]');

        $this->recordSellerlog(lang('buy') . $groupbuy_quota_quantity . lang('snap_up_package') . $current_price . lang('ds_yuan'));

        ds_json_encode(10000, lang('groupbuy_quota_add_success'));
    }

    /**
     * 抢购列表
     * */
    public function groupbuy_list() {
        $groupbuy_model = model('groupbuy');
        $groupbuyquota_model = model('groupbuyquota');

            $current_groupbuy_quota = $groupbuyquota_model->getGroupbuyquotaCurrent(session('store_id'));
            View::assign('current_groupbuy_quota', $current_groupbuy_quota);

        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        if ((input('param.groupbuy_state'))) {
            $condition[] = array('groupbuy_state', '=', input('param.groupbuy_state'));
        }
        $condition[] = array('groupbuy_name', 'like', '%' . input('param.groupbuy_name') . '%');

        if (strlen($groupbuy_vr = trim(input('param.groupbuy_vr')))) {
            $condition[] = array('groupbuy_is_vr', '=', $groupbuy_vr ? 1 : 0);
            View::assign('groupbuy_vr', $groupbuy_vr);
        }
        $groupbuy_list = $groupbuy_model->getGroupbuyExtendList($condition, 10);
        //halt($groupbuy_list);
        View::assign('group', $groupbuy_list);
        View::assign('show_page', $groupbuy_model->page_info->render());
        View::assign('groupbuy_state_array', $groupbuy_model->getGroupbuyStateArray());

        $this->setSellerCurMenu('Sellergroupbuy');
        $this->setSellerCurItem('groupbuy_list');
    }

    /**
     * 添加抢购页面
     * */
    public function groupbuy_add() {
        $groupbuyquota_model = model('groupbuyquota');

            $current_groupbuy_quota = $groupbuyquota_model->getGroupbuyquotaCurrent(session('store_id'));
            if (empty($current_groupbuy_quota)) {
                if (intval(config('ds_config.groupbuy_price')) != 0) {
                    $this->error(lang('please_buy_package_first'), (string) url('Sellergroupbuy/groupbuy_quota_add'));
                } else {
                    $current_groupbuy_quota = array('groupbuyquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                }
            }
            View::assign('current_groupbuy_quota', $current_groupbuy_quota);
        // 根据后台设置的审核期重新设置抢购开始时间
        View::assign('groupbuy_starttime', TIMESTAMP + intval(config('ds_config.groupbuy_review_day')) * 86400);

        View::assign('groupbuy_classes', model('groupbuy')->getGroupbuyClasses());

        $this->setSellerCurMenu('Sellergroupbuy');
        $this->setSellerCurItem('groupbuy_add');
        return View::fetch($this->template_dir . 'groupbuy_add');
    }

    /**
     * 抢购保存
     * */
    public function groupbuy_save() {
        //获取提交的数据
        $goods_id = intval(input('post.groupbuy_goods_id'));
        if (empty($goods_id)) {
            $this->error(lang('param_error'));
        }

        $groupbuy_model = model('groupbuy');
        $goods_model = model('goods');
        $groupbuyquota_model = model('groupbuyquota');

            // 检查套餐
            $current_groupbuy_quota = $groupbuyquota_model->getGroupbuyquotaCurrent(session('store_id'));
            if (empty($current_groupbuy_quota) && intval(config('ds_config.groupbuy_price')) != 0) {
                $this->error(lang('please_buy_package_first'), (string) url('Store_groupbuy/groupbuy_quota_add'));
            }

        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        if (empty($goods_info) || $goods_info['store_id'] != session('store_id')) {
            $this->error(lang('param_error'));
        }

        $param = array();
        $param['groupbuy_name'] = input('post.groupbuy_name');
        $param['groupbuy_remark'] = input('post.remark');
        $param['groupbuy_starttime'] = strtotime(input('post.start_time'));
        $param['groupbuy_endtime'] = strtotime(input('post.end_time'));
        $param['groupbuy_price'] = floatval(input('post.groupbuy_price'));
        $param['groupbuy_rebate'] = ds_price_format(floatval(input('post.groupbuy_price')) / floatval($goods_info['goods_price']) * 10);
        $param['groupbuy_image'] = input('post.groupbuy_image');
        $param['groupbuy_image1'] = input('post.groupbuy_image1');
        $param['virtual_quantity'] = intval(input('post.virtual_quantity'));
        $param['groupbuy_upper_limit'] = intval(input('post.upper_limit'));
        $param['groupbuy_intro'] = input('post.groupbuy_intro');
        $param['gclass_id'] = input('post.gclass_id', 0);
        $param['goods_id'] = $goods_info['goods_id'];
        $param['goods_commonid'] = $goods_info['goods_commonid'];
        $param['goods_name'] = $goods_info['goods_name'];
        $param['goods_price'] = $goods_info['goods_price'];
        $param['store_id'] = session('store_id');
        $param['store_name'] = session('store_name');

        // 虚拟抢购
        if (input('param.vr')) {
            if ($param['groupbuy_upper_limit'] > 0 && $goods_info['virtual_limit'] > 0 && $param['groupbuy_upper_limit'] > $goods_info['virtual_limit']) {
                $this->error(sprintf(lang('virtual_panic_buying'), $param['groupbuy_upper_limit'], $goods_info['virtual_limit']), (string) url('Sellergroupbuy/index'));
            }

            $param += array(
                'groupbuy_is_vr' => 1,
                'vr_class_id' => (int) input('post.class'),
                'vr_s_class_id' => (int) input('post.s_class'),
            );
        }

        //保存
        $result = $groupbuy_model->addGroupbuy($param);
        if ($result) {
            // 自动发布动态
            // group_id,group_name,goods_id,goods_price,groupbuy_price,group_pic,rebate,start_time,end_time
            $data_array = array();
            $data_array['group_id'] = $result;
            $data_array['group_name'] = $param['groupbuy_name'];
            $data_array['store_id'] = session('store_id');
            $data_array['goods_id'] = $param['goods_id'];
            $data_array['goods_price'] = $param['goods_price'];
            $data_array['groupbuy_price'] = $param['groupbuy_price'];
            $data_array['group_pic'] = $param['groupbuy_image1'];
            $data_array['rebate'] = $param['groupbuy_rebate'];
            $data_array['start_time'] = $param['groupbuy_starttime'];
            $data_array['end_time'] = $param['groupbuy_endtime'];
            $this->storeAutoShare($data_array, 'groupbuy');

            $this->recordSellerlog(lang('release_snap_up') . $param['groupbuy_name'] . '，' . lang('groupbuy_index_goods_name') . '：' . $param['goods_name']);
            $this->success(lang('groupbuy_add_success'), (string) url('Sellergroupbuy/index'));
        } else {
            $this->error(lang('groupbuy_add_fail'), (string) url('Sellergroupbuy/index'));
        }
    }

    public function groupbuy_goods_info() {
        $goods_commonid = intval(input('param.goods_commonid'));

        $data = array();
        $data['result'] = true;

        $goods_model = model('goods');

        $condition = array();
        $condition[] = array('goods_commonid', '=', $goods_commonid);
        $goods_list = $goods_model->getGoodsOnlineList($condition);

        if (empty($goods_list)) {
            $data['result'] = false;
            $data['message'] = lang('param_error');
            echo json_encode($data);
            die;
        }

        $goods_info = $goods_list[0];
        $data['goods_id'] = $goods_info['goods_id'];
        $data['goods_name'] = $goods_info['goods_name'];
        $data['goods_price'] = $goods_info['goods_price'];
        $data['goods_image'] = goods_thumb($goods_info, 240);
        $data['goods_href'] = (string) url('Goods/index', array('goods_id' => $goods_info['goods_id']));

        if ($goods_info['is_virtual']) {
            $data['is_virtual'] = 1;
            $data['virtual_indate'] = $goods_info['virtual_indate'];
            $data['virtual_indate_str'] = date('Y-m-d H:i', $goods_info['virtual_indate']);
            $data['virtual_limit'] = $goods_info['virtual_limit'];
        }

        echo json_encode($data);
        die;
    }

    public function check_groupbuy_goods() {
        $start_time = strtotime(input('param.start_time'));
        $goods_id = input('param.goods_id');

        $groupbuy_model = model('groupbuy');

        $data = array();
        $data['result'] = true;

        //检查商品是否已经参加同时段活动
        $condition = array();
        $condition[] = array('groupbuy_endtime', '>', $start_time);
        $condition[] = array('goods_id', '=', $goods_id);
        $groupbuy_list = $groupbuy_model->getGroupbuyAvailableList($condition);
        if (!empty($groupbuy_list)) {
            $data['result'] = false;
            echo json_encode($data);
            die;
        }

        echo json_encode($data);
        die;
    }

    /**
     * 上传图片
     * */
    public function image_upload() {
        $old_groupbuy_image = input('post.old_groupbuy_image');
        if (!empty($old_groupbuy_image)) {
            $this->_image_del($old_groupbuy_image);
        }
        $this->_image_upload('groupbuy_image');
    }

    private function _image_upload($file) {
        $data = array();
        $data['result'] = true;

        if (!empty($_FILES[$file]['name'])) {
            $upload_path = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_GROUPBUY . DIRECTORY_SEPARATOR . session('store_id') . DIRECTORY_SEPARATOR;
            $file_name = session('store_id') . '_' . date('YmdHis') . rand(10000, 99999) . '.png';

            $res = ds_upload_pic(ATTACH_GROUPBUY . DIRECTORY_SEPARATOR . session('store_id'), $file, $file_name);
            if ($res['code']) {
                $file_name = $res['data']['file_name'];
                $pic = $file_name;
                $data['file_name'] = $pic;
                $data['origin_file_name'] = $_FILES[$file]['name'];
                $data['file_url'] = ds_get_pic(ATTACH_GROUPBUY . DIRECTORY_SEPARATOR . session('store_id') , $pic);
                ds_create_thumb($upload_path, $file_name, '120,420', '120,420', '_small,_normal');
            } else {
                $data['result'] = false;
                $data['message'] = $res['msg'];
            }
        } else {
            $data['result'] = false;
        }
        echo json_encode($data);
        die;
    }

    /**
     * 图片删除
     */
    private function _image_del($image_name) {
        $image_array = explode('_', $image_name);
        $store_id = $image_array[0];
        //判断只能删除当前用户下的文件
        if ($store_id != session('store_id')) {
            return;
        }
        //防止构造恶意 ../ 进行任意删除文件
        if (strpos($image_name, '..') !== false) {
            return;
        }

        $upload_path = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_GROUPBUY . DIRECTORY_SEPARATOR . $store_id . DIRECTORY_SEPARATOR;
        ds_unlink($upload_path, $image_name);
    }

    /**
     * 选择活动商品
     * */
    public function search_goods() {
        $goods_model = model('goods');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('goods_name', 'like', '%' . input('param.goods_name') . '%');
        $goods_list = $goods_model->getGoodsCommonListForPromotion($condition, '*', 8, 'groupbuy');
        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'search_goods');
        exit;
    }

    /**
     * 添加虚拟抢购页面
     */
    public function groupbuy_add_vr() {
        $groupbuyquota_model = model('groupbuyquota');

            $current_groupbuy_quota = $groupbuyquota_model->getGroupbuyquotaCurrent(session('store_id'));
            if (empty($current_groupbuy_quota)) {
                if (intval(config('ds_config.groupbuy_price')) != 0) {
                    $this->error(lang('please_buy_package_first'), (string) url('Sellergroupbuy/groupbuy_quota_add'));
                } else {
                    $current_groupbuy_quota = array('groupbuyquota_endtime' => TIMESTAMP + 86400 * 30); //没有套餐时，最多一个月
                }
            }
            View::assign('current_groupbuy_quota', $current_groupbuy_quota);

        // 根据后台设置的审核期重新设置抢购开始时间
        View::assign('groupbuy_starttime', TIMESTAMP + intval(config('ds_config.groupbuy_review_day')) * 86400);

        // 虚拟抢购分类
        View::assign('groupbuy_vr_classes', model('groupbuy')->getGroupbuyVrClasses());
        $vrgroupbuyclass_model = model('vrgroupbuyclass');
        $classlist = $vrgroupbuyclass_model->getVrgroupbuyclassList(array('vrgclass_parent_id' => 0));
        View::assign('classlist', $classlist);

        $this->setSellerCurMenu('Sellergroupbuy');
        $this->setSellerCurItem('groupbuy_add_vr');
        return View::fetch($this->template_dir . 'groupbuy_add_vr');
    }

    public function ajax_vr_class() {
        $vrgclass_id = intval(input('param.vrgclass_id'));
        if (empty($vrgclass_id)) {
            exit('false');
        }

        $condition = array();
        $condition[] = array('vrgclass_parent_id', '=', $vrgclass_id);

        $vrgroupbuyclass_model = model('vrgroupbuyclass');
        $class_list = $vrgroupbuyclass_model->getVrgroupbuyclassList($condition);

        if (!empty($class_list)) {
            echo json_encode($class_list);
        } else {
            echo 'false';
        }

        exit;
    }

    /**
     * 选择活动虚拟商品
     */
    public function search_vr_goods() {
        $goods_model = model('goods');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('goods_name', 'like', '%' . input('param.goods_name') . '%');
        $goods_list = $goods_model->getGoodsCommonListForVrPromotion($condition, '*', 8);

        View::assign('goods_list', $goods_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'search_goods');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $name 当前导航的name
     * @param array $array 附加菜单
     * @return
     */
    protected function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'groupbuy_list', 'text' => lang('ds_member_path_group_list'),
                'url' => (string) url('Sellergroupbuy/index')
            )
        );
        switch (request()->action()) {
            case 'groupbuy_add':
                $menu_array[] = array(
                    'name' => 'groupbuy_add', 'text' => lang('ds_member_path_new_group'),
                    'url' => (string) url('Sellergroupbuy/groupbuy_add')
                );
                break;
            case 'groupbuy_add_vr':
                $menu_array[] = array(
                    'name' => 'groupbuy_add_vr', 'text' => lang('new_virtual_panic_buying'), 'url' => (string) url('Sellergroupbuy/groupbuy_add_vr')
                );
                break;
            case 'groupbuy_quota_add':
                $menu_array[] = array(
                    'name' => 'groupbuy_quota_add', 'text' => lang('purchase_plan'),
                    'url' => (string) url('Sellergroupbuy/groupbuy_quota_add')
                );
                break;
            case 'groupbuy_edit':
                $menu_array[] = array(
                    'name' => 'groupbuy_edit', 'text' => lang('ds_member_path_edit_group'),
                    'url' => (string) url('Sellergroupbuy/index')
                );
                break;
            case 'cancel':
                $menu_array[] = array('name' => 'groupbuy_cancel', 'text' => lang('ds_member_path_cancel_group'));
                break;
        }
        return $menu_array;
    }

}
