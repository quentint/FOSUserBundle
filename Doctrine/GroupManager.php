<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Doctrine;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\GroupManager as BaseGroupManager;

class GroupManager extends BaseGroupManager
{
    protected ObjectManager $objectManager;
    protected string$class;
    protected ObjectRepository $repository;

    public function __construct(ObjectManager $om, string $class)
    {
        $this->objectManager = $om;
        $this->repository = $om->getRepository($class);

        $metadata = $om->getClassMetadata($class);
        $this->class = $metadata->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGroup(GroupInterface $group): void
    {
        $this->objectManager->remove($group);
        $this->objectManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function findGroupBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findGroups()
    {
        return $this->repository->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function updateGroup(GroupInterface $group, $andFlush = true): void
    {
        $this->objectManager->persist($group);
        if ($andFlush) {
            $this->objectManager->flush();
        }
    }
}
