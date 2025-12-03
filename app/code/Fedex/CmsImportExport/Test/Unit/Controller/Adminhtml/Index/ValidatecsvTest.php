<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Controller\Adminhtml\Index;

use Fedex\CmsImportExport\Controller\Adminhtml\Index\Validatecsv;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use \Fedex\CmsImportExport\Model\Import\Validate;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Helper\ShipmentEmail
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ValidatecsvTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $validateCsv;
    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var Json|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultJson;

    /**
     * @var Validate|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validate;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->resultJsonFactory = $this->createPartialMock(
            JsonFactory::class,
            ['create']
        );

        $this->resultJson = $this->createPartialMock(
            Json::class,
            ['setData']
        );

        $this->validate = $this->getMockBuilder(Validate::class)
            ->setMethods(
                [
                    'validateCsv'
                ]
            )
         ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->validateCsv = $this->objectManager->getObject(
            Validatecsv::class,
            [
                'resultJsonFactory' => $this->resultJsonFactory,
                'validate' => $this->validate
            ]
        );
    }

    /**
     * Test execute function
     */
    public function testExecute()
    {
        $this->resultJsonFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);
    
        $this->validate->expects($this->once())
            ->method('validateCsv')
            ->willReturnSelf();

        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };

        $this->resultJson->expects($this->any())->method('setData')->willReturnCallback($setDataCallback);

        $this->assertEquals(null, $this->validateCsv->execute());
    }
}
