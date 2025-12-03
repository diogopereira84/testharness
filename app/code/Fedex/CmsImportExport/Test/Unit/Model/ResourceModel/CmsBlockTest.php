<?php

namespace Fedex\CmsImportExport\Test\Unit\Model\ResourceModel;

use Fedex\CmsImportExport\Model\ResourceModel\CmsBlock as cmsBlock;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Test class for Fedex\CmsImportExport\Cron\SendEmail
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CmsBlockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var object
     */
    protected $CmsImportExportFactory;
    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->CmsImportExportFactory = $this->objectManagerHelper->getObject(cmsBlock::class);
    }

    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
