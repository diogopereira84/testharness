<?php

namespace Fedex\SelfReg\Test\Unit\Plugin\UiComponent;

use Fedex\SelfReg\ViewModel\CompanyUser;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Fedex\SelfReg\Plugin\UiComponent\DataProviderPlugin;

class DataProviderPluginTest extends TestCase
{
    private $requestMock;
    private $companyUser;
    private $dataProviderPlugin;
    private $dataProviderMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->companyUser = $this->createMock(CompanyUser::class);
        $this->dataProviderMock = $this->getMockBuilder(BaseDataProvider::class)
            ->setMethods(['getSelect', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProviderPlugin = new DataProviderPlugin(
            $this->requestMock,
            $this->companyUser
        );
    }

    public function testSortingByGroupName()
    {
        $this->requestMock->method('getParams')->willReturn([
            'sorting' => ['field' => 'group_name', 'direction' => 'asc'],
        ]);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->dataProviderMock->method('getSelect')->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('order')
            ->with('group_name ASC');

        $this->dataProviderPlugin->afterGetSearchResult($this->dataProviderMock, $this->dataProviderMock);
    }

    public function testDefaultSorting()
    {
        $this->requestMock->method('getParams')->willReturn([]);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->dataProviderMock->method('getSelect')->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('order')
            ->with('id ASC');

        $this->dataProviderPlugin->afterGetSearchResult($this->dataProviderMock, $this->dataProviderMock);
    }

    public function testSearchFunctionality()
    {
        $searchTerm = 'testGroup';
        $this->requestMock->method('getParams')->willReturn(['search' => $searchTerm]);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->dataProviderMock->method('getSelect')->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('where')
            ->with("main_table.group_name LIKE ?", "%{$searchTerm}%");

        $this->dataProviderPlugin->afterGetSearchResult($this->dataProviderMock, $this->dataProviderMock);
    }
}
