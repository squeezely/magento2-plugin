<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ItemsQueue;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Items Queue resource class
 */
class ResourceModel extends AbstractDb
{

    /**
     * Table name
     */
    const ENTITY_TABLE = 'squeezely_items_queue';

    /**
     * Primary field
     */
    const PRIMARY = 'entity_id';

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(self::ENTITY_TABLE, self::PRIMARY);
    }

    /**
     * Check is entity exists
     *
     * @param  int $entityId
     * @return bool
     */
    public function isExists($entityId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::ENTITY_TABLE), self::PRIMARY)
            ->where('entity_id = :entity_id');
        $bind = [':entity_id' => $entityId];
        return (bool)$connection->fetchOne($select, $bind);
    }
}
