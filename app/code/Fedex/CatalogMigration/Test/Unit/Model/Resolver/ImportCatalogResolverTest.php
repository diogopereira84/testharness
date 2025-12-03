<?php
/**
 * @category     Fedex
 * @package      Fedex_CatalogMigration
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Brajmohan Rajput <brajmohan.rajput.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CatalogMigration\Model\Resolver;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\ResourceModel\Company\Collection as CompanyCollection;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Fedex\CatalogMigration\Model\Resolver\ImportCatalogResolver;

class ImportCatalogResolverTest extends TestCase
{
	protected $readInterfaceMock;
 protected $companyCollection;
 protected $fieldMock;
 protected $resolveInfoMock;
 protected $contextMock;
 /**
  * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
  */
 protected $objectManager;
 protected $importCatalogResolverMock;
 /**
     * @var Filesystem|MockObject
     */
	protected $fileSystemMock;

	/**
     * @var File|MockObject
     */
	protected $fileMock;

	/**
     * @var CompanyFactory|MockObject
     */
	protected $companyFactoryMock;

    /**
     * @var CatalogMigrationHelper|MockObject
     */
	protected $catalogMigrationHelperMock;

    /**
     * @var resource
     */
    protected $resource;

    public $base64CsvFile = 'U0tVKENUTEdfVVVJRF9OQlIpLE5BTUUoQ2F0YWxvZyBOYW1lKSxQcmljZSxDb21wYW55VVJMIEV4dGVuc2lvbihDb3B5IGZyb20gSW5wdXQgQ1NWKSxwcm9kdWN0SW5zdGFuY2UocHJvZHVjdCBKU09OKSxJbWFnZVByZXZpZXdVUkwsQ2F0ZWdvcnkgUGF0aChGdWxsIGFic29sdXRlIGZvbGRlciBwYXRoIGZyb20gL1N1YmZvbGRlcjEvU3ViZm9sZGVy4oCmLGlzX2N1c3RvbWl6YWJsZSAoRmxhZyBPbmx5IDAvMSksY3VzdG9taXphYmxlIGZpZWxkcyAoSlNPTiBkYXRhIG9mIGN1c3RvbSBmaWVsZHMpICxQT0QyLjAgRWRpdGFibGUgKElmIGF2YWlsYWJsZSB3ZSB3aWxsIHNhdmUgaW4gUE9EIDIuMCBvdGhlcndpc2UgYnkgZGVmYXVsdCB3aWxsIGJlIG5vbiBlZGl0YWJsZSksRExUCmQzNzFkOGYxLWM0Y2QtNDFkNy04NWJlLWEyMDM2Njc5YmIyMixGaWxlNSwxMjIsbDZzaXRlNTEsInsKCQkJIiJpZCIiOiAxNjk2NTM2NDEyNjcyLAoJCQkiInZlcnNpb24iIjogMSwKCQkJIiJuYW1lIiI6ICIiQ2F0YWxvZyBQcm9kdWN0IiIsCgkJCSIicXR5IiI6IDEsCgkJCSIicHJpY2VhYmxlIiI6IHRydWUsCgkJCSIicHJvcGVydGllcyIiOiBbCgkJCQl7CgkJCQkJIiJpZCIiOiAxNzAxNDQ4NDE0MjYwLAoJCQkJCSIibmFtZSIiOiAiIkNBVEFMT0dfUFJPRFVDVF9JRCIiLAoJCQkJCSIidmFsdWUiIjogIiJjMjIyMmFjZC1mOTZkLTQyMTQtYmQ5ZC04Y2VmYzZhOGE5ZTAiIgoJCQkJfSwKCQkJCXsKCQkJCQkiImlkIiI6IDE2OTY2MjQ2MjgwNTgsCgkJCQkJIiJuYW1lIiI6ICIiQ0FUQUxPR19QUklOVF9PUFRJT05TIiIsCgkJCQkJIiJ2YWx1ZSIiOiAiImN1dHRpbmcgOiBub25lLCBmb2xkaW5nIDogbm9uZSwgcHJpbnRTaWRlcyA6IHNpbmdsZS1zaWRlZCwgcGFwZXJUeXBlIDogV2hpdGUgKDIwIGxiKSwgcGFwZXJTaXplIDogOC41IHggMTEgKGxldHRlcikiIgoJCQkJfSwKCQkJCXsKCQkJCQkiImlkIiI6IDE2OTY2MjQ2NDM2MDksCgkJCQkJIiJuYW1lIiI6ICIiQ0FUQUxPR19GSU5JU0hJTkdfT1BUSU9OUyIiLAoJCQkJCSIidmFsdWUiIjogIiJCbGVlZCA6IG5vbmUsIGZyb250Q292ZXIgOiBibGFjayB2aW55bCwgYmFja0NvdmVyIDogY2xlYXIgY292ZXIsIHN0YXBsaW5nIDogbm9uZSwgYmluZGluZyA6IGNvaWwgYmluZGluZywgY29sbGF0ZWQgOiB1bmNvbGxhdGVkIChzdGFja3MpLCBGaW5pc2hlZCBQcm9kdWN0IEhlaWdodCA6IDMsIEZpbmlzaGVkIFByb2R1Y3QgV2lkdGggOiAyLjUiIgoJCQkJfSwKCQkJCXsKCQkJCQkiImlkIiI6IDE0NzAxNTE2MjY4NTQsCgkJCQkJIiJuYW1lIiI6ICIiU1lTVEVNX1NJIiIsCgkJCQkJIiJ2YWx1ZSIiOiAiIkNhdGFsb2cgSW5zdHIiIgoJCQkJfSwKCQkJCXsKCQkJCQkiImlkIiI6IDE0NTQ5NTAxMDk2MzYsCgkJCQkJIiJuYW1lIiI6ICIiVVNFUl9TUEVDSUFMX0lOU1RSVUNUSU9OUyIiCgkJCQl9CgkJCV0sCgkJCSIicGFnZUV4Y2VwdGlvbnMiIjogWwoJCQkJewoJCQkJCSIiaWQiIjogMTQ4Nzc5MjM3OTg0MywKCQkJCQkiInByb3BlcnRpZXMiIjogWwoJCQkJCQl7CgkJCQkJCQkiImlkIiI6IDE2OTY2MjQ2MjgwNTgsCgkJCQkJCQkiIm5hbWUiIjogIiJDQVRBTE9HX1BSSU5UX09QVElPTlMiIiwKCQkJCQkJCSIidmFsdWUiIjogIiJjdXR0aW5nIDogbm9uZSwgZm9sZGluZyA6IHRyaS1mb2xkLCBjb2xvciA6IGJsYWNrIGFuZCB3aGl0ZSwgcHJpbnRTaWRlcyA6IHNpbmdsZS1zaWRlZCwgcGFwZXJUeXBlIDogV2hpdGUgKDIwIGxiKSwgcGFwZXJTaXplIDogOC41IHggMTEgKGxldHRlcikiIgoJCQkJCQl9LAoJCQkJCQl7CgkJCQkJCQkiImlkIiI6IDE0ODc3OTI2MDc3MjEsCgkJCQkJCQkiIm5hbWUiIjogIiJFWENFUFRJT05fVFlQRSIiLAoJCQkJCQkJIiJ2YWx1ZSIiOiAiIlBSSU5USU5HX0VYQ0VQVElPTiIiCgkJCQkJCX0KCQkJCQldLAoJCQkJCSIiaGFzQ29udGVudCIiOiBmYWxzZSwKCQkJCQkiInJhbmdlcyIiOiBbCgkJCQkJCXsKCQkJCQkJCSIic3RhcnQiIjogMywKCQkJCQkJCSIiZW5kIiI6IDQKCQkJCQkJfQoJCQkJCV0KCQkJCX0sCgkJCQl7CgkJCQkJIiJpZCIiOiAxNDg3NzkyMzc5ODQzLAoJCQkJCSIicHJvcGVydGllcyIiOiBbCgkJCQkJCXsKCQkJCQkJCSIiaWQiIjogMTY5NjYyNDYyODA1OCwKCQkJCQkJCSIibmFtZSIiOiAiIkNBVEFMT0dfUFJJTlRfT1BUSU9OUyIiLAoJCQkJCQkJIiJ2YWx1ZSIiOiAiImN1dHRpbmcgOiBoYWxmLCBmb2xkaW5nIDogWi1mb2xkLCBjb2xvciA6IGNvbG9yLCBwcmludFNpZGVzIDogc2luZ2xlLXNpZGVkLCBwYXBlclR5cGUgOiBTYWxtb24sIHBhcGVyU2l6ZSA6IDExIHggMTcgKHRhYmxvaWQpIiIKCQkJCQkJfSwKCQkJCQkJewoJCQkJCQkJIiJpZCIiOiAxNDg3NzkyNjA3NzIxLAoJCQkJCQkJIiJuYW1lIiI6ICIiRVhDRVBUSU9OX1RZUEUiIiwKCQkJCQkJCSIidmFsdWUiIjogIiJQUklOVElOR19FWENFUFRJT04iIgoJCQkJCQl9CgkJCQkJXSwKCQkJCQkiImhhc0NvbnRlbnQiIjogZmFsc2UsCgkJCQkJIiJyYW5nZXMiIjogWwoJCQkJCQl7CgkJCQkJCQkiInN0YXJ0IiI6IDUsCgkJCQkJCQkiImVuZCIiOiA2CgkJCQkJCX0KCQkJCQldCgkJCQl9CgkJCV0sCgkJCSIicHJvb2ZSZXF1aXJlZCIiOiBmYWxzZSwKCQkJIiJpbnN0YW5jZUlkIiI6IDE3MDMwNTAzMTE4NjYsCgkJCSIiY29udGVudEFzc29jaWF0aW9ucyIiOiBbCgkJCQl7CgkJCQkJIiJjb250ZW50UmVmZXJlbmNlIiI6ICIiNTRiYTRhNTMtODg3NS0xMWVlLTg2MjgtNGRiODdlZmMwOThjIiIsCgkJCQkJIiJjb250ZW50VHlwZSIiOiAiImFwcGxpY2F0aW9uL3BkZiIiLAoJCQkJCSIiZmlsZVNpemVCeXRlcyIiOiAwLAoJCQkJCSIiZmlsZU5hbWUiIjogIiJCdXNpbmVzcyBDYXJkXzMuNXgyX0ltcG9zZWQucGRmIiIsCgkJCQkJIiJwcmludFJlYWR5IiI6IHRydWUsCgkJCQkJIiJjb250ZW50UmVxSWQiIjogMTQ4Mzk5OTk1Mjk3OSwKCQkJCQkiInB1cnBvc2UiIjogIiJNQUlOX0NPTlRFTlQiIiwKCQkJCQkiInNwZWNpYWxJbnN0cnVjdGlvbnMiIjogIiJmaXJzdCBwYWdlIGNvbG9yIiIsCgkJCQkJIiJwYWdlR3JvdXBzIiI6IFsKCQkJCQkJewoJCQkJCQkJIiJzdGFydCIiOiAxLAoJCQkJCQkJIiJlbmQiIjogMiwKCQkJCQkJCSIid2lkdGgiIjogMTEuMCwKCQkJCQkJCSIiaGVpZ2h0IiI6IDguNQoJCQkJCQl9CgkJCQkJXSwKCQkJCQkiInBoeXNpY2FsQ29udGVudCIiOiBmYWxzZQoJCQkJfSwKCQkJCXsKCQkJCQkiImNvbnRlbnRSZWZlcmVuY2UiIjogIiI1NGJhNGE1My04ODc1LTExZWUtODYyOC00ZGI4N2VmYzA5OGMiIiwKCQkJCQkiImNvbnRlbnRUeXBlIiI6ICIiYXBwbGljYXRpb24vcGRmIiIsCgkJCQkJIiJmaWxlU2l6ZUJ5dGVzIiI6IDAsCgkJCQkJIiJmaWxlTmFtZSIiOiAiIkJ1c2luZXNzIENhcmRfMy41eDJfSW1wb3NlZC5wZGYiIiwKCQkJCQkiInByaW50UmVhZHkiIjogdHJ1ZSwKCQkJCQkiImNvbnRlbnRSZXFJZCIiOiAxNDgzOTk5OTUyOTc5LAoJCQkJCSIicHVycG9zZSIiOiAiIk1BSU5fQ09OVEVOVCIiLAoJCQkJCSIicGFnZUdyb3VwcyIiOiBbCgkJCQkJCXsKCQkJCQkJCSIic3RhcnQiIjogMSwKCQkJCQkJCSIiZW5kIiI6IDIsCgkJCQkJCQkiIndpZHRoIiI6IDExLjAsCgkJCQkJCQkiImhlaWdodCIiOiA4LjUKCQkJCQkJfQoJCQkJCV0sCgkJCQkJIiJwaHlzaWNhbENvbnRlbnQiIjogZmFsc2UKCQkJCX0sCgkJCQl7CgkJCQkJIiJjb250ZW50UmVmZXJlbmNlIiI6ICIiNTRiYTRhNTMtODg3NS0xMWVlLTg2MjgtNGRiODdlZmMwOThjIiIsCgkJCQkJIiJjb250ZW50VHlwZSIiOiAiImFwcGxpY2F0aW9uL3BkZiIiLAoJCQkJCSIiZmlsZVNpemVCeXRlcyIiOiAwLAoJCQkJCSIiZmlsZU5hbWUiIjogIiJCdXNpbmVzcyBDYXJkXzMuNXgyX0ltcG9zZWQucGRmIiIsCgkJCQkJIiJwcmludFJlYWR5IiI6IHRydWUsCgkJCQkJIiJjb250ZW50UmVxSWQiIjogMTQ4Mzk5OTk1Mjk3OSwKCQkJCQkiInB1cnBvc2UiIjogIiJNQUlOX0NPTlRFTlQiIiwKCQkJCQkiInBhZ2VHcm91cHMiIjogWwoJCQkJCQl7CgkJCQkJCQkiInN0YXJ0IiI6IDEsCgkJCQkJCQkiImVuZCIiOiAyLAoJCQkJCQkJIiJ3aWR0aCIiOiAxMS4wLAoJCQkJCQkJIiJoZWlnaHQiIjogOC41CgkJCQkJCX0KCQkJCQldLAoJCQkJCSIicGh5c2ljYWxDb250ZW50IiI6IGZhbHNlCgkJCQl9CgkJCV0sCgkJCSIiZXh0ZXJuYWxTa3VzIiI6IFsKCQkJCXsKCQkJCQkiImNvZGUiIjogIiIwMzc3IiIsCgkJCQkJIiJ1bml0UHJpY2UiIjogMC45MTAwLAoJCQkJCSIicXR5IiI6IDEsCgkJCQkJIiJhcHBseVByb2R1Y3RRdHkiIjogdHJ1ZQoJCQkJfSwKCQkJCXsKCQkJCQkiImNvZGUiIjogIiI1NTkwIiIsCgkJCQkJIiJ1bml0UHJpY2UiIjogMy45ODAwLAoJCQkJCSIicXR5IiI6IDEsCgkJCQkJIiJhcHBseVByb2R1Y3RRdHkiIjogdHJ1ZQoJCQkJfSwKCQkJCXsKCQkJCQkiImNvZGUiIjogIiIyODIwIiIsCgkJCQkJIiJxdHkiIjogMSwKCQkJCQkiImFwcGx5UHJvZHVjdFF0eSIiOiB0cnVlCgkJCQl9LAoJCQkJewoJCQkJCSIiY29kZSIiOiAiIjAwMDEiIiwKCQkJCQkiInF0eSIiOiAzLAoJCQkJCSIiYXBwbHlQcm9kdWN0UXR5IiI6IHRydWUKCQkJCX0KCQkJXSwKCQkJIiJpc091dFNvdXJjZWQiIjogZmFsc2UsCgkJCSIiZXh0ZXJuYWxQcm9kdWN0aW9uRGV0YWlscyIiOiB7CgkJCQkiIndlaWdodCIiOiB7CgkJCQkJIiJ2YWx1ZSIiOiAwLjAxNjIwMzMzMywKCQkJCQkiInVuaXRzIiI6ICIiTEIiIgoJCQkJfQoJCQl9CgkJfQoJIiwiIGNvbmNhdCgnaHR0cHM6Ly9wcmludG9ubGluZTEudXRlLmZlZGV4LmNvbS92My44LjBfczgvUHJldmlld1NlcnZsZXQ/Z2V0SW1hZ2U9JywgY3RsZ19pdGVtX2tvaW4pIiwvTXkgRG9jdW1lbnRzL3Rlc3QsMCwsRkFMU0UsIiBbe3N0YXJ0OjEsZW5kOjEwMCxwcm9kdWN0aW9uX2hvdXJzOjJ9LCB7c3RhcnQ6MTAxLGVuZDoxMDAwLHByb2R1Y3Rpb25faG91cnM6Nn1dIg==';

    protected function setUp(): void
    {
        $this->fileSystemMock = $this->createMock(Filesystem::class);
		$this->readInterfaceMock = $this->getMockForAbstractClass(ReadInterface::class);

        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
			->setMethods(['fileOpen', 'fileWrite', 'fileClose', 'fileGetCsv', 'isExists', 'deleteFile'])
            ->getMock();

        $this->catalogMigrationHelperMock = $this->getMockBuilder(CatalogMigrationHelper::class)
		    ->setMethods(['validateSheetData'])
            ->disableOriginalConstructor()
            ->getMock();

		$this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
			->disableOriginalConstructor()
			->setMethods(['create'])
			->getMock();

        $this->companyCollection = $this->getMockBuilder(CompanyCollection::class)
		    ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToFilter', 'getFirstItem', 'getId', 'getSharedCatalogId'])
            ->getMock();

		$this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

		$this->objectManager = new ObjectManager($this);
        $this->importCatalogResolverMock = $this->objectManager->getObject(
            ImportCatalogResolver::class,
            [
                'fileSystem'             => $this->fileSystemMock,
                'file'                   => $this->fileMock,
                'companyFactory'         => $this->companyFactoryMock,
                'catalogMigrationHelper' => $this->catalogMigrationHelperMock,
            ]
        );
    }

	/**
     * Test Case for testResolve
     */
	public function testResolve()
    {
		$args = [
			'input' => [
				[
					'company_url_ext' => 'l6site51',
					'base64_encoded_file' => $this->base64CsvFile
				]
			]
		];

        // Set up mock data
        $compId = 23;
        $sharedCatId = 456;
        $extUrl = 'l6site51';
        $datas = [['col1', 'col2']];
        $fileName = 'pub/media/import/l6site51';

        $this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
        $this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getId')->willReturn($compId);
		$this->companyCollection->expects($this->any())->method('getSharedCatalogId')->willReturn($sharedCatId);

		$this->fileSystemMock->expects($this->any())->method('getDirectoryRead')
        ->with(DirectoryList::MEDIA)
        ->willReturn($this->readInterfaceMock);

        $this->readInterfaceMock->expects($this->any())->method('getAbsolutePath')->willReturn('pub/media/');

        $this->fileMock->expects($this->exactly(2))
            ->method('fileOpen')
            ->withConsecutive(
                [$fileName, 'wb'],
                [$fileName, 'r']
            )->willReturnOnConsecutiveCalls(['resource', 'resource']);

        $this->fileMock->expects($this->any())->method('fileWrite')->willReturnSelf();
        $this->fileMock->expects($this->any())->method('fileClose')->willReturn('content');

        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetCsv')
            ->withConsecutive(
                [null, 100000],
                [null, 100000]
            )->willReturnOnConsecutiveCalls(['col1', 'col2'], false);

        $this->fileMock->expects($this->any())->method('isExists')->with($fileName)->willReturn(true);
        $this->fileMock->expects($this->any())->method('deleteFile')->with($fileName)->willReturn(true);

        $this->catalogMigrationHelperMock->expects($this->once())
            ->method('validateSheetData')
            ->with($datas, $compId, $sharedCatId, $extUrl)
            ->willReturn(['status' => true, 'message' => 'Validation passed']);

        $this->assertNotNull(
            $this->importCatalogResolverMock->resolve(
                $this->fieldMock,
                $this->contextMock,
                $this->resolveInfoMock,
                null,
                $args
            )
        );
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
	public function testResolveWithoutInput()
    {
		$args = [
			'input' => []
		];

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage("You must specify your input.");
        $this->importCatalogResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
	public function testResolveWithoutCompanyUrlExt()
    {
		$args = [
			'input' => [
				[
					'company_url_ext' => '',
					'base64_encoded_file' => $this->base64CsvFile
				]
			]
		];

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You must specify your "company url extension".');
        $this->importCatalogResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
	public function testResolveWithoutCsvFile()
    {
		$args = [
			'input' => [
				[
					'company_url_ext' => 'l6site51',
					'base64_encoded_file' => ''
				]
			]
		];

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You must specify your "file base64 encode".');
        $this->importCatalogResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
	public function testResolveWithException()
    {
        $errorMsg = 'Some error message';
		$args = [
			'input' => [
				[
					'company_url_ext' => 'l6site51',
					'base64_encoded_file' => $this->base64CsvFile
				]
			]
		];

        $this->companyFactoryMock->expects($this->any())->method('create')
        ->willThrowException(new \Exception($errorMsg));

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage($errorMsg);

        $this->importCatalogResolverMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            $args
        );
    }
}
