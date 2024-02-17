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
namespace TencentCloud\Ess\V20201111\Models;
use TencentCloud\Common\AbstractModel;

/**
 * CreateFlowApprovers请求参数结构体
 *
 * @method UserInfo getOperator() 获取执行本接口操作的员工信息。
注: `在调用此接口时，请确保指定的员工已获得所需的接口调用权限，并具备接口传入的相应资源的数据权限。`
 * @method void setOperator(UserInfo $Operator) 设置执行本接口操作的员工信息。
注: `在调用此接口时，请确保指定的员工已获得所需的接口调用权限，并具备接口传入的相应资源的数据权限。`
 * @method string getFlowId() 获取合同流程ID，为32位字符串。
建议开发者妥善保存此流程ID，以便于顺利进行后续操作。
可登录腾讯电子签控制台，在 "合同"->"合同中心" 中查看某个合同的FlowId(在页面中展示为合同ID)。
 * @method void setFlowId(string $FlowId) 设置合同流程ID，为32位字符串。
建议开发者妥善保存此流程ID，以便于顺利进行后续操作。
可登录腾讯电子签控制台，在 "合同"->"合同中心" 中查看某个合同的FlowId(在页面中展示为合同ID)。
 * @method array getApprovers() 获取补充企业签署人信息。

- 如果发起方指定的补充签署人是企业微信签署人（ApproverSource=WEWORKAPP），则需要提供企业微信UserId进行补充；

- 如果不指定，则使用姓名和手机号进行补充。
 * @method void setApprovers(array $Approvers) 设置补充企业签署人信息。

- 如果发起方指定的补充签署人是企业微信签署人（ApproverSource=WEWORKAPP），则需要提供企业微信UserId进行补充；

- 如果不指定，则使用姓名和手机号进行补充。
 * @method string getInitiator() 获取在可定制的企业微信通知中，发起人可以根据具体需求进行自定义设置。
 * @method void setInitiator(string $Initiator) 设置在可定制的企业微信通知中，发起人可以根据具体需求进行自定义设置。
 * @method Agent getAgent() 获取代理企业和员工的信息。
在集团企业代理子企业操作的场景中，需设置此参数。在此情境下，ProxyOrganizationId（子企业的组织ID）为必填项。
 * @method void setAgent(Agent $Agent) 设置代理企业和员工的信息。
在集团企业代理子企业操作的场景中，需设置此参数。在此情境下，ProxyOrganizationId（子企业的组织ID）为必填项。
 * @method integer getFillApproverType() 获取签署人信息补充方式

<ul><li>**0**: 补充或签人，支持补充多个企业经办签署人（默认）注: `不可补充个人签署人`</li>
<li>**1**: 补充动态签署人，可补充企业和个人签署人。注: `每个签署方节点签署人是唯一的，一个节点只支持传入一个签署人信息`</li></ul>
 * @method void setFillApproverType(integer $FillApproverType) 设置签署人信息补充方式

<ul><li>**0**: 补充或签人，支持补充多个企业经办签署人（默认）注: `不可补充个人签署人`</li>
<li>**1**: 补充动态签署人，可补充企业和个人签署人。注: `每个签署方节点签署人是唯一的，一个节点只支持传入一个签署人信息`</li></ul>
 */
class CreateFlowApproversRequest extends AbstractModel
{
    /**
     * @var UserInfo 执行本接口操作的员工信息。
注: `在调用此接口时，请确保指定的员工已获得所需的接口调用权限，并具备接口传入的相应资源的数据权限。`
     */
    public $Operator;

    /**
     * @var string 合同流程ID，为32位字符串。
建议开发者妥善保存此流程ID，以便于顺利进行后续操作。
可登录腾讯电子签控制台，在 "合同"->"合同中心" 中查看某个合同的FlowId(在页面中展示为合同ID)。
     */
    public $FlowId;

    /**
     * @var array 补充企业签署人信息。

- 如果发起方指定的补充签署人是企业微信签署人（ApproverSource=WEWORKAPP），则需要提供企业微信UserId进行补充；

- 如果不指定，则使用姓名和手机号进行补充。
     */
    public $Approvers;

    /**
     * @var string 在可定制的企业微信通知中，发起人可以根据具体需求进行自定义设置。
     */
    public $Initiator;

    /**
     * @var Agent 代理企业和员工的信息。
在集团企业代理子企业操作的场景中，需设置此参数。在此情境下，ProxyOrganizationId（子企业的组织ID）为必填项。
     */
    public $Agent;

    /**
     * @var integer 签署人信息补充方式

<ul><li>**0**: 补充或签人，支持补充多个企业经办签署人（默认）注: `不可补充个人签署人`</li>
<li>**1**: 补充动态签署人，可补充企业和个人签署人。注: `每个签署方节点签署人是唯一的，一个节点只支持传入一个签署人信息`</li></ul>
     */
    public $FillApproverType;

    /**
     * @param UserInfo $Operator 执行本接口操作的员工信息。
注: `在调用此接口时，请确保指定的员工已获得所需的接口调用权限，并具备接口传入的相应资源的数据权限。`
     * @param string $FlowId 合同流程ID，为32位字符串。
建议开发者妥善保存此流程ID，以便于顺利进行后续操作。
可登录腾讯电子签控制台，在 "合同"->"合同中心" 中查看某个合同的FlowId(在页面中展示为合同ID)。
     * @param array $Approvers 补充企业签署人信息。

- 如果发起方指定的补充签署人是企业微信签署人（ApproverSource=WEWORKAPP），则需要提供企业微信UserId进行补充；

- 如果不指定，则使用姓名和手机号进行补充。
     * @param string $Initiator 在可定制的企业微信通知中，发起人可以根据具体需求进行自定义设置。
     * @param Agent $Agent 代理企业和员工的信息。
在集团企业代理子企业操作的场景中，需设置此参数。在此情境下，ProxyOrganizationId（子企业的组织ID）为必填项。
     * @param integer $FillApproverType 签署人信息补充方式

<ul><li>**0**: 补充或签人，支持补充多个企业经办签署人（默认）注: `不可补充个人签署人`</li>
<li>**1**: 补充动态签署人，可补充企业和个人签署人。注: `每个签署方节点签署人是唯一的，一个节点只支持传入一个签署人信息`</li></ul>
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
        if (array_key_exists("Operator",$param) and $param["Operator"] !== null) {
            $this->Operator = new UserInfo();
            $this->Operator->deserialize($param["Operator"]);
        }

        if (array_key_exists("FlowId",$param) and $param["FlowId"] !== null) {
            $this->FlowId = $param["FlowId"];
        }

        if (array_key_exists("Approvers",$param) and $param["Approvers"] !== null) {
            $this->Approvers = [];
            foreach ($param["Approvers"] as $key => $value){
                $obj = new FillApproverInfo();
                $obj->deserialize($value);
                array_push($this->Approvers, $obj);
            }
        }

        if (array_key_exists("Initiator",$param) and $param["Initiator"] !== null) {
            $this->Initiator = $param["Initiator"];
        }

        if (array_key_exists("Agent",$param) and $param["Agent"] !== null) {
            $this->Agent = new Agent();
            $this->Agent->deserialize($param["Agent"]);
        }

        if (array_key_exists("FillApproverType",$param) and $param["FillApproverType"] !== null) {
            $this->FillApproverType = $param["FillApproverType"];
        }
    }
}
