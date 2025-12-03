<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\ViewModel;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Canva\Api\Data\ConfigInterface;
use Fedex\Canva\Model\Service\CurrentProductService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class CanvaModal
 * Handle the viewModel of the Canva Modal selection
 * Based on B-736474
 */
class Canva implements ArgumentInterface
{
    /**
     * Canva has design attribute code
     */
    public const ATTRIBUTE_CODE_HAS_CANVA_DESIGN = 'has_canva_design';

    /**
     * Canva size attribute code
     */
    public const ATTRIBUTE_CODE_HAS_CANVAS_SIZE = 'canva_size';

    /**
     * Path where a Canva logo uploaded from admin is stored
     */
    public const CANVA_LOGO_UPLOAD_PATH = 'canva/canva_design/canva_logo/';

    /**
     * Path to the default Canva logo stored in this module
     */
    public const CANVA_LOGO_FALLBACK_PATH = 'Fedex_Canva::images/canva-logo.png';

    /**
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     * @param SerializerInterface $serializer
     * @param CurrentProductService $currentProductService
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param Repository $assetRepository
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private LoggerInterface $logger,
        private UrlInterface $urlBuilder,
        private SerializerInterface $serializer,
        /**
         * Current Product
         */
        private CurrentProductService $currentProductService,
        private ConfigInterface $config,
        private StoreManagerInterface $storeManager,
        private Repository $assetRepository,
        protected RequestInterface $request,
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Check if this page is enabled for canva design
     *
     * @return bool
     */
    public function hasDesign(): bool
    {
        try {
            $product = $this->currentProductService->getProduct();
            $hasCanvaDesign = $product->getCustomAttribute(self::ATTRIBUTE_CODE_HAS_CANVA_DESIGN);
            if ($hasCanvaDesign && count($this->getOptionsAvailable()) > 0) {
                return (bool)$hasCanvaDesign->getValue();
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }
        return false;
    }

    /**
     * Check if it should show modal
     *
     * @return bool
     */
    public function useModal(): bool
    {
        return (count($this->getOptionsAvailable()) > 1);
    }

    /**
     * Return The canva link
     *
     * @return string
     */
    public function getLink(): string
    {
        if (count($this->getOptionsAvailable()) == 1 && isset($this->getOptionsAvailable()[0]['product_mapping_id'])) {
            return (string)$this->urlBuilder->getUrl('/canva/index/index', [
                'canvaProductId' => $this->getOptionsAvailable()[0]['product_mapping_id']
            ]);
        }

        return (string)$this->urlBuilder->getUrl('canva');
    }

    /**
     * Return the Default size
     *
     * @return string
     */
    public function getDefaultSize(): string
    {
        if (count($this->getOptionsAvailable()) == 1 && isset($this->getOptionsAvailable()[0]['product_mapping_id'])) {
            return (string)$this->getOptionsAvailable()[0]['product_mapping_id'];
        }

        return '';
    }

    /**
     * Return the options available
     *
     * @return array
     */
    public function getOptionsAvailable(): array
    {
        try {
            $product = $this->currentProductService->getProduct();
            $canvaSizes = $product->getCustomAttribute(self::ATTRIBUTE_CODE_HAS_CANVAS_SIZE);
            if ($canvaSizes) {
                return $this->serializer->unserialize($canvaSizes->getValue());
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }

        return [];
    }

    /**
     * Return the Canva logo url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCanvaLogoUrl(): string
    {
        $logoConfigFilePath = $this->config->getCanvaLogoPath();

        if (null === $logoConfigFilePath) {
            $params = ['_secure' => $this->request->isSecure()];

            return $this->assetRepository->getUrlWithParams(self::CANVA_LOGO_FALLBACK_PATH, $params);
        }

        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $concatedFilePath = $mediaBaseUrl . self::CANVA_LOGO_UPLOAD_PATH . $logoConfigFilePath;

        return $concatedFilePath;
    }

    /**
     * Return canva app link
     *
     * @return string
     */
    public function getCanvaAppLink(): string
    {
        if ($this->useModal() && ($this->config->getBaseUrl() != '') && ($this->config->getPath() != null)) {
            return (string)$this->config->getBaseUrl() . $this->config->getPath();
        }

        if (count($this->getOptionsAvailable()) == 1 && isset($this->getOptionsAvailable()[0]['product_mapping_id'])) {
            return (string)$this->config->getBaseUrl() . $this->config->getPath()
                . '?canvaProductId=' . $this->getOptionsAvailable()[0]['product_mapping_id'];
        }

        return (string) $this->urlBuilder->getBaseUrl();
    }
}
