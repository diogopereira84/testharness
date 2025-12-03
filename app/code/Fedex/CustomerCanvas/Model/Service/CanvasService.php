<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Fedex\CustomerCanvas\Api\CanvasServiceInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\Service\DesignRetentionService;

class CanvasService implements CanvasServiceInterface
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly CustomerCanvasUserManager $userManager,
        private readonly CustomerCanvasUserInfo $userInfo,
        private readonly LoggerInterface $logger,
        private readonly DesignRetentionService $designRetentionService
    ) {}

    /**
     * @return bool
     */
    public function isDyeSubEnabled(): bool
    {
        return $this->configProvider->isDyeSubEnabled();
    }

    /**
     * @param $item
     * @return bool
     */
    public function isExpired($item): bool
    {
        if (!($this->isDyeSubEnabled()) || !($item->getProduct()->getData('is_customer_canvas'))) {
            return false;
        }

        try {
            return (bool) $this->designRetentionService->isExpiredDesign($item);
        } catch (\Throwable $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .'Failed to check Dyesub expiration', [
                'item_id' => $item->getId(),
                'exception'  => $e,
            ]);
            return false;
        }
    }
    /**
     * @param $product
     * @return bool
     */
    public function isExpiredCatalogProduct($product): bool
    {
        if (!($this->isDyeSubEnabled()) || !($product->getData('is_customer_canvas'))) {
            return false;
        }

        try {
            return (bool) $this->designRetentionService->isExpiredCatalogProductDesign($product);
        } catch (\Throwable $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .'Failed to check Dyesub expiration', [
                'product_id' => $product->getId(),
                'exception'  => $e,
            ]);
            return false;
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getRequiredCanvasParams(): array
    {
        $userToken = $this->userManager->getOrCreateToken();
        $userInfo  = $this->userInfo->getUserInfo();

        return [
            'userToken'    => $userToken,
            'userId'       => $userInfo['userId'] ?? '',
            'tenantId'     => (string) $userInfo['tenantId'] ?? '',
            'storefrontId' => (string) $userInfo['storefrontId'] ?? '',
            'isEditable'   => true,
        ];
    }
}
