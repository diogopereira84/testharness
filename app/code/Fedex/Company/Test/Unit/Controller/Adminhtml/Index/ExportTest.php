<?php

namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Index;

use PHPUnit\Framework\TestCase;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Controller\Adminhtml\Index\Export;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use \Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Exception;
use Fedex\Company\Helper\ExportCompanyData as ExportCompanyDataHelper;

class ExportTest extends TestCase
{
    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\App\Response\Http\FileFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fileFactoryMock;
    protected $csvProcessorMock;
    /**
     * @var (\Magento\Framework\App\Filesystem\DirectoryList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryListMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    /**
     * @var (\Fedex\Company\Helper\ExportCompanyData & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $exportCompanyDataHelperMock;
    protected $resultPage;
    protected $resultPageFactory;
    protected $companyRepository;
    protected $request;
    protected $resultRedirect;
    protected $exportMock;
    /** @var int */
    private $companyId;

	/**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyId = 48;

        $this->contextMock = $this->createMock(Context::class);
        $this->fileFactoryMock = $this->createMock(FileFactory::class);
        $this->csvProcessorMock = $this->createMock(Csv::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->loggerInterfaceMock = $this->createMock(LoggerInterface::class);
        $this->exportCompanyDataHelperMock = $this->createMock(ExportCompanyDataHelper::class);
        $resultForwardFactory = $this->createMock(ForwardFactory::class);
        $this->resultPage = $this->createMock(Page::class);

        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->resultPageFactory->expects($this->once())->method('create')->willReturn($this->resultPage);

        $this->companyRepository = $this->getMockForAbstractClass(CompanyRepositoryInterface::class);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->request->expects($this->once())->method('getParam')->willReturn($this->companyId);

        $this->resultRedirect = $this->createMock(Redirect::class);
        $resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);

        $objectManagerHelper = new ObjectManager($this);
        $this->exportMock = $objectManagerHelper->getObject(
            Export::class,
            [
                'context' => $this->contextMock,
                'resultForwardFactory' => $resultForwardFactory,
                'resultPageFactory' => $this->resultPageFactory,
                'companyRepository' => $this->companyRepository,
                '_request' => $this->request,
                'resultRedirectFactory' => $resultRedirectFactory,
                'fileFactory' => $this->fileFactoryMock,
                'csvProcessor' => $this->csvProcessorMock,
                'directoryList' => $this->directoryListMock,
                'logger' => $this->loggerInterfaceMock,
                'exportCompanyDataHelper' => $this->exportCompanyDataHelperMock,
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $company = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCompanyName']
        );

        $company->expects($this->any())->method('getCompanyName')->willReturn('test');
        $this->companyRepository->expects($this->any())->method('get')->with($this->companyId)->willReturn($company);
        $this->csvProcessorMock->expects($this->any())->method('setEnclosure')->willReturnSelf();
        $this->csvProcessorMock->expects($this->any())->method('setDelimiter')->willReturnSelf();
        $this->csvProcessorMock->expects($this->any())->method('saveData')->willReturnSelf();

        $this->assertSame($this->resultPage, $this->exportMock->execute());
    }

    public function testeExecuteWithException()
	{
        $exception = new Exception();

        $company = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCompanyName']
        );
        $company->expects($this->any())->method('getCompanyName')->willReturn('test');
        $this->companyRepository->expects($this->any())->method('get')->with($this->companyId)
        ->willThrowException($exception);

		$this->assertNotNull($this->exportMock->execute());
	}
}
