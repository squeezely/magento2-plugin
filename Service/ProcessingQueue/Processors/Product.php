<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ProcessingQueue\Processors;

use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;

/**
 * Product Processing Service class
 */
class Product
{

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @param StoreManagerInterface $storeManager
     * @param RequestRepository $requestRepository
     * @param ProductRepository $productRepository
     * @param LogRepository $logRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        RequestRepository $requestRepository,
        ProductRepository $productRepository,
        LogRepository $logRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->requestRepository = $requestRepository;
        $this->productRepository = $productRepository;
        $this->logRepository = $logRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function execute(int $productId): bool
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('Delete product', $e->getMessage());
            return false;
        }
        $products = [];
        foreach ($this->getLanguages() as $language) {
            $products[] = [
                'id' => $product->getSku(),
                'language' => $language
            ];
        }
        $productData = ['products' => $products];

        try {
            $this->requestRepository->sendDeleteProducts($productData);
            return true;
        } catch (\Exception $e) {
            $this->logRepository->addErrorLog('Delete product', $e->getMessage());
            return false;
        }
    }

    /**
     * @return array
     */
    private function getLanguages(): array
    {
        $languages = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $languages[] = (string)$this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
        }
        return array_unique($languages);
    }
}
