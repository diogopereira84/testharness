<?php

namespace Fedex\PersonalAddressBook\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Fedex\PersonalAddressBook\Controller\Index\AddressBookPopup;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\PersonalAddressBook\Helper\Parties as Data;
use Fedex\PersonalAddressBook\Block\View;
use Magento\Framework\Escaper;

class AddressBookPopupTest extends TestCase
{
    private $resultFactory;
    private $rawResult;
    private $toggleConfig;
    private $partiesHelper;
    private $view;
    private $escaper;
    private $addressBookPopup;

    protected function setUp(): void
    {
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->rawResult = $this->createMock(Raw::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->partiesHelper = $this->createMock(Data::class);
        $this->view = $this->createMock(View::class);
        $this->escaper = $this->createMock(Escaper::class);

        $this->addressBookPopup = new AddressBookPopup(
            $this->resultFactory,
            $this->rawResult,
            $this->toggleConfig,
            $this->partiesHelper,
            $this->view,
            $this->escaper
        );
    }

    /**
     * Provides a sample address book entry for testing.
     *
     * @return array
     */
    private function getSampleAddressBookData(): array
    {
        return [[
            'contactID' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'companyName' => 'Company A',
            'address' => [
                'streetLines' => ['123 Street'],
                'city' => 'City A',
                'stateOrProvinceCode' => 'State A',
                'postalCode' => '12345'
            ],
            'phoneNumber' => '1234567890',
            'phoneNumberExten' => '001'
        ]];
    }

    /**
     * Provides a large set of address book entries for pagination tests.
     *
     * @param int $count
     * @return array
     */
    private function getLargeAddressBookData(int $count = 100): array
    {
        return array_fill(0, $count, $this->getSampleAddressBookData()[0]);
    }

    /**
     * Tests generatePages returns correct number of pages for 25 records.
     */
    public function testGeneratePages()
    {
        $this->assertEquals(3, $this->addressBookPopup->generatePages(25));
    }

    /**
     * Tests generatePages with an exact multiple of the page size.
     */
    public function testGeneratePagesWithExactMultiple()
    {
        $this->assertEquals(2, $this->addressBookPopup->generatePages(20));
    }

    /**
     * Tests generatePages returns 0 when there are no records.
     */
    public function testGeneratePagesWithZeroRecords()
    {
        $this->assertEquals(0, $this->addressBookPopup->generatePages(0));
    }

    /**
     * Verifies constructor dependencies are properly initialized.
     */
    public function testConstructorInitialization()
    {
        $reflection = new \ReflectionClass($this->addressBookPopup);

        $resultFactoryProperty = $reflection->getProperty('resultFactory');
        $resultFactoryProperty->setAccessible(true);
        $this->assertInstanceOf(ResultFactory::class, $resultFactoryProperty->getValue($this->addressBookPopup));

        $rawResultProperty = $reflection->getProperty('rawResult');
        $rawResultProperty->setAccessible(true);
        $this->assertInstanceOf(Raw::class, $rawResultProperty->getValue($this->addressBookPopup));

        $toggleConfigProperty = $reflection->getProperty('toggleConfig');
        $toggleConfigProperty->setAccessible(true);
        $this->assertInstanceOf(ToggleConfig::class, $toggleConfigProperty->getValue($this->addressBookPopup));

        $partiesHelperProperty = $reflection->getProperty('partiesHelper');
        $partiesHelperProperty->setAccessible(true);
        $this->assertInstanceOf(Data::class, $partiesHelperProperty->getValue($this->addressBookPopup));

        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        $this->assertInstanceOf(View::class, $viewProperty->getValue($this->addressBookPopup));

        $escaperProperty = $reflection->getProperty('escaper');
        $escaperProperty->setAccessible(true);
        $this->assertInstanceOf(Escaper::class, $escaperProperty->getValue($this->addressBookPopup));
    }
    
    /**
     * Tests execute() method with a valid address book entry.
     */
    public function testExecuteWithAddressBookData()
    {
        $addressBookData = $this->getSampleAddressBookData();

        $this->view->method('addressBookData')->willReturn($addressBookData);
        $this->view->method('totalRecords')->willReturn(count($addressBookData));
        $this->resultFactory->method('create')->willReturn($this->rawResult);

        $this->rawResult->expects($this->once())
            ->method('setContents')
            ->with($this->stringContains('<div class="tab">'))
            ->willReturnSelf();

        $result = $this->addressBookPopup->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    /**
     * Tests execute() method when there is no address book data.
     */
    public function testExecuteWithEmptyAddressBookData()
    {
        $this->view->method('addressBookData')->willReturn([]);
        $this->view->method('totalRecords')->willReturn(0);
        $this->resultFactory->method('create')->willReturn($this->rawResult);

        $this->rawResult->expects($this->once())
            ->method('setContents')
            ->with($this->stringContains('No Record Found.'))
            ->willReturnSelf();

        $result = $this->addressBookPopup->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }

    /**
     * Tests execute() method with a large address book dataset.
     */
    public function testExecuteWithLargeAddressBookData()
    {
        $addressBookData = $this->getLargeAddressBookData();

        $this->view->method('addressBookData')->willReturn($addressBookData);
        $this->view->method('totalRecords')->willReturn(count($addressBookData));
        $this->resultFactory->method('create')->willReturn($this->rawResult);

        $this->rawResult->expects($this->once())
            ->method('setContents')
            ->with($this->stringContains('<div class="tab">'))
            ->willReturnSelf();

        $result = $this->addressBookPopup->execute();
        $this->assertInstanceOf(Raw::class, $result);
    }
}
