<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Setup data patch class to change config path
 */
class ConfigPath implements DataPatchInterface
{

    public const FIELDS = [
        'squeezely_plugin/general/enabled'               => 'squeezely/general/enabled',
        'squeezely_plugin/general/SQZLY_id'              => 'squeezely/general/account_id',
        'squeezely_plugin/general/squeezely_api_key'     => 'squeezely/general/api_key',
        'squeezely_plugin/general/squeezely_webhook_key' => 'squeezely/general/webhook_key',
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection       $resourceConnection
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->changeConfigPaths();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Change config paths for fields due to changes in config options.
     */
    public function changeConfigPaths()
    {
        $connection = $this->resourceConnection->getConnection();
        foreach (self::FIELDS as $oldField => $newField) {
            $connection->update(
                $this->resourceConnection->getTableName('core_config_data'),
                ['path' => $newField],
                ['path = ?' => $oldField]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
