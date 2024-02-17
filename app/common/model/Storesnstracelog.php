<?php

namespace app\common\model;



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
 * 数据层模型
 */
class Storesnstracelog extends BaseModel
{
    public $page_info;
    /**
     * 店铺动态列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $order 排序
     * @param int $limit 限制
     * @param int $pagesize 分页
     * @return array
     */
    public function getStoresnstracelogList($condition, $field = '*', $order = 'stracelog_id desc',$limit = 0, $pagesize = 0) {
        if($pagesize) {
            $res = Db::name('storesnstracelog')->where($condition)->field($field)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            return $res->items();
        }else{
            return Db::name('storesnstracelog')->where($condition)->field($field)->order($order)->limit($limit)->select()->toArray();
        }
    }

    /**
     * 获得店铺动态总数
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getStoresnstracelogCount($condition) {
        return Db::name('storesnstracelog')->where($condition)->count();
    }

    /**
     * 获取单条店铺动态
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return array
     */
    public function getStoresnstracelogInfo($condition) {
        return Db::name('storesnstracelog')->where($condition)->find();
    }

    /**
     * 保存店铺动态
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return boolean
     */
    public function addStoresnstracelog($data) {
        return Db::name('storesnstracelog')->insertGetId($data);
    }

    /**
     * 保存店铺动态
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return boolean
     */
    public function addStoresnstracelogAll($data) {
        return Db::name('storesnstracelog')->insertAll($data);
    }

    /**
     * 更新店铺动态
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editStoresnstracelog($update, $condition) {
        return Db::name('storesnstracelog')->where($condition)->update($update);
    }

    /**
     * 删除店铺动态
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return boolean
     */
    public function delStoresnstracelog($condition) {
        return Db::name('storesnstracelog')->where($condition)->delete();
    }

    /**
     * 拼写个类型样式
     * @param type $type 动态类型
     * @param type $data 相关数据
     * @return string
     */
    public function spellingStyle($type,$data){
        //1'relay',2'normal',3'new',4'coupon',5'xianshi',6'mansong',7'bundling',8'groupbuy',9'recommend',10'hotsell'
        $rs = '';
        switch ($type){
            case '2':
                break;
            case '3':
                $rs = "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\"><img src=\"" . goods_cthumb($data['goods_image'], 240, $data['store_id']) . "\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['goods_name'] . "\"></a></div>
    					<div class=\"goodsinfo\">
    					<dl>
    					   <dt><i class=\"desc-type desc-type-new\">" . lang('store_sns_new_selease') . "</i><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\">" . $data['goods_name'] . "</a></dt>
							<dd>" . lang('sns_sharegoods_price') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_price']) . "</dd>
							<dd>" . ((isset($data['goods_transfee_charge']) && $data['goods_transfee_charge'] == '1') ? lang('store_sns_free_shipping') : lang('sns_sharegoods_freight') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_freight'])) . "</dd>
	                  		<dd dstype=\"collectbtn_" . $data['goods_id'] . "\"><a href=\"javascript:void(0);\" onclick=\"javascript:collect_goods('" . $data['goods_id'] . "','succ','collectbtn_" . $data['goods_id'] . "');\">" . lang('sns_sharegoods_collect') . "</a></dd>
	                  	</dl>
	                  </div>
	             </div>";
                break;
            case '4':
                $rs = "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('Coupon_store/detail', array('coupon_id' => $data['coupon_id'], 'id' => $data['store_id'])) . "\"><img src=\"" . $data['coupon_pic'] . "\" onerror=\"this.src='" . HOME_SITE_ROOT . "/images/default_coupon_image.png'\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['coupon_title'] . "\"></a></div>
    					<div class=\"goodsinfo\">
    					<dl>
        					<dt><i class=\"desc-type desc-type-coupon\">" . lang('store_sns_coupon') . "</i><a target=\"_blank\" href=\"" . (string)url('Coupon_store/detail', array('coupon_id' => $data['coupon_id'], 'id' => $data['store_id'])) . "\">" . $data['coupon_title'] . "</a></dt>
        					<dd>" . lang('store_sns_coupon_price') . lang('ds_colon') . lang('currency') . ds_price_format($data['coupon_price']) . "</dd>
        					<dd>" . lang('store_sns_start-stop_time') . lang('ds_colon') . date('Y-m-d H:i', $data['coupon_start_date']) . "~" . date('Y-m-d H:i', $data['coupon_end_date']) . "</dd>
	                  	</dl>
	                  </div>
			        </div>";
                break;
            case '5':
                $rs = "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\"><img src=\"" . goods_cthumb($data['goods_image'], 240,$data['store_id']) . "\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['goods_name'] . "\"></a></div>
    					<div class=\"goodsinfo\">
    					<dl>
        					<dt><i class=\"desc-type desc-type-xianshi\">" . lang('store_sns_xianshi') . "</i><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\">" . $data['goods_name'] . "</a></dt>
    						<dd>" . lang('sns_sharegoods_price') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_price']) . "</dd>
    						<dd>" . lang('store_sns_formerprice') . lang('ds_colon') . lang('currency') . ds_price_format($data['xianshigoods_price']) . "</dd>
    				        <dd dstype=\"collectbtn_" . $data['goods_id'] . "\"><a href=\"javascript:void(0);\" onclick=\"javascript:collect_goods('" . $data['goods_id'] . "','succ','collectbtn_" . $data['goods_id'] . "');\">" . lang('sns_sharegoods_collect') . "</a></dd>
	                  	</dl>
	                  </div>
    	             </div>";
                break;
            case '6':
                $rs = "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('Store/index', array('store_id'=>$data['store_id'])) . "\"><img src=\"" . HOME_SITE_ROOT . "/images/mjs-pic.gif\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"".$data['mansong_name']."\"></a></div>
    					<div class=\"goodsinfo\">
    					<dl>
        					<dt><i class=\"desc-type desc-type-mansong\">" . lang('store_sns_mansong') . "</i><a target=\"_blank\" href=\"" . (string)url('Store/index', array('store_id'=>$data['store_id'])) . "\">" . $data['mansong_name'] . "</a></dt>
    						<dd>" . lang('store_sns_start-stop_time') . lang('ds_colon') . date('Y-m-d H:i', $data['mansong_starttime']) . "~" . date('Y-m-d H:i', $data['mansong_endtime']) . "</dd>
	                  	</dl>
				        </div>
    	             </div>";
                break;
            case '7':
                $rs = "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\"><img src=\"" . goods_cthumb($data['bl_img'], 240, $data['store_id']) . "\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['bl_name'] . "\"></a></div>
    					<div class=\"goodsinfo\">
    					<dl>
        					<dt><i class=\"desc-type desc-type-bundling\">" . lang('store_sns_bundling') . "</i><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\">".$data['bl_name']."</a></dt>
    						<dd>" . lang('store_sns_bundling_price') . lang('ds_colon') . lang('currency') . ds_price_format($data['bl_discount_price']) . "</dd>
    			            <dd>" . (($data['bl_freight_choose']==1) ? lang('store_sns_free_shipping') : lang('sns_sharegoods_freight') . lang('ds_colon') . lang('currency') . ds_price_format($data['bl_freight'])) . "</dd>
						</dl>
                    </div>
                    </div>";
                break;
            case '8':
                $rs = "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\"><img src=\"" . groupbuy_thumb($data['group_pic'],'small',$data['store_id']) . "\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['group_name'] . "\"></a></div>
    					<div class=\"goodsinfo\">
    					<dl>
        					<dt><i class=\"desc-type desc-type-groupbuy\">" . lang('store_sns_groupbuy') . "</i><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\">" . $data['group_name'] . "</a></dt>
        					<dd>" . lang('store_sns_goodsprice') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_price']) . "</dd>
    				        <dd>" . lang('store_sns_groupprice') . lang('ds_colon') . lang('currency') . ds_price_format($data['groupbuy_price']) . "</dd>
    		                <dd>" . lang('store_sns_start-stop_time') . lang('ds_colon') . date('Y-m-d H:i', $data['start_time']) . "~" . date('Y-m-d H:i', $data['end_time']) . "</dd>
		                </dl>
					</div>
				</div>";
                break;
            case '9':
                $rs = "<div class=\"fd-media\">
    				<div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\"><img src=\"" . goods_thumb($data, 240) . "\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['goods_name'] . "\"></a></div>
    				<div class=\"goodsinfo\">
    				<dl>
    					<dt><i class=\"desc-type desc-type-recommend\">" . lang('store_sns_store_recommend') . "</i><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\">" . $data['goods_name'] . "</a></dt>
				        <dd>" . lang('sns_sharegoods_price') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_price']) . "</dd>
		                <dd>" . lang('sns_sharegoods_freight') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_freight']) . "</dd>
		                <dd dstype=\"collectbtn_" . $data['goods_id'] . "\"><a href=\"javascript:void(0);\" onclick=\"javascript:collect_goods('" . $data['goods_id'] . "','succ','collectbtn_" . $data['goods_id'] . "');\">" . lang('sns_sharegoods_collect') . "</a></dd>
    				</dl>
                    </div>
	             </div>";
                break;
            case '10':
                $rs = "<div class=\"fd-media\">
                    <div class=\"goodsimg\"><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\"><img src=\"" . goods_thumb($data, 240) . "\" onload=\"javascript:ResizeImage(this,120,120);\" alt=\"" . $data['goods_name'] . "\"></a></div>
					<div class=\"goodsinfo\">
						<dl>
							<dt><i class=\"desc-type desc-type-hotsell\">" . lang('store_sns_hotsell') . "</i><a target=\"_blank\" href=\"" . (string)url('home/Goods/index', array('goods_id'=>$data['goods_id'])) . "\">" . $data['goods_name'] . "</a></dt>
					        <dd>" . lang('sns_sharegoods_price') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_price']) . "</dd>
							<dd>" . lang('sns_sharegoods_freight') . lang('ds_colon') . lang('currency') . ds_price_format($data['goods_freight']) . "</dd>
	                  		<dd dstype=\"collectbtn_" . $data['goods_id'] . "\"><a href=\"javascript:void(0);\" onclick=\"javascript:collect_goods('" . $data['goods_id'] . "','succ','collectbtn_" . $data['goods_id']. "');\">" . lang('sns_sharegoods_collect') . "</a></dd>
	                  	</dl>
	                  </div>
    	             </div>";
                break;
        }
        return $rs;
    }
}