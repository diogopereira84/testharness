<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\ViewModel\Cms\Page;

use Exception;
use Fedex\Canva\Api\Data\ConfigInterface;
use Fedex\Canva\Api\Data\SizeCollectionInterface;
use Fedex\Canva\Model\Builder;
use Fedex\Canva\Model\Service\CurrentPageService;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Canva\Model\Service\CurrentProductService;

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
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     * @param SerializerInterface $serializer
     * @param Builder $builder
     * @param CurrentPageService $currentPageService
     * @param ConfigInterface $config
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private LoggerInterface $logger,
        private UrlInterface $urlBuilder,
        private SerializerInterface $serializer,
        private Builder $builder,
        /**
         * Current Product
         */
        private CurrentPageService $currentPageService,
        private ConfigInterface $config,
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
            $page = $this->currentPageService->getCurrentPage();
            if ($page->getData('has_canva_sizes') && count($this->getOptionsAvailable()) > 0) {
                return (bool)$page->getData('has_canva_sizes');
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
     * @throws Exception
     */
    public function useModal(): bool
    {
        return (count($this->getOptionsAvailable()) > 1);
    }

    /**
     * Return The canva link
     *
     * @return string
     * @throws Exception
     */
    public function getLink(): string
    {
        if (count($this->getOptionsAvailable()) == 1) {
            if ($this->getOptionsAvailable()->getIterator()->current()->getProductMappingId()) {
                return (string)$this->urlBuilder->getUrl('/canva/index/index', [
                    'canvaProductId' => $this->getOptionsAvailable()->getIterator()->current()->getProductMappingId()
                ]);
            }
        }

        return (string)$this->urlBuilder->getUrl('canva');
    }

    /**
     * Return default option
     *
     * @return string
     * @throws Exception
     */
    public function getDefaultSize(): string
    {
        if (count($this->getOptionsAvailable()) == 1) {
            if ($this->getOptionsAvailable()->getIterator()->current()->getProductMappingId()) {
                return (string)$this->getOptionsAvailable()->getIterator()->current()->getProductMappingId();
            }
        }
        return '';
    }

    /**
     * Return options available
     *
     * @return SizeCollectionInterface
     * @throws Exception
     */
    public function getOptionsAvailable(): SizeCollectionInterface
    {
        try {
            $page = $this->currentPageService->getCurrentPage();
            $canvaSizes = $page->getData('canva_sizes');
            if ($canvaSizes) {
                return $this->builder->build($this->serializer->unserialize($canvaSizes));
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }

        return $this->builder->build([]);
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

        $productMappingId = count($this->getOptionsAvailable()) == 1 ? $this->getOptionsAvailable()->toArray()[0]['product_mapping_id'] : false;

        if (isset($productMappingId) && $productMappingId) {
            return (string)$this->config->getBaseUrl() . $this->config->getPath()
                . '?canvaProductId=' . $productMappingId;
        }

        return (string) $this->urlBuilder->getBaseUrl();
    }
}
