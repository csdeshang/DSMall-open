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
 * DescribeDrInstanceScriptContent请求参数结构体
 *
 * @method string getProjectId() 获取项目id
 * @method void setProjectId(string $ProjectId) 设置项目id
 * @method string getTaskSource() 获取任务来源 ADHOC || WORKFLOW
 * @method void setTaskSource(string $TaskSource) 设置任务来源 ADHOC || WORKFLOW
 * @method integer getRecordId() 获取试运行记录id
 * @method void setRecordId(integer $RecordId) 设置试运行记录id
 * @method integer getSonRecordId() 获取试运行子记录id
 * @method void setSonRecordId(integer $SonRecordId) 设置试运行子记录id
 */
class DescribeDrInstanceScriptContentRequest extends AbstractModel
{
    /**
     * @var string 项目id
     */
    public $ProjectId;

    /**
     * @var string 任务来源 ADHOC || WORKFLOW
     */
    public $TaskSource;

    /**
     * @var integer 试运行记录id
     */
    public $RecordId;

    /**
     * @var integer 试运行子记录id
     */
    public $SonRecordId;

    /**
     * @param string $ProjectId 项目id
     * @param string $TaskSource 任务来源 ADHOC || WORKFLOW
     * @param integer $RecordId 试运行记录id
     * @param integer $SonRecordId 试运行子记录id
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
        if (array_key_exists("ProjectId",$param) and $param["ProjectId"] !== null) {
            $this->ProjectId = $param["ProjectId"];
        }

        if (array_key_exists("TaskSource",$param) and $param["TaskSource"] !== null) {
            $this->TaskSource = $param["TaskSource"];
        }

        if (array_key_exists("RecordId",$param) and $param["RecordId"] !== null) {
            $this->RecordId = $param["RecordId"];
        }

        if (array_key_exists("SonRecordId",$param) and $param["SonRecordId"] !== null) {
            $this->SonRecordId = $param["SonRecordId"];
        }
    }
}
