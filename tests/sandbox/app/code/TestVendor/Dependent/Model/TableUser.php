<?php

declare(strict_types=1);

namespace TestVendor\Dependent\Model;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TableUser extends AbstractDb
{
    protected function _construct()
    {
        // DbDDL scanner looks for getTable / getTableName
        $table = $this->getTable('testvendor_base_entity');
        // Also direct usage for good measure
        $this->_init($table, 'entity_id');
    }
}
