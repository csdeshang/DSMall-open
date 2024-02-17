<?php

/*
 * 卖家相关控制中心
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
class BaseSeller extends BaseMall {

    //店铺信息
    protected $store_info = array();

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/basemember.lang.php');
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/baseseller.lang.php');
        if(input('post.PHPSESSID') && !session('seller_id') && request()->controller() == 'SellerTaobaoImport' && request()->action() == 'upload'){
          $this->app->session->setId(input('post.PHPSESSID'));
          $this->app->session->init();
          session('limited', 1);
        }
        if(session('limited') && (request()->controller() != 'SellerTaobaoImport' || request()->action() != 'upload')){
          $this->redirect('home/Sellerlogin/login');
        }
        //卖家中心模板路径
        $this->template_dir = 'default/seller/' . strtolower(request()->controller()) . '/';
        if (request()->controller() != 'Sellerlogin') {
            if (!session('member_id')) {
                $this->redirect('home/Sellerlogin/login');
            }
            if (!session('seller_id')) {
                $this->redirect('home/Sellerlogin/login');
            }

            // 验证店铺是否存在
            $store_model = model('store');
            $this->store_info = $store_model->getStoreInfoByID(session('store_id'));
            if (empty($this->store_info)) {
                $this->redirect('home/Sellerlogin/login');
            }

            // 店铺关闭标志
            if (intval($this->store_info['store_state']) === 0) {
                View::assign('store_closed', true);
                View::assign('store_close_info', $this->store_info['store_close_info']);
            }

            // 店铺等级
            if (session('is_platform_store')) {
                $this->store_grade = array(
                    'storegrade_id' => '0',
                    'storegrade_name' => lang('exclusive_grade_stores'),
                    'storegrade_goods_limit' => '0',
                    'storegrade_album_limit' => '0',
                    'storegrade_space_limit' => '999999999',
                    'storegrade_template_number' => '6',
                    // 'storegrade_template' => 'default|style1|style2|style3|style4|style5',
                    'storegrade_price' => '0.00',
                    'storegrade_description' => '',
                    'storegrade_sort' => '255',
                );
            } else {
                $store_grade = rkcache('storegrade', true);
                $this->store_grade = @$store_grade[$this->store_info['grade_id']];
            }
            if (session('seller_is_admin') !== 1 && request()->controller() !== 'Seller' && request()->controller() !== 'Sellerlogin') {
                $this->checkPermission();
            }
        }
    }

    /**
     * 记录卖家日志
     *
     * @param $content 日志内容
     * @param $state 1成功 0失败
     */
    protected function recordSellerlog($content = '', $state = 1) {
        $seller_info = array();
        $seller_info['sellerlog_content'] = $content;
        $seller_info['sellerlog_time'] = TIMESTAMP;
        $seller_info['sellerlog_seller_id'] = session('seller_id');
        $seller_info['sellerlog_seller_name'] = session('seller_name');
        $seller_info['sellerlog_store_id'] = session('store_id');
        $seller_info['sellerlog_seller_ip'] = request()->ip();
        $seller_info['sellerlog_url'] = 'home/' . request()->controller() . '/' . request()->action();
        $seller_info['sellerlog_state'] = $state;
        $sellerlog_model = model('sellerlog');
        $sellerlog_model->addSellerlog($seller_info);
    }

    /**
     * 记录店铺费用
     *
     * @param $storecost_price 费用金额
     * @param $storecost_remark 费用备注
     */
    protected function recordStorecost($storecost_price, $storecost_remark) {
        // 平台店铺不记录店铺费用
        if (check_platform_store()) {
            return false;
        }
        $storecost_model = model('storecost');
        $param = array();
        $param['storecost_store_id'] = session('store_id');
        $param['storecost_seller_id'] = session('seller_id');
        $param['storecost_price'] = $storecost_price;
        $param['storecost_remark'] = $storecost_remark;
        $param['storecost_state'] = 0;
        $param['storecost_time'] = TIMESTAMP;
        $storecost_model->addStorecost($param);

        // 发送店铺消息
        $param = array();
        $param['code'] = 'store_cost';
        $param['store_id'] = session('store_id');
        $param['ali_param'] = array(
            'price' => $storecost_price,
            'seller_name' => session('seller_name'),
            'remark' => $storecost_remark
        );
        $param['ten_param'] = array(
            $storecost_price,
            session('seller_name'),
            $storecost_remark
        );
        $param['param'] = $param['ali_param'];
        //微信模板消息
        $param['weixin_param'] = array(
            'url' => config('ds_config.h5_store_site_url') . '/pages/seller/cost/CostList',
            'data' => array(
                "keyword1" => array(
                    "value" => $storecost_price,
                    "color" => "#333"
                ),
                "keyword2" => array(
                    "value" => date('Y-m-d H:i'),
                    "color" => "#333"
                )
            ),
        );
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize($param)));
    }

    /**
     * 添加到任务队列
     *
     * @param array $goods_array
     * @param boolean $ifdel 是否删除以原记录
     */
    protected function addcron($data = array(), $ifdel = false) {
        $cron_model = model('cron');
        if (isset($data[0])) { // 批量插入
            $where = array();
            foreach ($data as $k => $v) {
                // 删除原纪录条件
                if ($ifdel) {
                    $where[] = '(cron_type = "' . $data['cron_type'] . '" and cron_value = "' . $data['cron_value'] . '")';
                }
            }
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(implode(',', $where));
            }
            $cron_model->addCronAll($data);
        } else { // 单条插入
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(array('cron_type' => $data['cron_type'], 'cron_value' => $data['cron_value']));
            }
            $cron_model->addCron($data);
        }
    }

    /**
     *    当前选中的栏目
     */
    protected function setSellerCurItem($curitem = '') {
        View::assign('seller_item', $this->getSellerItemList());
        View::assign('curitem', $curitem);
    }

    /**
     *    当前选中的子菜单
     */
    protected function setSellerCurMenu($cursubmenu = '') {
        $seller_menu = self::getSellerMenuList($this->store_info['is_platform_store']);
        $seller_menu=$this->parseMenu($seller_menu);
        View::assign('seller_menu', $seller_menu);
        $curmenu = '';
        foreach ($seller_menu as $key => $menu) {
            foreach ($menu['submenu'] as $subkey => $submenu) {
                if ($submenu['name'] == $cursubmenu) {
                    $curmenu = $menu['name'];
                }
            }
        }
        //当前一级菜单
        View::assign('curmenu', $curmenu);
        //当前二级菜单
        View::assign('cursubmenu', $cursubmenu);
    }

    /*
     * 获取卖家栏目列表,针对控制器下的栏目
     */

    protected function getSellerItemList() {
        return array();
    }

    /**
     * 验证当前管理员权限是否可以进行操作
     *
     * @param string $link_nav
     * @return
     */
    protected final function checkPermission($link_nav = null) {
        if (session('seller_is_admin') == 1)
            return true;

        $controller = request()->controller();
        $action = request()->action();
        if (!empty(session('seller_limits'))) {
            $permission = session('seller_limits');
            //显示隐藏小导航，成功与否都直接返回
            if (is_array($link_nav)) {
                if (!in_array("{$link_nav['controller']}.{$link_nav['action']}", $permission) && !in_array($link_nav['controller'], $permission)) {
                    return false;
                } else {
                    return true;
                }
            }
            //以下几项不需要验证
            $tmp = array();
            if (in_array($controller, $tmp)) {
                return true;
            }
            if (in_array($controller, $permission) || in_array("$controller.$action", $permission)) {
                return true;
            } else {
                $extlimit = array('ajax', 'export_step1');
                if (in_array($action, $extlimit) && (in_array($controller, $permission) || strpos(serialize($permission), '"' . $controller . '.'))) {
                    return true;
                }
                //带前缀的都通过
                foreach ($permission as $v) {
                    if (!empty($v) && strpos("$controller.$action", $v . '_') !== false) {
                        return true;
                        break;
                    }
                }
            }
        }

        $this->error(lang('have_no_legalpower'), 'Seller/index');
    }

    /**
     * 过滤掉无权查看的菜单
     *
     * @param array $menu
     * @return array
     */
    private final function parseMenu($menu = array()) {
        if (session('seller_is_admin') === 1) {
            return $menu;
        }
        foreach ($menu as $k => $v) {
            foreach ($v['submenu'] as $ck => $cv) {
                $tmp = array($cv['action'], $cv['controller']);
                //以下几项不需要验证
                $except = array();
                if (in_array($tmp[1], $except))
                    continue;
                if (!in_array($tmp[1], session('seller_limits')) && !in_array($tmp[1] . '.' . $tmp[0], session('seller_limits'))) {
                    unset($menu[$k]['submenu'][$ck]);
                }
            }
            if (empty($menu[$k]['submenu'])) {
                unset($menu[$k]);
                unset($menu[$k]['submenu']);
            } else {
                $temp=current($menu[$k]['submenu']);
                $menu[$k]['url'] = $temp['url'];
            }
        }
        return $menu;
    }

    /*
     * 获取卖家菜单列表
     */

    public static function getSellerMenuList($is_platform_store = 0) {
        //controller  注意第一个字母要大写
        $menu_list = array(
            'sellergoods' =>
            array(
                'ico' => '&#xe732;',
                'name' => 'sellergoods',
                'text' => lang('site_search_goods'),
                'url' => (string) url('Sellergoodsonline/index'),
                'submenu' => array(
                    array('name' => 'sellergoodsadd', 'text' => lang('goods_released'), 'action' => null, 'controller' => 'Sellergoodsadd', 'url' => (string) url('Sellergoodsadd/index'),),
                    array('name' => 'seller_taobao_import', 'text' => lang('taobao_import'), 'action' => null, 'controller' => 'SellerTaobaoImport', 'url' => (string) url('SellerTaobaoImport/index'),),
                    array('name' => 'sellergoodsonline', 'text' => lang('goods_on_sale'), 'action' => null, 'controller' => 'Sellergoodsonline', 'url' => (string) url('Sellergoodsonline/index'),),
                    array('name' => 'sellergoodsoffline', 'text' => lang('warehouse_goods'), 'action' => null, 'controller' => 'Sellergoodsoffline', 'url' => (string) url('Sellergoodsoffline/index'),),
                    array('name' => 'sellerplate', 'text' => lang('associated_format'), 'action' => null, 'controller' => 'Sellerplate', 'url' => (string) url('Sellerplate/index'),),
                    array('name' => 'sellerspec', 'text' => lang('product_specifications'), 'action' => null, 'controller' => 'Sellerspec', 'url' => (string) url('Sellerspec/index'),),
                    array('name' => 'selleralbum', 'text' => lang('image_space'), 'action' => null, 'controller' => 'Selleralbum', 'url' => (string) url('Selleralbum/index'),),
                    array('name' => 'sellervideo', 'text' => lang('seller_goodsvideo'), 'action' => null, 'controller' => 'Sellervideo', 'url' => (string) url('Sellervideo/index'),),
                    array('name' => 'seller_service', 'text' => lang('seller_service'), 'action' => null, 'controller' => 'SellerService', 'url' => (string) url('SellerService/index'),),
                )
            ),
            'sellerorder' =>
            array(
                'ico' => '&#xe71f;',
                'name' => 'sellerorder',
                'text' => lang('pointsorderdesc_1'),
                'url' => (string) url('Sellerorder/index'),
                'submenu' => array(
                    array('name' => 'sellerorder', 'text' => lang('order_physical_transaction'), 'action' => null, 'controller' => 'Sellerorder', 'url' => (string) url('Sellerorder/index'),),
                    array('name' => 'sellervrorder', 'text' => lang('code_order'), 'action' => null, 'controller' => 'Sellervrorder', 'url' => (string) url('Sellervrorder/index'),),
                    array('name' => 'sellerdeliver', 'text' => lang('delivery_management'), 'action' => null, 'controller' => 'Sellerdeliver', 'url' => (string) url('Sellerdeliver/index'),),
                    array('name' => 'sellerdeliverset', 'text' => lang('delivery_settings'), 'action' => null, 'controller' => 'Sellerdeliverset', 'url' => (string) url('Sellerdeliverset/index'),),
                    array('name' => 'sellerevaluate', 'text' => lang('evaluation_management'), 'action' => null, 'controller' => 'Sellerevaluate', 'url' => (string) url('Sellerevaluate/index'),),
                    array('name' => 'sellertransport', 'text' => lang('sales_area'), 'action' => null, 'controller' => 'Sellertransport', 'url' => (string) url('Sellertransport/index'),),
                    array('name' => 'Sellerbill', 'text' => lang('physical_settlement'), 'action' => null, 'controller' => 'Sellerbill', 'url' => (string) url('Sellerbill/index'),),
                )
            ),
            'sellergroupbuy' =>
            array(
                'ico' => '&#xe704;',
                'name' => 'sellergroupbuy',
                'text' => lang('sales_promotion'),
                'url' => (string) url('Sellergroupbuy/index'),
                'submenu' => array(
                    array('name' => 'Sellerpromotionwholesale', 'text' => lang('wholesale_management'), 'action' => null, 'controller' => 'Sellerpromotionwholesale', 'url' => (string) url('Sellerpromotionwholesale/index'),),
                    array('name' => 'Sellergroupbuy', 'text' => lang('snap_up_management'), 'action' => null, 'controller' => 'Sellergroupbuy', 'url' => (string) url('Sellergroupbuy/index'),),
                    array('name' => 'Sellerpromotionxianshi', 'text' => lang('time_discount'), 'action' => null, 'controller' => 'Sellerpromotionxianshi', 'url' => (string) url('Sellerpromotionxianshi/index'),),
                    array('name' => 'Sellermgdiscount', 'text' => lang('membership_level_discount'), 'action' => null, 'controller' => 'Sellerpromotionmgdiscount', 'url' => (string) url('Sellerpromotionmgdiscount/mgdiscount_store'),),
                    array('name' => 'Sellerpromotionpintuan', 'text' => lang('syndication'), 'action' => null, 'controller' => 'Sellerpromotionpintuan', 'url' => (string) url('Sellerpromotionpintuan/index'),),
                    array('name' => 'Sellerpromotionbargain', 'text' => lang('baseseller_bargain'), 'action' => null, 'controller' => 'Sellerpromotionbargain', 'url' => (string) url('Sellerpromotionbargain/index'),),
                    array('name' => 'Sellerpromotionpresell', 'text' => lang('baseseller_presell'), 'action' => null, 'controller' => 'Sellerpromotionpresell', 'url' => (string) url('Sellerpromotionpresell/index'),),
                    array('name' => 'Sellerpromotionmansong', 'text' => lang('free_on_delivery'), 'action' => null, 'controller' => 'Sellerpromotionmansong', 'url' => (string) url('Sellerpromotionmansong/index'),),
                    array('name' => 'Sellerpromotionbundling', 'text' => lang('discount_package'), 'action' => null, 'controller' => 'Sellerpromotionbundling', 'url' => (string) url('Sellerpromotionbundling/index'),),
                    array('name' => 'Sellerpromotionbooth', 'text' => lang('recommended_stand'), 'action' => null, 'controller' => 'Sellerpromotionbooth', 'url' => (string) url('Sellerpromotionbooth/index'),),
                    array('name' => 'Sellervoucher', 'text' => lang('voucher_management'), 'action' => null, 'controller' => 'Sellervoucher', 'url' => (string) url('Sellervoucher/templatelist'),),
                    array('name' => 'Selleractivity', 'text' => lang('activity_management'), 'action' => null, 'controller' => 'Selleractivity', 'url' => (string) url('Selleractivity/index'),),
                )
            ),
            'seller' =>
            array(
                'ico' => '&#xe663;',
                'name' => 'seller',
                'text' => lang('site_search_store'),
                'url' => (string) url('Seller/index'),
                'submenu' => array(
                    array('name' => 'seller_index', 'text' => lang('store_overview'), 'action' => null, 'controller' => 'Seller', 'url' => (string) url('Seller/index'),),
                    array('name' => 'seller_setting', 'text' => lang('store_setup'), 'action' => null, 'controller' => 'Sellersetting', 'url' => (string) url('Sellersetting/setting'),),
                    array('name' => 'seller_editable_page_pc', 'text' => lang('store_editable_page_pc'), 'action' => 'page_list', 'controller' => 'SellerEditablePage', 'url' => (string) url('SellerEditablePage/page_list'),),
                    array('name' => 'seller_editable_page_h5', 'text' => lang('store_editable_page_h5'), 'action' => 'h5_page_list', 'controller' => 'SellerEditablePage', 'url' => (string) url('SellerEditablePage/h5_page_list'),),
                    array('name' => 'seller_navigation', 'text' => lang('store_navigation'), 'action' => null, 'controller' => 'Sellernavigation', 'url' => (string) url('Sellernavigation/index'),),
                    array('name' => 'sellersns', 'text' => lang('store_dynamics'), 'action' => null, 'controller' => 'Sellersns', 'url' => (string) url('Sellersns/index'),),
                    array('name' => 'sellergoodsclass', 'text' => lang('store_classification'), 'action' => null, 'controller' => 'Sellergoodsclass', 'url' => (string) url('Sellergoodsclass/index'),),
                    array('name' => 'seller_chain', 'text' => lang('seller_chain'), 'action' => null, 'controller' => 'SellerChain', 'url' => (string) url('SellerChain/index'),),
                    array('name' => 'seller_brand', 'text' => lang('brand_application'), 'action' => null, 'controller' => 'Sellerbrand', 'url' => (string) url('Sellerbrand/index'),),
                )
            ),
            'sellerconsult' =>
            array(
                'ico' => '&#xe6ab;',
                'name' => 'sellerconsult',
                'text' => lang('after_sales_service'),
                'url' => (string) url('Sellerconsult/index'),
                'submenu' => array(
                    array('name' => 'seller_consult', 'text' => lang('consulting_management'), 'action' => null, 'controller' => 'Sellerconsult', 'url' => (string) url('Sellerconsult/index'),),
                    array('name' => 'seller_complain', 'text' => lang('complaint_record'), 'action' => null, 'controller' => 'Sellercomplain', 'url' => (string) url('Sellercomplain/index'),),
                    array('name' => 'seller_refund', 'text' => lang('refund_paragraph'), 'action' => null, 'controller' => 'Sellerrefund', 'url' => (string) url('Sellerrefund/index'),),
                    array('name' => 'seller_return', 'text' => lang('refund_cargo'), 'action' => null, 'controller' => 'Sellerreturn', 'url' => (string) url('Sellerreturn/index'),),
                )
            ),
            'sellerstatistics' =>
            array(
                'ico' => '&#xe6a3;',
                'name' => 'sellerstatistics',
                'text' => lang('statistics'),
                'url' => (string) url('Statisticsgeneral/index'),
                'submenu' => array(
                    array('name' => 'Statisticsgeneral', 'text' => lang('store_overview'), 'action' => null, 'controller' => 'Statisticsgeneral', 'url' => (string) url('Statisticsgeneral/index'),),
                    array('name' => 'Statisticsgoods', 'text' => lang('commodity_analysis'), 'action' => null, 'controller' => 'Statisticsgoods', 'url' => (string) url('Statisticsgoods/index'),),
                    array('name' => 'Statisticssale', 'text' => lang('operational_report'), 'action' => null, 'controller' => 'Statisticssale', 'url' => (string) url('Statisticssale/index'),),
                    array('name' => 'Statisticsindustry', 'text' => lang('industry_analysis'), 'action' => null, 'controller' => 'Statisticsindustry', 'url' => (string) url('Statisticsindustry/index'),),
                    array('name' => 'Statisticsflow', 'text' => lang('traffic_statistics'), 'action' => null, 'controller' => 'Statisticsflow', 'url' => (string) url('Statisticsflow/index'),),
                )
            ),
            'sellercallcenter' =>
            array(
                'ico' => '&#xe61c;',
                'name' => 'sellercallcenter',
                'text' => lang('news_service'),
                'url' => (string) url('Sellercallcenter/index'),
                'submenu' => array(
                    array('name' => 'Sellercallcenter', 'text' => lang('setting_service'), 'action' => null, 'controller' => 'Sellercallcenter', 'url' => (string) url('Sellercallcenter/index'),),
                    array('name' => 'Sellermsg', 'text' => lang('system_message'), 'action' => null, 'controller' => 'Sellermsg', 'url' => (string) url('Sellermsg/index'),),
                    array('name' => 'seller_instant_message', 'text' => lang('chat_query'), 'action' => null, 'controller' => 'SellerInstantMessage', 'url' => (string) url('SellerInstantMessage/index'),),
                )
            ),
            'selleraccount' =>
            array(
                'ico' => '&#xe702;',
                'name' => 'selleraccount',
                'text' => lang('account'),
                'url' => (string) url('Selleraccount/account_list'),
                'submenu' => array(
                    array('name' => 'selleraccount', 'text' => lang('account_list'), 'action' => null, 'controller' => 'Selleraccount', 'url' => (string) url('Selleraccount/account_list'),),
                    array('name' => 'selleraccountgroup', 'text' => lang('account_group'), 'action' => null, 'controller' => 'Selleraccountgroup', 'url' => (string) url('Selleraccountgroup/group_list'),),
                    array('name' => 'sellerlog', 'text' => lang('account_log'), 'action' => null, 'controller' => 'Sellerlog', 'url' => (string) url('Sellerlog/log_list'),),
                )
            ),
        );
        if (!$is_platform_store) {
            $menu_list['seller']['submenu'] = array_merge(array(array('name' => 'seller_money', 'text' => lang('store_money'), 'action' => null, 'controller' => 'Sellermoney', 'url' => (string) url('Sellermoney/index'),), array('name' => 'seller_deposit', 'text' => lang('store_deposit'), 'action' => null, 'controller' => 'Sellerdeposit', 'url' => (string) url('Sellerdeposit/index'),),array('name' => 'sellerinfo', 'text' => lang('store_information'), 'action' => null, 'controller' => 'Sellerinfo', 'url' => (string) url('Sellerinfo/index'),),), $menu_list['seller']['submenu']);
            $menu_list['selleraccount']['submenu'] = array_merge(array(array('name' => 'sellercost', 'text' => lang('store_consumption'), 'action' => null, 'controller' => 'Sellercost', 'url' => (string) url('Sellercost/cost_list'),)), $menu_list['selleraccount']['submenu']);
        }
        if (config('ds_config.inviter_open')) {
            $menu_list['sellerinviter'] = array(
                'ico' => '&#xe6ed;',
                'name' => 'sellerinviter',
                'text' => lang('distribution'),
                'url' => (string) url('Sellerinviter/goods_list'),
                'submenu' => array(
                    array('name' => 'sellerinviter_goods', 'text' => lang('distribution_management'), 'action' => 'goods_list', 'controller' => 'Sellerinviter', 'url' => (string) url('Sellerinviter/goods_list'),),
                    array('name' => 'sellerinviter_order', 'text' => lang('distribution_earnings'), 'action' => 'order_list', 'controller' => 'Sellerinviter', 'url' => (string) url('Sellerinviter/order_list'),),
                )
            );
        }
        return $menu_list;
    }

    /**
     * 自动发布店铺动态
     *
     * @param array $data 相关数据
     * @param string $type 类型 'new','coupon','xianshi','mansong','bundling','groupbuy'
     *            所需字段
     *            new       goods表'             goods_id,store_id,goods_name,goods_image,goods_price,goods_transfee_charge,goods_freight
     *            xianshi   pxianshigoods表'   goods_id,store_id,goods_name,goods_image,goods_price,goods_freight,xianshi_price
     *            mansong   pmansong表'         mansong_name,start_time,end_time,store_id
     *            bundling  pbundling表'        bl_id,bl_name,bl_img,bl_discount_price,bl_freight_choose,bl_freight,store_id
     *            groupbuy  goodsgroup表'       group_id,group_name,goods_id,goods_price,groupbuy_price,group_pic,rebate,start_time,end_time
     *            coupon在后台发布
     */
    public function storeAutoShare($data, $type) {
        $param = array(
            3 => 'new',
            4 => 'coupon',
            5 => 'xianshi',
            6 => 'mansong',
            7 => 'bundling',
            8 => 'groupbuy'
        );
        $param_flip = array_flip($param);
        if (!in_array($type, $param) || empty($data)) {
            return false;
        }

        $auto_setting = model('storesnssetting')->getStoresnssettingInfo(array('storesnsset_storeid' => session('store_id')));
        $auto_sign = false; // 自动发布开启标志

        if ($auto_setting['storesnsset_' . $type] == 1) {
            $auto_sign = true;

            $goodsdata = addslashes(json_encode($data));
            if ($auto_setting['storesnsset_' . $type . 'title'] != '') {
                $title = $auto_setting['storesnsset_' . $type . 'title'];
            } else {
                $auto_title = 'ds_store_auto_share_' . $type . rand(1, 5);
                $title = lang($auto_title);
            }
        }
        if ($auto_sign) {
            // 插入数据
            $stracelog_array = array();
            $stracelog_array['stracelog_storeid'] = $this->store_info['store_id'];
            $stracelog_array['stracelog_storename'] = $this->store_info['store_name'];
            $stracelog_array['stracelog_storelogo'] = empty($this->store_info['store_avatar']) ? '' : $this->store_info['store_avatar'];
            $stracelog_array['stracelog_title'] = $title;
            $stracelog_array['stracelog_content'] = '';
            $stracelog_array['stracelog_time'] = TIMESTAMP;
            $stracelog_array['stracelog_type'] = $param_flip[$type];
            $stracelog_array['stracelog_goodsdata'] = $goodsdata;
            model('storesnstracelog')->addStoresnstracelog($stracelog_array);
            return true;
        } else {
            return false;
        }
    }

}

?>
