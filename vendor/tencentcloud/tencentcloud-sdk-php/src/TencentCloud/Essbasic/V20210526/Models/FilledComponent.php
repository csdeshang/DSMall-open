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
 * 文档内的填充控件返回结构体，返回控件的基本信息和填写内容值
 *
 * @method string getComponentId() 获取控件Id
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setComponentId(string $ComponentId) 设置控件Id
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getComponentName() 获取控件名称
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setComponentName(string $ComponentName) 设置控件名称
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getComponentFillStatus() 获取控件填写状态；0-未填写；1-已填写
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setComponentFillStatus(string $ComponentFillStatus) 设置控件填写状态；0-未填写；1-已填写
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getComponentValue() 获取控件填写内容
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setComponentValue(string $ComponentValue) 设置控件填写内容
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getImageUrl() 获取图片填充控件下载链接，如果是图片填充控件时，这里返回图片的下载链接。
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setImageUrl(string $ImageUrl) 设置图片填充控件下载链接，如果是图片填充控件时，这里返回图片的下载链接。
注意：此字段可能返回 null，表示取不到有效值。
 */
class FilledComponent extends AbstractModel
{
    /**
     * @var string 控件Id
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ComponentId;

    /**
     * @var string 控件名称
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ComponentName;

    /**
     * @var string 控件填写状态；0-未填写；1-已填写
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ComponentFillStatus;

    /**
     * @var string 控件填写内容
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ComponentValue;

    /**
     * @var string 图片填充控件下载链接，如果是图片填充控件时，这里返回图片的下载链接。
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ImageUrl;

    /**
     * @param string $ComponentId 控件Id
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $ComponentName 控件名称
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $ComponentFillStatus 控件填写状态；0-未填写；1-已填写
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $ComponentValue 控件填写内容
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $ImageUrl 图片填充控件下载链接，如果是图片填充控件时，这里返回图片的下载链接。
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
        if (array_key_exists("ComponentId",$param) and $param["ComponentId"] !== null) {
            $this->ComponentId = $param["ComponentId"];
        }

        if (array_key_exists("ComponentName",$param) and $param["ComponentName"] !== null) {
            $this->ComponentName = $param["ComponentName"];
        }

        if (array_key_exists("ComponentFillStatus",$param) and $param["ComponentFillStatus"] !== null) {
            $this->ComponentFillStatus = $param["ComponentFillStatus"];
        }

        if (array_key_exists("ComponentValue",$param) and $param["ComponentValue"] !== null) {
            $this->ComponentValue = $param["ComponentValue"];
        }

        if (array_key_exists("ImageUrl",$param) and $param["ImageUrl"] !== null) {
            $this->ImageUrl = $param["ImageUrl"];
        }
    }
}
