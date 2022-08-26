<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository as Subject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;

/**
 * ProductRepository Plugin
 */
class ProductRepository
{
    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ProductRepository constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param RequestRepository $requestRepository
     * @param LogRepository $logRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        RequestRepository $requestRepository,
        LogRepository $logRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->requestRepository = $requestRepository;
        $this->logRepository = $logRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Subject $subject
     * @param $result
     * @param ProductInterface $product
     * @return mixed
     */
    public function afterDelete(Subject $subject, $result, ProductInterface $product)
    {
        if ($result === true) {
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
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('delete product', $e->getMessage());
            }
        }
        return $result;
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
