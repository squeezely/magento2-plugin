<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\System;

use Squeezely\Plugin\Api\Config\System\BackendEventsInterface;
use Squeezely\Plugin\Model\Config\Repository as ConfigRepository;

/**
 * Backend Events provider class
 */
class BackendEventsRepository extends ConfigRepository implements BackendEventsInterface
{
    /**
     * @inheritDoc
     */
    public function isEnabled(int $storeId = null): bool
    {
        if (!parent::isEnabled($storeId)) {
            return false;
        }

        return $this->getFlag(self::XML_PATH_BACKENDEVENTS_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getEnabledEvents(int $storeId = null): array
    {
        return explode(
            ',',
            $this->getStoreValue(self::XML_PATH_BACKENDEVENTS_EVENTS, $storeId)
        );
    }
}
