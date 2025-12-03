<?php
/**
 * @category  Fedex
 * @package   Fedex_Customer
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Model\SalesForce\Source;

use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Model\SalesForce\Source\SubscriberDataSource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriberDataSourceTest extends TestCase
{
    /**
     * @var SubscriberDataSource|MockObject
     */
    protected SubscriberDataSource $subscriberDataSourceMock;

    /**
     * @var SalesForceResponseInterface|MockObject
     */
    protected SalesForceResponseInterface $salesForceResponseMock;

    protected function setUp(): void
    {
        $this->salesForceResponseMock = $this->createMock(SalesForceResponseInterface::class);
        $objectManger = new ObjectManager($this);
        $this->subscriberDataSourceMock = $objectManger->getObject(
            SubscriberDataSource::class
        );
    }

    public function testMap()
    {
        $subscriberData = [
            'status' => 'ok',
            'subscriberResponse' => 1,
            'fxoSubscriberResponse' => 1,
            'emailSendResponse' => 'OK',
            'errorMessage' => 'Error Message',
        ];

        $this->salesForceResponseMock->expects($this->once())->method('setStatus')
            ->with($subscriberData['status'])->willReturnSelf();
        $this->salesForceResponseMock->expects($this->once())->method('setErrorMessage')
            ->with($subscriberData['errorMessage'])->willReturnSelf();
        $this->salesForceResponseMock->expects($this->once())->method('setSubscriberResponse')
            ->with((bool)$subscriberData['subscriberResponse'])->willReturnSelf();
        $this->salesForceResponseMock->expects($this->once())->method('setEmailSendResponse')
            ->with((bool)$subscriberData['fxoSubscriberResponse'])->willReturnSelf();
        $this->salesForceResponseMock->expects($this->once())->method('setFxoSubscriberResponse')
            ->with($subscriberData['emailSendResponse'])->willReturnSelf();

        $this->subscriberDataSourceMock->map(
            $this->salesForceResponseMock,
            $subscriberData
        );
    }
}
