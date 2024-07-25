<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\ProcessingQueue\Processors;

use DateTimeZone;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Store\Model\ScopeInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;

/**
 * Order Processing Service class
 */
class Order
{

    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;
    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @param Subscriber $subscriber
     * @param RequestRepository $requestRepository
     * @param LogRepository $logRepository
     * @param OrderRepository $orderRepository
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        Subscriber $subscriber,
        RequestRepository $requestRepository,
        LogRepository $logRepository,
        OrderRepository $orderRepository,
        TimezoneInterface $localeDate
    ) {
        $this->subscriber = $subscriber;
        $this->requestRepository = $requestRepository;
        $this->logRepository = $logRepository;
        $this->orderRepository = $orderRepository;
        $this->localeDate = $localeDate;
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function execute(int $orderId): bool
    {
        try {
            $order = $this->orderRepository->get($orderId);
            $this->logRepository->addDebugLog(
                'Purchase event',
                'Order id: ' . $order->getIncrementId()
            );
            $this->requestRepository->sendToPlatform(
                $this->transformOrderData($order),
                (int) $order->getStoreId()
            );
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Purchase event', $exception->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param MagentoOrder $order
     *
     * @return array
     */
    public function transformOrderData(MagentoOrder $order): array
    {
        return [
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
            'products' => $this->retrieveProductsFromOrder($order)
        ];
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function retrieveProductsFromOrder(MagentoOrder $order): array
    {
        $productItems = [];
        $language = $order->getStore()->getConfig('general/locale/code');
        foreach ($order->getAllVisibleItems() as $item) {
            $productItems[] = [
                'id' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'quantity' => (int)$item->getQtyOrdered(),
                'language' => $language
            ];
        }
        return $productItems;
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
     * @param MagentoOrder $order
     * @return string
     */
    private function createdAtStore(MagentoOrder $order): string
    {
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', (string)$order->getCreatedAt());
        if (!$datetime) {
            return '';
        }
        $timezone = $this->localeDate->getConfigTimezone(
            ScopeInterface::SCOPE_STORE,
            $order->getStore()->getCode()
        );
        $storeTime = new DateTimeZone($timezone);
        $datetime->setTimezone($storeTime);
        return $datetime->format('Y-m-d H:i:s');
    }
}
