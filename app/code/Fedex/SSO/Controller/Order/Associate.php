<?php
declare(strict_types=1);

namespace Fedex\SSO\Controller\Order;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\CustomerAssignment;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Json;
use \Magento\Sales\Model\Order;
use Fedex\SubmitOrderSidebar\ViewModel\OrderSuccess;

class Associate implements HttpPostActionInterface
{
    private Json $resultJson;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CustomerRepositoryInterface $customerRepository,
        JsonFactory $resultJsonFactory,
        private CustomerAssignment $customerAssignment,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private LoggerInterface $logger,
        private CustomerSession $customerSession,
        private HttpRequest $request,
        private OrderSuccess $orderSuccess
    ) {
        $this->resultJson = $resultJsonFactory->create();
    }

    public function execute(): Json
    {
        //toggle
        if (!$this->orderSuccess->isPopupEnabled()) {
            return $this->resultJson->setData([
                'success' => false,
                'message' => 'Feature is disabled by configuration'
            ]);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $this->resultJson->setData([
                'success' => false,
                'message' => 'User not logged in'
            ]);
        }

        $orderIncrementId = $this->request->getParam('orderId');
        $customerId = $this->customerSession->getCustomerId();

        if (!$orderIncrementId || !$customerId) {
            return $this->resultJson->setData([
                'success' => false,
                'message' => 'Order ID or Customer ID missing'
            ]);
        }

        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $orderIncrementId, 'eq')
                ->create();

            $orderList = $this->orderRepository->getList($searchCriteria);
            $orderItems = $orderList->getItems();

            if (empty($orderItems)) {
                return $this->resultJson->setData([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }

             /** @var Order $order */
            $order = reset($orderItems);

            if (!$order instanceof Order) {
                return $this->resultJson->setData([
                    'success' => false,
                    'message' => 'Invalid order object'
                ]);
            }

            if (!$order->getCustomerIsGuest()) {
                return $this->resultJson->setData([
                    'success' => false,
                    'message' => 'Order is already assigned to a registered user'
                ]);
            }

            $customer = $this->customerRepository->getById($customerId);
            $this->customerAssignment->execute($order, $customer);

            $order->setData('reorderable', 1);
            $this->orderRepository->save($order);


            return $this->resultJson->setData([
                'success' => true,
                'message' => 'Order successfully associated with the logged-in user'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error associating order: ' . $e->getMessage());
            return $this->resultJson->setData([
                'success' => false,
                'message' => 'Error associating order'
            ]);
        }
    }
}