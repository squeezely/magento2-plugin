<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Squeezely\Plugin\Api\Config\System\AdvancedOptionsInterface as AdvancedOptionsRepository;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Magento\Framework\UrlInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * Class PixelManager
 */
class PixelManager implements ArgumentInterface
{
    /**
     * Url path for ajax call
     */
    const URL_PATH = 'sqzl/events/get';

    /**
     * @var FrontendEventsRepository
     */
    private $configRepository;
    /**
     * @var AdvancedOptionsRepository
     */
    private $advancedOptionsRepository;
    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * PixelManager constructor.
     *
     * @param FrontendEventsRepository $configRepository
     * @param AdvancedOptionsRepository $advancedOptionsRepository
     * @param DataLayerInterface $dataLayer
     * @param UrlInterface $urlBuilder
     * @param LogRepository $logRepository
     */
    public function __construct(
        FrontendEventsRepository $configRepository,
        AdvancedOptionsRepository $advancedOptionsRepository,
        DataLayerInterface $dataLayer,
        UrlInterface $urlBuilder,
        LogRepository $logRepository
    ) {
        $this->configRepository = $configRepository;
        $this->advancedOptionsRepository = $advancedOptionsRepository;
        $this->dataLayer = $dataLayer;
        $this->urlBuilder = $urlBuilder;
        $this->logRepository = $logRepository;
    }

    /**
     * Check if the module is enabled
     *
     * @return boolean 0 or 1
     */
    public function isEnabled()
    {
        return $this->configRepository->isEnabled();
    }

    /**
     * @return string
     */
    public function getJsLink()
    {
        return sprintf(
            $this->advancedOptionsRepository->getEndpointTrackerUrl(),
            $this->getAccountId()
        );
    }

    /**
     * Get container id
     *
     * @return string
     */
    private function getAccountId()
    {
        return $this->configRepository->getAccountId();
    }

    /**
     * @return mixed
     */
    public function fireQueuedEvents()
    {
        return $this->dataLayer->fireQueuedEvents();
    }

    /**
     * Get url for queued events ajax call
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->urlBuilder->getUrl(self::URL_PATH);
    }
}
