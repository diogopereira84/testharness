<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerExportEmail\Model\Export;

use Fedex\CustomerExportEmail\Api\Data\ExportInfoInterface;

/**
 * Class ExportInfo implementation for ExportInfoInterface.
 */
class ExportInfo implements ExportInfoInterface
{

    /**
     * @var string
     */
    private $message;

    /**
     * @var mixed
     */
    private $customerdata;

    /**
     * @var mixed
     */
    private $inactivecolumns;

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @inheritdoc
     */
    public function getCustomerdata()
    {
        return $this->customerdata;
    }

    /**
     * @inheritdoc
     */
    public function setCustomerdata($customerdata)
    {
        $this->customerdata = $customerdata;
    }

    /**
     * @inheritdoc
     */
    public function getInActiveColumns()
    {
        return $this->inactivecolumns;
    }

    /**
     * @inheritdoc
     */
    public function setInActiveColumns($inactivecolumns)
    {
        $this->inactivecolumns = $inactivecolumns;
    }
}
