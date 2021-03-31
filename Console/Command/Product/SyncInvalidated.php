<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Console\Command\Product;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Squeezely\Plugin\Api\Config\System\StoreSyncInterface as ConfigRepository;
use Squeezely\Plugin\Api\Request\RepositoryInterface as RequestRepository;
use Squeezely\Plugin\Model\ItemsQueue\CollectionFactory as ItemsQueueCollectionFactory;
use Squeezely\Plugin\Model\ItemsQueue\ResourceModel as ItemsQueueResource;
use Squeezely\Plugin\Service\Product\GetData as ProductData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sync invalidated products via command line
 */
class SyncInvalidated extends Command
{

    /**
     * Command call name
     */
    const COMMAND_NAME = 'squeezely:product:sync-invalidated';

    /**
     * @var ItemsQueueResource
     */
    protected $itemsQueueResource;
    /**
     * @var RequestRepository
     */
    private $requestRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ProductData
     */
    private $productData;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var ItemsQueueCollectionFactory
     */
    private $itemsQueueCollectionFactory;

    /**
     * SyncInvalidated constructor.
     *
     * @param RequestRepository $requestRepository
     * @param ConfigRepository $configRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductData $productData
     * @param StoreRepositoryInterface $storeRepository
     * @param ItemsQueueCollectionFactory $itemsQueueCollection
     * @param ItemsQueueResource $itemsQueueResource
     */
    public function __construct(
        RequestRepository $requestRepository,
        ConfigRepository $configRepository,
        ProductCollectionFactory $productCollectionFactory,
        ProductData $productData,
        StoreRepositoryInterface $storeRepository,
        ItemsQueueCollectionFactory $itemsQueueCollection,
        ItemsQueueResource $itemsQueueResource
    ) {
        $this->requestRepository = $requestRepository;
        $this->configRepository = $configRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productData = $productData;
        $this->storeRepository = $storeRepository;
        $this->itemsQueueCollectionFactory = $itemsQueueCollection;
        $this->itemsQueueResource = $itemsQueueResource;
        parent::__construct();
    }

    /**
     *  {@inheritdoc}
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Squeezely: Sync product');
        parent::configure();
    }

    /**
     *  {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configRepository->isEnabled()) {
            $output->writeln(ConfigRepository::EXTENSION_DISABLED_ERROR);
            return 0;
        }

        $storeIds = $this->configRepository->getAllEnabledStoreIds();
        foreach ($storeIds as $storeId) {
            $itemsCollection = $this->itemsQueueCollectionFactory->create();
            $itemsCollection->addFieldToFilter('store_id', $storeId);
            if ($itemsCollection->getSize() == 0) {
                $output->writeln(
                    __(
                        '<info>Store %1: there is no invalidated products</info>',
                        $storeId
                    )
                );
                continue;
            }
            $productData = $this->productData->execute(
                $itemsCollection->getAllIds(),
                (int)$storeId
            );

            try {
                $response = $this->requestRepository->sendProducts(
                    ['products' => $productData]
                );
                if ($response['success'] == true) {
                    $output->writeln(
                        __(
                            '<info>Store %1, created %2, updated %3</info>',
                            $storeId,
                            $response['created'],
                            $response['updated']
                        )
                    );
                    $connection = $this->itemsQueueResource->getConnection();
                    $tableName = $this->itemsQueueResource->getTable('squeezely_items_queue');
                    $connection->delete($tableName, ['store_id' => $storeId]);
                } else {
                    foreach ($response['errors'] as $error) {
                        $output->writeln(sprintf('<error>%s</error>', $error));
                    }
                }
            } catch (\Exception $exception) {
                $output->writeln(sprintf('<error>Exception: %s</error>', $exception->getMessage()));
            }
        }

        return 0;
    }
}
