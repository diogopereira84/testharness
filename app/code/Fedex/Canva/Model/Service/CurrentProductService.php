<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\SessionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class CurrentProductService
 * Used to retrieve the current product information from session
 */
class CurrentProductService
{
    private $productId;

    /**
     * @param SessionFactory $sessionFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private SessionFactory $sessionFactory,
        private ProductRepositoryInterface $productRepository
    )
    {
    }

    /**
     * Return the current product
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct(): ProductInterface
    {
        return $this->productRepository->getById($this->getProductId());
    }

    /**
     * Return the current product id
     *
     * @return int
     */
    public function getProductId(): int
    {
        $catalogSessionFactory = $this->sessionFactory->create();
        $productId = $catalogSessionFactory->getData('last_viewed_product_id');
        $this->productId = $productId ? (int)$productId : 0;
        return $this->productId;
    }
}
