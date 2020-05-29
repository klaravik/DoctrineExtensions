<?php

namespace Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 * @Gedmo\Tree(type="materializedPath")
 */
class MPCategoryWithAppendIdOnIntegerSource
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Gedmo\TreePath(appendId=true)
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    private $path;

    /**
     * @Gedmo\TreePathHash
     * @ORM\Column(name="pathhash", type="string", length=32, nullable=true)
     */
    private $pathHash;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\TreePathSource
     * @ORM\Column(name="integer_sort", type="integer")
     */
    private $integerSort;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="MPCategoryWithAppendIdOnIntegerSource", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parentId;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    private $level;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="tree_root_value", type="string", nullable=true)
     */
    private $treeRootValue;

    /**
     * @ORM\OneToMany(targetEntity="MPCategoryWithAppendIdOnIntegerSource", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category")
     */
    private $comments;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setIntegerSort(int $sort)
    {
        $this->integerSort = $sort;
    }

    public function getIntegerSort()
    {
        return $this->integerSort;
    }

    public function setParent(MPCategoryWithAppendIdOnIntegerSource $parent = null)
    {
        $this->parentId = $parent;
    }

    public function getParent()
    {
        return $this->parentId;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getTreeRootValue()
    {
        return $this->treeRootValue;
    }

    public function getPathHash()
    {
        return $this->pathHash;
    }
}
