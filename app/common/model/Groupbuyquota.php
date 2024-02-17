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
class Groupbuyquota extends BaseModel
{
    public $page_info;
    /**
     * 读取抢购套餐列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 抢购套餐列表
     *
     */
    public function getGroupbuyquotaList($condition, $pagesize = null, $order = '', $field = '*')
    {
        if($pagesize){
            $result = Db::name('groupbuyquota')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$result;
            $result=$result->items();
        }else{
            $result=Db::name('groupbuyquota')->field($field)->where($condition)->order($order)->select()->toArray();
        }
        return $result;
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getGroupbuyquotaInfo($condition)
    {
        $result = Db::name('groupbuyquota')->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺id
     * @return array
     */
    public function getGroupbuyquotaCurrent($store_id)
    {
        $condition = array();
        $condition[] = array('store_id','=',$store_id);
        $condition[] = array('groupbuyquota_endtime','>',TIMESTAMP);
        return $this->getGroupbuyquotaInfo($condition);
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addGroupbuyquota($data)
    {
        return Db::name('groupbuyquota')->insertGetId($data);
    }

    /**
     * 编辑更新抢购套餐
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 检索条件
     * @return bool
     */
    public function editGroupbuyquota($update, $condition)
    {
        return Db::name('groupbuyquota')->where($condition)->update($update);
    }

    /*
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return bool
     */
    public function delGroupbuyquota($condition)
    {
        return Db::name('groupbuyquota')->where($condition)->delete();
    }
}