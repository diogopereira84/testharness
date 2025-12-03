<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer\Source;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\Customer\CustomerType;
use Magento\Customer\Model\Session;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\CustomerDataSource;
use Fedex\Base\Helper\Auth;

class CustomerDataSourceTest extends TestCase
{
    protected $unifiedDataLayerMock;
    protected $checkoutData;
    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @var CustomerDataSource
     */
    private $customerDataSource;

    protected Auth|MockObject $baseAuthMock;

    protected function setUp(): void
    {
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->customerDataSource = new CustomerDataSource($this->customerSessionMock,$this->baseAuthMock);

        $this->unifiedDataLayerMock = $this->createMock(UnifiedDataLayerInterface::class);
        $this->checkoutData = [
            json_encode([
                'output' => [
                    'checkout' => [
                        'contact' => [
                            'personName' => [
                                'firstName' => 'John',
                                'lastName' => 'Doe',
                            ],
                            'emailDetail' => [
                                'emailAddress' => 'john.doe@example.com',
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        $this->customerSessionMock->expects($this->once())
            ->method('getSessionId')
            ->willReturn('some_session_id');

        $this->unifiedDataLayerMock->expects($this->once())
            ->method('setCustomerName')
            ->with('John Doe');
        $this->unifiedDataLayerMock->expects($this->once())
            ->method('setCustomerEmail')
            ->with('john.doe@example.com');
        $this->unifiedDataLayerMock->expects($this->once())
            ->method('setCustomerSessionId')
            ->with('some_session_id');
    }

    public function testMapLoggedOut(): void
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method('setCustomerType')
            ->with(CustomerType::GUEST->value);
        $this->customerDataSource->map($this->unifiedDataLayerMock, $this->checkoutData);
    }

    public function testMapLoggedIn(): void
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method('setCustomerType')
            ->with(CustomerType::LOGGED_IN->value);

        $this->customerDataSource->map($this->unifiedDataLayerMock, $this->checkoutData);
    }

}
