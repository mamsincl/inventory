<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\Framework\MultiDimensionalIndexer\Alias;
use Magento\Framework\MultiDimensionalIndexer\IndexHandlerInterface;
use Magento\Framework\MultiDimensionalIndexer\IndexNameBuilder;
use Magento\Framework\MultiDimensionalIndexer\IndexStructureInterface;
use Magento\Framework\MultiDimensionalIndexer\IndexTableSwitcherInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Indexer\Stock\GetAllAssignedStockIds;
use Magento\InventoryConfigurableProductIndexer\Indexer\Stock\IndexDataByStockIdProvider;

class StockIndexer
{
    /**
     * @var GetAllAssignedStockIds
     */
    private $getAllAssignedStockIds;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var IndexHandlerInterface
     */
    private $indexHandler;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexDataByStockIdProvider
     */
    private $indexDataByStockIdProvider;

    /**
     * @var IndexTableSwitcherInterface
     */
    private $indexTableSwitcher;

    /**
     * $indexStructure is reserved name for construct variable in index internal mechanism
     *
     * @param GetAllAssignedStockIds $getAllAssignedStockIds
     * @param IndexStructureInterface $indexStructure
     * @param IndexHandlerInterface $indexHandler
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexDataByStockIdProvider $indexDataByStockIdProvider
     * @param IndexTableSwitcherInterface $indexTableSwitcher
     */
    public function __construct(
        GetAllAssignedStockIds $getAllAssignedStockIds,
        IndexStructureInterface $indexStructure,
        IndexHandlerInterface $indexHandler,
        IndexNameBuilder $indexNameBuilder,
        IndexDataByStockIdProvider $indexDataByStockIdProvider,
        IndexTableSwitcherInterface $indexTableSwitcher
    ) {
        $this->getAllAssignedStockIds = $getAllAssignedStockIds;
        $this->indexStructure = $indexStructure;
        $this->indexHandler = $indexHandler;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexDataByStockIdProvider = $indexDataByStockIdProvider;
        $this->indexTableSwitcher = $indexTableSwitcher;
    }

    /**
     * @return void
     * @throws StateException
     */
    public function executeFull()
    {
        $stockIds = $this->getAllAssignedStockIds->execute();
        $this->executeList($stockIds);
    }

    /**
     * @param int $stockId
     * @return void
     * @throws StateException
     */
    public function executeRow(int $stockId)
    {
        $this->executeList([$stockId]);
    }

    /**
     * @param array $stockIds
     * @return void
     * @throws StateException
     */
    public function executeList(array $stockIds)
    {
        foreach ($stockIds as $stockId) {
            $mainIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();

            if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
                $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            }

            $this->indexHandler->saveIndex(
                $mainIndexName,
                $this->indexDataByStockIdProvider->execute((int)$stockId),
                ResourceConnection::DEFAULT_CONNECTION
            );
        }
    }
}
