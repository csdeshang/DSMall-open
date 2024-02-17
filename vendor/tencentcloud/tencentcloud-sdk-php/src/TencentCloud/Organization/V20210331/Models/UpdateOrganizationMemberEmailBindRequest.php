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
namespace TencentCloud\Organization\V20210331\Models;
use TencentCloud\Common\AbstractModel;

/**
 * UpdateOrganizationMemberEmailBind请求参数结构体
 *
 * @method integer getMemberUin() 获取成员Uin
 * @method void setMemberUin(integer $MemberUin) 设置成员Uin
 * @method integer getBindId() 获取绑定ID
 * @method void setBindId(integer $BindId) 设置绑定ID
 * @method string getEmail() 获取邮箱
 * @method void setEmail(string $Email) 设置邮箱
 * @method string getCountryCode() 获取国际区号
 * @method void setCountryCode(string $CountryCode) 设置国际区号
 * @method string getPhone() 获取手机号
 * @method void setPhone(string $Phone) 设置手机号
 */
class UpdateOrganizationMemberEmailBindRequest extends AbstractModel
{
    /**
     * @var integer 成员Uin
     */
    public $MemberUin;

    /**
     * @var integer 绑定ID
     */
    public $BindId;

    /**
     * @var string 邮箱
     */
    public $Email;

    /**
     * @var string 国际区号
     */
    public $CountryCode;

    /**
     * @var string 手机号
     */
    public $Phone;

    /**
     * @param integer $MemberUin 成员Uin
     * @param integer $BindId 绑定ID
     * @param string $Email 邮箱
     * @param string $CountryCode 国际区号
     * @param string $Phone 手机号
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
        if (array_key_exists("MemberUin",$param) and $param["MemberUin"] !== null) {
            $this->MemberUin = $param["MemberUin"];
        }

        if (array_key_exists("BindId",$param) and $param["BindId"] !== null) {
            $this->BindId = $param["BindId"];
        }

        if (array_key_exists("Email",$param) and $param["Email"] !== null) {
            $this->Email = $param["Email"];
        }

        if (array_key_exists("CountryCode",$param) and $param["CountryCode"] !== null) {
            $this->CountryCode = $param["CountryCode"];
        }

        if (array_key_exists("Phone",$param) and $param["Phone"] !== null) {
            $this->Phone = $param["Phone"];
        }
    }
}
