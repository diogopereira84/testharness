<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Fedex\Orderhistory\Test\Plugin\Frontend\Magento\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\App\Request\Http;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Ui\Component\Listing\Columns\Column as PluginColumn;

class ColumnTest extends \PHPUnit\Framework\TestCase
{
    protected $subject;
    protected $helper;
    protected $request;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $plugin;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(Column::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['getData'])
                            ->getMock();

        $this->helper = $this->getMockBuilder(Data::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['isModuleEnabled'])
                            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['getFullActionName'])
                            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->plugin = $this->objectManager->getObject(
            PluginColumn::class,
            [
                'helper' => $this->helper,
                'request' => $this->request
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function testBeforePrepare()
    {
        
        $columnData['config'] = [
                                'dataType' => 'text',
                                'component' => 'Magento_NegotiableQuote/js/quote/grid/column',
                                'componentType' => 'column',
                                'sortable' => '',
                                'label' => 'Quote Name'
                               ];
        $columnData['name'] = 'quote_name';
        
        $this->request->expects($this->any())
                ->method('getFullActionName')
                ->willReturn("negotiable_quote_quote_index");

        $this->helper->expects($this->any())
                ->method('isModuleEnabled')
                ->willReturn(true);

        $this->subject->expects($this->any())
                ->method('getData')
                ->willReturn($columnData);

        $this->plugin->beforePrepare($this->subject);
    }

    public function testBeforePrepareColumnName()
    {
        
        $columnData['config'] = [
                                'dataType' => 'text',
                                'component' => 'Magento_NegotiableQuote/js/quote/grid/column',
                                'componentType' => 'column',
                                'sortable' => '',
                                'label' => 'Quote Name'
                               ];
        $columnData['name'] = 'created_by';
        
        $this->request->expects($this->any())
                ->method('getFullActionName')
                ->willReturn("negotiable_quote_quote_index");

        $this->helper->expects($this->any())
                ->method('isModuleEnabled')
                ->willReturn(true);

        $this->subject->expects($this->any())
                ->method('getData')
                ->willReturn($columnData);

        $this->plugin->beforePrepare($this->subject);
    }
}
