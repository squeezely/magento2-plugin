<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ItemUpdate;

use Magento\Framework\App\ResourceConnection;

/**
 * Class for flushing item table
 */
class FlushItems
{

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * GetReturnStatus constructor.
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $connection = $this->resource->getConnection();
        $connection->truncateTable($this->resource->getTableName('squeezely_items_queue'));
    }
}
