<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Plugin;

use Magento\Cms\Controller\Adminhtml\Page\Save;
use Magento\Framework\App\RequestInterface;
use Fedex\Canva\Model\Builder;
use Magento\Framework\View\LayoutInterface;

class EncodeCanvaSizesOnSavePage
{
    /**
     * @param RequestInterface $request
     * @param Builder $builder
     * @param LayoutInterface $layout
     */
    public function __construct(
        private RequestInterface $request,
        private Builder $builder,
        private LayoutInterface $layout
    )
    {
    }

    /**
     * Encode canva sizes
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Save $subject
     * @throws \Exception
     */
    public function beforeExecute(Save $subject): void
    {
        $collection = $this->builder->build($this->request->getPostValue('canva_sizes') ?? []);
        $default = $this->request->getPostValue('default') ?? 'option_0';
        $collection->setDefaultOption((int)substr($default, strrpos($default, '_')+1));
        $this->request->setPostValue('canva_sizes', $collection->toJson());
    }
}
