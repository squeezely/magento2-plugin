<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Config\System;

use Squeezely\Plugin\Api\Config\RepositoryInterface;

/**
 * Frontend Events group interface
 */
interface FrontendEventsInterface extends RepositoryInterface
{
    /**
     * Config paths for 'frontend events'-group
     */
    const XML_PATH_FRONTENDEVENTS_ENABLED = 'squeezely/frontend_events/enabled';

    /**
     * Frontend Events Enable FLag
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool;
}
