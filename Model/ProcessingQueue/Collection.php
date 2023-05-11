<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\ProcessingQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Squeezely\Plugin\Model\ProcessingQueue\Data as ProcessingQueueData;
use Squeezely\Plugin\Model\ProcessingQueue\ResourceModel as ProcessingQueueResource;

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
            ProcessingQueueData::class,
            ProcessingQueueResource::class
        );
    }
}
