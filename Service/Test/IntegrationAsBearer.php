<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Service\Test;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * isIntegrationAsBearerEnabled test
 */
class IntegrationAsBearer
{

    /**
     * Test type
     */
    public const TYPE = 'integration_as_bearer';
    /**
     * Test description
     */
    public const TEST = 'Check if OAuth Access Tokens can be used as standalone Bearer tokens.';
    /**
     * Visibility
     */
    public const VISIBLE = true;
    /**
     * Message on test success
     */
    public const SUCCESS_MSG = 'OAuth Access Tokens can be used as standalone Bearer tokens';
    /**
     * Message on test failed
     */
    public const FAILED_MSG = 'OAuth Access Tokens can not be used as standalone Bearer tokens, please enable this ' .
    'under Services > Oauth > Allow OAuth Access Tokens to be used as standalone Bearer tokens ';

    /**
     * Expected result
     */
    public const EXPECTED = true;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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

        if ($this->isIntegrationAsBearerEnabled() == self::EXPECTED) {
            $result['result_msg'] = self::SUCCESS_MSG;
            $result += ['result_code' => 'success'];
        } else {
            $result['result_msg'] = self::FAILED_MSG;
            $result += ['result_code' => 'failed'];
        }

        return $result;
    }

    /**
     * Return if integration access tokens can be used as bearer tokens
     *
     * @return bool
     */
    public function isIntegrationAsBearerEnabled(): bool
    {
        if (!class_exists(\Magento\Integration\Model\Config\AuthorizationConfig::class)) {
            return true; // class + limitation was added in 2.4.4-p1, before this version no need to check.
        }

        return $this->scopeConfig->isSetFlag(
            'oauth/consumer/enable_integration_as_bearer',
            ScopeInterface::SCOPE_STORE
        );
    }
}
