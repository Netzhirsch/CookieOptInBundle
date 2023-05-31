<?php

namespace Netzhirsch\CookieOptInBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mvo\ContaoGroupWidget\Entity\AbstractGroupEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'tl_ncoi_cookie_tool_container')]
class CookieToolContainer extends AbstractGroupEntity
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

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: CookieTool::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected $elements;
}
