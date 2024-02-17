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
namespace TencentCloud\Essbasic\V20210526\Models;
use TencentCloud\Common\AbstractModel;

/**
 * ChannelDescribeUserAutoSignStatus返回参数结构体
 *
 * @method boolean getIsOpen() 获取是否开通
 * @method void setIsOpen(boolean $IsOpen) 设置是否开通
 * @method integer getLicenseFrom() 获取自动签许可生效时间。当且仅当已开通自动签时有值。
 * @method void setLicenseFrom(integer $LicenseFrom) 设置自动签许可生效时间。当且仅当已开通自动签时有值。
 * @method integer getLicenseTo() 获取自动签许可到期时间。当且仅当已开通自动签时有值。
 * @method void setLicenseTo(integer $LicenseTo) 设置自动签许可到期时间。当且仅当已开通自动签时有值。
 * @method integer getLicenseType() 获取设置用户开通自动签时是否绑定个人自动签账号许可。一旦绑定后，将扣减购买的个人自动签账号许可一次（1年有效期），不可解绑释放。不传默认为绑定自动签账号许可。 0-绑定个人自动签账号许可，开通后将扣减购买的个人自动签账号许可一次
 * @method void setLicenseType(integer $LicenseType) 设置设置用户开通自动签时是否绑定个人自动签账号许可。一旦绑定后，将扣减购买的个人自动签账号许可一次（1年有效期），不可解绑释放。不传默认为绑定自动签账号许可。 0-绑定个人自动签账号许可，开通后将扣减购买的个人自动签账号许可一次
 * @method string getRequestId() 获取唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 * @method void setRequestId(string $RequestId) 设置唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 */
class ChannelDescribeUserAutoSignStatusResponse extends AbstractModel
{
    /**
     * @var boolean 是否开通
     */
    public $IsOpen;

    /**
     * @var integer 自动签许可生效时间。当且仅当已开通自动签时有值。
     */
    public $LicenseFrom;

    /**
     * @var integer 自动签许可到期时间。当且仅当已开通自动签时有值。
     */
    public $LicenseTo;

    /**
     * @var integer 设置用户开通自动签时是否绑定个人自动签账号许可。一旦绑定后，将扣减购买的个人自动签账号许可一次（1年有效期），不可解绑释放。不传默认为绑定自动签账号许可。 0-绑定个人自动签账号许可，开通后将扣减购买的个人自动签账号许可一次
     */
    public $LicenseType;

    /**
     * @var string 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
     */
    public $RequestId;

    /**
     * @param boolean $IsOpen 是否开通
     * @param integer $LicenseFrom 自动签许可生效时间。当且仅当已开通自动签时有值。
     * @param integer $LicenseTo 自动签许可到期时间。当且仅当已开通自动签时有值。
     * @param integer $LicenseType 设置用户开通自动签时是否绑定个人自动签账号许可。一旦绑定后，将扣减购买的个人自动签账号许可一次（1年有效期），不可解绑释放。不传默认为绑定自动签账号许可。 0-绑定个人自动签账号许可，开通后将扣减购买的个人自动签账号许可一次
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
        if (array_key_exists("IsOpen",$param) and $param["IsOpen"] !== null) {
            $this->IsOpen = $param["IsOpen"];
        }

        if (array_key_exists("LicenseFrom",$param) and $param["LicenseFrom"] !== null) {
            $this->LicenseFrom = $param["LicenseFrom"];
        }

        if (array_key_exists("LicenseTo",$param) and $param["LicenseTo"] !== null) {
            $this->LicenseTo = $param["LicenseTo"];
        }

        if (array_key_exists("LicenseType",$param) and $param["LicenseType"] !== null) {
            $this->LicenseType = $param["LicenseType"];
        }

        if (array_key_exists("RequestId",$param) and $param["RequestId"] !== null) {
            $this->RequestId = $param["RequestId"];
        }
    }
}
