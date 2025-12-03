<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Nathan Alves <nathan.alves.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\SubmitOrderSidebar\Api\BillingFieldBuilderInterface;
use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldCollectionInterface;
use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldCollectionInterfaceFactory;
use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldOptionInterfaceFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class BillingFieldBuilder implements BillingFieldBuilderInterface
{
    /**
     * @param RequestInterface $request
     * @param CartRepositoryInterface $quoteRepository
     * @param BillingFieldOptionInterfaceFactory $optionFactory
     * @param BillingFieldCollectionInterfaceFactory $collectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ToggleConfig $toggleConfig
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly BillingFieldOptionInterfaceFactory $optionFactory,
        private readonly BillingFieldCollectionInterfaceFactory $collectionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ToggleConfig $toggleConfig,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    ) {
    }

    /**
     * Manage Billing Field Collection
     *
     * @param CartInterface $quote
     * @param Boolean $isOrderApproval
     * @return BillingFieldCollectionInterface
     * @throws Exception
     */
    public function build(CartInterface $quote, $isOrderApproval = false): BillingFieldCollectionInterface
    {
        $collection = $this->collectionFactory->create();
        $requestData = $this->request->getParam('data');
        $data = json_decode($requestData ?? '', true);
        if ($isOrderApproval) {
            $data['billingFields'] = $quote->getBillingFields();
        }
        if (isset($data['billingFields'])) {
            $billingFields = json_decode($data['billingFields'], true);
            foreach ($billingFields as $field) {
                if(!empty($field['value'])){
                    $collection->addItem($this->optionFactory->create(['data' => $field]));
                }
            }
            $isEssendantToggleEnabled = $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
            if($collection->toArrayApi()){
                $quote->setBillingFields(json_encode($collection->toArray()));
                if( $isEssendantToggleEnabled){
                    $quote->save();
                }else{
                    $this->quoteRepository->save($quote);
                }
            }

        }

        return $collection;
    }
}
