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
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use stdClass;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Checkout
 */
class Checkout implements ArgumentInterface
{

    const EVENT_NAME = 'InitiateCheckout';

    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
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
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Checkout constructor.
     *
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param DataLayerInterface $dataLayer
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        FrontendEventsRepository $frontendEventsRepository,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        DataLayerInterface $dataLayer,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer
    ) {
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->checkoutSession = $checkoutSession;
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
            $quote = $this->getQuote();
            if ($quote == null) {
                return $dataScript;
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

            $dataScript = $this->dataLayer->generateDataScript($objOrder);
            $this->logRepository->addDebugLog(
                self::EVENT_NAME,
                'Event data: ' . $this->jsonSerializer->serialize($objOrder)
            );
            $this->logRepository->addDebugLog(self::EVENT_NAME, __('Finish'));
        }

        return $dataScript;
    }

    /**
     * Get quote
     *
     * @return Quote|null
     */
    private function getQuote()
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
    private function getStoreCurrency()
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
