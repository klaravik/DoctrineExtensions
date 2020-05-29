<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathORMTest extends BaseTestCaseORM
{
    const CATEGORY = 'Tree\\Fixture\\MPCategory';
    const CATEGORY_APPENDID = 'Tree\\Fixture\\MPCategoryWithAppendIdOnIntegerSource';

    protected $config;
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);
    }

    /**
     * @test
     */
    public function insertUpdateAndRemove()
    {
        // Insert
        $category = $this->createCategory();
        $category->setTitle('1');
        $category2 = $this->createCategory();
        $category2->setTitle('2');
        $category3 = $this->createCategory();
        $category3->setTitle('3');
        $category4 = $this->createCategory();
        $category4->setTitle('4');

        $category2->setParent($category);
        $category3->setParent($category2);

        $this->em->persist($category4);
        $this->em->persist($category3);
        $this->em->persist($category2);
        $this->em->persist($category);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);
        $this->em->refresh($category4);

        $this->assertEquals($this->generatePath(['1' => $category->getId()]), $category->getPath());
        $this->assertEquals($this->generatePath(['1' => $category->getId(), '2' => $category2->getId()]), $category2->getPath());
        $this->assertEquals($this->generatePath(['1' => $category->getId(), '2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        $this->assertEquals($this->generatePath(['4' => $category4->getId()]), $category4->getPath());
        $this->assertEquals(1, $category->getLevel());
        $this->assertEquals(2, $category2->getLevel());
        $this->assertEquals(3, $category3->getLevel());
        $this->assertEquals(1, $category4->getLevel());

        $this->assertEquals('1-4', $category->getTreeRootValue());
        $this->assertEquals('1-4', $category2->getTreeRootValue());
        $this->assertEquals('1-4', $category3->getTreeRootValue());
        $this->assertEquals('4-1', $category4->getTreeRootValue());

        // Update
        $category2->setParent(null);

        $this->em->persist($category2);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);

        $this->assertEquals($this->generatePath(['1' => $category->getId()]), $category->getPath());
        $this->assertEquals($this->generatePath(['2' => $category2->getId()]), $category2->getPath());
        $this->assertEquals($this->generatePath(['2' => $category2->getId(), '3' => $category3->getId()]), $category3->getPath());
        $this->assertEquals(1, $category->getLevel());
        $this->assertEquals(1, $category2->getLevel());
        $this->assertEquals(2, $category3->getLevel());
        $this->assertEquals(1, $category4->getLevel());

        $this->assertEquals('1-4', $category->getTreeRootValue());
        $this->assertEquals('2-3', $category2->getTreeRootValue());
        $this->assertEquals('2-3', $category3->getTreeRootValue());
        $this->assertEquals('4-1', $category4->getTreeRootValue());

        // Remove
        $this->em->remove($category);
        $this->em->remove($category2);
        $this->em->flush();

        $result = $this->em->createQueryBuilder()->select('c')->from(self::CATEGORY, 'c')->getQuery()->execute();

        $firstResult = $result[0];

        $this->assertCount(1, $result);
        $this->assertEquals('4', $firstResult->getTitle());
        $this->assertEquals(1, $firstResult->getLevel());
        $this->assertEquals('4-1', $firstResult->getTreeRootValue());
    }

    /**
     * @test
     */
    public function useOfSeparatorInPathSourceShouldThrowAnException()
    {
        $this->expectException('Gedmo\Exception\RuntimeException');

        $category = $this->createCategory();
        $category->setTitle('1'.$this->config['path_separator']);

        $this->em->persist($category);
        $this->em->flush();
    }

    /**
     * @test
     */
    public function useAppendIdWhenTreeSourceIsOfTypeInteger()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY_APPENDID);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);

        // Insert
        $category = $this->createCategoryWithAppendIdAndTreeSourceAsInteger();
        $category->setIntegerSort(10);
        $category->setTitle('1');
        $category2 = $this->createCategoryWithAppendIdAndTreeSourceAsInteger();
        $category2->setIntegerSort(20);
        $category2->setTitle('2');
        $category3 = $this->createCategoryWithAppendIdAndTreeSourceAsInteger();
        $category3->setIntegerSort(30);
        $category3->setTitle('3');
        $category4 = $this->createCategoryWithAppendIdAndTreeSourceAsInteger();
        $category4->setIntegerSort(40);
        $category4->setTitle('4');

        $category2->setParent($category);
        $category3->setParent($category2);

        $this->em->persist($category4);
        $this->em->persist($category3);
        $this->em->persist($category2);
        $this->em->persist($category);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);
        $this->em->refresh($category4);

        $this->assertEquals($this->generatePath(['10' => $category->getId()]), $category->getPath());

        $this->assertEquals(
            $this->generatePath(['10' => $category->getId(), '20' => $category2->getId()]),
            $category2->getPath()
        );
        $this->assertEquals(
            $this->generatePath([
                '10' => $category->getId(),
                '20' => $category2->getId(),
                '30' => $category3->getId()
            ]),
            $category3->getPath()
        );

        $this->assertEquals($this->generatePath(['40' => $category4->getId()]), $category4->getPath());
        $this->assertEquals(1, $category->getLevel());
        $this->assertEquals(2, $category2->getLevel());
        $this->assertEquals(3, $category3->getLevel());
        $this->assertEquals(1, $category4->getLevel());

        $this->assertEquals('10-4', $category->getTreeRootValue());
        $this->assertEquals('10-4', $category2->getTreeRootValue());
        $this->assertEquals('10-4', $category3->getTreeRootValue());
        $this->assertEquals('40-1', $category4->getTreeRootValue());

        // Update
        $category2->setParent(null);

        $this->em->persist($category2);
        $this->em->flush();

        $this->em->refresh($category);
        $this->em->refresh($category2);
        $this->em->refresh($category3);

        $this->assertEquals($this->generatePath(['10' => $category->getId()]), $category->getPath());
        $this->assertEquals($this->generatePath(['20' => $category2->getId()]), $category2->getPath());
        $this->assertEquals($this->generatePath(['20' => $category2->getId(), '30' => $category3->getId()]),
            $category3->getPath());
        $this->assertEquals(1, $category->getLevel());
        $this->assertEquals(1, $category2->getLevel());
        $this->assertEquals(2, $category3->getLevel());
        $this->assertEquals(1, $category4->getLevel());

        $this->assertEquals('10-4', $category->getTreeRootValue());
        $this->assertEquals('20-3', $category2->getTreeRootValue());
        $this->assertEquals('20-3', $category3->getTreeRootValue());
        $this->assertEquals('40-1', $category4->getTreeRootValue());

        // Remove
        $this->em->remove($category);
        $this->em->remove($category2);
        $this->em->flush();

        $result = $this->em->createQueryBuilder()->select('c')->from(self::CATEGORY_APPENDID, 'c')->getQuery()->execute();

        $firstResult = $result[0];

        $this->assertCount(1, $result);
        $this->assertEquals('4', $firstResult->getTitle());
        $this->assertEquals(1, $firstResult->getLevel());
        $this->assertEquals('40-1', $firstResult->getTreeRootValue());
    }

    public function createCategory()
    {
        $class = self::CATEGORY;

        return new $class();
    }

    public function createCategoryWithAppendIdAndTreeSourceAsInteger()
    {
        $class = self::CATEGORY_APPENDID;

        return new $class();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::CATEGORY_APPENDID,
        ];
    }

    public function generatePath(array $sources)
    {
        $path = '';

        foreach ($sources as $p => $id) {
            $path .= $p.'-'.$id.$this->config['path_separator'];
        }

        return $path;
    }
}
