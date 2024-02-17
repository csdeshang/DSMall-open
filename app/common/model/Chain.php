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
class Chain extends BaseModel
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
    public function addChain($data)
    {
        return Db::name('chain')->insertGetId($data);
    }

    /**
     * 查询门店列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getChainList($condition, $pagesize = 0, $order = 'chain_id desc')
    {
        if($pagesize){
            $res = Db::name('chain')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$res;
            return $res->items();
        }else{
            return Db::name('chain')->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 等待审核的门店列表
     * @access public
     * @author csdeshang 
     * @param unknown $condition 条件
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getChainWaitVerifyList($condition, $pagesize = 0, $order = 'chain_id desc')
    {
        $condition[]=array('chain_state','=',self::STATE10);
        return $this->getChainList($condition, $pagesize, $order);
    }

    /**
     * 等待审核的门店数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @return int
     */
    public function getChainWaitVerifyCount($condition)
    {
        $condition[]=array('chain_state','=',self::STATE10);
        return Db::name('chain')->where($condition)->count();
    }

    /**
     * 开启中物流门店列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param number $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getChainOpenList($condition, $pagesize = 0, $order = 'chain_id desc')
    {
        $condition[]=array('chain_state','=',self::STATE1);
        return $this->getChainList($condition, $pagesize, $order);
    }

    /**
     * 取得门店详细信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getChainInfo($condition, $field = '*')
    {
        return Db::name('chain')->where($condition)->field($field)->find();
    }

    /**
     * 取得开启中物流门店信息
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getChainOpenInfo($condition, $field = '*')
    {
        $condition[]=array('chain_state','=',self::STATE1);
        return Db::name('chain')->where($condition)->field($field)->find();
    }

    /**
     * 取得开启中物流门店信息
     * @access public
     * @author csdeshang 
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getChainFailInfo($condition, $field = '*')
    {
        $condition[]=array('chain_state','=',self::STATE20);
        return Db::name('chain')->where($condition)->field($field)->find();
    }

    /**
     * 门店信息
     * @access public
     * @author csdeshang 
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editChain($update, $condition)
    {
        return Db::name('chain')->where($condition)->update($update);
    }
    
    /**
     * 删除门店
     * @access public
     * @author csdeshang
     * @param int $condition 记录ID
     * @return bool 
     */
    public function delChain($condition) {
        return Db::name('chain')->where($condition)->delete();
    }
    
    /**
     * @access public
     * @author csdeshang 
     * 返回状态数组
     * @return array
     */
    public function getChainState()
    {
        return $this->state;
    }
}