<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;

/**
 * Class Success
 */
class Success implements ArgumentInterface
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var Session
     */
    private $checkoutSession;
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
     * @var LocaleResolver
     */
    private $localeResolver;

    /**
     * Success constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     * @param LocaleResolver $localeResolver
     */
    public function __construct(
        ConfigRepository $configRepository,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository,
        LocaleResolver $localeResolver
    ) {
        $this->configRepository = $configRepository;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->dataLayer = $dataLayer;
        $this->logRepository = $logRepository;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @return string
     */
    public function getDataScript(): ?string
    {
        if (!$this->configRepository->isFrontendEventEnabled(ConfigRepository::PURCHASE_EVENT)) {
            return null;
        }

        $order = $this->getOrder();
        $productItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $productItem = [];
            $productItem['id'] = $item->getSku();
            $productItem['language'] = $this->getStoreLocale();
            $productItem['name'] = $item->getName();
            $productItem['price'] = $item->getPrice();
            $productItem['quantity'] = (int)$item->getQtyOrdered();
            $productItems[] = (object)$productItem;
        }

        $objOrder = (object)[
            'event' => $order->hasInvoices()
                ? ConfigRepository::PURCHASE_EVENT
                : ConfigRepository::PRE_PURCHASE_EVENT,
            'email' => $order->getCustomerEmail(),
            'orderid' => $order->getIncrementId(),
            'firstname' => $order->getCustomerFirstname(),
            'lastname' => $order->getCustomerLastname(),
            'userid' => $order->getCustomerId(),
            'service' => 'enabled',
            'products' => $productItems,
            'currency' => $this->getStoreCurrency()
        ];

        return $this->dataLayer->generateDataScript($objOrder);
    }

    /**
     * Get order
     *
     * @return Order
     */
    protected function getOrder(): Order
    {
        return $this->checkoutSession->getLastRealOrder();
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
