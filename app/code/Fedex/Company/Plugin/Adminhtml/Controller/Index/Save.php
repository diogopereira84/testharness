<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Plugin\Adminhtml\Controller\Index;

use Exception;
use Psr\Log\LoggerInterface;
use Fedex\Company\Controller\Adminhtml\Index\Save as CompanySave;
use Fedex\Company\Model\Company\Custom\Billing\Invoiced\Mapper as InvoicedMapper;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Mapper as CreditCardMapper;
use Fedex\Company\Model\Company\Custom\Billing\Shipping\Mapper as ShippingMapper;
use Magento\Framework\App\RequestInterface;

class Save
{
    /**
     * Custom billing invoiced field key
     */
    private const CUSTOM_BILLING_INVOICED = 'custom_billing_invoiced';

    /**
     * Custom billing credit card field key
     */
    private const CUSTOM_BILLING_CREDIT_CARD = 'custom_billing_credit_card';

    /**
     * Custom billing shipping field key
     */
    private const CUSTOM_BILLING_SHIPPING = 'custom_billing_shipping';

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param InvoicedMapper $invoicedMapper
     * @param CreditCardMapper $creditCardMapper
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestInterface $request,
        private readonly InvoicedMapper $invoicedMapper,
        private readonly CreditCardMapper $creditCardMapper,
        private readonly ShippingMapper $shippingMapper
    ) {
    }

    /**
     * Before execute plugin
     *
     * @param CompanySave $subject
     */
    public function beforeExecute(CompanySave $subject): void
    {
        try {
            $this->request->setPostValue(
                self::CUSTOM_BILLING_INVOICED,
                $this->invoicedMapper->fromArrayToJson(
                    $this->request->getPostValue(
                        self::CUSTOM_BILLING_INVOICED
                    ) ?? []
                )
            );
            $this->request->setPostValue(
                self::CUSTOM_BILLING_CREDIT_CARD,
                $this->creditCardMapper->fromArrayToJson(
                    $this->request->getPostValue(
                        self::CUSTOM_BILLING_CREDIT_CARD
                    ) ?? []
                )
            );
            $this->request->setPostValue(
                self::CUSTOM_BILLING_SHIPPING,
                $this->shippingMapper->fromArrayToJson(
                    $this->request->getPostValue(
                        self::CUSTOM_BILLING_SHIPPING
                    ) ?? []
                )
            );
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
