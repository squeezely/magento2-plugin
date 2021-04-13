<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Log;

use Squeezely\Plugin\Api\Config\System\AdvancedOptionsInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepositoryInterface;
use Squeezely\Plugin\Logger\DebugLogger;
use Squeezely\Plugin\Logger\ErrorLogger;

/**
 * Logs repository class
 */
class Repository implements LogRepositoryInterface
{

    /**
     * @var DebugLogger
     */
    private $debugLogger;
    /**
     * @var ErrorLogger
     */
    private $errorLogger;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Repository constructor.
     *
     * @param ErrorLogger $errorLogger
     * @param DebugLogger $debugLogger
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ErrorLogger $errorLogger,
        DebugLogger $debugLogger,
        ConfigRepository $configRepository
    ) {
        $this->errorLogger = $errorLogger;
        $this->debugLogger = $debugLogger;
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public function addErrorLog(string $type, $data)
    {
        $this->errorLogger->addLog($type, $data);
    }

    /**
     * @inheritDoc
     */
    public function addDebugLog(string $type, $data)
    {
        if ($this->configRepository->isDebugEnabled()) {
            $this->debugLogger->addLog($type, $data);
        }
    }
}
