<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model\Service;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class CurrentPageService
{
    /**
     * @param Page $page
     * @param PageRepository $pageRepository
     */
    public function __construct(
        private Page $page,
        private PageRepository $pageRepository
    )
    {
    }

    /**
     * Return the current page
     *
     * @return Page
     * @throws NoSuchEntityException
     */
    public function getCurrentPage()
    {
        return $this->pageRepository->getById((string)$this->page->getId());
    }
}
