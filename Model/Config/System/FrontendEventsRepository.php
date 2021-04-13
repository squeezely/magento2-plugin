<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\System;

use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface;
use Squeezely\Plugin\Model\Config\Repository as ConfigRepository;

/**
 * Frontend Events provider class
 */
class FrontendEventsRepository extends ConfigRepository implements FrontendEventsInterface
{
    /**
     * @inheritDoc
     */
    public function isEnabled(int $storeId = null): bool
    {
        if (!parent::isEnabled($storeId)) {
            return false;
        }

        return $this->getFlag(self::XML_PATH_FRONTENDEVENTS_ENABLED, $storeId);
    }
}
