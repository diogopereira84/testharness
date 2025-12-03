<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerExportEmail\Test\Unit\Model\Export;

use Fedex\CIDPSG\Helper\Email as EmailHelper;
use Fedex\CustomerExportEmail\Api\Data\ExportInfoInterface;
use Fedex\CustomerExportEmail\Helper\Data;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\CustomerExportEmail\Model\Export\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;

class ConsumerTest extends TestCase
{
    protected $exportInfoMock;
    /**
     * @var (\Magento\Framework\Serialize\SerializerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerMock;
    /**
     * @var (\Fedex\CIDPSG\Helper\Email & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $emailHelperMock;
    protected $helperDataMock;
    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var ExportInfoInterface|MockObject
     */
    private $exportManagementMock;

    /**
     * @var Consumer
     */
    private $consumer;

    protected function setUp(): void
    {

        $this->exportInfoMock = $this->getMockBuilder(ExportInfoInterface::class)
                                    ->setMethods(['getMessage','getCustomerdata','getInActiveColumns'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                                    ->setMethods(['critical'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
                                    ->setMethods(['unserialize'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->emailHelperMock = $this->getMockBuilder(EmailHelper::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['sendEmail'])
                                    ->getMock();

        $this->helperDataMock = $this->getMockBuilder(Data::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['generateCustomerDataCsv'])
                                    ->getMock();

        $this->consumer = $this->getMockForAbstractClass(
            Consumer::class,
            [
                'logger' => $this->loggerMock,
                'emailHelper' => $this->emailHelperMock,
                'serializer' => $this->serializerMock,
                'helperData' => $this->helperDataMock
            ]
        );
    }

    /**
     * Test Process 
     */
    public function testProcess()
    {
        $this->exportInfoMock->expects($this->any())->method('getMessage')->willReturn('Test');

        $this->exportInfoMock->expects($this->any())->method('getCustomerdata')->willReturn('Test');

        $this->exportInfoMock->expects($this->any())->method('getInActiveColumns')->willReturn('Test');

        $this->helperDataMock->expects($this->any())->method('generateCustomerDataCsv')->willReturn('/var/pub/media/customer_data/customer.csv');

        $this->consumer->process($this->exportInfoMock);
    }

    /**
     * Test Process without file
     */
    public function testProcesswithoutFile()
    {
        $this->exportInfoMock->expects($this->any())->method('getMessage')->willReturn('Test');

        $this->exportInfoMock->expects($this->any())->method('getCustomerdata')->willReturn('Test');

        $this->exportInfoMock->expects($this->any())->method('getInActiveColumns')->willReturn('Test');

        $this->helperDataMock->expects($this->any())->method('generateCustomerDataCsv')->willReturn('');

        $this->consumer->process($this->exportInfoMock);
    }

    /**
     * Test Process with exception
     */
    public function testProcesswithException()
    {
        $this->exportInfoMock->expects($this->any())->method('getMessage')->willReturn('Test');

        $this->exportInfoMock->expects($this->any())->method('getCustomerdata')->willReturn('Test');

        $this->exportInfoMock->expects($this->any())->method('getInActiveColumns')->willReturn('Test');

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->helperDataMock->expects($this->any())->method('generateCustomerDataCsv')->willThrowException($exception);

        $this->consumer->process($this->exportInfoMock);
    }
}
