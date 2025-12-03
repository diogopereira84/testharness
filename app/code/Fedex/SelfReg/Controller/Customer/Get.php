<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Customer;

use Magento\Company\Api\AclInterface;
use Magento\Company\Controller\AbstractAction;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\Form\Element\Multiline;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Controller for retrieving customer info on the frontend.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Get extends \Magento\Company\Controller\Customer\Get implements HttpGetActionInterface
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var AclInterface
     */
    private $acl;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     * @param Structure $structureManager
     * @param AclInterface $acl
     * @param EavConfig|null $eavConfig
     * @param EnhanceUserRoles $roleUser
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        Structure $structureManager,
        private Customer $customerModel,
        AclInterface $acl,
        EavConfig $eavConfig = null,
        protected EnhanceUserRoles $roleUser,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct(
            $context,
            $companyContext,
            $logger,
            $customerRepository,
            $structureManager,
            $acl,
            $eavConfig
        );
        $this->acl = $acl;
        $this->customerRepository = $customerRepository;
        $this->structureManager = $structureManager;
        //@codeCoverageIgnoreStart
        $this->eavConfig = $eavConfig ?: ObjectManager::getInstance()
            ->get(EavConfig::class);
    }

    /**
     * Get customer action.
     *
     * @return Json
     */
    public function execute()
    {
        $companyAttributes = null;
        $request = $this->getRequest();
        $customerId = $request->getParam('customer_id');
        $companyId = "";
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerByModel = $this->customerModel->load($customerId);

            if ($customer->getExtensionAttributes() !== null
                && $customer->getExtensionAttributes()->getCompanyAttributes() !== null) {
                $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
                $companyId = $companyAttributes->getCompanyId();
            }

            $this->setCustomerCustomDateAttribute($customer);
            $this->setCustomerCustomMultilineAttribute($customer);

        } catch (LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return $this->handleJsonError();
        }

        $customerData = $customer->__toArray();

        if ($customerByModel->getSecondaryEmail() != null) {
            $customerData['email'] = $customerByModel->getSecondaryEmail();
        }

        if ($companyAttributes !== null) {
            $customerData['extension_attributes[company_attributes][job_title]'] = $companyAttributes->getJobTitle();
            $customerData['extension_attributes[company_attributes][telephone]'] = $companyAttributes->getTelephone();
            $customerData['extension_attributes[company_attributes][status]'] = $companyAttributes->getStatus();
        }
        if($customerByModel->getData('customer_status') !== null) {
            $customerData['extension_attributes[company_attributes][status]'] = $customerByModel->getData('customer_status');
        }
        $isRolerPermissionEnable = $this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions');
        if ($isRolerPermissionEnable) {
            $customerData['role_permissions'] = $this->getRolerPermission($customerId, $companyId);
        }

        $roles = $this->acl->getRolesByUserId($customerId);
        if (count($roles)) {
            foreach ($roles as $role) {
                $customerData['role'] = $role->getId();
                break;
            }
        }
        return $this->jsonSuccess($customerData);
    }

    /**
     * Get User specific permissions
     * @param int $customerId
     * @param int $companyId
     * @return array
    */
    public function getRolerPermission($customerId, $companyId)
    {
        $collection = $this->roleUser->getCollection()->addFieldToFilter('customer_id',$customerId)->addFieldToFilter('company_id',$companyId);
        return $collection->getColumnValues('permission_id');
    }
    /**
     * Get attribute type for upcoming validation.
     *
     * @param AbstractAttribute|Attribute $attribute
     * @return string
     */
    private function getAttributeType(AbstractAttribute $attribute): string
    {
        //@codeCoverageIgnoreStart
        $frontendInput = $attribute->getFrontendInput();
        if ($attribute->usesSource() && in_array($frontendInput, ['select', 'multiselect', 'boolean'])) {
            return $frontendInput;
        } elseif ($attribute->isStatic()) {
            return $frontendInput == 'date' ? 'datetime' : 'varchar';
        } else {
            return $attribute->getBackendType();
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * Set customer custom date attribute
     *
     * @param CustomerInterface $customer
     * @throws LocalizedException
     */
    private function setCustomerCustomDateAttribute(CustomerInterface $customer): void
    {
        //@codeCoverageIgnoreStart
        if ($customer->getCustomAttributes() !== null) {
            $customAttributes = $customer->getCustomAttributes();
            foreach ($customAttributes as $customAttribute) {
                $attributeCode = $customAttribute->getAttributeCode();
                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);
                $attributeType = $this->getAttributeType($attribute);
                if ($attributeType === 'datetime') {
                    $date = new \DateTime($customAttribute->getValue());
                    $customAttribute->setValue($date->format('m/d/Y'));
                }
                $customAttribute->setData('attributeType', $attributeType);
            }
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * Set customer custom multiline attribute
     *
     * @param CustomerInterface $customer
     * @throws LocalizedException
     */
    private function setCustomerCustomMultilineAttribute(CustomerInterface $customer): void
    {
        //@codeCoverageIgnoreStart
        if ($customer->getCustomAttributes() !== null) {
            $customAttributes = $customer->getCustomAttributes();
            foreach ($customAttributes as $customAttribute) {
                $attributeCode = $customAttribute->getAttributeCode();
                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);
                $attributeType = $attribute->getFrontendInput();
                if ($attributeType === Multiline::NAME) {
                    $customAttribute->setData('attributeType', $attributeType);
                }
            }
        }
        //@codeCoverageIgnoreEnd
    }
}
