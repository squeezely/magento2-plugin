<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Plugin;

use Magento\Framework\Session\Config;
use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepositoryInterface;

/**
 * Plugin for Squeezely cookie
 */
class CookieSecure
{
    /**
     * Always set option session.cookie_secure as true for Squeezely cookie
     *
     * @param Config $config
     * @return Config
     */
    public function afterSetCookieSecure(Config $config): Config
    {
        if ($config->getName() == ConfigRepositoryInterface::SQUEEZELY_COOKIE_NAME) {
            $config->setOption('session.cookie_secure', true);
        }
        return $config;
    }
}
