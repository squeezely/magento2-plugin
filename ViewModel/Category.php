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
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;

/**
 * Class Category
 */
class Category implements ArgumentInterface
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
     * Category constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Http $request
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        Http $request,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository
    ) {
        $this->configRepository = $configRepository;
        $this->request = $request;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->dataLayer = $dataLayer;
        $this->logRepository = $logRepository;
    }

    /**
     * @return string|null
     */
    public function getDataScript(): ?string
    {
        if (!$this->configRepository->isFrontendEventEnabled(ConfigRepository::VIEW_CATEGORY_EVENT)) {
            return null;
        }

        try {
            $categoryId = (int)$this->request->getParam('id', false);
            $category = $this->categoryRepository->get($categoryId, $this->getStoreId());
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
            return null;
        }

        return $this->dataLayer->generateDataScript((object)[
            'event' => ConfigRepository::VIEW_CATEGORY_EVENT,
            'category_id' => $categoryId,
            'objectname' => $category->getName()
        ]);
    }

    /**
     * @return int|null
     */
    protected function getStoreId(): ?int
    {
        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
            return null;
        }
    }
}
