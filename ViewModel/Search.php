<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;

/**
 * Class Search
 */
class Search implements ArgumentInterface
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var DataLayerInterface
     */
    private $dataLayer;

    /**
     * Search constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Http $request
     * @param DataLayerInterface $dataLayer
     */
    public function __construct(
        ConfigRepository $configRepository,
        Http $request,
        DataLayerInterface $dataLayer
    ) {
        $this->configRepository = $configRepository;
        $this->request = $request;
        $this->dataLayer = $dataLayer;
    }

    /**
     * @return string|null
     */
    public function getDataScript(): ?string
    {
        if (!$this->configRepository->isFrontendEventEnabled(ConfigRepository::SEARCH_EVENT)) {
            return null;
        }

        return $this->dataLayer->generateDataScript((object)[
            'event' => ConfigRepository::SEARCH_EVENT,
            'keyword' => $this->request->getParam('q', false)
        ]);
    }
}
