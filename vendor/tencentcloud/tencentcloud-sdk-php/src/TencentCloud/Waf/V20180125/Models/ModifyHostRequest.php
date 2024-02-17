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
namespace TencentCloud\Waf\V20180125\Models;
use TencentCloud\Common\AbstractModel;

/**
 * ModifyHost请求参数结构体
 *
 * @method HostRecord getHost() 获取编辑的域名配置信息
 * @method void setHost(HostRecord $Host) 设置编辑的域名配置信息
 * @method string getInstanceID() 获取实例id
 * @method void setInstanceID(string $InstanceID) 设置实例id
 */
class ModifyHostRequest extends AbstractModel
{
    /**
     * @var HostRecord 编辑的域名配置信息
     */
    public $Host;

    /**
     * @var string 实例id
     */
    public $InstanceID;

    /**
     * @param HostRecord $Host 编辑的域名配置信息
     * @param string $InstanceID 实例id
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
        if (array_key_exists("Host",$param) and $param["Host"] !== null) {
            $this->Host = new HostRecord();
            $this->Host->deserialize($param["Host"]);
        }

        if (array_key_exists("InstanceID",$param) and $param["InstanceID"] !== null) {
            $this->InstanceID = $param["InstanceID"];
        }
    }
}
