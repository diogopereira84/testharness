<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Controller\Index;

use Fedex\MarketplacePunchout\Model\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Exception;
use Magento\Framework\App\ActionInterface;
use Fedex\MarketplacePunchout\Model\Context;
use Magento\Framework\App\RequestInterface;

class Index implements ActionInterface, HttpGetActionInterface
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param Redirect $redirect
     * @param RequestInterface $request
     */
    public function __construct(
        private Context $context,
        private Redirect $redirect,
        private RequestInterface $request,
    ) {
    }

    /**
     * Execute index action
     */
    public function execute()
    {
        try {
            $productSku = $this->request->getParam('sku');
            return $this->context->getMarketplace()->punchout($productSku);
        } catch (Exception $e) {
            $this->context->getLogger()->error(
                __('SELLER ERROR: An error occurred on the server: %1', $e->getMessage())
            );
            $this->context->getLogger()->error(
                __('SELLER ERROR: An error occurred on the server: %1', $e->getTraceAsString())
            );
            return $this->redirect->redirect(false, '', true);
        }
    }
}
