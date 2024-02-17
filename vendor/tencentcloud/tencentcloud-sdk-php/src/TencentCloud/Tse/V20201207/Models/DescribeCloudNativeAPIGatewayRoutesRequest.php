<?php
/*
 * Copyright (c) 2017-2018 THL A29 Limited, a Tencent company. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentCloud\Tse\V20201207\Models;
use TencentCloud\Common\AbstractModel;

/**
 * DescribeCloudNativeAPIGatewayRoutes请求参数结构体
 *
 * @method string getGatewayId() 获取网关ID
 * @method void setGatewayId(string $GatewayId) 设置网关ID
 * @method integer getLimit() 获取翻页单页查询限制数量[0,1000], 默认值0
 * @method void setLimit(integer $Limit) 设置翻页单页查询限制数量[0,1000], 默认值0
 * @method integer getOffset() 获取翻页单页偏移量，默认值0
 * @method void setOffset(integer $Offset) 设置翻页单页偏移量，默认值0
 * @method string getServiceName() 获取服务的名字，精确匹配
 * @method void setServiceName(string $ServiceName) 设置服务的名字，精确匹配
 * @method string getRouteName() 获取路由的名字，精确匹配
 * @method void setRouteName(string $RouteName) 设置路由的名字，精确匹配
 * @method array getFilters() 获取过滤条件，多个过滤条件之间是与的关系，支持 name, path, host, method, service, protocol
 * @method void setFilters(array $Filters) 设置过滤条件，多个过滤条件之间是与的关系，支持 name, path, host, method, service, protocol
 */
class DescribeCloudNativeAPIGatewayRoutesRequest extends AbstractModel
{
    /**
     * @var string 网关ID
     */
    public $GatewayId;

    /**
     * @var integer 翻页单页查询限制数量[0,1000], 默认值0
     */
    public $Limit;

    /**
     * @var integer 翻页单页偏移量，默认值0
     */
    public $Offset;

    /**
     * @var string 服务的名字，精确匹配
     */
    public $ServiceName;

    /**
     * @var string 路由的名字，精确匹配
     */
    public $RouteName;

    /**
     * @var array 过滤条件，多个过滤条件之间是与的关系，支持 name, path, host, method, service, protocol
     */
    public $Filters;

    /**
     * @param string $GatewayId 网关ID
     * @param integer $Limit 翻页单页查询限制数量[0,1000], 默认值0
     * @param integer $Offset 翻页单页偏移量，默认值0
     * @param string $ServiceName 服务的名字，精确匹配
     * @param string $RouteName 路由的名字，精确匹配
     * @param array $Filters 过滤条件，多个过滤条件之间是与的关系，支持 name, path, host, method, service, protocol
     */
    function __construct()
    {

    }

    /**
     * For internal only. DO NOT USE IT.
     */
    public function deserialize($param)
    {
        if ($param === null) {
            return;
        }
        if (array_key_exists("GatewayId",$param) and $param["GatewayId"] !== null) {
            $this->GatewayId = $param["GatewayId"];
        }

        if (array_key_exists("Limit",$param) and $param["Limit"] !== null) {
            $this->Limit = $param["Limit"];
        }

        if (array_key_exists("Offset",$param) and $param["Offset"] !== null) {
            $this->Offset = $param["Offset"];
        }

        if (array_key_exists("ServiceName",$param) and $param["ServiceName"] !== null) {
            $this->ServiceName = $param["ServiceName"];
        }

        if (array_key_exists("RouteName",$param) and $param["RouteName"] !== null) {
            $this->RouteName = $param["RouteName"];
        }

        if (array_key_exists("Filters",$param) and $param["Filters"] !== null) {
            $this->Filters = [];
            foreach ($param["Filters"] as $key => $value){
                $obj = new ListFilter();
                $obj->deserialize($value);
                array_push($this->Filters, $obj);
            }
        }
    }
}
