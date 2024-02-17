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
namespace TencentCloud\Iotexplorer\V20190423\Models;
use TencentCloud\Common\AbstractModel;

/**
 * 实例信息
公共实例过期时间 0001-01-01T00:00:00Z，公共实例是永久有效
 *
 * @method string getInstanceId() 获取实例ID
 * @method void setInstanceId(string $InstanceId) 设置实例ID
 * @method integer getInstanceType() 获取实例类型（0 公共实例 1 标准企业实例 2专享企业实例）
 * @method void setInstanceType(integer $InstanceType) 设置实例类型（0 公共实例 1 标准企业实例 2专享企业实例）
 * @method string getRegion() 获取地域字母缩写
 * @method void setRegion(string $Region) 设置地域字母缩写
 * @method string getZoneId() 获取区域全拼
 * @method void setZoneId(string $ZoneId) 设置区域全拼
 * @method integer getTotalDeviceNum() 获取支持设备总数
 * @method void setTotalDeviceNum(integer $TotalDeviceNum) 设置支持设备总数
 * @method integer getUsedDeviceNum() 获取以注册设备数
 * @method void setUsedDeviceNum(integer $UsedDeviceNum) 设置以注册设备数
 * @method integer getProjectNum() 获取项目数
 * @method void setProjectNum(integer $ProjectNum) 设置项目数
 * @method integer getProductNum() 获取产品数
 * @method void setProductNum(integer $ProductNum) 设置产品数
 * @method string getCreateTime() 获取创建时间
 * @method void setCreateTime(string $CreateTime) 设置创建时间
 * @method string getUpdateTime() 获取更新时间
 * @method void setUpdateTime(string $UpdateTime) 设置更新时间
 * @method string getExpireTime() 获取过期时间，公共实例过期时间 0001-01-01T00:00:00Z，公共实例是永久有效
 * @method void setExpireTime(string $ExpireTime) 设置过期时间，公共实例过期时间 0001-01-01T00:00:00Z，公共实例是永久有效
 * @method integer getTotalDevice() 获取总设备数
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setTotalDevice(integer $TotalDevice) 设置总设备数
注意：此字段可能返回 null，表示取不到有效值。
 * @method integer getActivateDevice() 获取激活设备数
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setActivateDevice(integer $ActivateDevice) 设置激活设备数
注意：此字段可能返回 null，表示取不到有效值。
 */
class InstanceDetail extends AbstractModel
{
    /**
     * @var string 实例ID
     */
    public $InstanceId;

    /**
     * @var integer 实例类型（0 公共实例 1 标准企业实例 2专享企业实例）
     */
    public $InstanceType;

    /**
     * @var string 地域字母缩写
     */
    public $Region;

    /**
     * @var string 区域全拼
     */
    public $ZoneId;

    /**
     * @var integer 支持设备总数
     */
    public $TotalDeviceNum;

    /**
     * @var integer 以注册设备数
     */
    public $UsedDeviceNum;

    /**
     * @var integer 项目数
     */
    public $ProjectNum;

    /**
     * @var integer 产品数
     */
    public $ProductNum;

    /**
     * @var string 创建时间
     */
    public $CreateTime;

    /**
     * @var string 更新时间
     */
    public $UpdateTime;

    /**
     * @var string 过期时间，公共实例过期时间 0001-01-01T00:00:00Z，公共实例是永久有效
     */
    public $ExpireTime;

    /**
     * @var integer 总设备数
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $TotalDevice;

    /**
     * @var integer 激活设备数
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ActivateDevice;

    /**
     * @param string $InstanceId 实例ID
     * @param integer $InstanceType 实例类型（0 公共实例 1 标准企业实例 2专享企业实例）
     * @param string $Region 地域字母缩写
     * @param string $ZoneId 区域全拼
     * @param integer $TotalDeviceNum 支持设备总数
     * @param integer $UsedDeviceNum 以注册设备数
     * @param integer $ProjectNum 项目数
     * @param integer $ProductNum 产品数
     * @param string $CreateTime 创建时间
     * @param string $UpdateTime 更新时间
     * @param string $ExpireTime 过期时间，公共实例过期时间 0001-01-01T00:00:00Z，公共实例是永久有效
     * @param integer $TotalDevice 总设备数
注意：此字段可能返回 null，表示取不到有效值。
     * @param integer $ActivateDevice 激活设备数
注意：此字段可能返回 null，表示取不到有效值。
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
        if (array_key_exists("InstanceId",$param) and $param["InstanceId"] !== null) {
            $this->InstanceId = $param["InstanceId"];
        }

        if (array_key_exists("InstanceType",$param) and $param["InstanceType"] !== null) {
            $this->InstanceType = $param["InstanceType"];
        }

        if (array_key_exists("Region",$param) and $param["Region"] !== null) {
            $this->Region = $param["Region"];
        }

        if (array_key_exists("ZoneId",$param) and $param["ZoneId"] !== null) {
            $this->ZoneId = $param["ZoneId"];
        }

        if (array_key_exists("TotalDeviceNum",$param) and $param["TotalDeviceNum"] !== null) {
            $this->TotalDeviceNum = $param["TotalDeviceNum"];
        }

        if (array_key_exists("UsedDeviceNum",$param) and $param["UsedDeviceNum"] !== null) {
            $this->UsedDeviceNum = $param["UsedDeviceNum"];
        }

        if (array_key_exists("ProjectNum",$param) and $param["ProjectNum"] !== null) {
            $this->ProjectNum = $param["ProjectNum"];
        }

        if (array_key_exists("ProductNum",$param) and $param["ProductNum"] !== null) {
            $this->ProductNum = $param["ProductNum"];
        }

        if (array_key_exists("CreateTime",$param) and $param["CreateTime"] !== null) {
            $this->CreateTime = $param["CreateTime"];
        }

        if (array_key_exists("UpdateTime",$param) and $param["UpdateTime"] !== null) {
            $this->UpdateTime = $param["UpdateTime"];
        }

        if (array_key_exists("ExpireTime",$param) and $param["ExpireTime"] !== null) {
            $this->ExpireTime = $param["ExpireTime"];
        }

        if (array_key_exists("TotalDevice",$param) and $param["TotalDevice"] !== null) {
            $this->TotalDevice = $param["TotalDevice"];
        }

        if (array_key_exists("ActivateDevice",$param) and $param["ActivateDevice"] !== null) {
            $this->ActivateDevice = $param["ActivateDevice"];
        }
    }
}
