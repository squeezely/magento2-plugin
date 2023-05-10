<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;

/**
 * Class Product
 */
class Product implements ArgumentInterface
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LocaleResolver
     */
    private $localeResolver;
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
     * Product constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Http $request
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param LocaleResolver $localeResolver
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        ConfigRepository $configRepository,
        Http $request,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        LocaleResolver $localeResolver,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer
    ) {
        $this->configRepository = $configRepository;
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->dataLayer = $dataLayer;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return null|string
     */
    public function getDataScript(): ?string
    {
        if (!$this->configRepository->isFrontendEventEnabled(ConfigRepository::VIEW_CONTENT_EVENT)) {
            return null;
        }

        try {
            $productId = (int)$this->request->getParam('id', false);
            $product = $this->productRepository->getById($productId, false, $this->getStoreId());
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
            return null;
        }

        $objEcommerce = (object)[
            'event' => ConfigRepository::VIEW_CONTENT_EVENT,
            'products' => (object)[
                'name' => $product->getName(),
                'id' => $product->getSku(),
                'price' => $product->getFinalPrice(),
                'language' => $this->getStoreLocale(),
                'category_ids' => $product->getCategoryCollection()->getAllIds()
            ],
            'currency' => $this->getStoreCurrency()
        ];

        return $this->dataLayer->generateDataScript($objEcommerce);
    }

    /**
     * @return int|null
     */
    private function getStoreId(): ?int
    {
        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
            return null;
        }
    }

    /**
     * @return string
     */
    private function getStoreLocale(): string
    {
        $locale = $this->localeResolver->getLocale()
            ?: $this->localeResolver->getDefaultLocale();
        return str_replace('_', '-', $locale);
    }

    /**
     * @return string
     */
    private function getStoreCurrency(): string
    {
        try {
            return $this->storeManager->getStore()->getCurrentCurrencyCode();
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
            return '';
        }
    }
}
