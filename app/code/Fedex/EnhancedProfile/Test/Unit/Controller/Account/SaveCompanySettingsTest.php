<?php
namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

use PHPUnit\Framework\TestCase;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Fedex\EnhancedProfile\Controller\Account\SaveCompanySettings;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection;

class SaveCompanySettingsTest extends TestCase
{
    protected $companyMock;
    protected $resultJsonMock;
    protected $additionalDataCollectionMock;
    /** @var SaveCompanySettings */
    private $controller;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $requestMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $companyRepositoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $additionalDataFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $loggerMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $resultJsonFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $jsonMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $uploaderFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $adapterFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $filesystemMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->companyRepositoryMock = $this->createMock(CompanyRepositoryInterface::class);
        $this->additionalDataFactoryMock = $this->createMock(AdditionalDataFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->uploaderFactoryMock = $this->createMock(UploaderFactory::class);
        $this->adapterFactoryMock = $this->createMock(AdapterFactory::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
                                ->setMethods(['setData','save'])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();
        $this->resultJsonMock = $this->getMockBuilder(ResultInterface::class)
                                ->setMethods(['setData'])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->additionalDataCollectionMock = $this->getMockBuilder(Collection::class)
                                            ->setMethods(
                                                [
                                                    'getCollection',
                                                    'addFieldToSelect',
                                                    'addFieldToFilter',
                                                    'getSize',
                                                    'getIterator'
                                                ]
                                            )
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->controller = new SaveCompanySettings(
            $this->requestMock,
            $this->companyRepositoryMock,
            $this->additionalDataFactoryMock,
            $this->loggerMock,
            $this->resultJsonFactoryMock,
            $this->jsonMock,
            $this->uploaderFactoryMock,
            $this->adapterFactoryMock,
            $this->filesystemMock
        );
    }

    public function testExecuteSuccess()
    {
        $companyId = 1;
        $requestData = [
            'company_id' => $companyId,
            'is_success_email_enable' => true,
            'is_delivery' => true,
            'is_pickup' => true,
            'hc_toggle' => true,
            'allowed_delivery_options' => [],
            'allow_own_document' => true,
            'allow_shared_catalog' => true,
            'box_enabled' => true,
            'dropbox_enabled' => true,
            'google_enabled' => true,
            'microsoft_enabled' => true,
            'is_reorder_enabled' => true,
            'is_banner_enable' => true,
            'banner_title' => 'Banner Title',
            'iconography' => 'Iconography',
            'description' => 'Description',
            'cta_text' => 'CTA Text',
            'cta_link' => 'CTA Link',
            'link_open_in_new_tab' => true
        ];

        $expectedOutput = ['error' => false, 'msg' => 'Site Settings have been updated'];

        $this->requestMock->method('getParams')->willReturn($requestData);

        $this->companyRepositoryMock->method('get')->with($companyId)->willReturn($this->companyMock);

        $this->jsonMock->method('serialize')->willReturn(json_encode([]));
        
        
        $this->resultJsonFactoryMock->method('create')->willReturn($this->resultJsonMock);
        $this->resultJsonMock->expects($this->any())->method('setData')->willReturn($expectedOutput);

        $this->companyMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->companyMock->expects($this->any())->method('save')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('save');
        
        $this->additionalDataFactoryMock->method('create')->willReturn($this->additionalDataCollectionMock);
        $this->additionalDataCollectionMock->method('getSize')->willReturn(1);
        $this->additionalDataCollectionMock->method('getCollection')->willReturnSelf();
        $this->additionalDataCollectionMock->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollectionMock->method('addFieldToFilter')->willReturnSelf();

        $additionalDataItemMock = $this->createMock(\Fedex\Company\Model\AdditionalData::class);
        $this->additionalDataCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$additionalDataItemMock]));

        $additionalDataItemMock->expects($this->once())->method('setIsReorderEnabled')->with(1);
        $additionalDataItemMock->expects($this->once())->method('setIsBannerEnable')->with(1);
        $additionalDataItemMock->expects($this->once())->method('setBannerTitle')->with('Banner Title');
        $additionalDataItemMock->expects($this->once())->method('setIconography')->with('Iconography');
        $additionalDataItemMock->expects($this->once())->method('setDescription')->with('Description');
        $additionalDataItemMock->expects($this->once())->method('setCtaText')->with('CTA Text');
        $additionalDataItemMock->expects($this->once())->method('setCtaLink')->with('CTA Link');
        $additionalDataItemMock->expects($this->once())->method('setLinkOpenInNewTab')->with(1);
        $additionalDataItemMock->expects($this->once())->method('save');

        $result = $this->controller->execute();

        $this->assertEquals($expectedOutput, $result);
    }

    public function testExecuteException()
    {
        $requestData = [
            'company_id' => 1,
            'is_success_email_enable' => true,
            'is_delivery' => true,
            'is_pickup' => true,
            'hc_toggle' => true,
            'allowed_delivery_options' => [],
            'allow_own_document' => true,
            'allow_shared_catalog' => true,
            'box_enabled' => true,
            'dropbox_enabled' => true,
            'google_enabled' => true,
            'microsoft_enabled' => true,
            'is_reorder_enabled' => true,
            'is_banner_enable' => true,
            'banner_title' => 'Banner Title',
            'iconography' => 'Iconography',
            'description' => 'Description',
            'cta_text' => 'CTA Text',
            'cta_link' => 'CTA Link',
            'link_open_in_new_tab' => true
        ];

        $this->requestMock->method('getParams')->willReturn($requestData);

        $this->companyRepositoryMock->method('get')->willThrowException(new LocalizedException(__('Error message')));

        $this->resultJsonFactoryMock->method('create')->willReturn($this->resultJsonMock);

        $this->resultJsonMock->expects($this->any())->method('setData')->willReturn(['error' => true, 'msg' => 'Error message']);

        $result = $this->controller->execute();

        $this->assertEquals(['error' => true, 'msg' => 'Error message'], $result);
    }
}
