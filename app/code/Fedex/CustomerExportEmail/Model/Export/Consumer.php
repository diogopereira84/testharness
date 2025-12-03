<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerExportEmail\Model\Export;

use Psr\Log\LoggerInterface;
use Fedex\CIDPSG\Helper\Email as EmailHelper;
use Fedex\CustomerExportEmail\Api\Data\ExportInfoInterface;
use Fedex\CustomerExportEmail\Helper\Data;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Consumer for export message.
 */
class Consumer
{
/**
     * @param LoggerInterface $logger
     * @param EmailHelper $emailHelper
     * @param Data $helperData
     * @param SerializerInterface $serializer
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected EmailHelper $emailHelper,
        protected Data $helperData,
        private SerializerInterface $serializer
    )
    {
    }

    /**
     * Consumer logic.
     *
     * @param ExportInfoInterface $exportInfo
     * @return void
     */
    public function process(ExportInfoInterface $exportInfo)
    {
        try {
            $requestData = $exportInfo->getMessage();
            
            $customerdata = $exportInfo->getCustomerdata();
            $inActivecolumns = $exportInfo->getInActiveColumns();
            $unserializeCustomerData = $this->serializer->unserialize($customerdata);
            $unserializeInActiveColumns = $this->serializer->unserialize($inActivecolumns);

            $filePath = $this->helperData->generateCustomerDataCsv(
                $unserializeCustomerData,
                $unserializeInActiveColumns
            );

            $genericEmailData = json_decode((string)$requestData, true);
            $this->logger->info(
                __METHOD__.':'.__LINE__. "Email data read from customer export
                email queue " . $requestData);
            
            if ($filePath) {
                $genericEmailData['attachment'] = $filePath;
            } else {
                $this->logger->info(
                __METHOD__.':'.__LINE__. "Customer Data File Not Generated");
            }
            $this->emailHelper->sendEmail($genericEmailData);
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__.':'.__LINE__.': Error message in read data from
                customer export queue ',
                ['exception' => $e->getMessage()]
            );
        }
    }
}
