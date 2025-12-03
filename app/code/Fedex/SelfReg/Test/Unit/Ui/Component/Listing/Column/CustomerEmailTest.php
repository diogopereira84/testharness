<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Fedex\SelfReg\Ui\Component\Listing\Column\CustomerEmail;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;

class CustomerEmailTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\UiComponent\ContextInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextInterfaceMock;
    /**
     * @var (\Magento\Framework\View\Element\UiComponentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $uiComponentFactoryMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $selfReg;
    protected $customerEmailMock;
    /**
     * @var array<string, string>
     */
    protected $data;
    private CustomerEmail|MockObject $cEmail;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
		$this->contextInterfaceMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

		$this->uiComponentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

		$this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();



         $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer','isSelfRegCustomerAdmin'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->customerEmailMock = $objectManagerHelper->getObject(
            CustomerEmail::class,
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
		$this->data['name'] = "Users | Edit";
		$this->setName('name', 'email');
		//~ $this->name = 'email12';
		$testData = ['data' => ['items' => [['email' => 'primary@email.com', 'secondary_email' => 'secondary@email.com']]]];
		$expectedResult = ['data' => ['items' => [['email' => 'secondary@email.com', 'secondary_email' => 'secondary@email.com']]]];


		//~ $this->customerEmailMock->setData('email');
		$this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);

        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);


        $this->cEmail = $this->getMockBuilder(CustomerEmail::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
    	$this->cEmail->expects($this->any())
            ->method('getData')
            ->willReturn('email');


		//~ var_dump($this->customerEmailMock->prepareDataSource($testData));
		//~ exit;


		$this->assertEquals($expectedResult, $this->customerEmailMock->prepareDataSource($testData));
    }
}
