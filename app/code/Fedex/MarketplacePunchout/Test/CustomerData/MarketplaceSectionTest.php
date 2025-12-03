<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Controller\CustomerData;

use Fedex\MarketplacePunchout\CustomerData\MarketplaceSection;
use Magento\Customer\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketplaceSectionTest extends TestCase
{
    /**
     * @var Session|MockObject
     */
    private Session|MockObject $sessionMock;

    /**
     * @var MarketplaceSection
     */
    private MarketplaceSection $marketplaceModalSection;

    public function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMarketplaceError'])
            ->getMock();

        $this->marketplaceModalSection = new MarketplaceSection($this->sessionMock);
    }

    public function testGetSectionData(): void
    {
        $hasError = true;
        $this->sessionMock->method('getMarketplaceError')->willReturn($hasError);
        $expectedResult = ['has_error' => $hasError];
        $result = $this->marketplaceModalSection->getSectionData();
        $this->assertEquals($expectedResult, $result);
    }
}
