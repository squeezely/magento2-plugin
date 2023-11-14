<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\System;

use Squeezely\Plugin\Api\Config\System\AdvancedOptionsInterface;

/**
 * Advanced option provider class
 */
class AdvancedOptionsRepository extends BaseRepository implements AdvancedOptionsInterface
{

    /**
     * @inheritDoc
     */
    public function isDebugEnabled(): bool
    {
        return $this->getFlag(self::XML_PATH_DEBUG);
    }

    /**
     * @inheritDoc
     */
    public function getEndpointDataUrl(): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_ENDPOINT_DATA_URL);
    }

    /**
     * @inheritDoc
     */
    public function getEndpointTrackerUrl(): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_ENDPOINT_TRACKER_URL);
    }

    /**
     * @inheritDoc
     */
    public function getApiRequestUri(): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_API_REQUEST_URI);
    }

    /**
     * @inheritDoc
     */
    public function isPurchaseInclTax(int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_PURCHASE_INCL_TAX, $storeId);
    }
}
