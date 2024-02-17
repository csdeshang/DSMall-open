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
class Store extends BaseStore {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/store.lang.php');
    }

    public function index() {
        $editable_page_model = model('editable_page');
        $editable_page_info = $editable_page_model->getOneEditablePage(array('store_id' => $this->store_info['store_id'], 'editable_page_path' => 'store/index', 'editable_page_client' => 'pc'));
        if ($editable_page_info) {
            $editable_page_info['editable_page_theme_config'] = json_decode($editable_page_info['editable_page_theme_config'], true);
            View::assign('editable_page', $editable_page_info);
            $editable_page_config_model = model('editable_page_config');
            $editable_page_config_list=$editable_page_config_model->getEditablePageConfigList(array(array('editable_page_id', '=', $editable_page_info['editable_page_id'])));
            $config_list=array();
            foreach($editable_page_config_list as $key => $val){
                $config_info=json_decode($val['editable_page_config_content'], true);
                $model_id=$val['editable_page_model_id'];
                $var_html=array();
            if(!empty($config_info)){
                require_once PLUGINS_PATH.'/editable_page_model/'.$model_id.'/config.php';
                $model_name='Model'.$model_id;
                $model=new $model_name();
                $res=$model->filterData($config_info);
                if($res['code']){
                    $res=$model->formatData(json_encode($res['data']),$this->store_info['store_id']);
                    if($res['code']){
                        $var_html['config_info']=$res['data'];
                    }
                }
                
            }
            $html=View::fetch('../../../plugins/editable_page_model/'.$model_id.'/index',$var_html);
                $config_list[]=array(
                    'val'=>$val,
                    'html'=>$html,
                );
            }
            View::assign('config_list', $config_list);
            View::assign('editable_page', $editable_page_info);
        } else {
            $condition = array();
            $condition[]=array('store_id','=',$this->store_info['store_id']);

            $goods_model = model('goods'); // 字段
            $fieldstr = "goods_id,goods_commonid,goods_name,goods_advword,store_id,store_name,goods_price,goods_promotion_price,goods_marketprice,goods_storage,goods_image,goods_freight,goods_salenum,color_id,evaluation_good_star,evaluation_count,goods_promotion_type";
            //得到最新12个商品列表
            $new_goods_list = $goods_model->getGoodsListByColorDistinct($condition, $fieldstr, 'goods_id desc', 12);

            $condition[]=array('goods_commend','=',1);
            //得到12个推荐商品列表
            $recommended_goods_list = $goods_model->getGoodsListByColorDistinct($condition, $fieldstr, 'goods_sort desc,goods_id desc', 12);
            $goods_list = $this->getGoodsMore($new_goods_list, $recommended_goods_list);
            View::assign('new_goods_list', $goods_list[1]);
            View::assign('recommended_goods_list', $goods_list[2]);

            //幻灯片图片
            if ($this->store_info['store_slide'] != '' && $this->store_info['store_slide'] != ',,,,') {
                View::assign('store_slide', explode(',', $this->store_info['store_slide']));
                View::assign('store_slide_url', explode(',', $this->store_info['store_slide_url']));
            }
        }
        View::assign('page', 'index');
        return View::fetch($this->template_dir . 'index');
    }

    private function getGoodsMore($goods_list1, $goods_list2 = array()) {
        if (!empty($goods_list2)) {
            $goods_list = array_merge($goods_list1, $goods_list2);
        } else {
            $goods_list = $goods_list1;
        }
        // 商品多图
        if (!empty($goods_list)) {
            $goodsid_array = array();       // 商品id数组
            $commonid_array = array(); // 商品公共id数组
            $storeid_array = array();       // 店铺id数组
            foreach ($goods_list as $value) {
                $goodsid_array[] = $value['goods_id'];
                $commonid_array[] = $value['goods_commonid'];
                $storeid_array[] = $value['store_id'];
            }
            $goodsid_array = array_unique($goodsid_array);
            $commonid_array = array_unique($commonid_array);

            // 商品多图
            $goodsimage_more = model('goods')->getGoodsImageList(array(array('goods_commonid', 'in', $commonid_array)));

            foreach ($goods_list1 as $key => $value) {
                // 商品多图
                foreach ($goodsimage_more as $v) {
                    if ($value['goods_commonid'] == $v['goods_commonid'] && $value['store_id'] == $v['store_id'] && $value['color_id'] == $v['color_id']) {
                        $goods_list1[$key]['image'][] = $v;
                    }
                }
            }

            if (!empty($goods_list2)) {
                foreach ($goods_list2 as $key => $value) {
                    // 商品多图
                    foreach ($goodsimage_more as $v) {
                        if ($value['goods_commonid'] == $v['goods_commonid'] && $value['store_id'] == $v['store_id'] && $value['color_id'] == $v['color_id']) {
                            $goods_list2[$key]['image'][] = $v;
                        }
                    }
                }
            }
        }
        return array(1 => $goods_list1, 2 => $goods_list2);
    }

    public function article() {
        //判断是否为导航页面
        $storenavigation_model = model('storenavigation');
        $store_navigation_info = $storenavigation_model->getStorenavigationInfo(array('storenav_id' => intval(input('param.storenav_id'))));
        if (!empty($store_navigation_info) && is_array($store_navigation_info)) {
            View::assign('store_navigation_info', $store_navigation_info);
            return View::fetch($this->template_dir . 'article');
        }
    }

    /**
     * 全部商品
     */
    public function goods_all() {

        $condition = array();
        $condition[] = array('store_id', '=', $this->store_info['store_id']);
        $inkeyword = trim(input('inkeyword'));
        if ($inkeyword != '') {
            $condition[] = array('goods_name', 'like', '%' . $inkeyword . '%');
        }

        // 排序
        $order = input('order');
        $order = $order == 1 ? 'asc' : 'desc';
        $key = trim(input('key'));
        switch ($key) {
            case '1':
                $order = 'goods_id ' . $order;
                break;
            case '2':
                $order = 'goods_promotion_price ' . $order;
                break;
            case '3':
                $order = 'goods_salenum ' . $order;
                break;
            case '4':
                $order = 'goods_collect ' . $order;
                break;
            case '5':
                $order = 'goods_click ' . $order;
                break;
            default:
                $order = 'goods_id desc';
                break;
        }

        //查询分类下的子分类
        $storegc_id = intval(input('storegc_id'));
        if ($storegc_id > 0) {
            $condition[] = array('goods_stcids', 'like', '%,' . $storegc_id . ',%');
        }

        $goods_model = model('goods');
        $fieldstr = "goods_id,goods_commonid,goods_name,goods_advword,store_id,store_name,goods_price,goods_promotion_price,goods_marketprice,goods_storage,goods_image,goods_freight,goods_salenum,color_id,evaluation_good_star,evaluation_count,goods_promotion_type";

        $recommended_goods_list = $goods_model->getGoodsListByColorDistinct($condition, $fieldstr, $order, 24);
        $recommended_goods_list = $this->getGoodsMore($recommended_goods_list);
        View::assign('recommended_goods_list', $recommended_goods_list[1]);

        /* 引用搜索相关函数 */
        require_once(base_path() . '/home/common_search.php');

        //输出分页
        View::assign('show_page', empty($recommended_goods_list[1]) ? '' : $goods_model->page_info->render());
        $stc_class = model('storegoodsclass');
        $stc_info = $stc_class->getStoregoodsclassInfo(array('storegc_id' => $storegc_id));
        View::assign('storegc_name', $stc_info['storegc_name']);
        View::assign('page', 'index');

        return View::fetch($this->template_dir . 'goods_list');
    }

    /**
     * ajax获取动态数量
     */
    function ajax_store_trend_count() {
        $count = model('storesnstracelog')->getStoresnstracelogCount(array('stracelog_storeid' => $this->store_info['store_id']));
        echo json_encode(array('count' => $count));
        exit;
    }

    /**
     * ajax 店铺流量统计入库
     */
    public function ajax_flowstat_record() {
        $store_id = intval(input('param.store_id'));
        $goods_id = intval(input('param.goods_id'));
        $controller_param = input('param.controller_param');
        $action_param = input('param.action_param');
        $store_info = model('store')->getStoreOnlineInfoByID(session('store_id'));
        model('store')->flowstat_record($store_id,$goods_id,$controller_param,$action_param,$store_info);
    }

}
