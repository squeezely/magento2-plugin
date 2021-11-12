<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Quote\Model\Quote as QuoteModel;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface as FrontendEventsRepository;
use Squeezely\Plugin\Api\Service\DataLayerInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\RequestInterface;
use Squeezely\Plugin\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Locale\Resolver as LocaleResolver;

/**
 * Class Quote
 * Plugin for quote model
 */
class Quote
{

    const REMOVE_FROM_CART_EVENT_NAME = 'RemoveFromCart';
    const ADD_TO_CART_EVENT_NAME = 'AddToCart';

    /**
     * @var DataLayerInterface
     */
    private $dataLayer;
    /**
     * @var FrontendEventsRepository
     */
    private $frontendEventsRepository;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var LocaleResolver
     */
    private $localeResolver;

    /**
     * Quote constructor.
     *
     * @param DataLayerInterface $dataLayer
     * @param FrontendEventsRepository $frontendEventsRepository
     * @param RequestInterface $request
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     * @param LocaleResolver $localeResolver
     */
    public function __construct(
        DataLayerInterface $dataLayer,
        FrontendEventsRepository $frontendEventsRepository,
        RequestInterface $request,
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer,
        LocaleResolver $localeResolver
    ) {
        $this->dataLayer = $dataLayer;
        $this->frontendEventsRepository = $frontendEventsRepository;
        $this->request = $request;
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Fire event after item was removed from cart
     *
     * @param QuoteModel $subject
     * @param QuoteModel $result
     * @param int $itemId
     *
     * @return QuoteModel
     */
    public function afterRemoveItem(
        QuoteModel $subject,
        QuoteModel $result,
        int $itemId
    ) {
        /** @phpstan-ignore-next-line */
        if ($this->frontendEventsRepository->isEnabled() && !$this->request->isAjax()) {
            $this->logRepository->addDebugLog(self::REMOVE_FROM_CART_EVENT_NAME, __('Start'));
            $item = $subject->getItemById($itemId);
            if ($item) {
                $eventData = ['products' => ['id' => $item->getSku()]];
                $this->dataLayer->addEventToQueue(self::REMOVE_FROM_CART_EVENT_NAME, $eventData);
                $this->logRepository->addDebugLog(
                    self::REMOVE_FROM_CART_EVENT_NAME,
                    'Event data: ' . $this->jsonSerializer->serialize($eventData)
                );
            }
            $this->logRepository->addDebugLog(self::REMOVE_FROM_CART_EVENT_NAME, __('Finish'));
        }
        return $result;
    }

    /**
     * Fire event after item was added to the cart (only after post request)
     *
     * @param QuoteModel $subject
     * @param Item $result
     * @param Product $product
     *
     * @return Item
     */
    public function afterAddProduct(
        QuoteModel $subject,
        $result,
        Product $product
    ) {
        if ($this->frontendEventsRepository->isEnabled()) {
            $this->logRepository->addDebugLog(self::ADD_TO_CART_EVENT_NAME, __('Start'));
            $eventData = ['products' => [
                'id' => $product->getSku(),
                'language' => $this->getStoreLocale(),
                'quantity' => $product->getQty()
            ]];
            $this->dataLayer->addEventToQueue(self::ADD_TO_CART_EVENT_NAME, $eventData);
            $this->logRepository->addDebugLog(
                self::ADD_TO_CART_EVENT_NAME,
                'Event data: ' . $this->jsonSerializer->serialize($eventData)
            );
            $this->logRepository->addDebugLog(self::ADD_TO_CART_EVENT_NAME, __('Finish'));
        }
        return $result;
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
}
