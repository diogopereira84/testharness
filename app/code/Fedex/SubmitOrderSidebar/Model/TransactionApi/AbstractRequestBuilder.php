<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\TransactionApi;

abstract class AbstractRequestBuilder
{
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @return string
     */
    public function getDateFormatted(): string
    {
        return date(self::DATE_FORMAT);
    }
}
