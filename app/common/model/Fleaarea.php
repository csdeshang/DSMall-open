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
class Fleaarea extends BaseModel
{
    /**
     * 读取系统设置列表
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $order 排序条件
     * @return array 数组格式的返回结果
     */
    public function getFleaareaList($condition, $field='*', $order='fleaarea_sort desc',$limit=0){
        $result = Db::name('fleaarea')->field($field)->limit($limit)->order($order)->where($condition)->select()->toArray();
        return $result;
    }


    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addFleaarea($data) {
        $result = Db::name('fleaarea')->insertGetId($data);
        return $result;
    }

    /**
     * 取单个地区的内容
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return array 数组类型的返回结果
     */
    public function getOneFleaarea($condition){
        if (!empty($condition)){

            $result = Db::name('fleaarea')->where($condition)->find();
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 更新信息
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function editFleaarea($condition, $data) {
        $result = Db::name('fleaarea')->where($condition)->update($data);
        return $result;
    }

    /**
     * 删除地区
     * @access public
     * @author csdeshang
     * @param int/array $id 删除ID
     * @param int $deep 删除深度,默认为1
     * @return array $rs_row 返回数组形式的查询结果
     */
    public function delFleaarea($id,$deep=1){
        if (!empty($id)){
            if (!is_array($id)){
                $id = array($id);
            }

            /**
             * 取得地区缓存内容
             */
            $child_deep = $deep+1;
            for ($i=$child_deep; $i<=4; $i++){
                $cache_file = ROOT_PATH . "extend" . DIRECTORY_SEPARATOR . "area" . DIRECTORY_SEPARATOR . "area_" . $deep . ".php";
                if (file_exists($cache_file)){
                    require_once($cache_file);
                    $tmp = 'cache_data_'.$i;
                    $$tmp = $area_array;
                    unset($tmp,$area_array);
                }
            }
            foreach ($id as $k => $v){
                if (intval($v) > 0){
                    $del_tmp[] = "fleaarea_id = '". $v ."'";
                    /**
                     * 判断子类中是否还有内容
                     */
                    if ($child_deep <= 4){
                        $del_parent_id = array($v);
                        for ($i=$child_deep; $i<=4; $i++){
                            $tmp = 'cache_data_'.$i;
                            if (isset($$tmp) && is_array($$tmp) && !empty($del_parent_id)){
                                foreach ($del_parent_id as $k_parent => $v_parent){
                                    foreach ($$tmp as $k_2 => $v_2){
                                        if ($v_2['fleaarea_parent_id'] == $v_parent){
                                            $del_tmp[] = "fleaarea_id = '". $v_2['fleaarea_id'] ."'";
                                            $next_parent_id[] = $v_2['fleaarea_id'];
                                        }
                                    }
                                }
                                /**
                                 * 再下一级的父ID
                                 */
                                $del_parent_id = $next_parent_id;
                            }
                        }
                    }
                }
            }
            $where = implode(' or ',$del_tmp);
            $result = Db::name('fleaarea')->where($where)->delete();
            return $result;
        }else {
            return false;
        }
    }
    /**
     * 地址展示
     * @access public
     * @author csdeshang
     * @return type
     */
    public function fleaarea_show(){

        $area_one_level = array();
        $area_two_level = array();
        $condition = array();
        $condition[]=array('fleaarea_parent_id','=',1);
        $area_list=$this->getFleaareaList($condition,'fleaarea_id,fleaarea_name,fleaarea_parent_id','fleaarea_parent_id asc,fleaarea_sort asc,fleaarea_id asc');
        if(is_array($area_list) && !empty($area_list)) {
            foreach ($area_list as $val) {
                if($val['fleaarea_parent_id'] == 0) {
                    $fleaarea_id	= $val['fleaarea_id'];
                    $area_one_level[] = $val;
                    $area_two_level[$fleaarea_id]['id']=$fleaarea_id;
                } else {
                    $fleaarea_parent_id	= $val['fleaarea_parent_id'];
                    if(isset($area_two_level[$fleaarea_parent_id])) {
                        $area_two_level[$fleaarea_parent_id]['children'][] = $val;
                        $area_children = $area_two_level[$fleaarea_parent_id]['children'];
                        $area_two_level[$fleaarea_parent_id]['content'] = json_encode($area_children);
                    }
                }
            }
        }
        return(array('area_one_level'=>$area_one_level, 'area_two_level'=>$area_two_level));
    }
}