<?php

/**
 * 批发活动商品模型 
 *
 */

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
class Wholesalegoods extends BaseModel {

    public $page_info;

    const WHOLESALE_GOODS_STATE_CANCEL = 0;
    const WHOLESALE_GOODS_STATE_NORMAL = 1;


    /**
     * 读取批发商品列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @param type $field 字段
     * @param type $limit 个数限制
     * @return array 批发商品列表
     */
    public function getWholesalegoodsList($condition, $pagesize = null, $order = '', $field = '*', $limit = 0) {
        if ($pagesize) {
            $res = Db::name('wholesalegoods')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $res;
            return $res->items();
        } else {
            return $wholesalegoods_list = Db::name('wholesalegoods')->field($field)->where($condition)->limit($limit)->order($order)->select()->toArray();
        }
    }

    /**
     * 读取批发商品列表
     * @access public
     * @author csdeshang
     * @param type $condition 查询条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @param type $field 字段
     * @param type $limit 个数限制
     * @return array 
     */
    public function getWholesalegoodsExtendList($condition, $pagesize = null, $order = '', $field = '*', $limit = 0) {
        $wholesalegoods_list = $this->getWholesalegoodsList($condition, $pagesize, $order, $field, $limit);
        if (!empty($wholesalegoods_list)) {
            for ($i = 0, $j = count($wholesalegoods_list); $i < $j; $i++) {
                $wholesalegoods_list[$i] = $this->getWholesalegoodsExtendInfo($wholesalegoods_list[$i]);
            }
        }
        return $wholesalegoods_list;
    }
    
    /**
     * 获取秒杀折列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @param type $field 字段
     * @param type $limit 限制
     * @return type
     */
    public function getWholesalegoodsExtendIds($condition, $pagesize = null, $order = '', $field = 'goods_id', $limit = 0) {
        $wholesalegoods_id_list = $this->getWholesalegoodsList($condition, $pagesize, $order, $field, $limit);

        if (!empty($wholesalegoods_id_list)) {
            for ($i = 0; $i < count($wholesalegoods_id_list); $i++) {

                $wholesalegoods_id_list[$i] = $wholesalegoods_id_list[$i]['goods_id'];
            }
        }

        return $wholesalegoods_id_list;
    }


    /**
     * 根据条件读取限制折扣商品信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return array
     */
    public function getWholesalegoodsInfo($condition) {
        $result = Db::name('wholesalegoods')->where($condition)->find();
        return $result;
    }

    /**
     * 根据批发商品编号读取限制折扣商品信息
     * @access public
     * @author csdeshang
     * @param type $wholesalegoods_id ID编号
     * @param type $store_id 店铺ID
     * @return type
     */
    public function getWholesalegoodsInfoByID($wholesalegoods_id, $store_id = 0) {
        if (intval($wholesalegoods_id) <= 0) {
            return null;
        }

        $condition = array();
        $condition[] = array('wholesalegoods_id','=',$wholesalegoods_id);
        $wholesalegoods_info = $this->getWholesalegoodsInfo($condition);

        if ($store_id > 0 && $wholesalegoods_info['store_id'] != $store_id) {
            return null;
        } else {
            return $wholesalegoods_info;
        }
    }

    /**
     * @access public
     * @author csdeshang
     * 增加批发商品
     * @param type $wholesalegoods_info 批发商品信息
     * @return bool
     */
    public function addWholesalegoods($wholesalegoods_info) {
        $wholesalegoods_info['wholesalegoods_state'] = self::WHOLESALE_GOODS_STATE_NORMAL;
        $wholesalegoods_id = Db::name('wholesalegoods')->insertGetId($wholesalegoods_info);
        if($wholesalegoods_id){
            // 发布秒杀锁定商品
            $this->_lockGoods($wholesalegoods_info['goods_commonid'],$wholesalegoods_info['goods_id']);
        }
        // 删除商品批发缓存
        $this->_dGoodsWholesaleCache($wholesalegoods_info['goods_id']);

        $wholesalegoods_info['wholesalegoods_id'] = $wholesalegoods_id;
        $wholesalegoods_info = $this->getWholesalegoodsExtendInfo($wholesalegoods_info);
        return $wholesalegoods_info;
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 数据更新
     * @param type $condition 条件
     * @return type
     */
    public function editWholesalegoods($update, $condition) {
        $result = Db::name('wholesalegoods')->where($condition)->update($update);
        if ($result) {
            $wholesalegoods_list = $this->getWholesalegoodsList($condition, null, '', 'goods_id');
            if (!empty($wholesalegoods_list)) {
                foreach ($wholesalegoods_list as $val) {
                    // 删除商品批发缓存
                    $this->_dGoodsWholesaleCache($val['goods_id']);
                }
            }
        }
        return $result;
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function delWholesalegoods($condition) {
        $wholesalegoods_list = $this->getWholesalegoodsList($condition, null, '', 'goods_id,goods_commonid');
        $result = Db::name('wholesalegoods')->where($condition)->delete();
        if ($result) {
            if (!empty($wholesalegoods_list)) {
                foreach ($wholesalegoods_list as $val) {
                    $this->_unlockGoods($val['goods_commonid'],$val['goods_id']);
                    // 删除商品批发缓存
                    $this->_dGoodsWholesaleCache($val['goods_id']);
                }
            }
        }
        return $result;
    }

    /**
     * 获取批发商品扩展信息
     * @access public
     * @author csdeshang
     * @param type $wholesale_info  信息
     * @return string
     */
    public function getWholesalegoodsExtendInfo($wholesale_info) {
        $wholesale_info['goods_url'] = (string)url('/home/Goods/index', array('goods_id' => $wholesale_info['goods_id']));
        $wholesale_info['image_url'] = goods_cthumb($wholesale_info['goods_image'], 60, $wholesale_info['store_id']);
        $wholesale_info['wholesalegoods_price'] = unserialize($wholesale_info['wholesalegoods_price']);
        return $wholesale_info;
    }

    /**
     * 获取推荐批发商品
     * @access public
     * @author csdeshang
     * @param type $count 推荐数量
     * @return type
     */
    public function getWholesalegoodsCommendList($count = 4) {
        $condition = array();
        $condition[] = array('wholesalegoods_state', '=', self::WHOLESALE_GOODS_STATE_NORMAL);
        $condition[] = array('wholesale_starttime', '<', TIMESTAMP);
        $condition[] = array('wholesale_end_time', '>', TIMESTAMP);
        $wholesale_list = $this->getWholesalegoodsExtendList($condition, null, '', '*', $count);
        return $wholesale_list;
    }

     /**
     * 锁定商品
     * @access private
     * @author csdeshang
     * @param type $goods_commonid 商品编号
     */
    private function _lockGoods($goods_commonid,$goods_id)
    {
        $condition = array();
        $condition[] = array('goods_commonid','=',$goods_commonid);

        $goods_model = model('goods');
        $goods_model->editGoodsCommonLock($condition);
        
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        $goods_model->editGoodsLock($condition);
    }

    /**
     * 解锁商品
     * @access private
     * @author csdeshang
     * @param type $goods_commonid 商品编号ID
     */
    private function _unlockGoods($goods_commonid,$goods_id)
    {
        $goods_model = model('goods');
        $goods_model->editGoodsUnlock(array('goods_id' => $goods_id));
        if(!$goods_model->getGoodsCount(array('goods_commonid' => $goods_commonid,'goods_lock'=>1))){
            $goods_model->editGoodsCommonUnlock(array('goods_commonid' => $goods_commonid));
        }
    }
    
    /**
     * 根据商品编号查询是否有可用批发活动，如果有返回批发活动，没有返回null
     * @access public
     * @author csdeshang
     * @param type $goods_id 商品id
     * @return array
     */
    public function getWholesalegoodsInfoByGoodsID($goods_id) {
        $info = $this->_rGoodsWholesaleCache($goods_id);
        if (empty($info)) {
            $condition = array();
            $condition[] = array('wholesalegoods_state','=',self::WHOLESALE_GOODS_STATE_NORMAL);
            $condition[] = array('wholesale_end_time','>',TIMESTAMP);
            $condition[] = array('goods_id','=',$goods_id);
            $wholesalegoods_list = $this->getWholesalegoodsExtendList($condition, null, 'wholesale_starttime asc', '*', 1);
            $info['info'] = isset($wholesalegoods_list[0]) ? serialize($wholesalegoods_list[0]) : serialize("");
            $this->_wGoodsWholesaleCache($goods_id, $info);
        }
        $wholesalegoods_info = unserialize($info['info']);
        if (!empty($wholesalegoods_info) && ($wholesalegoods_info['wholesale_starttime'] > TIMESTAMP || $wholesalegoods_info['wholesale_end_time'] < TIMESTAMP)) {
            $wholesalegoods_info = array();
        }
        return $wholesalegoods_info;
    }

    /**
     * 根据商品编号查询是否有可用批发活动，如果有返回批发活动，没有返回null
     * @access public
     * @author csdeshang
     * @param type $goods_string 商品编号字符串，例：'11,22,33'
     * @return type
     */
    public function getWholesalegoodsListByGoodsString($goods_string) {
        $wholesalegoods_list = $this->_getWholesalegoodsListByGoods($goods_string);
        $wholesalegoods_list = array_under_reset($wholesalegoods_list, 'goods_id');
        return $wholesalegoods_list;
    }

    /**
     * 根据商品编号查询是否有可用批发活动，如果有返回批发活动，没有返回null
     * @access public
     * @author csdeshang
     * @param type $goods_id_string  商品编号字符串
     * @return type
     */
    private function _getWholesalegoodsListByGoods($goods_id_string) {
        $condition = array();
        $condition[] = array('wholesalegoods_state','=',self::WHOLESALE_GOODS_STATE_NORMAL);
        $condition[] = array('wholesale_starttime','<',TIMESTAMP);
        $condition[] = array('wholesale_end_time','>',TIMESTAMP);
        $condition[] = array('goods_id','in', $goods_id_string);
        $wholesalegoods_list = $this->getWholesalegoodsExtendList($condition, null, 'wholesalegoods_id desc', '*');
        return $wholesalegoods_list;
    }

    /**
     * 读取商品批发缓存
     * @access public
     * @author csdeshang
     * @param type $goods_id 商品id
     * @return type
     */
    private function _rGoodsWholesaleCache($goods_id) {
        return rcache($goods_id, 'goods_wholesale');
    }

    /**
     * 写入商品批发缓存
     * @access public
     * @author csdeshang
     * @param int $goods_id 商品id
     * @param array $info 信息
     * @return boolean
     */
    private function _wGoodsWholesaleCache($goods_id, $info) {
        return wcache($goods_id, $info, 'goods_wholesale');
    }

    /**
     * 删除商品批发缓存
     * @access public
     * @author csdeshang
     * @param type $goods_id 商品id
     * @return bool
     */
    private function _dGoodsWholesaleCache($goods_id) {
        return dcache($goods_id, 'goods_wholesale');
    }

}
