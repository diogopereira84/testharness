<?php
/**
 * @category Fedex
 * @package Fedex_PageBuilderBlocks
 * @copyright Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CanvaCarouselWidget extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Fedex_PageBuilderBlocks::canva-carousel-widget.phtml';

    /**
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getCanvaTagId(): string
    {
        return $this->getData('canva_tag_id') ?? "";
    }
}
