<?php

/**
 * 手机端提货站令牌模型
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
class Mbchaintoken extends BaseModel {
    
/**
     * 查询
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbchaintokenInfo($condition) {
        return Db::name('mbchaintoken')->where($condition)->find();
    }
    
    /**
     * 获取提货站令牌
     * @access public
     * @author csdeshang
     * @param type $token 令牌
     * @return type
     */
    public function getMbchaintokenInfoByToken($token) {
        if (empty($token)) {
            return null;
        }
        return $this->getMbchaintokenInfo(array('chain_token' => $token));
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbchaintoken($data) {
        return Db::name('mbchaintoken')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbchaintoken($condition) {
        return Db::name('mbchaintoken')->where($condition)->delete();
    }
    
    
}