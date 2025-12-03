<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FXOCMConfigurator\Test\Unit\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\FXOCMConfigurator\Helper\Data as FxocmHelper;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory as OrderRetationPeriodCollectionFactory;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriodFactory;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\Collection;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriod;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\FXOCMConfigurator\Cron\OrderDocumentLifeExtendCron;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;

class OrderDocumentLifeExtendCronTest extends TestCase
{
   
    protected $fxocmHelper;
    protected $orderRetationPeriodCollectionFactory;
    protected $orderRetationPeriodCollectionMock;
    protected $orderRetationPeriodFactory;
    protected $orderRetationPeriod;
    protected $catalogDocumentRefranceApiMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $orderdocumentlifeextendCron;
    protected function setUp(): void
    {
        $this->fxocmHelper = $this->getMockBuilder(FxocmHelper::class)
            ->DisableOriginalConstructor()->setMethods(['getNewDocumentsApiImagePreviewToggle', 'getLegacyDocsNoReorderCronToggle'])
            ->getMock();
        $this->orderRetationPeriodCollectionFactory = $this->getMockBuilder(OrderRetationPeriodCollectionFactory::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create','addFieldToFilter','getSize','getIterator','getWorkspaceData','save'])
            ->getMock();
        $this->orderRetationPeriodCollectionMock = $this->getMockBuilder(Collection::class)
            ->DisableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getSize', 'getIterator', 'getWorkspaceData', 'getUserworkspaceId','save'])
            ->getMock();
       $this->orderRetationPeriodFactory = $this->getMockBuilder(OrderRetationPeriodFactory::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create','load','save','delete','setExtendedDate','setExtendedFlag'])
            ->getMock();
        $this->orderRetationPeriod = $this->getMockBuilder(OrderRetationPeriod::class)
            ->DisableOriginalConstructor()
            ->setMethods(['create','getWorkspaceData', 'load', 'getUserworkspaceId','save','setWorkspaceData','setOldUploadDate','delete','getDocumentId'])
            ->getMock();
         $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->DisableOriginalConstructor()
            ->setMethods(['documentLifeExtendApiCallWithDocumentId'])
            ->getMock();
         $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
                 
        $objectManagerHelper = new ObjectManager($this);

        $this->orderdocumentlifeextendCron = $objectManagerHelper->getObject(
            OrderDocumentLifeExtendCron::class,
            [
                'fxocmHelper' => $this->fxocmHelper,
                'orderRetationPeriodCollectionFactory' => $this->orderRetationPeriodCollectionFactory,
                'orderRetationPeriod' => $this->orderRetationPeriodFactory,
                'catalogDocumentRefranceApi' => $this->catalogDocumentRefranceApiMock,
                'logger' => $this->loggerMock
            ]
        );
    }


      public function testExecute()
    {    
      $response = [
            'output' => [
                'document' => [
                    'expirationTime' => date("Y-m-d H:i:s")
                ]
            ]
        ];

        $this->fxocmHelper->expects($this->any())->method('getNewDocumentsApiImagePreviewToggle')->willReturn(1);
        $this->fxocmHelper->expects($this->any())->method('getLegacyDocsNoReorderCronToggle')->willReturn(1);
        $this->orderRetationPeriodCollectionFactory->expects($this->any())->method('create')->willReturn($this->orderRetationPeriodCollectionMock);
        $this->orderRetationPeriodCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderRetationPeriodCollectionMock->expects($this->any())->method('getSize')->willReturn(10);
        $this->orderRetationPeriodCollectionMock->expects($this->any())
        ->method('getIterator') ->willReturn(new \ArrayIterator([$this->orderRetationPeriod]));
        $this->orderRetationPeriodFactory->expects($this->any())->method('create')->willReturn($this->orderRetationPeriod);
        $this->orderRetationPeriodFactory->expects($this->any())->method('setExtendedDate')->willReturnSelf();
        $this->orderRetationPeriodFactory->expects($this->any())->method('setExtendedFlag')->willReturnSelf();
        $this->catalogDocumentRefranceApiMock->expects($this->any())->method('documentLifeExtendApiCallWithDocumentId')->willReturn($response);
        $result = $this->orderdocumentlifeextendCron->execute();
        $this->assertEquals(null, $result);
    }

      public function testExecuteWithException()
    {  

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->fxocmHelper->expects($this->any())->method('getNewDocumentsApiImagePreviewToggle')->willReturn(1);
        $this->fxocmHelper->expects($this->any())->method('getLegacyDocsNoReorderCronToggle')->willReturn(1);
        $this->orderRetationPeriodCollectionFactory->expects($this->any())->method('create')->willThrowException($exception);
        $this->assertEquals(null, $this->orderdocumentlifeextendCron->execute());
    }

}
