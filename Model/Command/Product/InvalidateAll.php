<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Command\Product;

use Squeezely\Plugin\Api\Config\RepositoryInterface as ConfigRepository;
use Squeezely\Plugin\Service\Invalidate\ByStore as InvalidateByStore;

/**
 * Invalidate all products command model
 */
class InvalidateAll
{

    /**
     * @var InvalidateByStore
     */
    protected $invalidateByStore;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * InvalidateAll constructor.
     *
     * @param ConfigRepository $configRepository
     * @param InvalidateByStore $invalidateByStore
     */
    public function __construct(
        ConfigRepository $configRepository,
        InvalidateByStore $invalidateByStore
    ) {
        $this->configRepository = $configRepository;
        $this->invalidateByStore = $invalidateByStore;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        if (!$this->configRepository->isStoreSyncEnabled()) {
            return [
                [
                    'success' => false,
                    'msg' => sprintf('<error>%s</error>', ConfigRepository::EXTENSION_DISABLED_ERROR)
                ]
            ];
        }
        $result = [];
        $storeIds = $this->configRepository->getAllEnabledStoreSyncStoreIds();
        foreach ($storeIds as $storeId) {
            $result[] = $this->invalidateByStore->execute((int)$storeId);
        }
        return $result;
    }
}
