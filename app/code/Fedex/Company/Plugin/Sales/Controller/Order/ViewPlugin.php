<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Plugin\Sales\Controller\Order;

use Closure;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Order\View;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel;

/**
 * Restrict access to the order view page depending on permissions for company users.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewPlugin
{
    public const TECH_TITANS_B_2041921 = 'tech_titans_B2041921_detected_on_fxo_ecommerce_platform';

    /**
     * @param UserContextInterface $userContext
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param AuthorizationInterface $authorization
     * @param Structure $companyStructure
     * @param CompanyContext $companyContext
     * @param CustomerRepositoryInterface $customerRepository
     * @param DeliveryDataHelper $deliveryDataHelper
     * @param ReviewOrderViewModel $reviewOrderViewModel
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected \Magento\Authorization\Model\UserContextInterface $userContext,
        protected \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        protected \Magento\Framework\App\RequestInterface $request,
        protected \Magento\Company\Api\AuthorizationInterface $authorization,
        protected \Magento\Company\Model\Company\Structure $companyStructure,
        protected \Magento\Company\Model\CompanyContext $companyContext,
        protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        protected DeliveryDataHelper $deliveryDataHelper,
        protected ReviewOrderViewModel $reviewOrderViewModel,
        private readonly ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * View around execute plugin.
     *
     * @param View $subject
     * @param Closure $proceed
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Exception
     */
    public function aroundExecute(
        View $subject,
        Closure $proceed
    ) {
        $customerId = $this->userContext->getUserId();
        $token = $this->request->getParam('key');
        if ($customerId) {
            if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2041921) && $token) {
                $orderId = $this->validateSecureToken($token, $customerId);
            } else {
                $orderId = $this->request->getParam('order_id');
            }
            $this->request->setParams([
                'order_id' => $orderId
            ]);

            try {
                $order = $this->orderRepository->get($orderId);
            } catch (\Exception $exception) {
                return $proceed();
            }

            if (!$this->canViewOrder($order)) {
                $resultRedirect = $this->resultRedirectFactory->create();

                if ($this->companyContext->isCurrentUserCompanyUser()) {
                    $resultRedirect->setPath('company/accessdenied');
                } else {
                    $resultRedirect->setPath('noroute');
                }

                return $resultRedirect;
            }
        }

         return $proceed();
    }
    private function validateSecureToken(string $token, int $customerId)
    {
        $decodedData = $this->decryptData(base64_decode($token), $customerId);
        $data = json_decode($decodedData, true);

        if (!$data || !isset($data['order_id'])) {
            throw new \Exception('Invalid token.');
        }

        return $data['order_id'];
    }

    private function decryptData($data, $key)
    {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);
        return openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * Order can be viewed.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function canViewOrder(\Magento\Sales\Model\Order $order)
    {
        $customerId = $this->userContext->getUserId();
        $orderOwnerId = $order->getCustomerId();
        if ($this->deliveryDataHelper->isCompanyAdminUser()) {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer->getGroupId() == $order->getCustomerGroupId()) {
                return true;
            }
        }
        
        if ($this->reviewOrderViewModel->isOrderApprovalB2bEnabled() && $this->reviewOrderViewModel->checkIfUserHasReviewOrderPermission()) {
            return true;
        }

        if ($orderOwnerId != $customerId &&
            (
                !($this->authorization->isAllowed('Magento_Sales::view_orders_sub') || $this->checkPermission()) ||
                !$this->companyContext->isModuleActive()
            )
        ) {
            return false;
        }

        if ($this->companyContext->isCurrentUserCompanyUser()
            && !($this->authorization->isAllowed('Magento_Sales::view_orders') || $this->checkPermission())) {
            return false;
        }
        $subCustomers = $this->companyStructure->getAllowedChildrenIds($customerId);
        if (!empty($subCustomers) && !in_array($orderOwnerId, $subCustomers) && $orderOwnerId != $customerId) {
            return false;
        }

        return true;
    }
   
     /**
     * check order permisison for nomal user
     * @return bool
     */
    private function checkPermission()
    {
        if ($this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions')) {
            return $this->deliveryDataHelper->checkPermission('shared_orders');
        }
        return false;
    }
}
