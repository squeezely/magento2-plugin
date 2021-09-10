<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\System;

use Squeezely\Plugin\Api\Config\System\StoreSyncInterface;
use Squeezely\Plugin\Model\Config\Repository as ConfigRepository;

/**
 * Store Sync provider class
 */
class StoreSyncRepository extends ConfigRepository implements StoreSyncInterface
{

    /**
     * @inheritDoc
     */
    public function getAllEnabledStoreIds(): array
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isEnabled((int)$store->getId()) && $store->getIsActive()) {
                $storeIds[] = (int)$store->getId();
            }
        }

        return $storeIds;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(int $storeId = null): bool
    {
        if (!parent::isEnabled($storeId)) {
            return false;
        }

        return $this->getFlag(self::XML_PATH_STORESYNC_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeName(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_ATTRIBUTE_NAME,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributeDescription(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_ATTRIBUTE_DESCRIPTION,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributeBrand(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_ATTRIBUTE_BRAND,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributeSize(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_ATTRIBUTE_SIZE,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributeColor(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_ATTRIBUTE_COLOR,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getAttributeCondition(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_ATTRIBUTE_CONDITION,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getUseParentImage(int $storeId = null): string
    {
        return (string)$this->getStoreValue(
            self::XML_PATH_STORESYNC_USE_PARENT_IMAGE,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getExtraFields(int $storeId = null): string
    {
        $extraFields = $this->getStoreValue(
            self::XML_PATH_STORESYNC_EXTRA_FIELDS,
            $storeId
        );
        if (!$extraFields) {
            $extraFields = '[]';
        }
        return $extraFields;
    }

    /**
     * @inheritDoc
     */
    public function getCronFrequency(int $storeId = null): string
    {
        return $this->getStoreValue(
            self::XML_PATH_SYNC_CRON,
            $storeId
        );
    }
}
