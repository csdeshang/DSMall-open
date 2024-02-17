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
 * DescribeBaselineInstanceGantt请求参数结构体
 *
 * @method integer getBaselineInstanceId() 获取基线实例id
 * @method void setBaselineInstanceId(integer $BaselineInstanceId) 设置基线实例id
 * @method string getProjectId() 获取项目id
 * @method void setProjectId(string $ProjectId) 设置项目id
 */
class DescribeBaselineInstanceGanttRequest extends AbstractModel
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
     * @param integer $BaselineInstanceId 基线实例id
     * @param string $ProjectId 项目id
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
    }
}
