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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Token as OauthTokenModel;
use Magento\Store\Model\StoreManagerInterface;
use Squeezely\Plugin\Api\Config\System\AdvancedOptionsInterface as ConfigRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;

/**
 * Service model to create and delete integrations
 */
class Service
{

    public const INTEGRATION_NAME = 'Squeezely Integration';

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
     * @param StoreManagerInterface $storeManager
     * @param RequestRepository $requestRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService,
        AuthorizationServiceInterface $authorizationService,
        StoreManagerInterface $storeManager,
        RequestRepository $requestRepository
    ) {
        $this->configRepository = $configRepository;
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
        $this->authorizationService = $authorizationService;
        $this->storeManager = $storeManager;
        $this->requestRepository = $requestRepository;
    }

    /**
     * @throws IntegrationException
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function verifyAuth(int $storeId): bool
    {
        //check if squeezely integration exists
        $integration = $this->integrationService->findByName(self::INTEGRATION_NAME);
        if (!$integration->getId()) {
            $token = $this->createIntegrationAndGetToken();
        } else {
            $consumerId = $integration->getConsumerId();
            $token = $this->oauthService->getAccessToken($consumerId);
        }
        if ($token instanceof OauthTokenModel) {
            return $this->sendMagentoTokenToSqueezelyAndVerifyAuth($token, $storeId);
        }

        return false;
    }

    /**
     * @throws IntegrationException
     * @throws LocalizedException
     */
    public function createIntegrationAndGetToken()
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
        return $this->oauthService->getAccessToken($customerId);
    }

    /**
     * @param OauthTokenModel $token
     * @param int $storeId
     * @return bool
     * @throws AuthenticationException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendMagentoTokenToSqueezelyAndVerifyAuth(OauthTokenModel $token, int $storeId): bool
    {
        if ($storeId) {
            $webshopName = $this->storeManager->getStore($storeId)->getName() . " - Magento 2";
            $webshopUrl = $this->storeManager->getStore($storeId)->getBaseUrl();
        } else {
            $webshopName = $this->storeManager->getDefaultStoreView()->getName() . " - Magento 2";
            $webshopUrl = $this->storeManager->getDefaultStoreView()->getBaseUrl();
        }

        $storeInformationAndToken = $token->toArray() +
            [
                'webshopName' => $webshopName,
                'webshopUrl' => $webshopUrl,
                'webshopSuffix' => ''
            ];

        return $this->requestRepository->sendMagentoTokenToSqueezelyAndVerifyAuth($storeInformationAndToken, $storeId);
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
            $consumerId = $integration->getConsumerId();

            $this->integrationService->delete($integrationId);
            $this->oauthService->deleteConsumer($consumerId);
            return true;
        }

        return false;
    }
}
