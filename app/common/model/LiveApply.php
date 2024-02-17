<?php

namespace app\common\model;

use think\facade\Db;

/**
 * ============================================================================
 * DSKMS多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 数据层模型
 */
class LiveApply extends BaseModel {

    public $page_info;

    public function getLiveApplyList($condition, $field = '*', $pagesize=10, $order = 'live_apply_id desc') {
        if ($pagesize) {
            $result = Db::name('live_apply')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = Db::name('live_apply')->field($field)->where($condition)->order($order)->select()->toArray();
            return $result;
        }
    }

    /**
     * 取单个内容
     * @access public
     * @author csdeshang
     * @param int $id 分类ID
     * @return array 数组类型的返回结果
     */
    public function getLiveApplyInfo($condition) {
        $result = Db::name('live_apply')->where($condition)->find();
        return $result;
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addLiveApply($data) {
        $result = Db::name('live_apply')->insertGetId($data);
        return $result;
    }

    /**
     * 取商品
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return array 数组类型的返回结果
     */
    public function getLiveApplyGoodsList($condition) {
        $result = Db::name('live_apply_goods')->where($condition)->select()->toArray();
        return $result;
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addLiveApplyGoodsAll($data) {
        $result = Db::name('live_apply_goods')->insertAll($data);
        return $result;
    }

    /**
     * 更新信息
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @param array $condition 条件
     * @return bool
     */
    public function editLiveApply($data, $condition) {
        $result = Db::name('live_apply')->where($condition)->update($data);
        return $result;
    }

    /**
     * 删除分类
     * @access public
     * @author csdeshang
     * @param int $condition 记录ID
     * @return bool 
     */
    public function delLiveApply($condition) {
        return Db::name('live_apply')->where($condition)->delete();
    }

    function getPushUrl($streamName, $time) {
            if (config('ds_config.video_type') == 'aliyun') {
                $domain=config('ds_config.aliyun_live_push_domain');
                $key=config('ds_config.aliyun_live_push_key');
                $rand=rand(10000,99999);
                $ext_str='?auth_key='.$time.'-'.$rand.'-0-'.md5('/live/'.$streamName.'-'.$time.'-'.$rand.'-0-'.$key);
            } else {
                $domain=config('ds_config.live_push_domain');
                $key=config('ds_config.live_push_key');
                $txTime = strtoupper(base_convert($time, 10, 16));
                $txSecret = md5($key . $streamName . $txTime);
                $ext_str = "?" . http_build_query(array(
                            "txSecret" => $txSecret,
                            "txTime" => $txTime
                ));
            }

        return "rtmp://" . $domain . "/live/" . $streamName . (isset($ext_str) ? $ext_str : "");
    }

    function getPlayUrl($streamName, $time,$rtmp=false){
        if (config('ds_config.video_type') == 'aliyun') {
            $domain=config('ds_config.aliyun_live_play_domain');
            $key=config('ds_config.aliyun_live_play_key');
            $rand=rand(10000,99999);
            $ext_str='?auth_key='.$time.'-'.$rand.'-0-'.md5('/live/'.$streamName.'.m3u8'.'-'.$time.'-'.$rand.'-0-'.$key);
        }else{
            $domain=config('ds_config.live_play_domain');
        }
        if($rtmp){
            return 'rtmp://'.$domain.'/live/'.$streamName . (isset($ext_str) ? $ext_str : "");
        }else{
            return HTTP_TYPE.$domain.'/live/'.$streamName.'.m3u8' . (isset($ext_str) ? $ext_str : "");
        }
        
    }

}
