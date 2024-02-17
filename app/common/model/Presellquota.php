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
class Presellquota extends BaseModel
{
    public $page_info;
    /**
     * 读取预售套餐列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 预售套餐列表
     *
     */
    public function getPresellquotaList($condition, $pagesize = null, $order = '', $field = '*')
    {
        if($pagesize){
            $result = Db::name('presellquota')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info=$result;
            $result=$result->items();
        }else{
            $result=Db::name('presellquota')->field($field)->where($condition)->order($order)->select()->toArray();
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
    public function getPresellquotaInfo($condition)
    {
        $result = Db::name('presellquota')->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺id
     * @return array
     */
    public function getPresellquotaCurrent($store_id)
    {
        $condition = array();
        $condition[] = array('store_id','=',$store_id);
        $condition[] = array('presellquota_endtime','>',TIMESTAMP);
        return $this->getPresellquotaInfo($condition);
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addPresellquota($data)
    {
        return Db::name('presellquota')->insertGetId($data);
    }

    /**
     * 编辑更新预售套餐
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 检索条件
     * @return bool
     */
    public function editPresellquota($update, $condition)
    {
        return Db::name('presellquota')->where($condition)->update($update);
    }

    /*
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return bool
     */
    public function delPresellquota($condition)
    {
        return Db::name('presellquota')->where($condition)->delete();
    }
}