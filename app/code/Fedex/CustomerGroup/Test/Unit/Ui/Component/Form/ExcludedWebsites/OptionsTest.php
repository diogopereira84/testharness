<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Ui\Component\Form\ExcludedWebsites;

use Fedex\CustomerGroup\Ui\Component\Form\ExcludedWebsites\Options;
use Magento\Store\Model\System\Store;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    /**
     * @var Options
     */
    private $optionsBlock;

    /**
     * @var Store
     */
    protected $systemStoreMock;
    /**
     * @param Store $systemStore
     */

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->systemStoreMock = $this->getMockBuilder(Store::class)
            ->setMethods(['getWebsiteValuesForForm'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsBlock = new Options(
            $this->systemStoreMock
        );
    }

    /**
     * Test GetAllOptions
     */
    public function testGetAllOptions(): void
    {
        $this->systemStoreMock->expects($this->any())
            ->method('getWebsiteValuesForForm')
            ->willReturn('options');
        $expectedResult = 'options';
        $actualResult = $this->optionsBlock->getAllOptions();

        $this->assertEquals($expectedResult, $actualResult);
    }
}
