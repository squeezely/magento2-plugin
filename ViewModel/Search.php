<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Search
 */
class Search implements ArgumentInterface
{

    public const EVENT_NAME = 'Search';

    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Search constructor.
     *
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param Http $request
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        FrontendEventsRepository $frontendEventsRepository,
        Http $request,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer
    ) {
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->request = $request;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->dataLayer = $dataLayer;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return string
     */
    public function getDataScript()
    {
        $dataScript = '';
        if ($this->frontendEventsRepository->isEnabled()) {
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Start'));
            $keyword = $this->request->getParam('q', false);

            $objSearch = (object)[
                'event' => 'Search',
                'keyword' => $keyword
            ];

            $dataScript = $this->dataLayer->generateDataScript($objSearch);
            $this->logRepository->addDebugLog(
                self::EVENT_NAME,
                'Event data: ' . $this->jsonSerializer->serialize($objSearch)
            );
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Finish'));
        }
        return $dataScript;
    }
}
