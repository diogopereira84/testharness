<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerExportEmail\Model\Export;

use Fedex\CustomerExportEmail\Api\Data\ExportInfoInterface;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Factory for Export Info
 */
class ExportInfoFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ObjectManagerInterface $objectManager,
        private LoggerInterface $logger,
        private SerializerInterface $serializer
    )
    {
    }

    /**
     * Create ExportInfo object.
     *
     * @param string $message
     * @param mixed $customerdata
     * @param mixed $inactivecolumns
     * @return ExportInfoInterface
     */
    public function create($message, $customerdata, $inactivecolumns)
    {
        /** @var ExportInfoInterface $exportInfo */
        $exportInfo = $this->objectManager->create(ExportInfoInterface::class);
        $exportInfo->setMessage($message);
        $exportInfo->setCustomerdata($this->serializer->serialize($customerdata));
        $exportInfo->setInActiveColumns($this->serializer->serialize($inactivecolumns));

        return $exportInfo;
    }
}
