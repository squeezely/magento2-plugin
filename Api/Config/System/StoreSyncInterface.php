<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Api\Config\System;

use Squeezely\Plugin\Api\Config\RepositoryInterface;

/**
 * Store Sync Events group interface
 */
interface StoreSyncInterface extends RepositoryInterface
{
    /**
     * Config paths for 'store_sync'-group
     */
    const XML_PATH_STORESYNC_ENABLED = 'squeezely/store_sync/enabled';
    const XML_PATH_STORESYNC_ATTRIBUTE_NAME = 'squeezely/store_sync/attribute_name';
    const XML_PATH_STORESYNC_ATTRIBUTE_DESCRIPTION = 'squeezely/store_sync/attribute_description';
    const XML_PATH_STORESYNC_ATTRIBUTE_BRAND = 'squeezely/store_sync/attribute_brand';
    const XML_PATH_STORESYNC_ATTRIBUTE_SIZE = 'squeezely/store_sync/attribute_size';
    const XML_PATH_STORESYNC_ATTRIBUTE_COLOR = 'squeezely/store_sync/attribute_color';
    const XML_PATH_STORESYNC_ATTRIBUTE_CONDITION = 'squeezely/store_sync/attribute_condition';
    const XML_PATH_STORESYNC_USE_PARENT_IMAGE = 'squeezely/store_sync/use_parent_image';
    const XML_PATH_STORESYNC_EXTRA_FIELDS = 'squeezely/store_sync/extra_fields';
    const XML_PATH_SYNC_CRON = 'squeezely/store_sync/cron';

    /**
     * Store Sync Enable FLag
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool;

    /**
     * Return all enabled storeIds
     *
     * @return array
     */
    public function getAllEnabledStoreIds(): array;

    /**
     * Get Attribute Name
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAttributeName(int $storeId = null): string;

    /**
     * Get Attribute Description
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAttributeDescription(int $storeId = null): string;

    /**
     * Get Attribute Brand
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAttributeBrand(int $storeId = null): string;

    /**
     * Get Attribute Size
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAttributeSize(int $storeId = null): string;

    /**
     * Get Attribute Color
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAttributeColor(int $storeId = null): string;

    /**
     * Get Attribute Condition
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getAttributeCondition(int $storeId = null): string;

    /**
     * Get Use Parent Image
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getUseParentImage(int $storeId = null): string;

    /**
     * Get Extra fields
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExtraFields(int $storeId = null): string;

    /**
     * Get cron frequency
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCronFrequency(int $storeId = null): string;
}
