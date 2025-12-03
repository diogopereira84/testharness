<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CusomizedMegamenu\Test\Unit\Plugin\Block;

use Magento\Framework\View\Element\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CustomizedMegamenu\Plugin\Block\TopMenu;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TopMenuTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Fedex\CustomizedMegamenu\Plugin\Block\TopMenu & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $topMenuMock;
    protected $subjectMock;
    protected $topMenu;
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $data;

    /**
     * @var Store|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeMock;
    

    // @codingStandardsIgnoreEnd

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->topMenuMock = $this->getMockBuilder(TopMenu::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subjectMock = $this->getMockBuilder(\Magedelight\Megamenu\Block\TopMenu::class)
            ->setMethods(["getCurrentCat", "setVerticalRightChildItem"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->topMenu = $this->objectManager->getObject(
            TopMenu::class,
            [
                'context' => $this->contextMock
            ]
        );
        
        
    }

    
    
    /**
     * Get store identifier
     *
     * @return  StoreId
     */
    public function testAfterSetVerticalRightParentItem()
    {   
        $result = '';
        $childrens = ['id' => 64, 'label' => 'Blackstone', 'url' => 'https://staging.office.fedex.com/kp/browse-catalog/blackstone.html', 'childrens' => [6 => ['id' => 67, 'label' => 'Compliance Training Materials', 'url' => 'https://staging.office.fedex.com/kp/browse-catalog/blackstone/compliance-training-materials.html', 'childrens' =>[] ] ]
        ];

        $this->topMenu->afterSetVerticalRightParentItem($this->subjectMock, $result, $childrens);

       // $this->assertEquals($storeId, $this->data->getStoreId());
    }

    /**
     * Get store identifier
     *
     * @return  StoreId
     */
    public function testAfterSetVerticalRightParentItemForElse()
    {
        $result = '';
        $childrens = ['id' => 64, 'label' => 'Blackstone',
        'url' => 'https://staging.office.fedex.com/kp/browse-catalog/blackstone.html',
        'childrens' => [0 => ['id' => 67, 'label' => 'Compliance Training Materials',
        'url' => 'https://staging.office.fedex.com/kp/browse-catalog/blackstone/compliance-training-materials.html',
        'childrens' =>[0=> "test", 1=> "test",  2=> "test", 3=> "test", 4=> "test", 5=> "test",] ] ]
        ];

        $this->topMenu->afterSetVerticalRightParentItem($this->subjectMock, $result, $childrens);

       // $this->assertEquals($storeId, $this->data->getStoreId());
    }
}
?>
