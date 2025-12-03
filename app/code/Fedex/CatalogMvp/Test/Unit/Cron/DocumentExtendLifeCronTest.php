<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Cron\DocumentExtendLifeCron;
use PHPUnit\Framework\TestCase;

class DocumentExtendLifeCronTest extends TestCase
{
    protected $toggleConfig;
    protected $catalogDocumentRefranceApiMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterface;
    protected $documentExtendLife;
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

         $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->DisableOriginalConstructor()
            ->setMethods(['extendDocumentLifeForProducts', 'getExtendDocumentLifeForPodEitableProduct'])
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->documentExtendLife = $objectManagerHelper->getObject(
            DocumentExtendLifeCron::class,
            [
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'catalogDocumentRefranceApiHelper' => $this->catalogDocumentRefranceApiMock
            ]
        );
    }
    /**
     * @test Execute
     */
    public function testExecute()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('extendDocumentLifeForProducts')
            ->willReturn(true);

        $result = $this->documentExtendLife->execute();
        $this->assertTrue($result);
    }
    /**
     * @test Execute with toggle off
     */
    public function testExecuteWithToggleOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->catalogDocumentRefranceApiMock->expects($this->any())
            ->method('getExtendDocumentLifeForPodEitableProduct')
            ->willReturn(true);
        $result = $this->documentExtendLife->execute();
        $this->assertTrue($result);
    }
}
