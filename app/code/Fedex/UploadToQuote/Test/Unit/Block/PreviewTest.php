<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\UploadToQuote\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Block\Preview;
use Magento\Framework\View\Element\Template\Context;

class PreviewTest extends TestCase
{
    
    protected $previewBlock;
    /**
     * @var Context|MockObject
     */
    private $contextMock;
    
    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);

        $this->previewBlock = new Preview(
            $this->contextMock
        );
    }

    /**
     * Get and Set item
     *
     * @return void
     */
    public function testSetAndGetItem()
    {
        $testItem = 'testItem';
        $this->previewBlock->setItem($testItem);

        $this->assertEquals($testItem, $this->previewBlock->getItem());
    }
}
