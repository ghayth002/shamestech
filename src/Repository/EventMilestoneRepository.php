<?php

namespace App\Repository;

use App\Entity\EventMilestone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventMilestone>
 *
 * @method EventMilestone|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventMilestone|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventMilestone[]    findAll()
 * @method EventMilestone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventMilestoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventMilestone::class);
    }

    public function save(EventMilestone $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventMilestone $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEventId(int $eventId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.event', 'e')
            ->andWhere('e.id = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('m.expectedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', $status)
            ->orderBy('m.expectedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 