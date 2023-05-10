<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use stdClass;

/**
 * Class Checkout
 */
class Checkout implements ArgumentInterface
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
     * Checkout constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository
    ) {
        $this->configRepository = $configRepository;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->dataLayer = $dataLayer;
        $this->logRepository = $logRepository;
    }

    /**
     * @return string|null
     */
    public function getDataScript(): ?string
    {
        if (!$this->configRepository->isFrontendEventEnabled(ConfigRepository::INITIATE_CHECKOUT_EVENT)) {
            return null;
        }

        $quote = $this->getQuote();
        if ($this->getQuote() == null) {
            return null;
        }

        $productItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productItems[] = (object)[
                'id' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'quantity' => (int)$item->getQty()
            ];
        }

        $objOrder = new stdClass();
        $objOrder->event = 'InitiateCheckout';

        if ($quote->getCustomerEmail()) {
            $objOrder->email = $quote->getCustomerEmail();
        }
        if ($quote->getCustomerFirstname()) {
            $objOrder->firstname = $quote->getCustomerFirstname();
        }
        if ($quote->getCustomerLastname()) {
            $objOrder->lastname = $quote->getCustomerLastname();
        }
        if ($quote->getCustomerId()) {
            $objOrder->userid = $quote->getCustomerId();
        }
        $objOrder->products = $productItems;
        $objOrder->currency = $this->getStoreCurrency();

        return $this->dataLayer->generateDataScript($objOrder);
    }

    /**
     * Get quote
     *
     * @return Quote|null
     */
    private function getQuote(): ?Quote
    {
        $quote = null;
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException $e) {
            $this->logRepository->addErrorLog('NoSuchEntityException', $e->getMessage());
        } catch (LocalizedException $e) {
            $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
        }
        return $quote;
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
