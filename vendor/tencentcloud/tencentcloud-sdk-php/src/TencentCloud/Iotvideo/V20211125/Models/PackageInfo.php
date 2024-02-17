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
namespace TencentCloud\Iotvideo\V20211125\Models;
use TencentCloud\Common\AbstractModel;

/**
 * 结构体（PackageInfo）记录了设备拥有的有效套餐信息，包括云存开启状态、云存类型、云存回看时长、云存套餐过期时间
 *
 * @method integer getStatus() 获取云存开启状态，0为未开启，2为正在生效，1为已过期
注：这里只返回状态为0的数据
 * @method void setStatus(integer $Status) 设置云存开启状态，0为未开启，2为正在生效，1为已过期
注：这里只返回状态为0的数据
 * @method integer getCSType() 获取云存类型，1为全时云存，2为事件云存
 * @method void setCSType(integer $CSType) 设置云存类型，1为全时云存，2为事件云存
 * @method integer getCSShiftDuration() 获取云存回看时长
 * @method void setCSShiftDuration(integer $CSShiftDuration) 设置云存回看时长
 * @method integer getCSExpiredTime() 获取云存套餐过期时间
 * @method void setCSExpiredTime(integer $CSExpiredTime) 设置云存套餐过期时间
 * @method integer getCreatedAt() 获取云存套餐创建时间
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setCreatedAt(integer $CreatedAt) 设置云存套餐创建时间
注意：此字段可能返回 null，表示取不到有效值。
 * @method integer getUpdatedAt() 获取云存套餐更新时间
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setUpdatedAt(integer $UpdatedAt) 设置云存套餐更新时间
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getPackageId() 获取套餐id
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setPackageId(string $PackageId) 设置套餐id
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getOrderId() 获取订单id
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setOrderId(string $OrderId) 设置订单id
注意：此字段可能返回 null，表示取不到有效值。
 */
class PackageInfo extends AbstractModel
{
    /**
     * @var integer 云存开启状态，0为未开启，2为正在生效，1为已过期
注：这里只返回状态为0的数据
     */
    public $Status;

    /**
     * @var integer 云存类型，1为全时云存，2为事件云存
     */
    public $CSType;

    /**
     * @var integer 云存回看时长
     */
    public $CSShiftDuration;

    /**
     * @var integer 云存套餐过期时间
     */
    public $CSExpiredTime;

    /**
     * @var integer 云存套餐创建时间
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $CreatedAt;

    /**
     * @var integer 云存套餐更新时间
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $UpdatedAt;

    /**
     * @var string 套餐id
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $PackageId;

    /**
     * @var string 订单id
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $OrderId;

    /**
     * @param integer $Status 云存开启状态，0为未开启，2为正在生效，1为已过期
注：这里只返回状态为0的数据
     * @param integer $CSType 云存类型，1为全时云存，2为事件云存
     * @param integer $CSShiftDuration 云存回看时长
     * @param integer $CSExpiredTime 云存套餐过期时间
     * @param integer $CreatedAt 云存套餐创建时间
注意：此字段可能返回 null，表示取不到有效值。
     * @param integer $UpdatedAt 云存套餐更新时间
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $PackageId 套餐id
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $OrderId 订单id
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
        if (array_key_exists("Status",$param) and $param["Status"] !== null) {
            $this->Status = $param["Status"];
        }

        if (array_key_exists("CSType",$param) and $param["CSType"] !== null) {
            $this->CSType = $param["CSType"];
        }

        if (array_key_exists("CSShiftDuration",$param) and $param["CSShiftDuration"] !== null) {
            $this->CSShiftDuration = $param["CSShiftDuration"];
        }

        if (array_key_exists("CSExpiredTime",$param) and $param["CSExpiredTime"] !== null) {
            $this->CSExpiredTime = $param["CSExpiredTime"];
        }

        if (array_key_exists("CreatedAt",$param) and $param["CreatedAt"] !== null) {
            $this->CreatedAt = $param["CreatedAt"];
        }

        if (array_key_exists("UpdatedAt",$param) and $param["UpdatedAt"] !== null) {
            $this->UpdatedAt = $param["UpdatedAt"];
        }

        if (array_key_exists("PackageId",$param) and $param["PackageId"] !== null) {
            $this->PackageId = $param["PackageId"];
        }

        if (array_key_exists("OrderId",$param) and $param["OrderId"] !== null) {
            $this->OrderId = $param["OrderId"];
        }
    }
}
