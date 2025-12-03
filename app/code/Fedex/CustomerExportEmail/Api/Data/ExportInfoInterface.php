<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerExportEmail\Api\Data;

/**
 * Basic interface with data needed for export operation.
 * @api
 * @since 100.3.2
 */
interface ExportInfoInterface
{
    /**
     * Return message.
     *
     * @return string
     * @since 100.3.2
     */
    public function getMessage();

    /**
     * Set message into local variable.
     *
     * @param string $message
     * @return void
     * @since 100.3.2
     */
    public function setMessage($message);

    /**
     * Returns customerdata.
     *
     * @return string
     * @since 100.3.2
     */
    public function getCustomerdata();

    /**
     * Set customerdata.
     *
     * @param string $customerdata
     * @return void
     * @since 100.3.2
     */
    public function setCustomerdata($customerdata);

    /**
     * Returns inactivecolumns.
     *
     * @return string
     * @since 100.3.2
     */
    public function getInActiveColumns();

    /**
     * Set inactivecolumns.
     *
     * @param string $inactivecolumns
     * @return void
     * @since 100.3.2
     */
    public function setInActiveColumns($inactivecolumns);

}
