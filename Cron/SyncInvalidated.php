<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Cron;

use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
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
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @param SyncAll $syncAll
     * @param LogRepository $logRepository
     */
    public function __construct(
        SyncAll $syncAll,
        LogRepository $logRepository
    ) {
        $this->syncAll = $syncAll;
        $this->logRepository = $logRepository;
    }

    /**
     * Send Invalidated products to API
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->syncAll->execute();
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('SyncInvalidated Cron', $exception->getMessage());
        }
    }
}
