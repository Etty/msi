<?php
namespace Magento\Inventory\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceResource;
use Magento\Inventory\Model\ResourceModel\SourceItem\Collection as SourceItemCollection;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory as SourceItemCollectionFactory;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItemRepository;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterfaceFactory;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceItemRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceSource;

    /**
     * @var SourceItemInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceItemFactory;

    /**
     * @var CollectionProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessor;

    /**
     * @var SourceItemCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceItemCollectionFactory;

    /**
     * @var SourceItemSearchResultsInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceItemSearchResultsFactory;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var SourceItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceItem;

    /**
     * @var SourceItemRepository
     */
    private $sourceItemRepository;

    protected function setUp()
    {
        $this->resourceSource = $this->getMockBuilder(SourceResource::class)->disableOriginalConstructor()->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceItemFactory = $this->getMockBuilder(SourceItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionProcessor = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->setMethods(['process'])
            ->getMock();
        $this->sourceItemCollectionFactory = $this->getMockBuilder(SourceItemCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sourceItemSearchResultsFactory = $this->getMockBuilder(SourceItemSearchResultsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $this->sourceItem = $this->getMockBuilder(SourceItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->sourceItemRepository = $objectManager->getObject(
            SourceItemRepository::class,
            [
                'resourceSource' => $this->resourceSource,
                'sourceItemFactory' => $this->sourceItemFactory,
                'collectionProcessor' => $this->collectionProcessor,
                'sourceItemCollectionFactory' => $this->sourceItemCollectionFactory,
                'sourceItemSearchResultsFactory' => $this->sourceItemSearchResultsFactory,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'logger' => $this->logger,
            ]
        );
    }

    public function testGet()
    {
        $sourceItemId = 345;

        $this->sourceItem
            ->expects($this->once())
            ->method('getSourceItemId')
            ->willReturn($sourceItemId);
        $this->sourceItemFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sourceItem);
        $this->resourceSource
            ->expects($this->once())
            ->method('load')
            ->with($this->sourceItem, $sourceItemId, SourceItemInterface::SOURCE_ITEM_ID);

        self::assertSame($this->sourceItem, $this->sourceItemRepository->get($sourceItemId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetIfEntityIsNotFound()
    {
        $sourceItemId = 0;

        $this->sourceItem
            ->expects($this->once())
            ->method('getSourceItemId')
            ->willReturn(null);
        $this->sourceItemFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sourceItem);
        $this->resourceSource->expects($this->once())
            ->method('load')
            ->with(
                $this->sourceItem,
                $sourceItemId,
                SourceItemInterface::SOURCE_ITEM_ID
            );

        $this->sourceItemRepository->get($sourceItemId);
    }

    public function testGetListWithSearchCriteria()
    {
        $items = [
            $this->getMockBuilder(SourceItemInterface::class)->getMock(),
            $this->getMockBuilder(SourceItemInterface::class)->getMock()
        ];
        $totalCount = 2;
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->getMock();

        $sourceItemCollection = $this->getMockBuilder(SourceItemCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sourceItemCollection
            ->expects($this->once())
            ->method('getItems')
            ->willReturn($items);
        $sourceItemCollection
            ->expects($this->once())
            ->method('getSize')
            ->willReturn($totalCount);
        $this->sourceItemCollectionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($sourceItemCollection);

        $searchResults = $this->getMockBuilder(SourceItemSearchResultsInterface::class)
            ->getMock();
        $searchResults
            ->expects($this->once())
            ->method('setItems')
            ->with($items);
        $searchResults
            ->expects($this->once())
            ->method('setTotalCount')
            ->with($totalCount);
        $searchResults
            ->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteria);
        $this->sourceItemSearchResultsFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchResults);

        $this->collectionProcessor
            ->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $sourceItemCollection);

        self::assertSame($searchResults, $this->sourceItemRepository->getList($searchCriteria));
    }

    public function testDelete()
    {
        $sourceItemId = 345;

        $this->sourceItem
            ->expects($this->once())
            ->method('getSourceItemId')
            ->willReturn($sourceItemId);
        $this->sourceItemFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sourceItem);
        $this->resourceSource
            ->expects($this->once())
            ->method('load')
            ->with($this->sourceItem, $sourceItemId, SourceItemInterface::SOURCE_ITEM_ID);

        $this->resourceSource
            ->expects($this->once())
            ->method('delete')
            ->with($this->sourceItem);

        $this->sourceItemRepository->delete($sourceItemId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testDeleteIfEntityIsNotFound()
    {
        $sourceItemId = 0;

        $this->sourceItem
            ->expects($this->once())
            ->method('getSourceItemId')
            ->willReturn(null);
        $this->sourceItemFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sourceItem);
        $this->resourceSource->expects($this->once())
            ->method('load')
            ->with(
                $this->sourceItem,
                $sourceItemId,
                SourceItemInterface::SOURCE_ITEM_ID
            );

        $this->sourceItemRepository->delete($sourceItemId);
    }
}