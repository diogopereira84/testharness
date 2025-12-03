<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Helper;

use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\GenerateCsvHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for GenerateCsvHelper
 */
class GenerateCsvHelperTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $generateCsvHelperMock;
    public const EXCEL_DIR_PATH = 'cidpsg';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Write
     */
    protected $directoryWriteMock;

    /**
     * @var AdminConfigHelper
     */
    protected $adminConfigHelperMock;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath'])
            ->getMock();

        $this->filesystem = $this->createMock(Filesystem::class);

        $this->directoryWriteMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDirectory', 'lock', 'create', 'openFile', 'writeCsv'])
            ->getMock();

        $this->filesystem->expects($this->any())->method('getDirectoryWrite')->willReturn($this->directoryWriteMock);

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllStates'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);

        $this->generateCsvHelperMock = $this->objectManagerHelper->getObject(
            GenerateCsvHelper::class,
            [
                'context' => $this->context,
                'directoryList' => $this->directoryList,
                'filesystem' => $this->filesystem,
                'directory' => $this->directoryWriteMock,
                'adminConfigHelper' => $this->adminConfigHelperMock,
            ]
        );

        $this->generateCsvHelperMock->formData = [
            'company_name' => 'Refl Corps.',
            'office_account_no' => 98765432154,
            'account_user_name' => 'John due Soga',
            'country' => 'US',
            'street_address' => 'CA 24 Plano',
            'address_line_two' => 'street add',
            'city' => 'Plano',
            'state' => 'AZ',
            'zipcode' => 23454,
            'suite' => 'suite address',
            'email' => 'johndue@example.com',
            'phone' => '9876543215',
            'account_no_radio' => 'YES'
        ];

        $this->generateCsvHelperMock->usStates = [
            ['label' => 'AL', 'title' => 'Alabama'],
            ['label' => 'AK', 'title' => 'Alaska'],
            ['label' => 'AZ', 'title' => 'Arizona'],
            ['label' => 'AR', 'title' => 'Arkansas'],
            ['label' => 'CA', 'title' => 'California'],
        ];
    }

    /**
     * Test method for generateExcel
     *
     * @return void
     */
    public function testGenerateExcel()
    {
        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');

        $headerDataArray = ['Account Name', 'FXK Account Number'];
        $rowDataArray = ['test', 'data'];
        $fileName = 'AuthorizedUserUpdate_Robert_Due_22222222222_20230731_224125.csv';

        $this->assertNotNull(
            $this->generateCsvHelperMock->
                generateExcel($headerDataArray, $rowDataArray, $fileName, self::EXCEL_DIR_PATH)
        );
    }

    /**
     * Test method for generateExcel for the dir not exists
     *
     * @return void
     */
    public function testGenerateExcelWithFalse()
    {
        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(false);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');

        $headerDataArray = ['Account Name', 'FXK Account Number'];
        $rowDataArray = ['Robert', '12345'];

        $fileName = 'AuthorizedUserUpdate_Robert_due_data_20230801_002220.csv';

        $this->assertNotNull(
            $this->generateCsvHelperMock->
                generateExcel($headerDataArray, $rowDataArray, $fileName, self::EXCEL_DIR_PATH)
        );
    }

    /**
     * Test method for generateExcelForAuthrizedUser
     *
     * @return void
     */
    public function testGenerateExcelForAuthrizedUser()
    {
        $this->adminConfigHelperMock->expects($this->any())->method('getAllStates')
            ->willReturn($this->generateCsvHelperMock->usStates);
        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(false);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();

        $this->assertNotNull(
            $this->generateCsvHelperMock->generateExcelForAuthrizedUser($this->generateCsvHelperMock->formData)
        );
    }
}
