<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class AddToCartContext
{
    /**
     * @param RequestInterface $request
     * @param QuoteProductAdd $quoteProductAdd
     * @param ProductRepositoryInterface $productRepository
     * @param RedirectFactory $resultRedirectFactory
     * @param RedirectInterface $redirectInterface
     * @param Session $session
     */
    public function __construct(
        private RequestInterface $request,
        private QuoteProductAdd $quoteProductAdd,
        private ProductRepositoryInterface $productRepository,
        private RedirectFactory $resultRedirectFactory,
        private RedirectInterface $redirectInterface,
        private Session $session,
    ) {
    }

    /**
     * Returns Request Interface
     *
     * @return RequestInterface
     */
    public function getRequestInterface(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Returns QuoteProductAdd
     *
     * @return QuoteProductAdd
     */
    public function getQuoteProductAdd(): QuoteProductAdd
    {
        return $this->quoteProductAdd;
    }

    /**
     * Returns ProductRepositoryInterface
     *
     * @return ProductRepositoryInterface
     */
    public function getProductRepositoryInterface(): ProductRepositoryInterface
    {
        return $this->productRepository;
    }

    /**
     * Returns RedirectFactory
     *
     * @return RedirectFactory
     */
    public function getRedirectFactory(): RedirectFactory
    {
        return $this->resultRedirectFactory;
    }

    /**
     * Returns RedirectInterface
     *
     * @return RedirectInterface
     */
    public function getRedirectInterface(): RedirectInterface
    {
        return $this->redirectInterface;
    }

    /**
     * Returns Session
     *
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }
}
