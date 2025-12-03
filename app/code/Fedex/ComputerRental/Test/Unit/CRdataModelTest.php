<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\ComputerRental\Test\Unit\Model;

use Fedex\ComputerRental\Model\CRdataModel;
use Magento\Framework\Session\SessionManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Helper\Data as deliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CRdataModelTest extends TestCase
{
    protected $toggleConfigMock;
    /**
     * @var (\Fedex\Delivery\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $deliveryHelperMock;
    /**
     * @var CRdataModel
     */
    protected $model;

    /**
     * @var MockObject|SessionManagerInterface
     */
    protected $sessionMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->addMethods(['getData','setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->deliveryHelperMock = $this->createMock(deliveryHelper::class);
        $this->model = new CRdataModel(
            $this->sessionMock,
            $this->toggleConfigMock,
            $this->deliveryHelperMock
        );
    }
    public function testSaveStoreCodeInSession()
    {
        $this->sessionMock->expects($this->once())
            ->method('setData')
            ->with('storeCode', '0798');

        $this->model->saveStoreCodeInSession('0798');
    }

    public function testGetStoreCodeInSession()
    {
        $this->sessionMock->expects($this->once())
            ->method('getData')
            ->with('storeCode')
            ->willReturn('0798');

        $this->assertEquals('0798', $this->model->getStoreCodeFromSession());
    }

    public function testGetLocationCode()
    {
        $this->sessionMock->expects($this->once())
            ->method('getData')
            ->with('locationCode')
            ->willReturn('DNEK');

        $this->assertEquals('DNEK', $this->model->getLocationCode());
    }

    public function testSaveLocationCode()
    {
        $this->sessionMock->expects($this->once())
            ->method('setData')
            ->with('locationCode', 'DNEK');

        $this->model->saveLocationCode('DNEK');
    }
}
