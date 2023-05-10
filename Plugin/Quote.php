<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\Quote\Item;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

class Quote
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var LocaleResolver
     */
    private $localeResolver;
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @param ConfigRepository $configRepository
     * @param LocaleResolver $localeResolver
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        ConfigRepository $configRepository,
        LocaleResolver $localeResolver,
        ProcessingQueueRepository $processingQueueRepository,
        CookieManagerInterface $cookieManager
    ) {
        $this->configRepository = $configRepository;
        $this->localeResolver = $localeResolver;
        $this->processingQueueRepository = $processingQueueRepository;
        $this->cookieManager = $cookieManager;
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
        if (!$this->configRepository->isBackendEventEnabled(ConfigRepository::ADD_TO_CART_EVENT)
            || $this->configRepository->isFrontendEventEnabled(ConfigRepository::ADD_TO_CART_EVENT)) {
            return $result;
        }

        if ($item = $subject->getItemById($itemId)) {
            $process = $this->processingQueueRepository->create();
            $process->setType('remove_from_cart')
                ->setProcessingData([
                    'event' => ConfigRepository::REMOVE_FROM_CART_EVENT,
                    'products' => ['id' => $item->getSku()],
                    'sqzly_cookie' => $this->cookieManager->getCookie(
                        ConfigRepository::SQUEEZELY_COOKIE_NAME
                    )
                ]);
            $this->processingQueueRepository->save($process);
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
        if (!$this->configRepository->isBackendEventEnabled(ConfigRepository::ADD_TO_CART_EVENT)
            || $this->configRepository->isFrontendEventEnabled(ConfigRepository::ADD_TO_CART_EVENT)) {
            return $result;
        }

        $process = $this->processingQueueRepository->create();
        $process->setType('add_to_cart')
            ->setProcessingData([
                'event' => ConfigRepository::ADD_TO_CART_EVENT,
                'products' => [
                    'id' => $product->getSku(),
                    'language' => $this->getStoreLocale(),
                    'quantity' => $product->getQty()
                ],
                'sqzly_cookie' => $this->cookieManager->getCookie(
                    ConfigRepository::SQUEEZELY_COOKIE_NAME
                )
            ]);
        $this->processingQueueRepository->save($process);

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
