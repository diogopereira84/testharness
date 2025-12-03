<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Form\Users;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\OptionSourceInterface;
use Fedex\SelfReg\Ui\Component\Form\Users\Site;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Company\Api\Data\CompanyInterface;


class SiteTest extends TestCase
{
    protected $siteMock;
    protected $searchCriteriaBuilder;
    protected $companyRepository;
    protected $request;
    protected $searchCriteria;
    protected $companyInterface;

    protected function setUp(): void
    {

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMock();
        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->siteMock = $objectManagerHelper->getObject(
            Site::class,
            [
                'request' => $this->request,
                'companyRepository' => $this->companyRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder
            ]
        );
    }

    public function testGetCompanyData()
    {
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->companyRepository->expects($this->any())
            ->method('getList')
            ->willReturn($this->searchCriteria);
        
        $this->searchCriteria->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->companyInterface]);
        
        $this->companyInterface->expects($this->any())
            ->method('getId')
            ->willReturn(12);
        
        $this->companyInterface->expects($this->any())
            ->method('getCompanyName')
            ->willReturn('Test Company');
        
        $this->assertNotNull($this->siteMock->getCompanyData());
    }

    public function testToOptionArray() {
        $this->testGetCompanyData();
        $this->assertNotNull($this->siteMock->toOptionArray());
    }
}
