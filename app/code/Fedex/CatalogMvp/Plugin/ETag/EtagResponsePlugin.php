<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\ETag;

use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Psr\Log\LoggerInterface;

class EtagResponsePlugin
{
    private const CONTROLLER_NAME = 'category';
    private const ACTION_NAME = 'view';

    /**
     * EtagResponsePlugin
     *
     * @param CategoryRepository $categoryRepository
     * @param RequestInterface $request
     * @param CatalogMvpConfigInterface $catalogMvpConfigInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected RequestInterface $request,
        protected CatalogMvpConfigInterface $catalogMvpConfigInterface,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Around Send Response
     *
     * @param HttpResponse $subject
     * @return HttpResponse
     */
    public function beforeSendResponse(
        HttpResponse $subject
    ) {
        /* Check B 2371268 toggle enabled */
        $isB2371268enabled = $this->catalogMvpConfigInterface->isB2371268ToggleEnabled();
        if (!$isB2371268enabled) {
            return $subject;
        }

        $controllerName = $this->request->getControllerName();
        $actionName = $this->request->getActionName();
        if ($controllerName !== self::CONTROLLER_NAME || $actionName !== self::ACTION_NAME) {
            return $subject;
        }

        $categoryId = $this->request->getParam('id');
        if (!$categoryId) {
            return $subject;
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
            $etag = $category->getEtag();
            if ($etag) {
                $subject->setHeader('cache-control', "public, max-age=3600", true);
                $subject->setHeader('pragma', 'cache', true);
                $subject->setHeader('ETag', 'W/"' . $etag . '"', true);
                $ifNoneMatch = $this->request->getHeader('If-None-Match');
                if ($ifNoneMatch && $ifNoneMatch === 'W/"' . $etag . '"') {
                    $subject->setHttpResponseCode(304);
                    return $subject;
                }

            }
        } catch (\Exception $e) {
            $this->logger->error('Category not found for ID: ' . $categoryId . ' - ' . $e->getMessage());
        }
        return $subject;
    }
}
