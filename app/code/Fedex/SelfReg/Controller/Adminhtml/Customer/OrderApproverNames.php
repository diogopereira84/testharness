<?php
declare(strict_types=1);

namespace Fedex\SelfReg\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\SelfReg\Model\Source\CustomerNames;

class OrderApproverNames extends Action implements HttpGetActionInterface
{
    /**
     * Admin resource authorization
     */
    const ADMIN_RESOURCE = 'Fedex_SelfReg::order_approver';

    /**
     * Maximum allowed page size to prevent performance issues
     */
    private const MAX_PAGE_SIZE = 500;

    /**
     * Default page size
     */
    private const DEFAULT_PAGE_SIZE = 50;

    public function __construct(
        Action\Context $context,
        protected JsonFactory $jsonFactory,
        protected CustomerNames $customerNames
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        // Normalize page - ensure it's at least 1
        $page = max(1, (int) ($this->getRequest()->getParam('page') ?? 1));
        
        // Normalize page size - ensure it's between 1 and MAX_PAGE_SIZE
        $requestedPageSize = (int) ($this->getRequest()->getParam('page_size') ?? self::DEFAULT_PAGE_SIZE);
        $pageSize = max(1, min(self::MAX_PAGE_SIZE, $requestedPageSize));
        
        // Sanitize search term
        $search = trim((string) ($this->getRequest()->getParam('search') ?? ''));

        $result = $this->customerNames->getPaginatedCustomers($page, $pageSize, $search);
        
        return $this->jsonFactory->create()->setData($result);
    }

    /**
     * Check if user has enough privileges (optional - only if additional logic needed)
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}