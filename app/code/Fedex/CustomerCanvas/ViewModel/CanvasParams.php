<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\CustomerCanvas\Api\CanvasServiceInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Magento\Framework\App\RequestInterface;

class CanvasParams implements ArgumentInterface
{
    /**
     * @param CanvasServiceInterface $canvasService
     * @param ConfigProvider $configProvider
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly CanvasServiceInterface $canvasService,
        private readonly ConfigProvider $configProvider,
        private readonly RequestInterface $request,

    ) {}

    /**
     * @return array
     */
    public function getRequiredCanvasParams(): array
    {
        return $this->canvasService->getRequiredCanvasParams();
    }

    /**
     * @param $item
     * @return bool
     */
    public function isExpired($item): bool
    {
        return $this->canvasService->isExpired($item);
    }
    /**
     * @param $product
     * @return bool
     */
    public function isExpiredCatalogProduct($product): bool
    {
        return $this->canvasService->isExpiredCatalogProduct($product);
    }

    /**
     * @return bool
     */
    public function isDyeSubEnabled(): bool
    {
        return (bool)$this->configProvider->isDyeSubEnabled();
    }

    /**
     * @return bool
     */
    public function isDyeSubEditEnabled():bool
    {
        return (bool)$this->configProvider->isDyeSubEditEnabled();
    }

    /**
     * @return mixed
     */
    public function getParamIsDyeSubFromCatalog()
    {
        return $this->request->getParam('isDyeSubFromCatalog');
    }
}
