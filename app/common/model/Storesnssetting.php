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
class Storesnssetting extends BaseModel
{
    /**
     * 获取单条动态设置设置信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getStoresnssettingInfo($condition, $field = '*')
    {
        return Db::name('storesnssetting')->field($field)->where($condition)->find();
    }

    /**
     * 保存店铺动态设置
     * @access public
     * @author csdeshang
     * @param array $data 参数数据
     * @return boolean
     */
    public function addStoresnssetting($data)
    {
        return Db::name('storesnssetting')->insert($data);
    }
    /**
     * 保存店铺动态设置
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return boolean
     */
    public function editStoresnssetting($update, $condition)
    {
        return Db::name('storesnssetting')->where($condition)->update($update);
    }
}