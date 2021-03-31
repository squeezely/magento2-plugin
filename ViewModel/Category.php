<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Category
 */
class Category implements ArgumentInterface
{

    const EVENT_NAME = 'ViewCategory';

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
     * Category constructor.
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
     * @return string|null
     */
    public function getDataScript()
    {
        $dataScript = '';
        if ($this->frontendEventsRepository->isEnabled()) {
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Start'));
            try {
                $categoryId = (int)$this->request->getParam('id', false);
                $category = $this->categoryRepository->get(
                    $categoryId,
                    $this->getStoreId()
                );
            } catch (NoSuchEntityException $e) {
                $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
                return null;
            }

            $objViewCategory = (object)[
                'event' => self::EVENT_NAME,
                'category_id' => $categoryId,
                'objectname' => $category->getName()
            ];

            $dataScript = $this->dataLayer->generateDataScript($objViewCategory);
            $this->logRepository->addDebugLog(
                self::EVENT_NAME,
                'Event data: ' . $this->jsonSerializer->serialize($objViewCategory)
            );
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Finish'));
        }
        return $dataScript;
    }

    /**
     * @return int|null
     */
    protected function getStoreId()
    {
        try {
            return $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
            return null;
        }
    }
}
