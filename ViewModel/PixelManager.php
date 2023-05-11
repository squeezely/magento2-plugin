<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class PixelManager
 */
class PixelManager implements ArgumentInterface
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var int
     */
    private $storeId = 0;

    /**
     * PixelManager constructor.
     *
     * @param ConfigRepository $configRepository
     * @param StoreManagerInterface $storeManager
     * @param LogRepository $logRepository
     * @param Session $session
     */
    public function __construct(
        ConfigRepository $configRepository,
        StoreManagerInterface $storeManager,
        LogRepository $logRepository,
        Session $session
    ) {
        $this->configRepository = $configRepository;
        $this->storeManager = $storeManager;
        $this->logRepository = $logRepository;
        $this->session = $session;
    }

    /**
     * Check if the module is enabled
     *
     * @return boolean 0 or 1
     */
    public function isEnabled(): bool
    {
        return $this->configRepository->isFrontendEventsEnabled($this->getStoreId());
    }

    /**
     * Check if the frontend add to cart should be tracked
     *
     * @return bool
     */
    public function trackAddToCart(): bool
    {
        return $this->configRepository->isFrontendEventEnabled(
            ConfigRepository::ADD_TO_CART_EVENT
        );
    }

    /**
     * Return current store id
     *
     * @return int
     */
    private function getStoreId(): int
    {
        if (!$this->storeId) {
            try {
                $this->storeId = (int)$this->storeManager->getStore()->getId();
            } catch (NoSuchEntityException $e) {
                $this->logRepository->addDebugLog('pixel manager', $e->getMessage());
            }
        }
        return $this->storeId;
    }

    /**
     * @return string
     */
    public function getJsLink(): string
    {
        return sprintf(
            $this->configRepository->getEndpointTrackerUrl(),
            $this->getAccountId()
        );
    }

    /**
     * Get container id
     *
     * @return string
     */
    private function getAccountId(): string
    {
        return $this->configRepository->getAccountId($this->getStoreId());
    }

    /**
     * @return bool
     */
    public function isNewSession(): bool
    {
        if (!$this->session->getSessionInitilized()) {
            $this->session->setSessionInitilized(true);
            return true;
        }

        return false;
    }
}
