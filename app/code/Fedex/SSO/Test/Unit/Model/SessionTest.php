<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\ValidatorInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\State;
use Magento\Framework\Session\Generic;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SSO\Model\Session;
use PHPUnit\Framework\TestCase;

/**
 * Test class for StorageTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SessionTest extends TestCase
{
    protected $httpMock;
    protected $sidResolverMock;
    protected $configMock;
    protected $saveHandlerMock;
    protected $validatorMock;
    protected $storageMock;
    protected $cookieManagerMock;
    protected $cookieMetadataMock;
    protected $contextMock;
    protected $stateMock;
    protected $genericMock;
    protected $managerMock;
    protected $responseHttpMock;
    protected $session;
    /**
     * Test setUp
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sidResolverMock = $this->getMockBuilder(SidResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->saveHandlerMock = $this->getMockBuilder(SaveHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorMock = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storageMock = $this->getMockBuilder(StorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieMetadataMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->genericMock = $this->getMockBuilder(Generic::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseHttpMock = $this->getMockBuilder(ResponseHttp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->session = $objectManager->getObject(
            Session::class,
            [
                'request' => $this->httpMock,
                'sidResolver' => $this->sidResolverMock,
                'sessionConfig' => $this->configMock,
                'saveHandler' => $this->saveHandlerMock,
                'validator' => $this->validatorMock,
                'storage' => $this->storageMock,
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataMock,
                'appState' => $this->stateMock,
                '_eventManager' => $this->managerMock,
                'response' => $this->responseHttpMock,
                '_session' => $this->genericMock,
            ]
        );
    }
    /**
     * Function test for Constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $sessionObject = new Session(
            $this->httpMock,
            $this->sidResolverMock,
            $this->configMock,
            $this->saveHandlerMock,
            $this->validatorMock,
            $this->storageMock,
            $this->cookieManagerMock,
            $this->cookieMetadataMock,
            $this->contextMock,
            $this->stateMock,
            $this->genericMock,
            $this->managerMock,
            $this->responseHttpMock,
        );
        $this->assertEquals($this->session, $sessionObject);
    }
}
