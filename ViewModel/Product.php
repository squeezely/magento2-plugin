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
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Product
 */
class Product implements ArgumentInterface
{

    public const EVENT_NAME = 'ViewContent';

    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
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
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param Http $request
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param LocaleResolver $localeResolver
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        FrontendEventsRepository $frontendEventsRepository,
        Http $request,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        LocaleResolver $localeResolver,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer
    ) {
        $this->frontendEventsRepository = $frontendEventsRepository;
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
    public function getDataScript()
    {
        $dataScript = '';
        if ($this->frontendEventsRepository->isEnabled()) {
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Start'));
            try {
                $productId = (int)$this->request->getParam('id', false);
                $product = $this->productRepository->getById(
                    $productId,
                    false,
                    $this->getStoreId()
                );
            } catch (NoSuchEntityException $e) {
                $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
                return null;
            }
            $categoryCollection = $product->getCategoryCollection();

            $objProduct = (object)[
                'name' => $product->getName(),
                'id' => $product->getSku(),
                'price' => $product->getFinalPrice(),
                'language' => $this->getStoreLocale(),
                'category_ids' => $categoryCollection->getAllIds()
            ];

            $objEcommerce = (object)[
                'event' => self::EVENT_NAME,
                'products' => $objProduct,
                'currency' => $this->getStoreCurrency()
            ];

            $dataScript = $this->dataLayer->generateDataScript($objEcommerce);
            $this->logRepository->addDebugLog(
                self::EVENT_NAME,
                'Event data: ' . $this->jsonSerializer->serialize($objEcommerce)
            );
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Finish'));
        }
        return $dataScript;
    }

    /**
     * @return int|null
     */
    private function getStoreId()
    {
        try {
            return $this->storeManager->getStore()->getId();
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
        $currencyCode = '';
        try {
            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
        }
        return $currencyCode;
    }
}
