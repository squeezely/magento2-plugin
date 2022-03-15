<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Config\System;

use Squeezely\Plugin\Api\Config\RepositoryInterface;

/**
 * Backend Events group interface
 */
interface BackendEventsInterface extends RepositoryInterface
{
    /**
     * Config paths for 'backend-events'-group
     */
    public const XML_PATH_BACKENDEVENTS_ENABLED = 'squeezely/backend_events/enabled';
    public const XML_PATH_BACKENDEVENTS_EVENTS = 'squeezely/backend_events/events';

    /**
     * Backend Events Enable FLag
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool;

    /**
     * Get Array of Enabled Backend Events
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getEnabledEvents(int $storeId = null): array;
}
