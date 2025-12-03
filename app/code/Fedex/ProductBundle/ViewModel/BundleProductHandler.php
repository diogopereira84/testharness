<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\ViewModel;

use Fedex\ProductBundle\Model\BundleProductValidator;
use Fedex\ProductBundle\Model\OrderBundleInfoProvider;
use Fedex\ProductBundle\Model\TokenProvider;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class BundleProductHandler implements ArgumentInterface
{
    /**
     * @param BundleProductValidator $bundleProductValidator
     * @param OrderBundleInfoProvider $orderBundleInfoProvider
     * @param TokenProvider $tokenProvider
     * @param ConfigInterface $config
     */
    public function __construct(
        private BundleProductValidator  $bundleProductValidator,
        private OrderBundleInfoProvider $orderBundleInfoProvider,
        private TokenProvider           $tokenProvider,
        private ConfigInterface         $config
    )
    {
    }

    /**
     * @return bool
     */
    public function isTigerE468338ToggleEnabled()
    {
        return $this->config->isTigerE468338ToggleEnabled();
    }

    /**
     * @return bool
     */
    public function isBundleProductSetupCompleted(): bool
    {
        return $this->bundleProductValidator->isBundleProductSetupCompleted();
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleItemSetupCompleted(QuoteItem $item): bool
    {
        return $this->bundleProductValidator->isBundleItemSetupCompleted($item);
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleParentSetupCompleted(QuoteItem $item): bool
    {
        return $this->bundleProductValidator->isBundleParentSetupCompleted($item);
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleChildSetupCompleted(QuoteItem $item): bool
    {
        return $this->bundleProductValidator->isBundleChildSetupCompleted($item);
    }

    /**
     * @return bool
     */
    public function hasBundleProductInCart(): bool
    {
        return $this->bundleProductValidator->hasBundleProductInCart();
    }

    /**
     * @param string|int $instanceId
     * @return bool
     */
    public function hasQuoteItemWithInstanceId(string|int $instanceId): bool
    {
        return $this->bundleProductValidator->hasQuoteItemWithInstanceId($instanceId);
    }

    /**
     * @param QuoteItem $item
     * @return int
     */
    public function getBundleChildrenCount(QuoteItem $item): int
    {
        return $this->bundleProductValidator->getBundleChildrenCount($item);
    }

    public function getBundleChildrenItemsCount($item): int
    {
        return $this->bundleProductValidator->getBundleChildrenItemsCount($item);
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function isBundleChild(QuoteItem $item): bool
    {
        return $this->bundleProductValidator->isBundleChild($item);
    }

    /**
     * @param bool $publicFlag
     * @return string|null
     */
    public function getTazToken(bool $publicFlag = false): ?string
    {
        return $this->tokenProvider->getTazToken($publicFlag);
    }

    /**
     * @return string|null
     */
    public function getCompanySite(): ?string
    {
        return $this->tokenProvider->getCompanySite();
    }

    /**
     * @return array
     */
    public function getBundleItemsSuccessPage(): array
    {
        return $this->orderBundleInfoProvider->getBundleItemsSuccessPage();
    }

    public function getTitleStepOne(): ?string
    {
        return $this->config->getTitleStepOne();
    }

    public function getDescriptionStepOne(): ?string
    {
        return $this->config->getDescriptionStepOne();
    }

    public function getTitleStepTwo(): ?string
    {
        return $this->config->getTitleStepTwo();
    }

    public function getDescriptionStepTwo(): ?string
    {
        return $this->config->getDescriptionStepTwo();
    }

    public function getTitleStepThree(): ?string
    {
        return $this->config->getTitleStepThree();
    }

    public function getDescriptionStepThree(): ?string
    {
        return $this->config->getDescriptionStepThree();
    }
}
