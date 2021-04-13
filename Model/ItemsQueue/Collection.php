<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ItemsQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Squeezely\Plugin\Model\ItemsQueue\Data as ItemsQueueData;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;

/**
 * Account collection class
 */
class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            ItemsQueueData::class,
            ItemsQueueResource::class
        );
    }
}
