<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Cron;

use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Service\ProcessingQueue\Process;

/**
 * Cron class to process queue
 */
class ProcessQueue
{
    /**
     * @var Process
     */
    private $process;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @param Process $process
     * @param LogRepository $logRepository
     */
    public function __construct(
        Process $process,
        LogRepository $logRepository
    ) {
        $this->process = $process;
        $this->logRepository = $logRepository;
    }

    /**
     * Process backend events queue to API
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->process->cleanupQueue();
            $this->process->execute();
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('ProcessQueue Cron', $exception->getMessage());
        }
    }
}
