<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\InBranch\Test\Unit\CustomerData;

use Fedex\InBranch\Model\InBranchValidation;
use Fedex\InBranch\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\InBranch\CustomerData\InBranchSection;

class InBranchSectionTest extends TestCase
{
    protected $dataMock;
    protected $inBranchValidationMock;
    protected $inBranchSection;
    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inBranchSection = (new ObjectManager($this))->getObject(
            InBranchSection::class,
            [
                'dataMock' => $this->dataMock,
                'inBranchValidationMock' => $this->inBranchValidationMock
            ]
        );
    }
    /**
     * @test testGetSectionData
     */
    public function testGetSectionData()
    {
        $response = [
            'isInBranchDataInCart' => false,
            'isInBranchUser' => null,
        ];

        $this->dataMock->expects($this->any())
            ->method('checkInCartINBranch')
            ->willReturn('0718');
        $this->inBranchValidationMock->expects($this->any())
            ->method('isInBranchUser')
            ->willReturn(true);

        $this->assertEquals($response, $this->inBranchSection->getSectionData());
    }
}
