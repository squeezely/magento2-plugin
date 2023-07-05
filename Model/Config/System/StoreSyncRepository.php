<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Config\System;

use Squeezely\Plugin\Api\Config\System\StoreSyncInterface;

/**
 * Store Sync provider class
 */
class StoreSyncRepository extends FrontendEventsRepository implements StoreSyncInterface
{

    /**
     * @inheritDoc
     */
    public function getAllEnabledStoreSyncStoreIds(): array
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isStoreSyncEnabled((int)$store->getId()) && $store->getIsActive()) {
                $storeIds[] = (int)$store->getId();
            }
        }

        return $storeIds;
    }

    /**
     * @inheritDoc
     */
    public function isStoreSyncEnabled(int $storeId = null): bool
    {
        return $this->getFlag(self::XML_PATH_STORESYNC_ENABLED, $storeId);
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
    public function getLanguage(int $storeId = null): string
    {
        if ($this->getLanguageOption($storeId) !== 'custom') {
            return $this->getStoreValue('general/locale/code', $storeId);
        }

        $customLanguage = $this->getStoreValue(self::XML_PATH_STORESYNC_LANGUAGE_CUSTOM, $storeId);
        return !empty($customLanguage)
            ? $customLanguage
            : $this->getStoreValue('general/locale/code', $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    private function getLanguageOption(int $storeId = null): string
    {
        return $this->getStoreValue(self::XML_PATH_STORESYNC_LANGUAGE, $storeId);
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
