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
class Statgeneral extends AdminControl
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        include_once root_path(). 'extend/mall/statistics.php';
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/stat.lang.php');
    }

    /**
     * 促销分析
     */
    public function general()
    {
        $stat_model = model('stat');
        //统计的日期0点
        $stat_time = strtotime(date('Y-m-d', TIMESTAMP)) - 86400;
        /*
         * 昨日最新情报
         */
        $stime = $stat_time;
        $etime = $stat_time + 86400 - 1;

        $statnew_arr = array();

        //查询订单表下单量、下单金额、下单客户数、平均客单价
        $where = array();
        $where[] = array('order_isvalid','=',1); //计入统计的有效订单
        $where[] = array('order_add_time','between',array($stime, $etime));
        $field = ' COUNT(*) as ordernum, SUM(order_amount) as orderamount, COUNT(DISTINCT buyer_id) as ordermembernum, AVG(order_amount) as orderavg ';
        $stat_order = $stat_model->getoneByStatorder($where, $field);
        $statnew_arr['ordernum'] = ($t = $stat_order['ordernum']) ? $t : 0;
        $statnew_arr['orderamount'] = ds_price_format(($t = $stat_order['orderamount']) ? $t : (0));
        $statnew_arr['ordermembernum'] = ($t = $stat_order['ordermembernum']) ? $t : 0;
        $statnew_arr['orderavg'] = ds_price_format(($t = $stat_order['orderavg']) ? $t : 0);
        unset($stat_order);

        //查询订单商品表下单商品数
        $where = array();
        $where[] = array('order_isvalid','=',1);//计入统计的有效订单
        $where[] = array('order_add_time','between',array($stime, $etime));
        $field = ' SUM(goods_num) as ordergoodsnum,AVG(goods_pay_price/goods_num) as priceavg ';
        $stat_ordergoods = $stat_model->getoneByStatordergoods($where, $field);
        $statnew_arr['ordergoodsnum'] = ($t = $stat_ordergoods['ordergoodsnum']) ? $t : 0;
        $statnew_arr['priceavg'] = ds_price_format(($t = $stat_ordergoods['priceavg']) ? $t : 0);
        unset($stat_ordergoods);

        //新增会员数
        $where = array();
        $where[] = array('member_addtime','between',array($stime, $etime));
        $field = ' COUNT(*) as newmember ';
        $stat_member = $stat_model->getOneByMember($where, $field);
        $statnew_arr['newmember'] = ($t = $stat_member['newmember']) ? $t : 0;
        unset($stat_member);

        //会员总数
        $where = array();
        $field = ' COUNT(*) as membernum ';
        $stat_member = $stat_model->getOneByMember($where, $field);
        $statnew_arr['membernum'] = ($t = $stat_member['membernum']) ? $t : 0;
        unset($stat_member);

        //新增店铺
        $where = array();
        $where[] = array('store_addtime','between',array($stime, $etime));
        $field = ' COUNT(*) as newstore ';
        $stat_store = $stat_model->getOneByStore($where, $field);
        $statnew_arr['newstore'] = ($t = $stat_store['newstore']) ? $t : 0;
        unset($stat_store);

        //店铺总数
        $where = array();
        $field = ' COUNT(*) as storenum ';
        $stat_store = $stat_model->getOneByStore($where, $field);
        $statnew_arr['storenum'] = ($t = $stat_store['storenum']) ? $t : 0;
        unset($stat_store);

        //新增商品，商品总数
        $goods_list = $stat_model->statByGoods(array('is_virtual' => 0), "COUNT(*) as goodsnum, SUM(IF(goods_addtime>=$stime and goods_addtime<=$etime,'1',0)) as newgoods");
        $statnew_arr['goodsnum'] = ($t = $goods_list[0]['goodsnum']) > 0 ? $t : 0;
        $statnew_arr['newgoods'] = ($t = $goods_list[0]['newgoods']) > 0 ? $t : 0;

        /*
         * 昨日销售走势
         */
        //构造横轴数据
        for ($i = 0; $i < 24; $i++) {
            //统计图数据
            $curr_arr[$i] = 0;//今天
            $up_arr[$i] = 0;//昨天
            //横轴
            $stat_arr['xAxis']['categories'][] = "$i";
        }
        $stime = $stat_time - 86400;//昨天0点
        $etime = $stat_time + 86400 - 1;//今天24点
        $yesterday_day = @date('d', $stime);//昨天日期
        $today_day = @date('d', $etime);//今天日期
        $where = array();
        $where[] = array('order_isvalid','=',1);//计入统计的有效订单
        $where[] = array('order_add_time','between',array($stime, $etime));
        $field = ' SUM(order_amount) as orderamount,DAY(FROM_UNIXTIME(order_add_time)) as dayval,HOUR(FROM_UNIXTIME(order_add_time)) as hourval ';
        $stat_order = $stat_model->statByStatorder($where, $field, 0, 0, '', 'dayval,hourval');
        if ($stat_order) {
            foreach ($stat_order as $k => $v) {
                if ($today_day == $v['dayval']) {
                    $curr_arr[$v['hourval']] = intval($v['orderamount']);
                }
                if ($yesterday_day == $v['dayval']) {
                    $up_arr[$v['hourval']] = intval($v['orderamount']);
                }
            }
        }
        $stat_arr['series'][0]['name'] = lang('yestoday');
        $stat_arr['series'][0]['data'] = array_values($up_arr);
        $stat_arr['series'][1]['name'] = lang('today');
        $stat_arr['series'][1]['data'] = array_values($curr_arr);
        //得到统计图数据
        $stat_arr['title'] = date('Y-m-d', $stat_time) . lang('sale_trend');
        $stat_arr['yAxis'] = lang('stattrade_order_amount');
        $stattoday_json = getStatData_LineLabels($stat_arr);
        unset($stat_arr);

        /*
         * 7日内店铺销售TOP30
         */
        $stime = $stat_time - 86400 * 6;//7天前0点
        $etime = $stat_time + 86400 - 1;//今天24点
        $where = array();
        $where[] = array('order_isvalid','=',1);//计入统计的有效订单
        $where[] = array('order_add_time','between',array($stime, $etime));
        $field = ' SUM(order_amount) as orderamount, store_id, store_name ';
        $storetop30_arr = $stat_model->statByStatorder($where, $field, 0, 0, 'orderamount desc', 'store_id');

        /*
         * 7日内商品销售TOP30
         */
        $stime = $stat_time - 86400 * 6;//7天前0点
        $etime = $stat_time + 86400 - 1;//今天24点
        $where = array();
        $where[] = array('order_isvalid','=',1);//计入统计的有效订单
        $where[] = array('order_add_time','between',array($stime, $etime));
        $field = ' sum(goods_num) as ordergoodsnum, goods_id, goods_name ';
        $goodstop30_arr = $stat_model->statByStatordergoods($where, $field, 0, 30, 'ordergoodsnum desc', 'goods_id');
        View::assign('goodstop30_arr', $goodstop30_arr);
        View::assign('storetop30_arr', $storetop30_arr);
        View::assign('stattoday_json', $stattoday_json);
        View::assign('statnew_arr', $statnew_arr);
        View::assign('stat_time', $stat_time);
        $this->setAdminCurItem('general');
        return View::fetch();
    }

    /**
     * 统计设置
     */
    public function setting()
    {
        $config_model = model('config');
        if (request()->isPost()) {
            $update_array = array();
            
            $pricerange_temp_array = input('post.pricerange/a');
            if (is_array($pricerange_temp_array)) {
                foreach ($pricerange_temp_array as $k => $v) {
                    if(!is_numeric($v['s']) || !is_numeric($v['e'])){
                      $this->error(lang('is_numeric_error'));
                    }
                    if($v['s']<0 || $v['e']<0){
                      $this->error(lang('is_zero_error'));
                    }
                    if($v['s']>$v['e']){
                      $this->error(lang('amount_set_error'));
                    }
                    $pricerange_arr[] = $v;
                }
                $update_array['stat_pricerange'] = serialize($pricerange_arr);
            } else {
                $update_array['stat_pricerange'] = '';
            }
            $result = $config_model->editConfig($update_array);
            if ($result === true) {
                $this->log(lang('ds_edit') . lang('stat_setting'), 1);
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->log(lang('ds_edit') . lang('stat_setting'), 0);
                $this->error(lang('ds_common_save_fail'));
            }
        } else {
            $list_setting = rkcache('config', true);
            $list_setting['stat_pricerange'] = unserialize($list_setting['stat_pricerange']);
            View::assign('list_setting', $list_setting);
            $this->setAdminCurItem('setting');
            return View::fetch();
        }
    }

    /**
     * 统计设置
     */
    public function orderprange()
    {
        $config_model = model('config');
        if (request()->isPost()) {
            $update_array = array();
            $pricerange_temp_array = input('post.pricerange/a');
            if (is_array($pricerange_temp_array)) {
                foreach ($pricerange_temp_array as $k => $v) {
                    if(!is_numeric($v['s']) || !is_numeric($v['e'])){
                      $this->error(lang('is_numeric_error'));
                    }
                    if($v['s']<0 || $v['e']<0){
                      $this->error(lang('is_zero_error'));
                    }
                    if($v['s']>$v['e']){
                      $this->error(lang('amount_set_error'));
                    }
                    $pricerange_arr[] = $v;
                }
                $update_array['stat_orderpricerange'] = serialize($pricerange_arr);
            }
            else {
                $update_array['stat_orderpricerange'] = '';
            }
            $result = $config_model->editConfig($update_array);
            if ($result === true) {
                $this->log(lang('ds_edit').lang('stat_setting'), 1);
                $this->success(lang('ds_common_save_succ'));
            }
            else {
                $this->log(lang('ds_edit').lang('stat_setting'), 0);
                $this->error(lang('ds_common_save_fail'));
            }
        } else {
            $list_setting = rkcache('config', true);
            $list_setting['stat_orderpricerange'] = unserialize($list_setting['stat_orderpricerange']);
            View::assign('list_setting', $list_setting);
            $this->setAdminCurItem('orderprange');
            return View::fetch();
        }
    }

    protected function getAdminItemList()
    {
        $menu_array = array(
            array(
                'name' => 'general', 'text' => lang('stat_generalindex'), 'url' => (string)url('Statgeneral/general')
            ), array(
                'name' => 'setting', 'text' => lang('stat_goodspricerange'), 'url' => (string)url('Statgeneral/setting')
            ), array(
                'name' => 'orderprange', 'text' => lang('stat_orderpricerange'), 'url' => (string)url('Statgeneral/orderprange')
            )
        );
        return $menu_array;
    }
}