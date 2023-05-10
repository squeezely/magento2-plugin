<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ProcessingQueue\Processors;

use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;

/**
 * Default Processing Service class
 */
class DefaultProcessor
{
    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @param LogRepository $logRepository
     * @param RequestRepository $requestRepository
     */
    public function __construct(
        LogRepository $logRepository,
        RequestRepository $requestRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        try {
            $this->requestRepository->sendToPlatform($data);
            return true;
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog($data['event'], $exception->getMessage());
            return false;
        }
    }
}
