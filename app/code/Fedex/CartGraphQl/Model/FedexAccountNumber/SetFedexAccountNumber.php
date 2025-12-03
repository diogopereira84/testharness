<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Athira Indrakumar <aindrakumar@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\FedexAccountNumber;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\Cart\Helper\Data as CartDataHelper;

/**
 * @inheritdoc
 */
class SetFedexAccountNumber
{
    /**
     * @param CartDataHelper $cartHelper
     */
    public function __construct(
        protected CartDataHelper $cartHelper
    ) {}

    /**
     * @param $fedexAccountNumber
     * @param $fedexShipNumber
     * @param $quote
     * @return void
     * @throws GraphQlFujitsuResponseException
     */
    public function setFedexAccountNumber($fedexAccountNumber, $fedexShipNumber, $quote): void
    {
        try {
            $quote->setData(
                'fedex_account_number',
                $fedexAccountNumber ? $this->cartHelper->encryptData($fedexAccountNumber) : null
            );
            $quote->setData('fedex_ship_account_number', $fedexShipNumber);
        } catch (\Exception $exception) {
            throw new GraphQlFujitsuResponseException(__($exception->getMessage()));
        }
    }
}
