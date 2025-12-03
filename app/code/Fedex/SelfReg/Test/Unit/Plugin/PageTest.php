<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SelfReg\Test\Unit\Plugin;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Plugin\Page;
use Magento\Cms\Block\Page as Subject;
use Magento\Cms\Model\Page as Result;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Helper\SelfReg;

class PageTest extends TestCase
{
    protected $selfReg;
    protected $subject;
    protected $result;
    /**
     * @var (\Magento\Company\Api\CompanyRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyRepo;
    protected $sdeHelper;
    protected $deliveryHelperMock;
    protected $sessionMock;
    protected $requestMock;
    protected $page;
    /**
     * @inheritDoc
     * B-1145896
     */
    protected function setUp(): void
    {

        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer'])
            ->getMock();

        $this->subject = $this->getMockBuilder(Subject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepo = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMockForAbstractClass();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore'])
            ->getMock();

        $this->deliveryHelperMock = $this
            ->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this
            ->getMockBuilder(Session::class)
            ->setMethods(['isEproCustomer', 'getOndemandCompanyInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this
            ->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder(Http::class)
            ->setMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->page = $objectManagerHelper->getObject(
            Page::class,
            [
                'customerSession' => $this->sessionMock,
                'companyRepository' => $this->companyRepo,
                'request' => $this->requestMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'sdeHelper' => $this->sdeHelper,
                'selfReg' => $this->selfReg
            ]
        );
    }

    /**
     * TestCase for afterGetPage
     *
     * @inheritDoc
     */
    public function testAfterGetPage()
    {
		// B-1515570
		$ondemandCompanyInfo = [
            'url_extension' => true,
            'company_type' => 'sde'
        ];
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(1);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(1);
        $this->requestMock->expects($this->any())
            ->method('getFullActionName')
            ->willReturn('cms_index_index');
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(0);            
        $this->sessionMock->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($ondemandCompanyInfo);

        $this->assertEquals($this->result,$this->page->afterGetPage($this->subject, $this->result));
    }
}
