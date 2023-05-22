<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\System;

use Squeezely\Plugin\Api\Config\RepositoryInterface;
use Squeezely\Plugin\Api\Config\System\FrontendEventsInterface;

/**
 * Frontend Events provider class
 */
class FrontendEventsRepository extends BackendEventsRepository implements FrontendEventsInterface
{

    /**
     * @inheritDoc
     */
    public function isFrontendEventEnabled(string $eventName): bool
    {
        return $this->isFrontendEventsEnabled()
            && in_array($eventName, $this->getEnabledFrontendEvents());
    }

    /**
     * @inheritDoc
     */
    public function isFrontendEventsEnabled(int $storeId = null): bool
    {
        return $this->getFlag(RepositoryInterface::XML_PATH_ENABLED, $storeId)
            && $this->getFlag(self::XML_PATH_FRONTEND_EVENTS_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getEnabledFrontendEvents(int $storeId = null): array
    {
        return explode(
            ',',
            $this->getStoreValue(self::XML_PATH_FRONTEND_EVENTS_EVENTS, $storeId)
        );
    }
}
