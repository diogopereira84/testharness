<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model;

use Fedex\Company\Model\Company\Custom\Billing\Invoiced\Mapper as InvoicedMapper;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Mapper as CreditCardMapper;
use Fedex\Company\Model\Company\Custom\Billing\Shipping\Mapper as ShippingMapper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Company\Api\CompanyRepositoryInterface;

class ConfigProvider implements ConfigProviderInterface
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
     * Custom billing fxo account number editable key
     */
    private const CUSTOM_BILLING_FXO_ACCOUNT_NUMBER_EDITABLE = 'fxo_account_number_editable';

    /**
     * Custom billing shipping account number editable key
     */
    private const CUSTOM_BILLING_SHIPPING_ACCOUNT_NUMBER_EDITABLE = 'shipping_account_number_editable';

    /**
     * Custom billing discount account number editable key
     */
    private const CUSTOM_BILLING_FXO_ACCOUNT_EDITABLE = 'discount_account_number_editable';

    /**
     * @param Session $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param InvoicedMapper $invoicedMapper
     * @param CreditCardMapper $creditCardMapper
     * @param ShippingMapper $shippingMapper
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly InvoicedMapper $invoicedMapper,
        private readonly CreditCardMapper $creditCardMapper,
        private readonly ShippingMapper $shippingMapper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $data = [];

        $companyId = $this->customerSession->getCustomerCompany();

        if ($companyId) {
            $company = $this->companyRepository->get((int) $companyId);
            $data = [
                self::CUSTOM_BILLING_INVOICED => $this->invoicedMapper->fromJson(
                    (string)$company->getData(self::CUSTOM_BILLING_INVOICED)
                )->getItemsArray(),
                self::CUSTOM_BILLING_CREDIT_CARD => $this->creditCardMapper->fromJson(
                    (string)$company->getData(self::CUSTOM_BILLING_CREDIT_CARD)
                )->getItemsArray(),
                self::CUSTOM_BILLING_SHIPPING => $this->shippingMapper->fromJson(
                    (string)$company->getData(self::CUSTOM_BILLING_SHIPPING)
                )->getItemsArray(),
                self::CUSTOM_BILLING_FXO_ACCOUNT_NUMBER_EDITABLE =>
                    (string)$company->getData(self::CUSTOM_BILLING_FXO_ACCOUNT_NUMBER_EDITABLE),
                self::CUSTOM_BILLING_SHIPPING_ACCOUNT_NUMBER_EDITABLE =>
                    (string)$company->getData(self::CUSTOM_BILLING_SHIPPING_ACCOUNT_NUMBER_EDITABLE),
                self::CUSTOM_BILLING_FXO_ACCOUNT_EDITABLE =>
                    (string)$company->getData(self::CUSTOM_BILLING_FXO_ACCOUNT_EDITABLE),
            ];
        }

        return $data;
    }
}
