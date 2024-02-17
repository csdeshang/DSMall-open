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
 * DescribeBaselineInstanceDag请求参数结构体
 *
 * @method integer getBaselineInstanceId() 获取基线实例id
 * @method void setBaselineInstanceId(integer $BaselineInstanceId) 设置基线实例id
 * @method string getProjectId() 获取项目id
 * @method void setProjectId(string $ProjectId) 设置项目id
 * @method string getUpstreamInstanceIds() 获取要展开的上游实例id，格式为 taskIdA_curRunDate1,taskIdB_curRunDate2
 * @method void setUpstreamInstanceIds(string $UpstreamInstanceIds) 设置要展开的上游实例id，格式为 taskIdA_curRunDate1,taskIdB_curRunDate2
 * @method integer getLevel() 获取向上展开层级
 * @method void setLevel(integer $Level) 设置向上展开层级
 */
class DescribeBaselineInstanceDagRequest extends AbstractModel
{
    /**
     * @var integer 基线实例id
     */
    public $BaselineInstanceId;

    /**
     * @var string 项目id
     */
    public $ProjectId;

    /**
     * @var string 要展开的上游实例id，格式为 taskIdA_curRunDate1,taskIdB_curRunDate2
     */
    public $UpstreamInstanceIds;

    /**
     * @var integer 向上展开层级
     */
    public $Level;

    /**
     * @param integer $BaselineInstanceId 基线实例id
     * @param string $ProjectId 项目id
     * @param string $UpstreamInstanceIds 要展开的上游实例id，格式为 taskIdA_curRunDate1,taskIdB_curRunDate2
     * @param integer $Level 向上展开层级
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
        if (array_key_exists("BaselineInstanceId",$param) and $param["BaselineInstanceId"] !== null) {
            $this->BaselineInstanceId = $param["BaselineInstanceId"];
        }

        if (array_key_exists("ProjectId",$param) and $param["ProjectId"] !== null) {
            $this->ProjectId = $param["ProjectId"];
        }

        if (array_key_exists("UpstreamInstanceIds",$param) and $param["UpstreamInstanceIds"] !== null) {
            $this->UpstreamInstanceIds = $param["UpstreamInstanceIds"];
        }

        if (array_key_exists("Level",$param) and $param["Level"] !== null) {
            $this->Level = $param["Level"];
        }
    }
}
