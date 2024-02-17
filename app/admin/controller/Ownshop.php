<?php

namespace app\admin\controller;
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
class Ownshop extends AdminControl {
    
    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/ownshop.lang.php');
    }

    public function index() {
        $condition = array();
        $condition[]=array('is_platform_store' ,'=', 1);
        
        $store_name = trim(input('get.store_name'));
        if (strlen($store_name) > 0) {
            $condition[]=array('store_name','like', "%$store_name%");
            View::assign('store_name', $store_name);
        }
        $ownshop_model = model('store');
        $storeList = $ownshop_model->getStoreList($condition,10);
        View::assign('store_list', $storeList);
        View::assign('show_page', $ownshop_model->page_info->render());
        $this->setAdminCurItem('index');
        return View::fetch('ownshop_list');
    }

    public function add() {
        if (!request()->isPost()) {
            return View::fetch('ownshop_add');
        } else {

            $member_name = input('post.member_name');
            $member_password = input('post.member_password');
            $store_name = input('post.store_name');

            if (strlen($member_name) < 3 || strlen($member_name) > 15)
                $this->error(lang('account_length_error'));

            if (strlen($member_password) < 6)
                $this->error(lang('password_length_error'));

            if (!$this->checkMemberName($member_name))
                $this->error(lang('member_name_remote'));


            try {
                $memberId = model('member')->addMember(array(
                    'member_name' => $member_name,
                    'member_password' => $member_password,
                ));
            } catch (Exception $ex) {
                $this->error(lang('account_add_fail'));
            }

            $store_model = model('store');

            $saveArray = array();
            $saveArray['store_name'] = $store_name;
            $saveArray['member_id'] = $memberId;
            $saveArray['member_name'] = $member_name;
            $saveArray['seller_name'] = $member_name;
            $saveArray['bind_all_gc'] = 1;
            $saveArray['store_state'] = 1;
            $saveArray['store_addtime'] = TIMESTAMP;
            $saveArray['is_platform_store'] = 1;

            $store_id = $store_model->addStore($saveArray);

            model('seller')->addSeller(array(
                'seller_name' => $member_name,
                'member_id' => $memberId,
                'store_id' => $store_id,
                'sellergroup_id' => 0,
                'is_admin' => 1,
            ));
            model('storejoinin')->addStorejoinin(array(
                'seller_name' => $member_name,
                'store_name' => $store_name,
                'member_name' => $member_name,
                'member_id' => $memberId,
                'joinin_state' => 40,
                'company_province_id' => 0,
                'storeclass_bail' => 0,
                'joinin_year' => 1,
            ));

            // 添加相册默认
            $album_model = model('album');
            $album_arr = array();
            $album_arr['aclass_name'] = lang('default_album');
            $album_arr['store_id'] = $store_id;
            $album_arr['aclass_des'] = '';
            $album_arr['aclass_sort'] = '255';
            $album_arr['aclass_cover'] = '';
            $album_arr['aclass_uploadtime'] = TIMESTAMP;
            $album_arr['aclass_isdefault'] = '1';
            $album_model->addAlbumclass($album_arr);

            //插入店铺扩展表
            $store_model->addStoreextend(array('store_id' => $store_id));

            // 删除自营店id缓存
            model('store')->dropCachedOwnShopIds();

            $this->log(lang('add_ownshop').": {$saveArray['store_name']}");
            dsLayerOpenSuccess(lang('ds_common_op_succ'),(string)url('Ownshop/index'));
        }
    }

    /*
    // 删除自营店铺
    public function del() {
        $store_id = intval(input('param.id'));
        $store_model = model('store');
        $storeArray = $store_model->getOneStore(array('store_id'=>$store_id),'is_platform_store,store_name');
        if (empty($storeArray)) {
            $this->error('自营店铺不存在');
        }
        if (!$storeArray['is_platform_store']) {
            $this->error('不能在此删除非自营店铺');
        }
        $condition = array(
            'store_id' => $store_id,
        );
        if (model('goods')->getGoodsCount($condition) > 0)
            $this->error('已经发布商品的自营店铺不能被删除');

        // 完全删除店铺
        $store_model->delStoreEntirely($condition);
        // 删除自营店id缓存
        model('store')->dropCachedOwnShopIds();
        $this->log("删除自营店铺: {$storeArray['store_name']}");
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }
     */

    public function edit() {
        $store_model = model('store');
        $store_id = intval(input('param.id'));
        $storeArray = $store_model->getStoreInfoByID($store_id);

        if (!$storeArray['is_platform_store']) {
            $this->error(lang('cannot_manage_no_ownshop'));
        }

        if (!request()->isPost()) {
            if (empty($storeArray))
                $this->error(lang('store_not_exist'));
            View::assign('store_array', $storeArray);
            //店铺分类
            $storeclass_model = model('storeclass');
            $parent_list = $storeclass_model->getStoreclassList(array(), '', false);

            View::assign('class_list', $parent_list);
            return View::fetch('ownshop_edit');
        }else {

            $saveArray = array();
            $saveArray['storeclass_id'] = intval(input('post.storeclass_id'));
            $saveArray['store_name'] = input('post.store_name');
            $saveArray['bind_all_gc'] = input('post.bind_all_gc') ? 1 : 0;
            $saveArray['store_state'] = input('post.store_state') ? 1 : 0;
            $saveArray['store_close_info'] = input('post.store_close_info');

            $goods_model = model('goods');
            $condition = array();
            $condition[] = array('store_id','=',$store_id);
            $goods_model->editProducesOffline($condition);
            $store_model->editStore($saveArray, $condition);
            if($storeArray['store_name']!=$saveArray['store_name']){
                $goods_model = model('goods');
                $goods_model->editGoodsCommon(array('store_name'=>$saveArray['store_name']), array('store_id'=>$store_id));
                $goods_model->editGoods(array('store_name'=>$saveArray['store_name']), array('store_id'=>$store_id));
            }
            // 删除自营店id缓存
            model('store')->dropCachedOwnShopIds();

            $this->log(lang('edit_ownshop').": {$saveArray['store_name']}");
            dsLayerOpenSuccess(lang('ds_common_op_succ'),(string)url('Ownshop/index'));
        }
    }

    /**
     * 编辑保存注册信息
     */
    public function storejoinin_edit() {
        if (request()->isPost()) {
            $member_id = input('post.member_id');
            if ($member_id <= 0) {
                $this->error(lang('param_error'));
            }
            $param = array();
            $param['company_name'] = input('post.company_name');
            $param['company_province_id'] = intval(input('post.province_id'));
            $param['company_address'] = input('post.company_address');
            $param['company_address_detail'] = input('post.company_address_detail');
            $param['company_registered_capital'] = intval(input('post.company_registered_capital'));
            $param['contacts_name'] = input('post.contacts_name');
            $param['contacts_phone'] = input('post.contacts_phone');
            $param['contacts_email'] = input('post.contacts_email');
            $param['business_licence_number'] = input('post.business_licence_number');
            $param['business_licence_address'] = input('post.business_licence_address');
            $param['business_licence_start'] = input('post.business_licence_start');
            $param['business_licence_end'] = input('post.business_licence_end');
            $param['business_sphere'] = input('post.business_sphere');
            if (!empty($_FILES['business_licence_number_electronic']['name'])) {
                $param['business_licence_number_electronic'] = $this->upload_image('business_licence_number_electronic');
            }


            $param['bank_account_name'] = input('post.bank_account_name');
            $param['bank_account_number'] = input('post.bank_account_number');
            $param['bank_name'] = input('post.bank_name');
            $param['bank_address'] = input('post.bank_address');

            $param['settlement_bank_account_name'] = input('post.settlement_bank_account_name');
            $param['settlement_bank_account_number'] = input('post.settlement_bank_account_number');
            $param['settlement_bank_name'] = input('post.settlement_bank_name');
            $param['settlement_bank_address'] = input('post.settlement_bank_address');

            $result = model('storejoinin')->editStorejoinin($param, array('member_id' => $member_id));
            if ($result >= 0) {
                //更新店铺信息
                $store_update = array();
                $store_update['store_company_name'] = $param['company_name'];
                $store_update['area_info'] = $param['company_address'];
                $store_update['store_address'] = $param['company_address_detail'];
                $store_model = model('store');
                $store_info = $store_model->getStoreInfo(array('member_id' => $member_id));
                if (!empty($store_info)) {
                    $r = $store_model->editStore($store_update, array('member_id' => $member_id));
                    $this->log('编辑店铺信息' . '[ID:' . $r . ']', 1);
                }
                $this->success(lang('ds_common_op_succ'), (string) url('Ownshop/index'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }else{
            $store_model = model('store');
            $store_id = intval(input('param.id'));
            $storeArray = $store_model->getStoreInfoByID($store_id);
            $joinin_detail = model('storejoinin')->getOneStorejoinin(array('member_id' => $storeArray['member_id']));
            View::assign('joinin_detail', $joinin_detail);
            $this->setAdminCurItem('store_edit');
            return View::fetch('storejoinin_edit');
        }
    }
    public function check_seller_name() {
        $seller_name = input('get.seller_name');
        echo json_encode($this->checkSellerName($seller_name));
        exit;
    }

    private function checkSellerName($sellerName) {
        // 判断store_joinin是否存在记录
        $count = (int) model('storejoinin')->getStorejoininCount(array(
                    'seller_name' => $sellerName,
        ));
        if ($count > 0) {
            return FALSE;
        }
        $seller = model('seller')->getSellerInfo(array(
            'seller_name' => $sellerName,
        ));
        if (!empty($seller)) {
            return FALSE;
        }
        return TRUE;
    }

    public function check_member_name() {
        $member_name = input('get.member_name');
        echo json_encode($this->checkMemberName($member_name));
        exit;
    }

    private function checkMemberName($member_name) {
        // 判断store_joinin是否存在记录
        $count = (int) model('storejoinin')->getStorejoininCount(array(
                    'member_name' => $member_name,
        ));
        if ($count > 0)
            return false;

        return !model('member')->getMemberCount(array(
                    'member_name' => $member_name,
        ));
    }

    private function upload_image($file) {
    
        //上传文件保存路径
        $pic_name = '';
    
        if (!empty($_FILES[$file]['name'])) {
            //设置特殊图片名称
            $member_id = input('post.member_id');
            $file_name = $member_id . '_' . date('YmdHis') . rand(10000, 99999) . '.png';
    
            $res = ds_upload_pic('home' . DIRECTORY_SEPARATOR . 'store_joinin', $file, $file_name);
            if ($res['code']) {
                $pic_name = $res['data']['file_name'];
            } else {
                $this->error($res['msg']);
            }
        }
        return $pic_name;
    }
    
    public function bind_class() {
        $store_id = intval(input('param.id'));

        $store_model = model('store');
        $storebindclass_model = model('storebindclass');
        $goodsclass_model = model('goodsclass');

        $gc_list = $goodsclass_model->getGoodsclassListByParentId(0);
        View::assign('gc_list', $gc_list);

        $store_info = $store_model->getStoreInfoByID($store_id);
        if (empty($store_info)) {
            $this->error(lang('param_error'));
        }
        View::assign('store_info', $store_info);

        $store_bind_class_list = $storebindclass_model->getStorebindclassList(array('store_id' => $store_id), 30);

        $goods_class = model('goodsclass')->getGoodsclassIndexedListAll();

        for ($i = 0, $j = count($store_bind_class_list); $i < $j; $i++) {
            $store_bind_class_list[$i]['class_1_name'] = @$goods_class[$store_bind_class_list[$i]['class_1']]['gc_name'];
            $store_bind_class_list[$i]['class_2_name'] = @$goods_class[$store_bind_class_list[$i]['class_2']]['gc_name'];
            $store_bind_class_list[$i]['class_3_name'] = @$goods_class[$store_bind_class_list[$i]['class_3']]['gc_name'];
        }
        View::assign('store_bind_class_list', $store_bind_class_list);
        View::assign('showpage', $storebindclass_model->page_info->render());
        $this->setAdminCurItem('bind_class');
        return View::fetch('ownshop_bind_class');
    }

    /**
     * 添加经营类目
     */
    public function bind_class_add() {
        $store_id = intval(input('post.store_id'));
        $commis_rate = intval(input('post.commis_rate'));
        if ($commis_rate < 0 || $commis_rate > 100) {
            $this->error(lang('param_error'));
        }
        @list($class_1, $class_2, $class_3) = explode(',', input('post.goods_class'));
        $storebindclass_model = model('storebindclass');
        $goodsclass_model = model('goodsclass');

        $param = array();
        $param['store_id'] = $store_id;
        $param['class_1'] = $class_1;
        $param['storebindclass_state'] = 2;
        $param['commis_rate'] = $commis_rate;

        if (empty($class_2)) {
            //如果没选 二级
            $class_2_list = $goodsclass_model->getGoodsclassList(array('gc_parent_id' => $class_1));
            if (!empty($class_2_list)) {
                foreach ($class_2_list as $class_2_info) {
                    $class_3_list = $goodsclass_model->getGoodsclassList(array('gc_parent_id' => $class_2_info['gc_id']));
                    if (!empty($class_3_list)) {
                        $param['class_2'] = $class_2_info['gc_id'];
                        foreach ($class_3_list as $class_3_info) {
                            $param['class_3'] = $class_3_info['gc_id'];
                            $result = $this->_add_bind_class($param);
                        }
                    }
                }
            } else {
                //只有一级分类
                $param['class_2'] = $param['class_3'] = 0;
                $result = $this->_add_bind_class($param);
            }
        } else if (empty($class_3)) {
            //如果没选二没选三级
            $param['class_2'] = $class_2;
            $class_3_list = $goodsclass_model->getGoodsclassList(array('gc_parent_id' => $class_2));
            if (!empty($class_3_list)) {
                foreach ($class_3_list as $class_3_info) {
                    $param['class_3'] = $class_3_info['gc_id'];
                    // 检查类目是否已经存在
                    $store_bind_class_info = $storebindclass_model->getStorebindclassInfo($param);
                    if (empty($store_bind_class_info)) {
                        $result = $this->_add_bind_class($param);
                    }
                }
            } else {
                //二级就是最后一级
                $param['class_3'] = 0;
                $result = $this->_add_bind_class($param);
            }
        } else {
            $param['class_2'] = $class_2;
            $param['class_3'] = $class_3;
            $result = $this->_add_bind_class($param);
        }

        if ($result) {
            // 删除自营店id缓存
            model('store')->dropCachedOwnShopIds();

            $this->log('增加自营店铺经营类目，类目编号:' . $result . ',店铺编号:' . $store_id);
            $this->success(lang('ds_common_save_succ'));
        } else {
            $this->error(lang('ds_common_save_fail'));
        }
    }

    private function _add_bind_class($param) {
        $storebindclass_model = model('storebindclass');
        // 检查类目是否已经存在
        $store_bind_class_info = $storebindclass_model->getStorebindclassInfo($param);
        if (!empty($store_bind_class_info))
            return true;
        return $storebindclass_model->addStorebindclass($param);
    }

    /**
     * 删除经营类目
     */
    public function bind_class_del() {
        $bid = input('param.bid');
        $bid_array = ds_delete_param($bid);
        if ($bid_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $storebindclass_model = model('storebindclass');
        
        foreach ($bid_array as $key => $bid) {
            $store_bind_class_info = $storebindclass_model->getStorebindclassInfo(array('storebindclass_id' => $bid));
            if (empty($store_bind_class_info)) {
                ds_json_encode('10001', lang('store_bind_class_drop_fail'));
            }

            /* 自营店不下架商品
              $goods_model = model('goods');
              // 商品下架
              $condition = array();
              $condition[] = array('store_id','=',$store_bind_class_info['store_id']);
              $gc_id = $store_bind_class_info['class_1'].','.$store_bind_class_info['class_2'].','.$store_bind_class_info['class_3'];
              $update = array();
              $update['goods_stateremark'] = '管理员删除经营类目';
              $condition[]=array('gc_id','in', rtrim($gc_id, ','));
              $goods_model->editProducesLockUp($update, $condition);
             */

            $result = $storebindclass_model->delStorebindclass(array('storebindclass_id' => $bid));

            if (!$result) {
                ds_json_encode('10001', lang('store_bind_class_drop_fail'));
            }
            // 删除自营店id缓存
            model('store')->dropCachedOwnShopIds();
            $this->log('删除自营店铺经营类目，类目编号:' . $bid . ',店铺编号:' . $store_bind_class_info['store_id']);
        }
        ds_json_encode('10000', lang('ds_common_del_succ'));
        
    }


    public function bind_class_update() {
        $bid = intval(input('param.id'));
        if ($bid <= 0) {
            echo json_encode(array('result' => FALSE, 'message' => lang('param_error')));
            die;
        }
        $new_commis_rate = intval(input('get.value'));
        if ($new_commis_rate < 0 || $new_commis_rate >= 100) {
            echo json_encode(array('result' => FALSE, 'message' => lang('param_error')));
            die;
        } else {
            $update = array('commis_rate' => $new_commis_rate);
            $condition = array('storebindclass_id' => $bid);
            $storebindclass_model = model('storebindclass');
            $result = $storebindclass_model->editStorebindclass($update, $condition);
            if ($result) {
                // 删除自营店id缓存
                model('store')->dropCachedOwnShopIds();

                $this->log('更新自营店铺经营类目，类目编号:' . $bid);
                echo json_encode(array('result' => TRUE));
                die;
            } else {
                echo json_encode(array('result' => FALSE, 'message' => lang('ds_common_op_fail')));
                die;
            }
        }
    }

    /**
     * 验证店铺名称是否存在
     */
    public function ckeck_store_name() {
        $store_name = trim(input('get.store_name'));
        if (empty($store_name)) {
            echo 'false';
            exit;
        }
        $where = array();
        $where[]=array('store_name','=',$store_name);
        $store_id = input('get.store_id');
        if (isset($store_id)) {
            $where[]=array('store_id','<>', $store_id);
        }
        $store_info = model('store')->getStoreInfo($where);
        if (!empty($store_info['store_name'])) {
            echo 'false';
        } else {
            echo 'true';
        }
    }
    //ajax操作
    public function ajax() {
        $store_model = model('store');
        switch (input('param.branch')) {
            /**
             * 品牌名称
             */
            case 'store_sort':
                $id = intval(input('param.id'));
                $result = $store_model->editStore(array('store_sort'=>trim(input('param.value'))), array('store_id' => $id));
                if($result){
                    $this->log(lang('ds_edit').'自营店铺' . '[' . $id . ']', 1);
                }
                echo 'true';
                exit;
                break;
        }
    }
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_manage'),
                'url' => (string)url('Ownshop/index')
            ), array(
                'name' => 'add',
                'text' => lang('ds_new'),
                'url' => "javascript:dsLayerOpen('".(string)url('Ownshop/add')."','".lang('ds_new')."')"
            )
        );
        if (request()->action() == 'bind_class') {
            $menu_array[] = array(
                'name' => 'bind_class',
                'text' => lang('bind_class'),
                'url' => (string)url('Ownshop/bind_class')
            );
        }
        return $menu_array;
    }

}
