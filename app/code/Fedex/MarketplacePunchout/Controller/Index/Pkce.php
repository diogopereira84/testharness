<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Model\Context as MarketplaceContext;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Context;

class Pkce extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param MarketplaceContext $marketplaceContext
     * @param NonCustomizableProduct $nonCustomizableProduct
     */
    public function __construct(
        private Context                $context,
        private JsonFactory            $resultJsonFactory,
        private RequestInterface       $request,
        private LoggerInterface        $logger,
        private MarketplaceContext     $marketplaceContext,
        private NonCustomizableProduct $nonCustomizableProduct
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $response = [];
        $resultJson = $this->resultJsonFactory->create();

        try {
            if (!$this->nonCustomizableProduct->isMktCbbEnabled()) {
                return $resultJson->setData($response);
            }

            if (!$this->request->isPost() || !$this->validateRequest($this->request->getPost())) {
                return $resultJson->setData($response);
            }

            $codeChallenge = $this->request->getPostValue('code_challenge');
            $response = $this->marketplaceContext->getMarketplace()->punchout(
                $this->request->getPost('sku'),
                false,
                [
                    'code_challenge' => $codeChallenge
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Stack trace: ' . $e->getTraceAsString() .
                ' Exception during Punchout process from PDP or Cart' .
                $e->getMessage()
            );
        }

        return $resultJson->setData($response);
    }

    /**
     * @param mixed[] $post
     * @return bool
     */
    private function validateRequest(mixed $post): bool
    {
        try {
            if (empty($post['source'])) {
                return false;
            }

            $fields = [
                'code_challenge',
                'sku',
                'offer_id',
                'seller_sku',
                'form_key'
            ];

            if ($post['source'] === 'Cart') {
                $fields = array_merge($fields, ['quote_item_id', 'supplier_part_auxiliary_id', 'supplier_part_id']);
            }

            foreach ($fields as $field) {
                if (empty($post[$field])) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Stack trace: ' . $e->getTraceAsString() .
                ' Invalid request during Login/Register process from Marketplace Configurator' .
                $e->getMessage()
            );
        }

        return true;
    }
}
