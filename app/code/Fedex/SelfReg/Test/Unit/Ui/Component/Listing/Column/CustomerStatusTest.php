<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Ui\Component\Listing\Column\CustomerStatus;
use Magento\Framework\View\Element\UiComponentFactory;
use Fedex\Commercial\Helper\CommercialHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerStatusTest extends TestCase
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ToggleConfig
     */
    protected $commercialHelperMock;

    /**
     * @var SelfReg
     */
    protected $selfReg;

    /**
     * @var ContextInterface
     */
    protected $contextInterface;

    /**
     * @var UiComponentFactory
     */
    protected $uiComponentFactory;

    /**
     * @var customerStatusMock
     */
    protected $customerStatusMock;

	/**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        
        $this->contextInterface = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->uiComponentFactory = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commercialHelperMock = $this->getMockBuilder(CommercialHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegAdminUpdates'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
		
        $this->customerStatusMock = $objectManagerHelper->getObject(
            CustomerStatus::class,
            [
                'commercialHelper' => $this->commercialHelperMock,
            ]
        );
    }
    
    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'customer_status' => 'Active',
                        'status' => new \Magento\Framework\Phrase('Active'),
                    ],
                ],
            ],
        ];
        
        $this->commercialHelperMock->expects($this->any())
        ->method('isSelfRegAdminUpdates')
        ->willReturn(false);

		$this->assertNotNull($this->customerStatusMock->prepareDataSource($dataSource));
    }

    public function testPrepareDataSourceIfBlock()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'customer_status' => 1,
                        'status' => new \Magento\Framework\Phrase('Active'),
                    ],
                ],
            ],
        ];
        
        $this->commercialHelperMock->expects($this->any())
        ->method('isSelfRegAdminUpdates')
        ->willReturn(true);

		$this->assertNotNull($this->customerStatusMock->prepareDataSource($dataSource));
    }


    public function testPrepareDataSourceElse()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'status' => new \Magento\Framework\Phrase('Active'),
                    ],
                ],
            ],
        ];
        
        $this->commercialHelperMock->expects($this->any())
        ->method('isSelfRegAdminUpdates')
        ->willReturn(true);

		$this->assertNotNull($this->customerStatusMock->prepareDataSource($dataSource));
    }
}
