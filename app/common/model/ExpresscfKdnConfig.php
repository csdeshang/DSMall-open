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
class ExpresscfKdnConfig extends BaseModel {

    public $page_info;

    public function getExpresscfKdnConfigList($condition, $field = '*', $pagesize = 10, $order = 'expresscf_kdn_config_id desc') {
        if ($pagesize) {
            $result = Db::name('expresscf_kdn_config')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = Db::name('expresscf_kdn_config')->field($field)->where($condition)->order($order)->select()->toArray();
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
    public function getExpresscfKdnConfigInfo($condition) {
        $result = Db::name('expresscf_kdn_config')->where($condition)->find();
        return $result;
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addExpresscfKdnConfig($data) {
        $result = Db::name('expresscf_kdn_config')->insertGetId($data);
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
    public function editExpresscfKdnConfig($data, $condition) {
        $result = Db::name('expresscf_kdn_config')->where($condition)->update($data);
        return $result;
    }

    /**
     * 删除分类
     * @access public
     * @author csdeshang
     * @param int $condition 记录ID
     * @return bool 
     */
    public function delExpresscfKdnConfig($condition) {
        return Db::name('expresscf_kdn_config')->where($condition)->delete();
    }

    public function requestExpresscfKdnApi($requestData, $RequestType, $EBusinessID, $ApiKey) {
        $requestData = json_encode($requestData, JSON_UNESCAPED_UNICODE);
        // 组装系统级参数
        $datas = array(
            'EBusinessID' => $EBusinessID,
            'RequestType' => $RequestType,
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = urlencode(base64_encode(md5($requestData . $ApiKey)));
        //以form表单形式提交post请求，post请求体中包含了应用级参数和系统级参数
        $result = http_request('https://api.kdniao.com/api/EOrderService', 'POST', $datas);
        $result = json_decode($result, true);
        //根据公司业务处理返回的信息......
        return $result;
    }

    public function printExpresscfKdnOrder($requestData, $EBusinessID, $ApiKey) {
        $requestData = json_encode($requestData, JSON_UNESCAPED_UNICODE);
        $data_sign = urlencode(base64_encode(md5($this->get_ip() . $requestData . $ApiKey)));
        //是否预览，0-不预览 1-预览
        $is_priview = '0';

        //组装表单
        $form = '<form id="form1" method="POST" action="' . 'https://www.kdniao.com/External/PrintOrder.aspx' . '"><input type="text" name="RequestData" value=\'' . $requestData . '\'/><input type="text" name="EBusinessID" value="' . $EBusinessID . '"/><input type="text" name="DataSign" value="' . $data_sign . '"/><input type="text" name="IsPriview" value="' . $is_priview . '"/></form><script>form1.submit();</script>';
        return $form;
    }

    /**
     * 判断是否为内网IP
     * @param ip IP
     * @return 是否内网IP
     */
    private function is_private_ip($ip) {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * 获取客户端IP(非用户服务器IP)
     * @return 客户端IP
     */
    private function get_ip() {
        //获取客户端IP
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip = strstr($ip, '<!DOCTYPE html>', true);
        $ip = trim($ip);
        if (!$ip || $this->is_private_ip($ip)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.kdniao.com/External/GetIp.aspx');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            return $output;
        } else {
            return $ip;
        }
    }

}
