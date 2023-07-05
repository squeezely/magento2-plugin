<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Message\ManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Service\Integration\Service as IntegrationService;

/**
 * Observer to update integration on configuration save
 */
class EditConfigAdmin implements ObserverInterface
{

    /**
     * Message on successfully integration
     */
    public const SUCCESS_MSG = 'Squeezely credentials are successfully verified';

    /**
     * Message on unsuccessfully integration
     */
    public const ERROR_MSG = 'Could not verify given Squeezely credentials, please try again later or contact '
    . 'support@squeezely.tech.';

    /**
     * Message on exception in integration call
     */
    public const EXCEPTION_MSG = 'Could not verify given Squeezely credentials, please try again later or contact ' .
    'support@squeezely.tech. Exception message: %1';

    /**
     * Message on exception in integration call
     */
    public const EXCEPTION_CREDENTIALS_MSG = 'Credentials are incorrect, please try again!';

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var IntegrationService
     */
    private $integrationService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * EditConfigAdmin constructor.
     *
     * @param LogRepository $logRepository
     * @param ManagerInterface $messageManager
     * @param IntegrationService $integrationService
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        LogRepository $logRepository,
        ManagerInterface $messageManager,
        IntegrationService $integrationService,
        ConfigRepository $configRepository
    ) {
        $this->logRepository = $logRepository;
        $this->messageManager = $messageManager;
        $this->integrationService = $integrationService;
        $this->configRepository = $configRepository;
    }

    /**
     * Update integration on configuration save
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $storeId = (int)$observer->getData('store');
        if (!$this->configRepository->isEnabled($storeId)) {
            return;
        }

        try {
            if ($this->integrationService->verifyAuth((int)$storeId)) {
                $msg = (string)self::SUCCESS_MSG;
                $this->messageManager->addSuccessMessage(__($msg));
            } else {
                $msg = (string)self::ERROR_MSG;
                $this->messageManager->addErrorMessage(__($msg));
            }
        } catch (AuthenticationException $exception) {
            $msg = (string)self::EXCEPTION_CREDENTIALS_MSG;
            $this->messageManager->addErrorMessage(__($msg));
        } catch (\Exception $e) {
            $this->logRepository->addErrorLog("Integration Observer", $e->getMessage());
            $msg = (string)self::EXCEPTION_MSG;
            $this->messageManager->addErrorMessage(__($msg, $e->getMessage()));
        }
    }
}
