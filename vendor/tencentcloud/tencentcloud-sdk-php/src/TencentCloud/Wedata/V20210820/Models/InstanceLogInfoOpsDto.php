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
namespace TencentCloud\Wedata\V20210820\Models;
use TencentCloud\Common\AbstractModel;

/**
 * 实例日志信息详情
 *
 * @method string getLogInfo() 获取实例运行日志
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setLogInfo(string $LogInfo) 设置实例运行日志
注意：此字段可能返回 null，表示取不到有效值。
 * @method array getYarnLogInfo() 获取实例运行提交的yarn日志地址
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setYarnLogInfo(array $YarnLogInfo) 设置实例运行提交的yarn日志地址
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getDataLogInfo() 获取实例运行产生的datax日志
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setDataLogInfo(string $DataLogInfo) 设置实例运行产生的datax日志
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getThirdTaskRunLogInfo() 获取第三方任务运行日志
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setThirdTaskRunLogInfo(string $ThirdTaskRunLogInfo) 设置第三方任务运行日志
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getThirdTaskLogUrlDesc() 获取第三方任务日志链接描述
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setThirdTaskLogUrlDesc(string $ThirdTaskLogUrlDesc) 设置第三方任务日志链接描述
注意：此字段可能返回 null，表示取不到有效值。
 */
class InstanceLogInfoOpsDto extends AbstractModel
{
    /**
     * @var string 实例运行日志
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $LogInfo;

    /**
     * @var array 实例运行提交的yarn日志地址
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $YarnLogInfo;

    /**
     * @var string 实例运行产生的datax日志
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $DataLogInfo;

    /**
     * @var string 第三方任务运行日志
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ThirdTaskRunLogInfo;

    /**
     * @var string 第三方任务日志链接描述
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $ThirdTaskLogUrlDesc;

    /**
     * @param string $LogInfo 实例运行日志
注意：此字段可能返回 null，表示取不到有效值。
     * @param array $YarnLogInfo 实例运行提交的yarn日志地址
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $DataLogInfo 实例运行产生的datax日志
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $ThirdTaskRunLogInfo 第三方任务运行日志
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $ThirdTaskLogUrlDesc 第三方任务日志链接描述
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
        if (array_key_exists("LogInfo",$param) and $param["LogInfo"] !== null) {
            $this->LogInfo = $param["LogInfo"];
        }

        if (array_key_exists("YarnLogInfo",$param) and $param["YarnLogInfo"] !== null) {
            $this->YarnLogInfo = $param["YarnLogInfo"];
        }

        if (array_key_exists("DataLogInfo",$param) and $param["DataLogInfo"] !== null) {
            $this->DataLogInfo = $param["DataLogInfo"];
        }

        if (array_key_exists("ThirdTaskRunLogInfo",$param) and $param["ThirdTaskRunLogInfo"] !== null) {
            $this->ThirdTaskRunLogInfo = $param["ThirdTaskRunLogInfo"];
        }

        if (array_key_exists("ThirdTaskLogUrlDesc",$param) and $param["ThirdTaskLogUrlDesc"] !== null) {
            $this->ThirdTaskLogUrlDesc = $param["ThirdTaskLogUrlDesc"];
        }
    }
}
