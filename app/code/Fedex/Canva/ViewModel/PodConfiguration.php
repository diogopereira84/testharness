<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\ViewModel;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;
use Fedex\Canva\Model\Service\CurrentProductService;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchOutHelper;

class PodConfiguration implements ArgumentInterface
{
    /**
     * @param LoggerInterface $logger
     * @param CurrentProductService $currentProductService
     * @param DeliveryHelper $deliveryHelper
     * @param PunchOutHelper $punchOutHelper
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        private LoggerInterface $logger,
        private CurrentProductService $currentProductService,
        private DeliveryHelper $deliveryHelper,
        private PunchOutHelper $punchOutHelper,
        protected \Magento\Framework\App\Request\Http $request
    )
    {
    }

    /**
     * Return the current product sku
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSku(): string
    {
        try {
            $product = $this->currentProductService->getProduct();
            return $product->getSku();
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }

        return "";
    }

    /**
     * Return site name
     *
     * @return string
     */
    public function getSiteName(): string
    {
        return $this->deliveryHelper->getCompanySite() ?? '';
    }

    /**
     * Return TazToken
     *
     * @return string|null
     */
    public function getTazToken(): string | null
    {
        return $this->punchOutHelper->getTazToken();
    }

    public function getDesignId(): string
    {
        $params = $this->request->getParams();
        return $params["edit"] ?? '';
    }
}
