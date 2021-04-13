<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Integration;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Token as OauthTokenModel;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\AdvancedOptionsInterface as ConfigRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;

/**
 * Sevice model to create and delete integrations
 */
class Service
{

    const INTEGRATION_NAME = 'Squeezely Integration';

    /**
     * @var IntegrationServiceInterface
     */
    private $integrationService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var OauthServiceInterface
     */
    private $oauthService;
    /**
     * @var AuthorizationServiceInterface
     */
    private $authorizationService;
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * Create constructor.
     * @param ConfigRepository $configRepository
     * @param IntegrationServiceInterface $integrationService
     * @param OauthServiceInterface $oauthService
     * @param AuthorizationServiceInterface $authorizationService
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param StoreManagerInterface $storeManager
     * @param RequestRepository $requestRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService,
        AuthorizationServiceInterface $authorizationService,
        CustomerTokenServiceInterface $customerTokenService,
        StoreManagerInterface $storeManager,
        RequestRepository $requestRepository
    ) {
        $this->configRepository = $configRepository;
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
        $this->authorizationService = $authorizationService;
        $this->customerTokenService = $customerTokenService;
        $this->storeManager = $storeManager;
        $this->requestRepository = $requestRepository;
    }

    /**
     * Create a new integration
     *
     * @return bool
     * @throws IntegrationException
     * @throws LocalizedException
     * @throws AuthenticationException
     */
    public function createIntegration(): bool
    {
        $integrationData = [
            'name' => self::INTEGRATION_NAME,
            'endpoint' => $this->configRepository->getEndpointDataUrl(),
            'status' => '1',
            'setup_type' => '0',
        ];

        $integration = $this->integrationService->create($integrationData);
        $integrationId = $integration->getId();
        $customerId = $integration->getConsumerId();

        $this->authorizationService->grantAllPermissions($integrationId);
        $this->oauthService->createAccessToken($customerId, true);
        $token = $this->oauthService->getAccessToken($customerId);

        if ($token instanceof OauthTokenModel) {
            return $this->sendMagentoTokenToSqueezelyAndVerifyAuth($token);
        }

        return false;
    }

    /**
     * @param OauthTokenModel $token
     * @return bool
     * @throws LocalizedException
     * @throws AuthenticationException
     */
    public function sendMagentoTokenToSqueezelyAndVerifyAuth(OauthTokenModel $token): bool
    {
        $storeInformationAndToken = $token->toArray() +
            [
                'webshopName' => $this->storeManager->getDefaultStoreView()->getName() . " - Magento 2",
                'webshopUrl' => $this->storeManager->getDefaultStoreView()->getBaseUrl(),
                'webshopSuffix' => ''
            ];

        return $this->requestRepository->sendMagentoTokenToSqueezelyAndVerifyAuth($storeInformationAndToken);
    }

    /**
     * Delete current integration
     *
     * @throws IntegrationException
     */
    public function deleteIntegration(): bool
    {
        $integration = $this->integrationService->findByName(self::INTEGRATION_NAME);
        if ($integrationId = $integration->getId()) {
            $integrationId = $integration->getId();
            $consumerId = $integration->getConsumerId();

            $this->integrationService->delete($integrationId);
            $this->oauthService->deleteConsumer($consumerId);
            return true;
        }

        return false;
    }
}
