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
 * CreateFlowSignReview请求参数结构体
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
 * @method string getReviewType() 获取企业审核结果
<ul><li>PASS: 通过</li> 
<li>REJECT: 拒绝</li></ul>
 * @method void setReviewType(string $ReviewType) 设置企业审核结果
<ul><li>PASS: 通过</li> 
<li>REJECT: 拒绝</li></ul>
 * @method string getReviewMessage() 获取审核结果原因，
字符串长度不超过200
当ReviewType 是拒绝（REJECT） 时此字段必填。

 * @method void setReviewMessage(string $ReviewMessage) 设置审核结果原因，
字符串长度不超过200
当ReviewType 是拒绝（REJECT） 时此字段必填。

 * @method Agent getAgent() 获取代理相关应用信息，如集团主企业代子企业操作的场景中ProxyOrganizationId必填
 * @method void setAgent(Agent $Agent) 设置代理相关应用信息，如集团主企业代子企业操作的场景中ProxyOrganizationId必填
 * @method string getRecipientId() 获取审核签署节点人标识，
用来标识审核的签署方。
如果签署审核节点是个人， 此参数必填。
 * @method void setRecipientId(string $RecipientId) 设置审核签署节点人标识，
用来标识审核的签署方。
如果签署审核节点是个人， 此参数必填。
 * @method string getOperateType() 获取操作类型：（接口通过该字段区分不同的操作类型）

<ul><li>SignReview: 签署审核（默认）</li>
<li>CreateReview: 创建审核</li></ul>

如果审核节点是个人，则操作类型只能为SignReview。
 * @method void setOperateType(string $OperateType) 设置操作类型：（接口通过该字段区分不同的操作类型）

<ul><li>SignReview: 签署审核（默认）</li>
<li>CreateReview: 创建审核</li></ul>

如果审核节点是个人，则操作类型只能为SignReview。
 */
class CreateFlowSignReviewRequest extends AbstractModel
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
     * @var string 企业审核结果
<ul><li>PASS: 通过</li> 
<li>REJECT: 拒绝</li></ul>
     */
    public $ReviewType;

    /**
     * @var string 审核结果原因，
字符串长度不超过200
当ReviewType 是拒绝（REJECT） 时此字段必填。

     */
    public $ReviewMessage;

    /**
     * @var Agent 代理相关应用信息，如集团主企业代子企业操作的场景中ProxyOrganizationId必填
     */
    public $Agent;

    /**
     * @var string 审核签署节点人标识，
用来标识审核的签署方。
如果签署审核节点是个人， 此参数必填。
     */
    public $RecipientId;

    /**
     * @var string 操作类型：（接口通过该字段区分不同的操作类型）

<ul><li>SignReview: 签署审核（默认）</li>
<li>CreateReview: 创建审核</li></ul>

如果审核节点是个人，则操作类型只能为SignReview。
     */
    public $OperateType;

    /**
     * @param UserInfo $Operator 执行本接口操作的员工信息。
注: `在调用此接口时，请确保指定的员工已获得所需的接口调用权限，并具备接口传入的相应资源的数据权限。`
     * @param string $FlowId 合同流程ID，为32位字符串。
建议开发者妥善保存此流程ID，以便于顺利进行后续操作。
可登录腾讯电子签控制台，在 "合同"->"合同中心" 中查看某个合同的FlowId(在页面中展示为合同ID)。
     * @param string $ReviewType 企业审核结果
<ul><li>PASS: 通过</li> 
<li>REJECT: 拒绝</li></ul>
     * @param string $ReviewMessage 审核结果原因，
字符串长度不超过200
当ReviewType 是拒绝（REJECT） 时此字段必填。

     * @param Agent $Agent 代理相关应用信息，如集团主企业代子企业操作的场景中ProxyOrganizationId必填
     * @param string $RecipientId 审核签署节点人标识，
用来标识审核的签署方。
如果签署审核节点是个人， 此参数必填。
     * @param string $OperateType 操作类型：（接口通过该字段区分不同的操作类型）

<ul><li>SignReview: 签署审核（默认）</li>
<li>CreateReview: 创建审核</li></ul>

如果审核节点是个人，则操作类型只能为SignReview。
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

        if (array_key_exists("ReviewType",$param) and $param["ReviewType"] !== null) {
            $this->ReviewType = $param["ReviewType"];
        }

        if (array_key_exists("ReviewMessage",$param) and $param["ReviewMessage"] !== null) {
            $this->ReviewMessage = $param["ReviewMessage"];
        }

        if (array_key_exists("Agent",$param) and $param["Agent"] !== null) {
            $this->Agent = new Agent();
            $this->Agent->deserialize($param["Agent"]);
        }

        if (array_key_exists("RecipientId",$param) and $param["RecipientId"] !== null) {
            $this->RecipientId = $param["RecipientId"];
        }

        if (array_key_exists("OperateType",$param) and $param["OperateType"] !== null) {
            $this->OperateType = $param["OperateType"];
        }
    }
}
