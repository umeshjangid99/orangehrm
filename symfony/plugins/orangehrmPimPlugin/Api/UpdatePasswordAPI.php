<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Pim\Api;

use OrangeHRM\Admin\Api\Model\UserModel;
use OrangeHRM\Admin\Service\UserService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Framework\Services;

class UpdatePasswordAPI extends Endpoint implements ResourceEndpoint
{
    use UserRoleManagerTrait;
    use ServiceContainerTrait;

    public const PARAMETER_CURRENT_PASSWORD = 'currentPassword';
    public const PARAMETER_NEW_PASSWORD = 'newPassword';

    public const PARAM_RULE_STRING_MAX_LENGTH = 64;

    /**
     * @return UserService|null
     */
    public function getSystemUserService(): ?UserService
    {
        return $this->getContainer()->get(Services::USER_SERVICE);
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $user = $this->getUserRoleManager()->getUser();
        $newPassword = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_NEW_PASSWORD
        );
        $user->setUserPassword($newPassword);
        $user = $this->getSystemUserService()->saveSystemUser($user, true);
        return new EndpointResourceResult(UserModel::class, $user);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_CURRENT_PASSWORD,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_STRING_MAX_LENGTH]),
                    new Rule(Rules::CALLBACK, [
                        function () {
                            $currentPassword = $this->getRequestParams()->getString(
                                RequestParams::PARAM_TYPE_BODY,
                                self::PARAMETER_CURRENT_PASSWORD
                            );
                            $userId = $this->getUserRoleManager()->getUser()->getId();
                            return $this->getSystemUserService()->isCurrentPassword($userId, $currentPassword);
                        }
                    ])
                )
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_NEW_PASSWORD,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_STRING_MAX_LENGTH]),
                    new Rule(Rules::CALLBACK, [
                        function () {
                            $newPassword = $this->getRequestParams()->getString(
                                RequestParams::PARAM_TYPE_BODY,
                                self::PARAMETER_NEW_PASSWORD
                            );
                            $uppercase = preg_match('@[A-Z]@', $newPassword);
                            $lowercase = preg_match('@[a-z]@', $newPassword);
                            $number    = preg_match('@[0-9]@', $newPassword);
                            $specialCharacter    = preg_match('/^(?=.*[!@#$%^&*-])(?=.*[0-9])(?=.*[A-Z]).{8,20}$/', $newPassword);
                            if (!$uppercase || !$lowercase || !$number || !$specialCharacter || strlen($newPassword) < 8) {
                                return false;
                            } else {
                                return true;
                            }
                        }
                    ])
                ),
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}