<?php

/**
 * 批发活动模型 
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
class Wholesale extends BaseModel {

    public $page_info;
    const WHOLESALE_STATE_NORMAL = 1;
    const WHOLESALE_STATE_CLOSE = 2;
    const WHOLESALE_STATE_CANCEL = 3;

    private $wholesale_state_array = array(
        0 => '全部',
        self::WHOLESALE_STATE_NORMAL => '正常',
        self::WHOLESALE_STATE_CLOSE => '已结束',
        self::WHOLESALE_STATE_CANCEL => '管理员关闭'
    );

    /**
     * 读取批发列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @param type $field 字段
     * @return type
     */
    public function getWholesaleList($condition, $pagesize = null, $order = '', $field = '*') {
        if($pagesize){
        $res = Db::name('wholesale')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        $this->page_info=$res;
        $wholesale_list= $res->items();
        }else{
            $wholesale_list= Db::name('wholesale')->field($field)->where($condition)->order($order)->select()->toArray();
        }
        
        if (!empty($wholesale_list)) {
            for ($i = 0, $j = count($wholesale_list); $i < $j; $i++) {
                $wholesale_list[$i] = $this->getWholesaleExtendInfo($wholesale_list[$i]);
            }
        }
        
        return $wholesale_list;
    }

    /**
     * 根据条件读取限制折扣信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getWholesaleInfo($condition) {
        $wholesale_info = Db::name('wholesale')->where($condition)->find();
        $wholesale_info = $this->getWholesaleExtendInfo($wholesale_info);
        return $wholesale_info;
    }

    /**
     * 根据批发编号读取限制折扣信息
     * @access public
     * @author csdeshang
     * @param type $wholesale_id 限制折扣活动编号
     * @param type $store_id 如果提供店铺编号，判断是否为该店铺活动，如果不是返回null
     * @return array
     */
    public function getWholesaleInfoByID($wholesale_id, $store_id = 0) {
        if (intval($wholesale_id) <= 0) {
            return null;
        }

        $condition = array();
        $condition[] = array('wholesale_id','=',$wholesale_id);
        $wholesale_info = $this->getWholesaleInfo($condition);
        if ($store_id > 0 && $wholesale_info['store_id'] != $store_id) {
            return null;
        } else {
            return $wholesale_info;
        }
    }

    /**
     * 批发状态数组
     * @access public
     * @author csdeshang
     * @return type
     */
    public function getWholesaleStateArray() {
        return $this->wholesale_state_array;
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return bool
     */
    public function addWholesale($data) {
        $data['wholesale_state'] = self::WHOLESALE_STATE_NORMAL;
        return Db::name('wholesale')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 数据
     * @param type $condition 条件
     * @return type
     */
    public function editWholesale($update, $condition) {
        return Db::name('wholesale')->where($condition)->update($update);
    }

    /**
     * 删除批发活动，同时删除批发商品
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return bool
     */
    public function delWholesale($condition) {
        $wholesale_list = $this->getWholesaleList($condition);
        $wholesale_id_string = '';
        if (!empty($wholesale_list)) {
            foreach ($wholesale_list as $value) {
                $wholesale_id_string .= $value['wholesale_id'] . ',';
            }
        }

        //删除批发商品
        if ($wholesale_id_string !== '') {
            $wholesalegoods_model = model('wholesalegoods');
            $wholesalegoods_model->delWholesalegoods(array(array('wholesale_id','in', $wholesale_id_string)));
        }

        return Db::name('wholesale')->where($condition)->delete();
    }

    /**
     * 取消批发活动，同时取消批发商品
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function cancelWholesale($condition) {
        $wholesale_list = $this->getWholesaleList($condition);
        $wholesale_id_string = '';
        if (!empty($wholesale_list)) {
            foreach ($wholesale_list as $value) {
                $wholesale_id_string .= $value['wholesale_id'] . ',';
            }
        }

        $update = array();
        $update['wholesale_state'] = self::WHOLESALE_STATE_CANCEL;

        //删除批发商品
        if ($wholesale_id_string !== '') {
            $wholesalegoods_model = model('wholesalegoods');
            $condition = array();
            $condition[] = array('wholesalegoods_state','=',self::WHOLESALE_STATE_CANCEL);
            $condition[] = array('wholesale_id','in',$wholesale_id_string);
            $wholesalegoods_model->editWholesalegoods($condition);
        }

        return $this->editWholesale($update, $condition);
    }

    /**
     * 获取批发扩展信息，包括状态文字和是否可编辑状态
     * @access public
     * @author csdeshang
     * @param type $wholesale_info 批发信息
     * @return boolean
     */
    public function getWholesaleExtendInfo($wholesale_info) {
        if(!$wholesale_info){
            return false;
        }
        if ($wholesale_info['wholesale_end_time'] > TIMESTAMP) {
            $wholesale_info['wholesale_state_text'] = $this->wholesale_state_array[$wholesale_info['wholesale_state']];
        } else {
            $wholesale_info['wholesale_state_text'] = $this->wholesale_state_array[self::WHOLESALE_STATE_CLOSE];
        }

        if ($wholesale_info['wholesale_state'] == self::WHOLESALE_STATE_NORMAL && $wholesale_info['wholesale_end_time'] > TIMESTAMP) {
            $wholesale_info['editable'] = true;
        } else {
            $wholesale_info['editable'] = false;
        }

        return $wholesale_info;
    }

    /**
     * 编辑过期修改状态
     * @access public
     * @author csdeshang
     * @param type $condition
     * @return boolean
     */
    public function editExpireWholesale($condition) {
        $condition[] = array('wholesale_end_time','<', TIMESTAMP);

        // 更新商品促销价格
        $wholesalegoods_list = model('wholesalegoods')->getWholesalegoodsList(array(array('wholesale_end_time','<', TIMESTAMP)));

        $condition[] = array('wholesale_state','=',self::WHOLESALE_STATE_NORMAL);

        $updata = array();
        $update['wholesale_state'] = self::WHOLESALE_STATE_CLOSE;
        $result = $this->editWholesale($update, $condition);
        if ($result) {
            foreach ($wholesalegoods_list as $value) {
                $this->_unlockGoods($value['goods_commonid']);
            }
        }
        return true;
    }
    
    /**
     * 解锁商品
     * @access private
     * @author csdeshang
     * @param type $goods_commonid 商品编号ID
     */
    private function _unlockGoods($goods_commonid)
    {
        $goods_model = model('goods');
        $goods_model->editGoodsCommonUnlock(array('goods_commonid' => $goods_commonid));
        $goods_model->editGoodsUnlock(array('goods_commonid' => $goods_commonid));
    }

}
