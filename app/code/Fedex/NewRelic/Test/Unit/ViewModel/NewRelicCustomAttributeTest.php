<?php
declare(strict_types=1);

namespace Fedex\NewRelic\Test\Unit\ViewModel;

use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\Delivery\Helper\Data;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyRepository;
use Magento\Customer\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\NewRelic\ViewModel\NewRelicCustomAttribute;
use Fedex\Base\Helper\Auth;

class NewRelicCustomAttributeTest extends TestCase
{
    protected Auth|MockObject $baseAuthMock;
    /**
     * @var Session|MockObject
     */
    private Session|MockObject $sessionMock;

    /**
     * @var Data|MockObject
     */
    private Data|MockObject $helperMock;

    /**
     * @var CompanyInterface|MockObject
     */
    private CompanyInterface|MockObject $companyMock;

    /**
     * @var CompanyRepository|MockObject
     */
    private CompanyRepository|MockObject $companyRepositoryMock;

    /**
     * @var NewRelicCustomAttribute
     */
    protected NewRelicCustomAttribute $newRelicCustomAttribute;

    /**
     * Setup tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['getCustomerCompany'])
            ->onlyMethods(['isLoggedIn'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->helperMock = $this->createMock(Data::class);
        $this->companyMock = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepositoryMock = $this->createMock(CompanyRepository::class);
        $this->newRelicCustomAttribute = new NewRelicCustomAttribute(
            $this->sessionMock,
            $this->helperMock,
            $this->companyRepositoryMock,
            $this->baseAuthMock
        );
    }

    /**
     * Test method isEproCustomer
     *
     * @return void
     */
    public function testIsEproCustomer(): void
    {
        $this->helperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->assertEquals(true, $this->newRelicCustomAttribute->isEproCustomer());
    }

    /**
     * Test method getAssignedCompany when do not have company assigned
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetAssignedCompanyFalse(): void
    {
        $this->helperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->assertEquals('', $this->newRelicCustomAttribute->getAssignedCompany());
    }

    /**
     * Test method getAssignedCompany when have company assigned
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetAssignedCompanyTrue(): void
    {
        $this->companyRepositoryMock->method('get')->willReturn($this->companyMock);
        $this->helperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->sessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(123);
        $this->assertEquals('', $this->newRelicCustomAttribute->getAssignedCompany());
    }

    /**
     * Test method isCustomerLoggedIn
     *
     * @return void
     */
    public function testIsCustomerLoggedIn(): void
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);
        $this->assertEquals(false, $this->newRelicCustomAttribute->isCustomerLoggedIn());
    }
}
