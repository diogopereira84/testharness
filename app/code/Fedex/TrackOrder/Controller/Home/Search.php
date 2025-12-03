<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\TrackOrder\Controller\Home;

use Fedex\TrackOrder\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\TrackOrder\Model\OrderDetailApi;

/**
 * Track Order details Controller class
 */
class Search extends \Magento\Framework\App\Action\Action
{
    /**
     * @var bool
     */
    protected $flag;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderDetailApi $orderDetailApi
     * @param Config $config
     */
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        protected PageFactory $resultPageFactory,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected OrderRepositoryInterface $orderRepository,
        protected OrderDetailApi $orderDetailApi,
        protected readonly Config $config,
    ) {
        parent::__construct($context);
        $this->flag = false;
    }

    /**
     * Track Order Details result page
     */
    public function execute()
    {
        $orderIds = $this->getRequest()->getPost('inputValues');
        
        $resultJson = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();

        $orderDetailsBlockResult = $resultPage->getLayout()
            ->createBlock('Fedex\TrackOrder\Block\Order\OrderDetails')
            ->setTemplate('Fedex_TrackOrder::result.phtml')
            ->setData('search', $orderIds)
            ->toHtml();

        $this->flag = false;
        $resultJson->setData(['output' => $orderDetailsBlockResult, 'flag' => $this->flag]);

        return $resultJson;
    }
}
