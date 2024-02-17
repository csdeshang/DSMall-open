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
class ChainOrder extends BaseModel {

    public $page_info;

    /**
     * 取单条订单信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $fields 字段
     * @return type
     */
    public function getChainOrderInfo($condition = array(), $fields = '*') {
        return Db::name('chain_order')->field($fields)->where($condition)->find();
    }

    /**
     * 插入订单支付表信息
     * @access public
     * @author csdeshang 
     * @param array $data 参数内容
     * @return type
     */
    public function addChainOrder($data) {
        return Db::name('chain_order')->insert($data);
    }

    /**
     * 更改信息
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return type
     */
    public function editChainOrder($data, $condition) {
        return Db::name('chain_order')->where($condition)->update($data);
    }

    /**
     * 更改信息(包裹到达自提服务站)
     * @access public
     * @author csdeshang 
     * @param array $data
     * @param array $condition 条件
     * @return bool
     */
    public function editChainOrderArrive($data, $condition) {
        $data['chain_order_state'] = ORDER_STATE_PICKUP;
        return $this->editChainOrder($data, $condition);
    }

    /**
     * 更改信息（买家从物流自提服务张取走包裹）
     * @access public
     * @author csdeshang 
     * @param array $data 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editChainOrderPickup($data, $condition) {
        $data['chain_order_state'] = ORDER_STATE_SUCCESS;
        return $this->editChainOrder($data, $condition);
    }

    /**
     * 取订单列表信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @param int $limit 数目限制
     * @return array
     */
    public function getChainOrderList($condition = array(), $fields = '*', $pagesize = 0, $order = 'order_id desc', $limit = 0) {
        if ($pagesize) {
            $res = Db::name('chain_order')->field($fields)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            return $res->items();
        } else {
            return Db::name('chain_order')->field($fields)->where($condition)->order($order)->limit($limit)->select()->toArray();
        }
    }

    /**
     * 取未到站订单列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $fields 字段
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @param int $limit 数目限制
     * @return array
     */
    public function getChainOrderDefaultList($condition = array(), $fields = '*', $pagesize = 0, $order = 'order_id desc', $limit = 0) {
        $condition[] = array('chain_order_state', '=', ORDER_STATE_PAY);
        return $this->getChainOrderList($condition, $fields, $pagesize, $order, $limit);
    }

    /**
     * 取未到站/已到站订单列表
     * @access public
     * @author csdeshang
     * @param unknown $condition 检索条件
     * @param string $fields 字段
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @param int $limit 数目限制
     * @return array
     */
    public function getChainOrderDefaultAndArriveList($condition = array(), $fields = '*', $pagesize = 0, $order = 'order_id desc', $limit = 0) {
        $condition[] = array('chain_order_state', 'not in', [ORDER_STATE_CANCEL, ORDER_STATE_SUCCESS]);
        return $this->getChainOrderList($condition, $fields, $pagesize, $order, $limit);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @return type
     */
    public function delChainOrder($condition) {
        return Db::name('chain_order')->where($condition)->delete();
    }

    /**
     * 更改门店订单状态
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @return type
     */
    public function editChainOrderPay($order_id) {
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $condition[] = array('chain_order_state', '=', ORDER_STATE_NEW);
        $chain_order_info = $this->getChainOrderInfo($condition);
        if ($chain_order_info) {
            if ($chain_order_info['chain_order_type'] == 2) {//自提
                $this->editChainOrder(array('chain_order_state' => ORDER_STATE_PICKUP), $condition);
                $update_order['order_state'] = ORDER_STATE_PICKUP;
                $order_model = model('order');
                $order_model->editOrder($update_order, array(
                    'order_id' => $order_id,
                    'order_state'=>ORDER_STATE_PAY
                ));
            } elseif ($chain_order_info['chain_order_type'] == 1) {//代收
                $this->editChainOrder(array('chain_order_state' => ORDER_STATE_PAY), $condition);
            }
        }
    }

    /**
     * 取消门店订单
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @return type
     */
    public function editChainOrderCancel($order_id, $refund_state = 0, $return_state = 0) {
        $condition = array();
        $condition[] = array('order_id', '=', $order_id);
        $chain_order_info = $this->getChainOrderInfo($condition);
        if ($chain_order_info) {
            $data = array();
            if ($refund_state) {
                $data['chain_order_refund_state'] = $refund_state;
            }
            if ($chain_order_info['chain_order_type'] == 2) {//自提
                if($return_state){
                    $data['chain_order_state'] = ORDER_STATE_CANCEL;
                    //退库存
                    $order_model=model('order');
                    $ordergoods_list=$order_model->getOrdergoodsList(array(array('order_id','=',$chain_order_info['order_id'])));
                    if($ordergoods_list){
                        foreach($ordergoods_list as $key => $val){
                            Db::name('chain_goods')->where(array(array('chain_id','=',$chain_order_info['chain_id']),array('goods_id','=',$val['goods_id'])))->inc('goods_storage',$val['goods_num'])->update();
                        }
                    }
                }else{
                    if($chain_order_info['chain_order_state']<ORDER_STATE_SUCCESS){//还未提货则取消
                         $data['chain_order_state'] = ORDER_STATE_CANCEL;
                    }
                }
            } elseif ($chain_order_info['chain_order_type'] == 1) {//代收
                if($chain_order_info['chain_order_state']>ORDER_STATE_PAY){//如果已经到站则是完成状态
                    $data['chain_order_state'] = ORDER_STATE_SUCCESS;
                }else{
                    $data['chain_order_state'] = ORDER_STATE_CANCEL;
                }
            }
            if(!empty($data)){
                $this->editChainOrder($data, $condition);
            }
        }
    }

    /**
     * 订单锁定
     * @access public
     * @author csdeshang
     * @param type $order_id 订单编号
     * @return boolean
     */
    public function editChainOrderLock($order_id) {
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $condition = array();
            $condition[] = array('order_id', '=', $order_id);
            $data = array();
            $data['chain_order_lock_state'] = Db::raw('chain_order_lock_state+1');
            $result = $this->editChainOrder($data, $condition);
            return $result;
        }
        return false;
    }

    /**
     * 订单解锁
     * @access public
     * @author csdeshang
     * @param type $order_id 订单编号
     * @return boolean
     */
    public function editChainOrderUnlock($order_id) {
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $condition = array();
            $condition[] = array('order_id', '=', $order_id);
            $condition[] = array('chain_order_lock_state', '>=', '1');
            $data = array();
            $data['chain_order_lock_state'] = Db::raw('chain_order_lock_state-1');
            $result = $this->editChainOrder($data, $condition);
            return $result;
        }
        return false;
    }


    /**
     * 添加订单代收表内容
     */
    public function saveChainOrder($param)
    {
        if (!is_array($param['order_sn_list']))
            return ds_callback(true);
        $data = array();
        foreach ($param['order_sn_list'] as $order_id => $v) {
            $data['order_id'] = $order_id;
            $data['order_sn'] = $v['order_sn'];
            $data['chain_order_add_time'] = $v['add_time'];
            $data['chain_id'] = $param['chain_id'];
            $data['chain_order_type'] = 1;
            $data['store_id'] = $v['store_id'];
            $insert = $this->addChainOrder($data);
            if (!$insert) {
                return ds_callback(false, '保存代收订单信息失败order_sn:' . $v['order_sn']);
            }
        }
        return ds_callback(true);
    }
}
