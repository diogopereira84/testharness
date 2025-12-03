<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Header\Controller\Account;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AddressRegistry;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Fedex\Header\Helper\Data;
use Magento\Customer\Block\Account\Dashboard\Info;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditPost extends Action
{
    /**
     * Form code for data extractor
     */
    protected const FORM_DATA_EXTRACTOR_CODE = 'customer_account_edit';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var EmailNotificationInterface
     */
    private $emailNotification;

    /**
     * @var Mapper
     */
    private $customerMapper;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var AddressRegistry
     */
    private $addressRegistry;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param Validator $formKeyValidator
     * @param CustomerExtractor $customerExtractor
     * @param LoggerInterface $logger
     * @param Escaper|null $escaper
     * @param AddressRegistry|null $addressRegistry
     * @param Data $data
     * @param Info $info
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        protected AccountManagementInterface $customerAccountManagement,
        protected CustomerRepositoryInterface $customerRepository,
        protected Validator $formKeyValidator,
        protected CustomerExtractor $customerExtractor,
        protected LoggerInterface $logger,
        readonly private Data $data,
        readonly private Info $info,
        ?Escaper $escaper = null,
        AddressRegistry $addressRegistry = null
    ) {
        parent::__construct($context);
        $this->session = $customerSession;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(Escaper::class);
        $this->addressRegistry = $addressRegistry ?: ObjectManager::getInstance()->get(AddressRegistry::class);
    }

    /**
     * Get email notification
     *
     * @return EmailNotificationInterface
     * @deprecated 100.1.0
     */
    private function getEmailNotification()
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return ObjectManager::getInstance()->get(
                EmailNotificationInterface::class
            );
        } else {
            return $this->emailNotification;
        }
    }
    
    /**
     * Change customer email or password action
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());

        if ($validFormKey && $this->getRequest()->isPost()) {
            $currentCustomerDataObject = $this->getCustomerDataObject($this->session->getCustomerId());
            $customerCandidateDataObject = $this->populateNewCustomerDataObject(
                $this->_request,
                $currentCustomerDataObject
            );
            try {

                /* Whether a customer enabled change password option */
                $isPasswordChanged = $this->changeCustomerPassword($currentCustomerDataObject->getEmail());

                /* No need to validate customer address while editing customer profile */
                $this->disableAddressValidation($customerCandidateDataObject);
                $explorersD193926Fix = $this->data->getToggleD193926Fix();
                if ($explorersD193926Fix) {
                    //retrieves the customer model/data object using the ID of the customer
                    $customer = $this->data->getCustomer($this->info->getCustomer()->getId());
                    //Getting customer Data from model and setting it value
                    if ($this->getRequest()->getParam('email') != $currentCustomerDataObject->getEmail()) {
                        $customer->setEmail($this->getRequest()->getParam('email'));
                    }
                    if ($this->getRequest()->getParam('firstname') != $currentCustomerDataObject->getFirstname()) {
                        $customer->setFirstname($this->getRequest()->getParam('firstname'));
                    }
                    if ($this->getRequest()->getParam('lastname') != $currentCustomerDataObject->getLastname()) {
                        $customer->setLastname($this->getRequest()->getParam('lastname'));
                    }
                    if ($this->getRequest()->getParam('contact_number') != $currentCustomerDataObject->getCustomAttribute('contact_number')) {
                        $currentCustomerDataObject->setCustomAttribute('contact_number', $this->getRequest()->getParam('contact_number'));
                    }
                    if ($this->getRequest()->getParam('contact_ext') != $currentCustomerDataObject
                        ->getCustomAttribute('contact_ext')) {
                        $currentCustomerDataObject->setCustomAttribute('contact_ext',
                            $this->getRequest()->getParam('contact_ext'));
                    }
                    $this->customerRepository->save($customerCandidateDataObject);
                    $customer->save();
                    $this->getEmailNotification()->credentialsChanged(
                        $customerCandidateDataObject,
                        $currentCustomerDataObject->getEmail(),
                        $isPasswordChanged
                    );
                    $this->dispatchSuccessEvent($customerCandidateDataObject);
                    $this->messageManager->addSuccessMessage(__('You saved the account information.'));
                    return $resultRedirect->setPath('customer/account');
                }
                if ($this->getRequest()->getParam('email') != $currentCustomerDataObject->getEmail()) {
                    $customerCandidateDataObject->setEmail($this->getRequest()->getParam('email'));
                }
                if($this->getRequest()->getParam('contact_ext')!=$currentCustomerDataObject
                ->getCustomAttribute('contact_ext'))
                {
                    $customerCandidateDataObject->setCustomAttribute('contact_ext',
                    $this->getRequest()->getParam('contact_ext'));
                }
                $this->customerRepository->save($customerCandidateDataObject);
                $this->getEmailNotification()->credentialsChanged(
                    $customerCandidateDataObject,
                    $currentCustomerDataObject->getEmail(),
                    $isPasswordChanged
                );
                $this->dispatchSuccessEvent($customerCandidateDataObject);
                $this->messageManager->addSuccessMessage(__('You saved the account information.'));
                return $resultRedirect->setPath('customer/account');
            } catch (InvalidEmailOrPasswordException $e) {
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
            } catch (UserLockedException $e) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ' User account sign-in is incorrect or account is temporarily disabled.');
                $message = __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                );
                $this->session->logout();
                $this->session->start();
                $this->messageManager->addErrorMessage($message);
                return $resultRedirect->setPath('customer/account/login');
            } catch (InputException $e) {
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
                $this->populateErrorMessage($e);
                
            } catch (LocalizedException $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error cannot save the customer.');
                $this->messageManager->addException($e, __('We can\'t save the customer.'));
            }

            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/edit');
        
        return $resultRedirect;
    }
    
    public function populateErrorMessage($e){
		foreach ($e->getErrors() as $error) {
			$this->messageManager->addErrorMessage($this->escaper->escapeHtml($error->getMessage()));
		}
	}

    /**
     * Account editing action completed successfully event
     *
     * @param CustomerInterface $customerCandidateDataObject
     * @return void
     */
    private function dispatchSuccessEvent(CustomerInterface $customerCandidateDataObject)
    {
        $this->_eventManager->dispatch(
            'customer_account_edited',
            ['email' => $customerCandidateDataObject->getEmail()]
        );
    }

    /**
     * Get customer data object
     *
     * @param int $customerId
     * @return CustomerInterface
     */
    private function getCustomerDataObject($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * Create Data Transfer Object of customer candidate
     *
     * @param RequestInterface $inputData
     * @param CustomerInterface $currentCustomerData
     * @return CustomerInterface
     */
    private function populateNewCustomerDataObject(
        RequestInterface $inputData,
        CustomerInterface $currentCustomerData
    ) {
        $attributeValues = $this->getCustomerMapper()->toFlatArray($currentCustomerData);
        $customerDto = $this->customerExtractor->extract(
            self::FORM_DATA_EXTRACTOR_CODE,
            $inputData,
            $attributeValues
        );
        $customerDto->setId($currentCustomerData->getId());
        if (!$customerDto->getAddresses()) {
            $customerDto->setAddresses($currentCustomerData->getAddresses());
        }
        if (!$inputData->getParam('change_email')) {
            $customerDto->setEmail($currentCustomerData->getEmail());
        }

        return $customerDto;
    }

    /**
     * Change customer password
     *
     * @param string $email
     * @return boolean
     * @throws InvalidEmailOrPasswordException|InputException
     */
    protected function changeCustomerPassword($email)
    {
        $isPasswordChanged = false;
        if ($this->getRequest()->getParam('change_password')) {
            $currPass = $this->getRequest()->getPost('current_password');
            $newPass = $this->getRequest()->getPost('password');
            $confPass = $this->getRequest()->getPost('password_confirmation');
            if ($newPass != $confPass) {
                throw new InputException(__('Password confirmation doesn\'t match entered password.'));
            }

            $isPasswordChanged = $this->customerAccountManagement->changePassword($email, $currPass, $newPass);
        }

        return $isPasswordChanged;
    }

    /**
     * Get Customer Mapper instance
     *
     * @return Mapper
     *
     * @deprecated 100.1.3
     */
    private function getCustomerMapper()
    {
        if ($this->customerMapper === null) {
            $this->customerMapper = ObjectManager::getInstance()->get(Mapper::class);
        }
        return $this->customerMapper;
    }

    /**
     * Disable Customer Address Validation
     *
     * @param CustomerInterface $customer
     * @throws NoSuchEntityException
     */
    private function disableAddressValidation($customer)
    {
        foreach ($customer->getAddresses() as $address) {
            $addressModel = $this->addressRegistry->retrieve($address->getId());
            $addressModel->setShouldIgnoreValidation(true);
        }
    }
}
