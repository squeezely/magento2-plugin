<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as InvoiceModel;
use Squeezely\Plugin\Api\Config\System\BackendEventsInterface as BackendEventsRepository;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Quote
 * Plugin for Invoice model
 */
class Invoice
{

    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var BackendEventsRepository
     */
    private $backendEventsRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * Invoice constructor.
     *
     * @param RequestRepository $requestRepository
     * @param Subscriber $subscriber
     * @param BackendEventsRepository $backendEventsRepository
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        RequestRepository $requestRepository,
        Subscriber $subscriber,
        BackendEventsRepository $backendEventsRepository,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer,
        TimezoneInterface $localeDate
    ) {
        $this->requestRepository = $requestRepository;
        $this->subscriber = $subscriber;
        $this->backendEventsRepository = $backendEventsRepository;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->localeDate = $localeDate;
    }

    /**
     * Fire event after invoice created
     *
     * @param InvoiceModel $subject
     * @param InvoiceModel $result
     *
     * @return InvoiceModel
     */
    public function afterPay(
        InvoiceModel $subject,
        InvoiceModel $result
    ) {
        if ($this->backendEventsRepository->isEnabled()
            && in_array(
                RequestRepository::PURCHASE_EVENT_NAME,
                $this->backendEventsRepository->getEnabledEvents()
            )
        ) {
            $this->logRepository->addDebugLog('Purchase event', __('Start'));
            try {
                $order = $subject->getOrder();
                $this->logRepository->addDebugLog(
                    'Purchase event',
                    'Order id: ' . $order->getIncrementId()
                );
                if ($subject->getState() === Order\Invoice::STATE_PAID) {
                    $this->requestRepository->sendPurchases(
                        $this->transformOrderData($order)
                    );
                }
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('afterPay plugin', $exception->getMessage());
            }
        }
        $this->logRepository->addDebugLog('Purchase event', __('Finish'));
        return $result;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function transformOrderData($order): array
    {
        $data = [
            'event' => 'Purchase',
            'email' => $order->getCustomerEmail(),
            'firstname' => $order->getCustomerFirstname(),
            'lastname' => $order->getCustomerLastname(),
            'orderid' => $order->getRealOrderId(),
            'timestamp' => $this->createdAtStore($order),
            'userid' => $order->getCustomerId() ?? null,
            'gender' => $order->getCustomerGender(),
            'birthdate' => $order->getCustomerDob(),
            'phone' => $order->getBillingAddress()->getTelephone(),
            'postcode' => $order->getBillingAddress()->getPostcode(),
            'city' => $order->getBillingAddress()->getCity(),
            'country' => $order->getBillingAddress()->getCountryId(),
            'currency' => $order->getOrderCurrencyCode(),
            'service' => 'yes',
            'newsletter' => $this->getSubscriberStatus($order->getCustomerEmail()),
            'products' => $this->retrieveProductsFromOrder($order->getAllVisibleItems())
        ];
        $this->logRepository->addDebugLog(
            'Purchase event',
            'Event data: ' . $this->jsonSerializer->serialize($data)
        );
        return $data;
    }

    /**
     * @param string $email
     * @return string
     */
    private function getSubscriberStatus(string $email): string
    {
        $checkSubscriber = $this->subscriber->loadByEmail($email);

        switch ($checkSubscriber->getStatus()) {
            case Subscriber::STATUS_SUBSCRIBED:
                return 'yes';
            case Subscriber::STATUS_UNSUBSCRIBED:
                return 'no';
            default:
                return '';
        }
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function retrieveProductsFromOrder(array $items): array
    {
        $productItems = [];
        foreach ($items as $item) {
            $productItems[] = [
                'id' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'quantity' => (int)$item->getQtyOrdered(),
            ];
        }
        return $productItems;
    }

    /**
     * @param Order $order
     * @return string
     */
    private function createdAtStore($order)
    {
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', (string)$order->getCreatedAt());
        $timezone = $this->getTimezoneForStore($order->getStore());
        $storeTime = new \DateTimeZone($timezone);
        $datetime->setTimezone($storeTime);
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Get timezone for store
     *
     * @param mixed $store
     * @return string
     */
    private function getTimezoneForStore($store)
    {
        return $this->localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }
}
