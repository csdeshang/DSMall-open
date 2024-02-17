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
 * DeleteIntegrationTask返回参数结构体
 *
 * @method boolean getData() 获取任务删除成功与否标识
 * @method void setData(boolean $Data) 设置任务删除成功与否标识
 * @method integer getDeleteFlag() 获取任务删除成功与否标识
0表示删除成功
1 表示失败，失败原因见 DeleteErrInfo
100 表示running or suspend task can't be deleted失败，失败原因也会写到DeleteErrInfo里面
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setDeleteFlag(integer $DeleteFlag) 设置任务删除成功与否标识
0表示删除成功
1 表示失败，失败原因见 DeleteErrInfo
100 表示running or suspend task can't be deleted失败，失败原因也会写到DeleteErrInfo里面
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getDeleteErrInfo() 获取删除失败原因
注意：此字段可能返回 null，表示取不到有效值。
 * @method void setDeleteErrInfo(string $DeleteErrInfo) 设置删除失败原因
注意：此字段可能返回 null，表示取不到有效值。
 * @method string getRequestId() 获取唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 * @method void setRequestId(string $RequestId) 设置唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
 */
class DeleteIntegrationTaskResponse extends AbstractModel
{
    /**
     * @var boolean 任务删除成功与否标识
     */
    public $Data;

    /**
     * @var integer 任务删除成功与否标识
0表示删除成功
1 表示失败，失败原因见 DeleteErrInfo
100 表示running or suspend task can't be deleted失败，失败原因也会写到DeleteErrInfo里面
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $DeleteFlag;

    /**
     * @var string 删除失败原因
注意：此字段可能返回 null，表示取不到有效值。
     */
    public $DeleteErrInfo;

    /**
     * @var string 唯一请求 ID，每次请求都会返回。定位问题时需要提供该次请求的 RequestId。
     */
    public $RequestId;

    /**
     * @param boolean $Data 任务删除成功与否标识
     * @param integer $DeleteFlag 任务删除成功与否标识
0表示删除成功
1 表示失败，失败原因见 DeleteErrInfo
100 表示running or suspend task can't be deleted失败，失败原因也会写到DeleteErrInfo里面
注意：此字段可能返回 null，表示取不到有效值。
     * @param string $DeleteErrInfo 删除失败原因
注意：此字段可能返回 null，表示取不到有效值。
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
        if (array_key_exists("Data",$param) and $param["Data"] !== null) {
            $this->Data = $param["Data"];
        }

        if (array_key_exists("DeleteFlag",$param) and $param["DeleteFlag"] !== null) {
            $this->DeleteFlag = $param["DeleteFlag"];
        }

        if (array_key_exists("DeleteErrInfo",$param) and $param["DeleteErrInfo"] !== null) {
            $this->DeleteErrInfo = $param["DeleteErrInfo"];
        }

        if (array_key_exists("RequestId",$param) and $param["RequestId"] !== null) {
            $this->RequestId = $param["RequestId"];
        }
    }
}
