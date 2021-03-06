<?php

/*
 * This file is part of the LyraContentBundle package.
 *
 * Copyright 2011 Massimo Giagnoni <gimassimo@gmail.com>
 *
 * This source file is subject to the MIT license. Full copyright and license
 * information are in the LICENSE file distributed with this source code.
 */

namespace Lyra\ContentBundle\Entity;

use Doctrine\ORM\EntityManager;
use Lyra\ContentBundle\Model\PageInterface;
use Lyra\ContentBundle\Model\PageManager as AbstractPageManager;

/**
 * Manager of page entities
 */
class PageManager extends AbstractPageManager
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Doctrine\ORM\EntityRepository;
     */
    protected $repository;

    /**
     * @var string entity class name
     */
    protected $class;

    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->repository = $em->getRepository($class);
        $this->class = $class;
    }

    public function findPage($pageId)
    {
        return $this->repository->find($pageId);
    }

    /**
     * {@inheritdoc}
     */
    public function findPageByNodeId($nodeId)
    {
        $qb = $this->repository
            ->createQueryBuilder('p')
            ->select('p, n')
            ->innerJoin('p.node', 'n')
            ->where('n.id = :nid')
            ->setParameter('nid', $nodeId);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function savePage(PageInterface $page)
    {
        $create = null === $page->getId();
        $this->em->getConnection()->beginTransaction();

        try {
            $this->em->persist($page);
            $this->em->flush();

            if ($create) {
                $this->getNodeManager()->createItemLink('page', $page);
            }

            $this->em->flush();
            $this->em->getConnection()->commit();

        } catch(\Exception $e) {

            cDump($e);

            $this->em->getConnection()->rollback();
            $this->em->close();

            return false;
        }

        return true;
    }

    public function getClass()
    {
        return $this->class;
    }
}
