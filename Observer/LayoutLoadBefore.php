<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class LayoutLoadBefore
 * Observer to set public Cookie
 */
class LayoutLoadBefore implements ObserverInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;
    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;
    /**
     * @var ConfigRepositoryInterface
     */
    private $configRepository;

    /**
     * LayoutLoadBefore constructor.
     *
     * @param LogRepository $logRepository
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param ConfigRepositoryInterface $configRepository
     */
    public function __construct(
        LogRepository $logRepository,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        ConfigRepositoryInterface $configRepository
    ) {
        $this->logRepository = $logRepository;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->configRepository = $configRepository;
    }

    /**
     * Set public Cookie
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->configRepository->isEnabled()) {
            if ($cookieValue = $this->cookieManager->getCookie(
                ConfigRepositoryInterface::SQUEEZELY_COOKIE_NAME
            )
            ) {
                $metadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDurationOneYear()
                    ->setPath($this->sessionManager->getCookiePath())
                    ->setDomain($this->sessionManager->getCookieDomain());

                try {
                    $this->cookieManager->setPublicCookie(
                        ConfigRepositoryInterface::SQUEEZELY_COOKIE_NAME,
                        $cookieValue,
                        $metadata
                    );
                } catch (\Exception $e) {
                    $this->logRepository->addErrorLog('Exception', $e->getMessage());
                }
            }
        }
    }
}
