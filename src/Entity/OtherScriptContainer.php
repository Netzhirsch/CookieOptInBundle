<?php

namespace Netzhirsch\CookieOptInBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mvo\ContaoGroupWidget\Entity\AbstractGroupEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'tl_ncoi_other_script_container')]
class OtherScriptContainer extends AbstractGroupEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(type: 'integer')]
    protected $sourceId;

    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'string')]
    protected $sourceTable;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: OtherScript::class,orphanRemoval: true)]
    protected $elements;

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId): void
    {
        $this->sourceId = $sourceId;
    }


    /**
     * @param mixed $sourceTable
     */
    public function setSourceTable($sourceTable): void
    {
        $this->sourceTable = $sourceTable;
    }
}
