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
namespace TencentCloud\Ess\V20201111\Models;
use TencentCloud\Common\AbstractModel;

/**
 * DescribeThirdPartyAuthCode返回参数结构体
 *
 * @method string getVerifyStatus() 获取AuthCode 中对应个人用户是否实名
<ul>
<li> **VERIFIED** : 此个人已实名</li>
<li> **UNVERIFIED**: 此个人未实名</li></ul>
 * @method void setVerifyStatus(string $VerifyStatus) 设置AuthCode 中对应个人用户是否实名
<ul>
<li> **VERIFIED** : 此个人已实名</li>
<li> **UNVERIFIED**: 此个人未实名</li></ul>
 * @method string getRequestId() 获取唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 * @method void setRequestId(string $RequestId) 设置唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 */
class DescribeThirdPartyAuthCodeResponse extends AbstractModel
{
    /**
     * @var string AuthCode 中对应个人用户是否实名
<ul>
<li> **VERIFIED** : 此个人已实名</li>
<li> **UNVERIFIED**: 此个人未实名</li></ul>
     */
    public $VerifyStatus;

    /**
     * @var string 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
     */
    public $RequestId;

    /**
     * @param string $VerifyStatus AuthCode 中对应个人用户是否实名
<ul>
<li> **VERIFIED** : 此个人已实名</li>
<li> **UNVERIFIED**: 此个人未实名</li></ul>
     * @param string $RequestId 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
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
        if (array_key_exists("VerifyStatus",$param) and $param["VerifyStatus"] !== null) {
            $this->VerifyStatus = $param["VerifyStatus"];
        }

        if (array_key_exists("RequestId",$param) and $param["RequestId"] !== null) {
            $this->RequestId = $param["RequestId"];
        }
    }
}
