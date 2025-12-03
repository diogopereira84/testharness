<?php

namespace Fedex\Company\Test\Unit\Model\Company;

use Fedex\Company\Model\Company\AuthDataProvider;
use Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection;
use Fedex\Company\Model\ResourceModel\AuthDynamicRows\CollectionFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AuthDataProviderTest extends TestCase
{

    protected $collectionMock;
    protected $collectionFactoryMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $modelAuthDataProvider;
    /**
     * @var
     */
    protected $CompanyInterface;

    /**
     * @var
     */
    protected $AuthDataProvider;

    /**
     * @var
     */

    protected $collectionFactory;

    /**
     * @var
     */

    protected $objectManagerInstance;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {

        $this->CompanyInterface = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAcceptanceOption'])
            ->getMockForAbstractClass();

        $this->collectionMock =
        $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock = $this->createMock(
            CollectionFactory::class
        );//B-1326233

        $this->objectManager = new ObjectManager($this);
        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();
        $this->modelAuthDataProvider = $this->objectManager->getObject(
            AuthDataProvider::class,
            [
                'name' => 'contact',
                'primaryFieldName' => 'contact',
                'requestFieldName' => 'UserEmail',
                'collectionFactory' => $this->collectionFactoryMock,
                'meta' => [],
                'data' => [],
            ]
        );
    }
    /**
     * Test for getAuthenticationData method.
     *
     * @return void
     */
    public function testGetAuthenticationData()
    {
        $acceptanceOption = 'extrinsic';

        $this->CompanyInterface->expects($this->any())
            ->method('getAcceptanceOption')->willReturn($acceptanceOption);

        $response = ['acceptance_option' => 'extrinsic'];
        $this->assertEquals($response, $this->modelAuthDataProvider->getAuthenticationData($this->CompanyInterface));
    }

    /**
     * @return array
     */
    public function getCompanyDataProvider()
    {
        return [
            [
                'acceptance_option' => 'extrinsic', // acceptance_options are extrinsic,contact,both
                'acceptance_option', //result acceptance_option

            ],
        ];
    }

    /**
     * Test for getData method.
     *
     * @return array
     */
    public function testGetData()
    {

        $collectionItem = $this->objectManagerInstance->create('Magento\Framework\Data\Collection');
        $item = ["key" => "value"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($item);
        $collectionItem->addItem($varienObject);

        $this->collectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionItem);

        $getDataVal = $this->modelAuthDataProvider->getData();
        $excpeted = [];
        $excpeted['stores']['dynamic_rows_container'][] = ["key" => "value"];
        $this->assertEquals($excpeted, $getDataVal);
        
        //validate return value with loadedData
        $getLoadedDataVal = $this->modelAuthDataProvider->getData();
        $this->assertEquals($excpeted, $getLoadedDataVal);
    }
}
