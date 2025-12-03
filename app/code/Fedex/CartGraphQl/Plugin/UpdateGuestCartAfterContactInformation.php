<?php
/**
 * @category    Fedex
 * @package     Fedex_FujitsuGateway
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\CartGraphQl\Model\Resolver\UpdateGuestCartContactInformation;
use Fedex\FXOPricing\Model\FXORateQuote as ModelFXORateQuote;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;

class UpdateGuestCartAfterContactInformation
{
    /**
     * @param ModelFXORateQuote $modelFXORateQuote
     * @param CartRepositoryInterface $cartRepository
     * @param Cart $cartModel
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param ShippingRate $rate
     */
    public function __construct(
        private readonly ModelFXORateQuote $modelFXORateQuote,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly Cart $cartModel,
        private readonly FuseBidGraphqlHelper $fuseBidGraphqlHelper,
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private readonly ShippingRate $rate
    ) {}

    /**
     * Update cart after contact information update
     *
     * @param UpdateGuestCartContactInformation $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        UpdateGuestCartContactInformation $subject,
                                          $result,
        ContextInterface $context,
        Field $field,
        array $requests
    ) {
        $inputArguments = $requests[0]->getArgs()['input'];
        if ($this->fuseBidGraphqlHelper->validateToggleConfig()) {
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cart =  $this->fuseBidGraphqlHelper->getCartForBidQuote(
                $inputArguments[UpdateGuestCartContactInformation::CART_ID],
                $storeId
            );
        } else {
            $cart = $this->cartModel->getCart($inputArguments[UpdateGuestCartContactInformation::CART_ID], $context);
        }
        try {
            $this->modelFXORateQuote->getFXORateQuote($cart);
        } catch (GraphQlFujitsuResponseException $e) {
            throw new GraphQlFujitsuResponseException(__($e->getMessage()));
        }

        $shippingAddress = $cart->getShippingAddress();
        $integration = $this->cartIntegrationRepository->getByQuoteId($cart->getId());
        if ((!empty($shippingAddress)) && (!empty($shippingAddress->getCountryId()))) {
            $this->rate->collect($shippingAddress, $integration);
            $shippingAddress->save();
            $this->cartRepository->save($cart);
        }

        return $result;
    }
}
