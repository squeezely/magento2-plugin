<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Test;

use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Token as OauthTokenModel;
use Squeezely\Plugin\Service\Integration\Service as IntegrationService;

/**
 * AuthorizedIntegration test
 */
class AuthorizedIntegration
{

    /**
     * Test type
     */
    public const TYPE = 'authorized_integration';

    /**
     * Test description
     */
    public const TEST = 'Check if there is an active Squeezely Integration.';
    /**
     * Visibility
     */
    public const VISIBLE = true;
    /**
     * Message on test success
     */
    public const SUCCESS_MSG = 'Active Squeezely Integration found';
    /**
     * Expected result
     */
    public const EXPECTED = true;

    /**
     * @var IntegrationServiceInterface
     */
    private $integrationService;
    /**
     * @var OauthServiceInterface
     */
    private $oauthService;

    /**
     * @param IntegrationServiceInterface $integrationService
     * @param OauthServiceInterface $oauthService
     */
    public function __construct(
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService
    ) {
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE
        ];

        try {
            $this->checkIntegration();
            $result['result_msg'] = self::SUCCESS_MSG;
            $result += ['result_code' => 'success'];
        } catch (LocalizedException $exception) {
            $result['result_msg'] = $exception->getMessage();
            $result += ['result_code' => 'failed'];
        }

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    private function checkIntegration(): void
    {
        $integration = $this->integrationService->findByName(IntegrationService::INTEGRATION_NAME);
        if (!$integration->getId()) {
            throw new LocalizedException(
                __('No integration found, please set API keys and/or save the config!')
            );
        }

        $consumerId = $integration->getConsumerId();
        $token = $this->oauthService->getAccessToken($consumerId);

        if ($token instanceof OauthTokenModel) {
            if (empty($token['token'])) {
                throw new LocalizedException(
                    __('No token found, please set API keys and/or save the config!')
                );
            }

            if (!mb_detect_encoding($token['token'], 'UTF-8', true)) {
                throw new LocalizedException(
                    __('Issue found with generated token, as token has invalid UTF-8 characters')
                );
            }

            return;
        }

        throw new LocalizedException(
            __('Unknown error')
        );
    }
}
