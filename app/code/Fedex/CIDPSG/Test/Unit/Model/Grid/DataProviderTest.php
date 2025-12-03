<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Model\Grid;

use Fedex\CIDPSG\Model\Customer;
use Fedex\CIDPSG\Model\Grid\DataProvider;
use Fedex\CIDPSG\Model\ResourceModel\Customer\Collection;
use Fedex\CIDPSG\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test class for DataProvider
 */
class DataProviderTest extends TestCase
{
    /**
     * @var DataProvider $dataProvider;
     */
    protected $dataProvider;

    /**
     * @var Collection $collection;
     */
    protected $collection;

    /**
     * @var CollectionFactory $collectionFactory;
     */
    protected $collectionFactory;

    /**
     * @var RequestInterface $request;
     */
    protected $request;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->collection = $this->getMockBuilder(Collection::class)
            ->setMethods(['addFieldToFilter', 'getSelect', 'joinLeft', 'getTable', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMock();

        $this->dataProvider = new DataProvider(
            'test_data_provider',
            'entity_id',
            'entity_id',
            $this->collection,
            $this->collectionFactory,
            $this->request
        );
    }

    /**
     * Test method for getData
     *
     * @return void
     */
    public function testGetData()
    {
        $entityId = 123;
        $testData = [
            'entity_id' => '22',
            'client_id' => 'default',
            'company_participation_id' => '22',
            'company_name' => 'test',
            'participation_agreement' => 'test',
            'support_account_type' => '2',
            'field_group' => 1,
            'company_logo' => json_encode([
                0 => [
                    'name' => 'Picture2_3.png',
                    'full_path' => 'Picture2.png',
                    'type' => 'image/png',
                    'tmp_name' => '/tmp/phpEMbFmt',
                    'error' => 0,
                    'size' => 113158,
                    'path' => '/var/www/html/staging3.office.fedex.com/pub/media/CIDPSG/temp',
                    'file' => 'Picture2_3.png',
                    'url' => '/media/CIDPSG/temp/Picture2_3.png',
                    'previewType' => 'image',
                    'id' => 'UGljdHVyZTJfMy5wbmc',
                ]
            ])
        ];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('entity_id')
            ->willReturn($entityId);

        $collectionItems = [$this->getMockModel($testData)];

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->collection);

        $this->collection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->collection->expects($this->any())
            ->method('getTable')
            ->willReturn('psg_customer_fields');

        $this->collection->expects($this->any())
            ->method('joinLeft')
            ->willReturnSelf();

        $this->collection->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->collection->expects($this->once())
            ->method('getItems')
            ->willReturn($collectionItems);

        $result = $this->dataProvider->getData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form', $result);
        $this->assertIsArray($result['form']);
        $this->assertNotEquals($entityId, $result);
    }

    /**
     * Test getData with default fields
     *
     * @return void
     */
    public function testGetDataWithDefaultFields()
    {
        $entityId = 0;
        $testData = [
            'entity_id' => '22',
            'client_id' => 'default',
            'company_participation_id' => '22',
            'company_name' => 'test',
            'participation_agreement' => 'test',
            'support_account_type' => '2',
            'field_group' => 0,
            'company_logo' => json_encode([
                0 => [
                    'name' => 'Picture2_3.png',
                    'full_path' => 'Picture2.png',
                    'type' => 'image/png',
                    'tmp_name' => '/tmp/phpEMbFmt',
                    'error' => 0,
                    'size' => 113158,
                    'path' => '/var/www/html/staging3.office.fedex.com/pub/media/CIDPSG/temp',
                    'file' => 'Picture2_3.png',
                    'url' => '/media/CIDPSG/temp/Picture2_3.png',
                    'previewType' => 'image',
                    'id' => 'UGljdHVyZTJfMy5wbmc',
                ]
            ])
        ];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('entity_id')
            ->willReturn($entityId);

        $collectionItems = [$this->getMockModel($testData)];

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->collection);

        $this->collection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->collection->expects($this->any())
            ->method('getTable')
            ->willReturn('psg_customer_fields');

        $this->collection->expects($this->any())
            ->method('joinLeft')
            ->willReturnSelf();

        $this->collection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->collection->expects($this->once())
            ->method('getItems')
            ->willReturn($collectionItems);

        $result = $this->dataProvider->getData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form', $result);
        $this->assertIsArray($result['form']);
        $this->assertNotEquals($entityId, $result);
    }

    /**
     * To get mock collection
     *
     * @return void
     */
    protected function getMockModel($data)
    {
        $model = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model->expects($this->any())
            ->method('getData')
            ->willReturnCallback(function ($field) use ($data) {
                return $data[$field] ?? null;
            });

        return $model;
    }

    /**
     * Test GetAutoGenerateClientId method
     *
     * @return void
     */
    public function testGetAutoGenerateClientId()
    {
        $clientId = $this->dataProvider->getAutoGenerateClientId(10);

        $this->assertEquals(10, strlen($clientId));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{4}-[0-9a-f]{2}-[0-9a-f]{2}$/',
            $clientId
        );
    }
}
