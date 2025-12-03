<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Ui\Component\Listing\Column\AddedDate;
use Magento\Framework\View\Element\UiComponentFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AddedDateTest extends TestCase
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

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
     * @var AddedDate
     */
    protected $addedDateMock;

	/**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->contextInterface = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->uiComponentFactory = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $objectManagerHelper = new ObjectManager($this);
		
        $this->addedDateMock = $objectManagerHelper->getObject(
            AddedDate::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'selfReg' => $this->selfReg
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
		$testData = ['data' => ['items' => [['created_at' => 'Sep 08, 2023']]]];
		$expectedResult = ['data' => ['items' => [['created_at' => 'Sep 08, 2023']]]];
		$this->assertEquals($expectedResult, $this->addedDateMock->prepareDataSource($testData));
    }
}
