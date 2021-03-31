<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\ViewModel;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Success
 */
class Success implements ArgumentInterface
{

    const PURCHASE_EVENT_NAME = 'Purchase';
    const PRE_PURCHASE_EVENT_NAME = 'PrePurchase';

    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
    /**
     * Checkout Session
     *
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
     * Success constructor.
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
            $order = $this->getOrder();
            if ($order->hasInvoices()) {
                $this->logRepository->addDebugLog(self::PURCHASE_EVENT_NAME, __('Start'));
            } else {
                $this->logRepository->addDebugLog(self::PRE_PURCHASE_EVENT_NAME, __('Start'));
            }
            $productItems = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $productItem = [];
                $productItem['id'] = $item->getSku();
                $productItem['name'] = $item->getName();
                $productItem['price'] = $item->getPrice();
                $productItem['quantity']
                    = (int)$item->getQtyOrdered();
                $productItems[] = (object)$productItem;
            }

            if ($order->hasInvoices()) {
                $event = self::PURCHASE_EVENT_NAME;
            } else {
                $event = self::PRE_PURCHASE_EVENT_NAME;
            }

            $objOrder = (object)[
                'event' => $event,
                'email' => $order->getCustomerEmail(),
                'orderid' => $order->getIncrementId(),
                'firstname' => $order->getCustomerFirstname(),
                'lastname' => $order->getCustomerLastname(),
                'userid' => $order->getCustomerId(),
                'service' => 'enabled',
                'products' => $productItems,
                'currency' => $this->getStoreCurrency()
            ];

            $dataScript = $this->dataLayer->generateDataScript($objOrder);
            if ($order->hasInvoices()) {
                $this->logRepository->addDebugLog(
                    self::PURCHASE_EVENT_NAME,
                    'Event data: ' . $this->jsonSerializer->serialize($objOrder)
                );
                $this->logRepository->addDebugLog(self::PURCHASE_EVENT_NAME, __('Finish'));
            } else {
                $this->logRepository->addDebugLog(
                    self::PRE_PURCHASE_EVENT_NAME,
                    'Event data: ' . $this->jsonSerializer->serialize($objOrder)
                );
                $this->logRepository->addDebugLog(self::PRE_PURCHASE_EVENT_NAME, __('Finish'));
            }
        }

        return $dataScript;
    }

    /**
     * Get order
     *
     * @return Order
     */
    protected function getOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @return string
     */
    protected function getStoreCurrency()
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
