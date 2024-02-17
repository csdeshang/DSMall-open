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
class Deliverypoint extends BaseModel
{
    const STATE1 = 1;   // 开启
    const STATE0 = 0;   // 关闭
    const STATE10 = 10; // 等待审核
    const STATE20 = 20; // 等待失败
    private $state = array(
        self::STATE0 => '关闭', self::STATE1 => '开启', self::STATE10 => '等待审核', self::STATE20 => '审核失败'
    );
    public $page_info;

    /**
     * 插入数据
     * @access public
     * @author csdeshang 
     * @param array $data 数据
     * @return boolean
     */
    public function addDeliverypoint($data)
    {
        return Db::name('deliverypoint')->insertGetId($data);
    }

    /**
     * 查询自提服务站列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getDeliverypointList($condition, $pagesize = 0, $order = 'dlyp_id desc')
    {
        if($pagesize){
            $res = Db::name('deliverypoint')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            return $res->items();
        }else{
            return Db::name('deliverypoint')->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 等待审核的自提服务站列表
     * @access public
     * @author csdeshang 
     * @param unknown $condition 条件
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getDeliverypointWaitVerifyList($condition, $pagesize = 0, $order = 'dlyp_id desc')
    {
        $condition[]=array('dlyp_state','=',self::STATE10);
        return $this->getDeliverypointList($condition, $pagesize, $order);
    }

    /**
     * 等待审核的自提服务站数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @return int
     */
    public function getDeliverypointWaitVerifyCount($condition)
    {
        $condition[]=array('dlyp_state','=',self::STATE10);
        return Db::name('deliverypoint')->where($condition)->count();
    }

    /**
     * 开启中物流自提服务列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getDeliverypointOpenList($condition, $pagesize = 0, $order = 'dlyp_id desc')
    {
        $condition[]=array('dlyp_state','=',self::STATE1);
        return $this->getDeliverypointList($condition, $pagesize, $order);
    }

    /**
     * 取得自提服务站详细信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getDeliverypointInfo($condition, $field = '*')
    {
        return Db::name('deliverypoint')->where($condition)->field($field)->find();
    }

    /**
     * 取得开启中物流自提服务信息
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getDeliverypointOpenInfo($condition, $field = '*')
    {
        $condition[]=array('dlyp_state','=',self::STATE1);
        return Db::name('deliverypoint')->where($condition)->field($field)->find();
    }

    /**
     * 取得开启中物流自提服务信息
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getDeliverypointFailInfo($condition, $field = '*')
    {
        $condition[]=array('dlyp_state','=',self::STATE20);
        return Db::name('deliverypoint')->where($condition)->field($field)->find();
    }

    /**
     * 自提服务站信息
     * @access public
     * @author csdeshang 
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editDeliverypoint($update, $condition)
    {
        return Db::name('deliverypoint')->where($condition)->update($update);
    }

    /**
     * @access public
     * @author csdeshang 
     * 返回状态数组
     * @return array
     */
    public function getDeliveryState()
    {
        return $this->state;
    }
}