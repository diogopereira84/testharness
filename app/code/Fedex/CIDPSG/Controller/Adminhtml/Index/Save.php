<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Fedex\CIDPSG\Model\PsgCustomerFieldsFactory;
use Magento\Framework\App\ResourceConnection;
use Fedex\CIDPSG\Model\CustomerFactory;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Save class for psg customer form
 */
class Save implements ActionInterface
{
    public const COMPANY_LOGO = 'company_logo';
    public const DEFAULT_FIELDS = 'default_fields';
    public const CUSTOM_FIELDS = 'custom_fields';
    public const DEFAULT = 'default';

    /**
     * Save Class Constructor
     *
     * @param CustomerFactory $customerFactory
     * @param PsgCustomerFieldsFactory $psgCustomerFieldsFactory
     * @param ResourceConnection $resourceConnection
     * @param AuthSession $authSession
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $requestInterface
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CustomerFactory $customerFactory,
        protected PsgCustomerFieldsFactory $psgCustomerFieldsFactory,
        protected ResourceConnection $resourceConnection,
        protected AuthSession $authSession,
        protected RedirectFactory $resultRedirectFactory,
        protected RequestInterface $requestInterface,
        protected ManagerInterface $messageManager,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Execute class
     *
     * @return mixed
     */
    public function execute()
    {
        $id = $this->requestInterface->getParam('entity_id');
        $data = $this->requestInterface->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $model = $this->customerFactory->create();
            if (isset($data[self::COMPANY_LOGO][0]['url'])) {
                $parseUrl = parse_url($data[self::COMPANY_LOGO][0]['url']);
                $data[self::COMPANY_LOGO][0]['url'] = $parseUrl['path'];
            }
            $data[self::COMPANY_LOGO] = json_encode($data[self::COMPANY_LOGO][0]);
            if ($id) {
                $data['updated_by'] =  $this->authSession->getUser()->getEmail();
            } else {
                $data['created_by'] =  $this->authSession->getUser()->getEmail();
            }
            $model->setData($data);
            $model->save();
            $psgCustomeEntityId = $model->getId();
            $customerFieldtable = $this->resourceConnection->getTableName("psg_customer_fields");
            $this->resourceConnection->getConnection()
            ->delete($customerFieldtable, ['psg_customer_entity_id = ?' => $psgCustomeEntityId]);
            if (isset($data[self::DEFAULT_FIELDS])) {
                $this->savePsgFieldsValue($data[self::DEFAULT_FIELDS], self::DEFAULT, $psgCustomeEntityId);
            }
            if (isset($data[self::CUSTOM_FIELDS])) {
                $this->savePsgFieldsValue($data[self::CUSTOM_FIELDS], 'custom', $psgCustomeEntityId);
            }
            if (!$id) {
                $data = $model->load($id);
                $id = $data->getData('entity_id');
            }
            $this->messageManager->addSuccessMessage(__('You saved the form successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the form.'));
            $this->logger->critical(__METHOD__ . ':' . __LINE__
            .': Failed to save PSG customer details : ' . $e->getMessage());
        }
        
        if ($this->requestInterface->getParam('back')) {
            return $resultRedirect->setPath('*/*/edit/psg/form', ['entity_id' => $id, '_current' => true]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Saving default and custom fields value
     *
     * @param array $arrFields
     * @param string $fieldGroup
     * @param int $psgCustomeEntityId
     * @return void
     */
    public function savePsgFieldsValue($arrFields, $fieldGroup, $psgCustomeEntityId)
    {
        foreach ($arrFields as $arrField) {
            $customFieldModel = $this->psgCustomerFieldsFactory->create();
            $customFieldModel->setPsgCustomerEntityId($psgCustomeEntityId);
            if ($fieldGroup == self::DEFAULT) {
                $customFieldModel->setFieldGroup(0);
            } else {
                $customFieldModel->setFieldGroup(1);
            }
            $customFieldModel->setFieldType('textbox');
            $customFieldModel->setFieldLabel($arrField['field_label']);
            $customFieldModel->setFieldDescription($arrField['field_description']);
            $customFieldModel->setValidationType($arrField['validation_type']);
            $customFieldModel->setMaxCharacterLength($arrField['max_character_length']);
            $customFieldModel->setIsRequired($arrField['is_required']);
            $customFieldModel->setPosition($arrField['position']);
            $customFieldModel->save();
        }
    }
}
