<?php

namespace Fedex\Orderhistory\Test\Unit\Frontend\Magento\OrderHistorySearch\Model\Status;

use Fedex\Orderhistory\Plugin\Frontend\Magento\OrderHistorySearch\Model\Order\Status\DataProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var Data
     */
    private $orderHistoryDataHelperMock;

    /**
     * @var Data
     */
    private $dataProviderMock;

    /**
     * @var Object
     */
    private $subject;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->orderHistoryDataHelperMock = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isRetailOrderHistoryEnabled','isCommercialCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(\Magento\OrderHistorySearch\Model\Order\Status\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->dataProviderMock = $this->objectManager->getObject(
            DataProvider::class,
            [
                'orderHistoryDataHelper' => $this->orderHistoryDataHelperMock
            ]
        );
    }//end of setup

    /**
     * Assert afterGetOrderStatusOptions.
     *
     * @return array
     */
    public function testafterGetOrderStatusOptions()
    {
        $result = [['value' => 'new', 'label'=> 'New']];
        $this->orderHistoryDataHelperMock->expects($this->any())->method('isRetailOrderHistoryEnabled')
            ->willReturn(true);
        $this->dataProviderMock->afterGetOrderStatusOptions($this->subject, $result, true);
    }

    /**
     * Assert afterGetOrderStatusOptions without If statement
     *
     * @return array
     */
    public function testafterGetOrderStatusOptionsisWithoutIf()
    {
        $result = [];
        $this->orderHistoryDataHelperMock->expects($this->any())->method('isRetailOrderHistoryEnabled')
            ->willReturn(false);
        $this->dataProviderMock->afterGetOrderStatusOptions($this->subject, $result, true);
    }

    /**
     * Assert testafterGetOrderStatusOptionsDuplicate.
     *
     * @return array
     */
    public function testafterGetOrderStatusOptionsDuplicate()
    {
        $result = [ ['value' => 'processing', 'label'=> 'Processing']];
        $this->orderHistoryDataHelperMock->expects($this->any())->method('isCommercialCustomer')
            ->willReturn(true);
        $this->dataProviderMock->afterGetOrderStatusOptions($this->subject, $result, true);
    }
}
