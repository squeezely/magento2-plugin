<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Api\ProcessingQueue\RepositoryInterface as ProcessingQueueRepository;

/**
 * Class CustomerAccountManagement
 * Plugin for Customer AccountManagement model
 */
class CustomerAccountManagement
{

    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ProcessingQueueRepository
     */
    private $processingQueueRepository;
    /**
     * @var LocaleResolver
     */
    private $localeResolver;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * CustomerAccountManagement constructor.
     * @param Subscriber $subscriber
     * @param ConfigRepository $configRepository
     * @param ProcessingQueueRepository $processingQueueRepository
     * @param LocaleResolver $localeResolver
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Subscriber $subscriber,
        ConfigRepository $configRepository,
        ProcessingQueueRepository $processingQueueRepository,
        LocaleResolver $localeResolver,
        CookieManagerInterface $cookieManager
    ) {
        $this->subscriber = $subscriber;
        $this->configRepository = $configRepository;
        $this->processingQueueRepository = $processingQueueRepository;
        $this->localeResolver = $localeResolver;
        $this->cookieManager = $cookieManager;
    }

    /**
     * Fire events after new customer registered
     *
     * @param AccountManagement $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function afterCreateAccountWithPasswordHash(
        AccountManagement $subject,
        CustomerInterface $result
    ): CustomerInterface {
        $customer = $result;
        if (!$customer->getEmail()) {
            return $customer;
        }

        // Backend event, to connect email_hash with cookie
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::COMPLETE_REGISTRATION_EVENT)) {
            $this->registrationEvent($customer);
        }

        // Backend event, to add the raw email
        if ($this->configRepository->isBackendEventEnabled(ConfigRepository::EMAIL_OPT_IN_EVENT)) {
            $this->optInEvent($customer);
        }

        return $customer;
    }

    /**
     * @param CustomerInterface $customer
     * @return void
     */
    private function registrationEvent(CustomerInterface $customer)
    {
        $process = $this->processingQueueRepository->create();
        $process->setType('complete_registration')
            ->setProcessingData([
                'event' => ConfigRepository::COMPLETE_REGISTRATION_EVENT,
                'email' => hash('sha256', $customer->getEmail()),
                'language' => $this->getLanguage(),
                'sqzly_cookie' => $this->cookieManager->getCookie(
                    ConfigRepository::SQUEEZELY_COOKIE_NAME
                )
            ]);
        $this->processingQueueRepository->save($process);
    }

    /**
     * @return string
     */
    private function getLanguage(): string
    {
        $locale = $this->localeResolver->getLocale()
            ?: $this->localeResolver->getDefaultLocale();
        return str_replace('_', '-', $locale);
    }

    /**
     * @param CustomerInterface $customer
     * @return void
     */
    private function optInEvent(CustomerInterface $customer): void
    {
        $subscription = $this->subscriber->loadByCustomerId($customer->getId());
        $process = $this->processingQueueRepository->create();
        $process->setType('registration')
            ->setProcessingData([
                'event' => ConfigRepository::EMAIL_OPT_IN_EVENT,
                'email' => $customer->getEmail(),
                'newsletter' => (int)$subscription->getStatus() == Subscriber::STATUS_SUBSCRIBED ? 'yes' : 'no'
            ]);
        $this->processingQueueRepository->save($process);
    }
}
