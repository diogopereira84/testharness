<?php
namespace Fedex\CmsImportExport\Model\ResourceModel\CmsBuilder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fedex\CmsImportExport\Model\CmsBuilder as Model;
use Fedex\CmsImportExport\Model\ResourceModel\CmsBuilder as ResourceModel;

/**
 * @codeCoverageIgnore
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define Resource Model and Model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
