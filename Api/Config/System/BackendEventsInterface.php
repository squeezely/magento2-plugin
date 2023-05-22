<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Config\System;

/**
 * Backend Events group interface
 */
interface BackendEventsInterface extends AdvancedOptionsInterface
{
    /**
     * Config paths for 'backend-events'-group
     */
    public const XML_PATH_BACKEND_EVENTS_ENABLED = 'squeezely/backend_events/enabled';
    public const XML_PATH_BACKEND_EVENTS_EVENTS = 'squeezely/backend_events/events';
    public const XML_PATH_BACKEND_EVENTS_POOL_SIZE = 'squeezely/backend_events/pool_size';

    /**
     * Backend Events Enable FLag
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isBackendEventsEnabled(int $storeId = null): bool;

    /**
     * Return all enabled storeIds for backend events sync
     *
     * @return array
     */
    public function getAllEnabledBackendSyncStoreIds(): array;

    /**
     * Get Array of Enabled Backend Events
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getEnabledBackendEvents(int $storeId = null): array;

    /**
     * Check if event enabled
     *
     * @param string $eventName
     * @return bool
     */
    public function isBackendEventEnabled(string $eventName): bool;

    /**
     * Return sync pool size
     *
     * @return int
     */
    public function getPoolSize(): int;
}
