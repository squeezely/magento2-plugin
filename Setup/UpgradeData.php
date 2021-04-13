<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{

    const FIELDS = [
        'squeezely_plugin/general/enabled' => 'squeezely/general/enabled',
        'squeezely_plugin/general/SQZLY_id' => 'squeezely/general/account_id',
        'squeezely_plugin/general/squeezely_api_key' => 'squeezely/general/api_key',
        'squeezely_plugin/general/squeezely_webhook_key' => 'squeezely/general/webhook_key',
    ];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * UpgradeData constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), "2.0.0", "<")) {
            $connection = $this->resourceConnection->getConnection();
            foreach (self::FIELDS as $oldField => $newField) {
                $connection->update(
                    $this->resourceConnection->getTableName('core_config_data'),
                    ['path' => $newField],
                    ['path = ?' => $oldField]
                );
            }
        }
    }
}
