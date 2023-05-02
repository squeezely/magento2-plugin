<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Cron;

use Squeezely\Plugin\Service\ItemUpdate\SyncAll;

/**
 * Cron class to sync products
 */
class SyncInvalidated
{
    /**
     * @var SyncAll
     */
    private $syncAll;

    /**
     * SyncInvalidated constructor.
     * @param SyncAll $syncAll
     */
    public function __construct(
        SyncAll $syncAll
    ) {
        $this->syncAll = $syncAll;
    }

    /**
     * Send Invalidated products to API
     *
     * @return void
     */
    public function execute()
    {
        $this->syncAll->execute();
    }
}
