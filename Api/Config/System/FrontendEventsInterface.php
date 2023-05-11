<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Config\System;

/**
 * Frontend Events group interface
 */
interface FrontendEventsInterface extends BackendEventsInterface
{
    /**
     * Config paths for 'frontend events'-group
     */
    public const XML_PATH_FRONTEND_EVENTS_ENABLED = 'squeezely/frontend_events/enabled';
    public const XML_PATH_FRONTEND_EVENTS_EVENTS = 'squeezely/frontend_events/events';

    /**
     * Frontend Events Enable FLag
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isFrontendEventsEnabled(int $storeId = null): bool;

    /**
     * Get Array of Enabled Frontend Events
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getEnabledFrontendEvents(int $storeId = null): array;

    /**
     * Check if event enabled
     *
     * @param string $eventName
     * @return bool
     */
    public function isFrontendEventEnabled(string $eventName): bool;
}
