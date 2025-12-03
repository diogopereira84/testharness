<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Test\Unit\Model;

use Fedex\EmailVerification\Model\EmailVerification;
use Fedex\EmailVerification\Model\EmailVerificationCustomer;
use Fedex\EmailVerification\Model\EmailVerificationCustomerFactory;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Math\Random;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Ramsey\Uuid\Uuid;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SelfReg\Block\Landing;

class EmailVerificationTest extends TestCase
{
    /**
     * @var EmailVerificationCustomerFactory|MockObject
     */
    protected $emailVerifCustMock;

    /**
     * @var CompanyCustomerInterfaceFactory|MockObject
     */
    protected $compCustInterfaceMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var CustomerFactory|MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var Random|MockObject
     */
    protected $randomMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var StoreInterface|MockObject
     */
    protected $storeMock;

    /**
     * @var EmailVerificationCustomer|MockObject
     */
    protected $emailVerificationCustomerMock;

    /**
     * @var Uuid|MockObject
     */
    protected $uuidMock;

    /**
     * @var CustomerInterface|MockObject
     */
    protected $customerInterfaceMock;

    /**
     * @var CustomerExtensionInterface|MockObject
     */
    protected $customerExtensionMock;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    protected $companyCustomerMock;

    /**
     * @var SessionFactory|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    protected $companyMgmtMock;

    /**
     * @var CompanyInterface|MockObject
     */
    protected $companyMock;

    /**
     * @var SelfReg|MockObject
     */
    protected $selfRegHelperMock;

    /**
     * @var Landing|MockObject
     */
    protected $selfRegLandingMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var EmailVerification
     */
    protected $emailVerificationMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->emailVerifCustMock = $this->getMockBuilder(EmailVerificationCustomerFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['load'])
            ->getMock();
        $this->compCustInterfaceMock = $this->getMockBuilder(CompanyCustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['setStatus'])
            ->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'getById'])
            ->getMockForAbstractClass();
        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['load', 'save', 'getEmail', 'setEmail'])
            ->getMock();
        $this->randomMock = $this->getMockBuilder(Random::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRandomString'])
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->emailVerificationCustomerMock = $this->getMockBuilder(EmailVerificationCustomer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'save', 'addData'])
            ->addMethods(['getVerificationKey', 'getCustomerEntityId', 'getKeyExpirationDatetime'])
            ->getMock();
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBaseUrl'])
            ->getMockForAbstractClass();
        $this->uuidMock = $this->getMockBuilder(Uuid::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fromString', 'getBytes', 'uuid4', 'toString', 'fromBytes'])
            ->getMockForAbstractClass();
        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setCustomAttribute', 'getExtensionAttributes', 'setExtensionAttributes'])
            ->getMockForAbstractClass();
        $this->customerExtensionMock = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $this->companyCustomerMock = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setStatus'])
            ->getMockForAbstractClass();
        $this->customerSessionMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['unsEmailVerificationErrorMessage', 'setEmailVerificationErrorMessage'])
            ->getMock();
        $this->companyMgmtMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByCustomerId'])
            ->getMockForAbstractClass();
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();
        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSettingByCompanyId'])
            ->getMock();
        $this->selfRegLandingMock = $this->getMockBuilder(Landing::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoginUrl'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->emailVerificationMock = $this->objectManager->getObject(
            EmailVerification::class,
            [
                'emailVerificationCustomerFactory' => $this->emailVerifCustMock,
                'compCustInterface' => $this->compCustInterfaceMock,
                'customerRepositoryInterface' => $this->customerRepositoryMock,
                'customerFactory' => $this->customerFactoryMock,
                'randomDataGenerator' => $this->randomMock,
                'storeManager' => $this->storeManagerMock,
                'customerSession' => $this->customerSessionMock,
                'companyManagement' => $this->companyMgmtMock,
                'selfRegHelperMock' => $this->selfRegHelperMock,
                'selfRegLanding' => $this->selfRegLandingMock
            ]
        );
    }

    /**
     * Test updateEmailVerificationCustomer
     *
     * @param int|null $customerId
     * @dataProvider getUpdateEmailVerificationCustomerDataProvider
     * @return void
     */
    public function testUpdateEmailVerificationCustomer($customerId): void
    {
        $this->emailVerifCustMock->expects($this->once())
            ->method('create')
            ->willReturn($this->emailVerificationCustomerMock);
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);
        $this->uuidMock->expects($this->any())
            ->method('fromString')
            ->willReturnSelf();
        $this->uuidMock->expects($this->any())
            ->method('getBytes')
            ->willReturn('91BB430C12C4416690A95F0F00D8E49C');
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('addData')
            ->willReturnSelf();
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->assertNull($this->emailVerificationMock
            ->updateEmailVerificationCustomer(1, '91BB430C12C4416690A95F0F00D8E49C'));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getUpdateEmailVerificationCustomerDataProvider(): array
    {
        return [[null], [1]];
    }

    /**
     * Test getEmailVerificationLink
     *
     * @param string|null $emailUrl
     * @param string|null $uuid
     * @dataProvider getEmailVerificationLinkDataProvider
     * @return void
     */
    public function testGetEmailVerificationLink($emailUrl, $uuid): void
    {
        $baseUrl = 'https://test.com/';
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_WEB)
            ->willReturn($baseUrl);
        
        $this->assertEquals($emailUrl, $this->emailVerificationMock->getEmailVerificationLink($uuid));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getEmailVerificationLinkDataProvider(): array
    {
        return [['https://test.com/emailverification?key=abcd1234', 'abcd1234'], [null, '']];
    }

    /**
     * Test generateCustomerEmailUuid
     *
     * @param int|null $customerId
     * @param string $uuid
     * @param string|null $binaryUuid
     * @dataProvider getGenerateCustomerEmailUuidDataProvider
     * @return void
     */
    public function testGenerateCustomerEmailUuid($customerId, $uuid, $binaryUuid): void
    {
        $this->emailVerifCustMock->expects($this->once())
            ->method('create')
            ->willReturn($this->emailVerificationCustomerMock);
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);
        $this->uuidMock->expects($this->any())
            ->method('fromBytes')
            ->willReturnSelf();
        $this->emailVerificationCustomerMock->expects($this->any())
            ->method('getVerificationKey')
            ->willReturn($binaryUuid);
        $this->uuidMock->expects($this->any())
            ->method('uuid4')
            ->willReturnSelf();
        $this->uuidMock->expects($this->any())
            ->method('toString')
            ->willReturn($uuid);
        $this->assertNotNull($this->emailVerificationMock->generateCustomerEmailUuid(1));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getGenerateCustomerEmailUuidDataProvider(): array
    {
        return
        [
            [1, '69f78d44dc6e498784f95f3295026386', '6e498784f95f3295'],
            [null, '79f78d44dc6e498784f95f3295026386', null]
        ];
    }

    /**
     * Test isVerificationLinkActive
     *
     * @param int|null $customerId
     * @param string|null $expirationDatetime
     * @param bool $returnBool
     * @dataProvider getIsVerificationLinkActiveDataProvider
     * @return void
     */
    public function testIsVerificationLinkActive($customerId, $expirationDatetime, $returnBool): void
    {
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);
        $this->emailVerificationCustomerMock->expects($this->any())
            ->method('getKeyExpirationDatetime')
            ->willReturn($expirationDatetime);
        $this->customerSessionMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sessionMock);
        $this->sessionMock->expects($this->any())
            ->method('unsEmailVerificationErrorMessage')
            ->willReturnSelf();

        $this->assertEquals($returnBool, $this->emailVerificationMock
            ->isVerificationLinkActive($this->emailVerificationCustomerMock));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getIsVerificationLinkActiveDataProvider(): array
    {
        return [
            [
                1,
                date("Y-m-d H:i:s", strtotime('+10 minutes', strtotime(date("Y-m-d H:i:s")))),
                true
            ],
            [
                1,
                date("Y-m-d H:i:s"),
                false
            ],
            [
                1,
                null,
                false
            ],
            [
                null,
                null,
                false
            ]
        ];
    }

    /**
     * Test getCustomerByEmailUuid
     *
     * @return void
     */
    public function testGetCustomerByEmailUuid(): void
    {
        $uuidVal = '29f78d44dc6e498784f95f3295026386';

        $this->emailVerifCustMock->expects($this->once())
            ->method('create')
            ->willReturn($this->emailVerificationCustomerMock);
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->uuidMock->expects($this->any())
            ->method('fromString')
            ->willReturnSelf();
        $this->uuidMock->expects($this->any())
            ->method('getBytes')
            ->willReturn($uuidVal);

        $this->assertNotNull($this->emailVerificationMock->getCustomerByEmailUuid($uuidVal));
    }

    /**
     * Test changeCustomerStatus
     *
     * @return void
     */
    public function testChangeCustomerStatus(): void
    {
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('getCustomerEntityId')
            ->willReturn(1);
        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionMock);
        $this->customerExtensionMock->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerMock);
        $this->compCustInterfaceMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->assertTrue($this->emailVerificationMock->changeCustomerStatus($this->emailVerificationCustomerMock));
    }

    /**
     * Test setExpiredLinkErrorMessage
     *
     * @param int|null $customerId
     * @param int|null $companyId
     * @param array $approvalSetting
     * @dataProvider getIsVerificationLinkActiveDataProvider
     * @return void
     */
    public function testSetExpiredLinkErrorMessage($customerId, $companyId, $approvalSetting): void
    {
        $this->emailVerificationCustomerMock->expects($this->once())
            ->method('getCustomerEntityId')
            ->willReturn($customerId);
        $this->companyMgmtMock->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())
            ->method('getId')
            ->willReturn($companyId);
        $this->selfRegHelperMock->expects($this->any())
            ->method('getSettingByCompanyId')
            ->willReturn($approvalSetting);
        $this->selfRegLandingMock->expects($this->any())
            ->method('getLoginUrl')
            ->willReturn('https://test/');
        $this->customerSessionMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sessionMock);
        $this->sessionMock->expects($this->any())
            ->method('setEmailVerificationErrorMessage')
            ->willReturnSelf();
        
        $this->assertNull($this->emailVerificationMock->setExpiredLinkErrorMessage($this->emailVerificationCustomerMock));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getSetExpiredLinkErrorMessageDataProvider(): array
    {
        return
        [
            [1, 1, ['fcl_user_email_verification_error_message' => 'Testing']],
            [1, null, []],
            [null, null, []]
        ];
    }
}
