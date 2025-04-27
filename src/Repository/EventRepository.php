<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->setParameter('category', $category)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Get statistics about events
     * 
     * @return array An array containing event statistics
     */
    public function getStatistics(): array
    {
        $entityManager = $this->getEntityManager();
        $totalEvents = $this->count([]);
        
        // Get count of events by category
        $categoriesQuery = $entityManager->createQuery(
            'SELECT e.category, COUNT(e) as count 
            FROM App\Entity\Event e 
            GROUP BY e.category'
        );
        $categoriesResult = $categoriesQuery->getResult();
        
        // Get upcoming events (future events)
        $now = new \DateTime();
        $upcomingQuery = $entityManager->createQuery(
            'SELECT COUNT(e) as count 
            FROM App\Entity\Event e 
            WHERE e.startDate > :now'
        )->setParameter('now', $now);
        $upcomingCount = $upcomingQuery->getSingleScalarResult();
        
        // Get recent events (within the last 30 days)
        $thirtyDaysAgo = new \DateTime('-30 days');
        $recentQuery = $entityManager->createQuery(
            'SELECT COUNT(e) as count 
            FROM App\Entity\Event e 
            WHERE e.startDate BETWEEN :thirtyDaysAgo AND :now'
        )
        ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
        ->setParameter('now', $now);
        $recentEventsCount = $recentQuery->getSingleScalarResult();
        
        return [
            'totalEvents' => $totalEvents,
            'categoriesCount' => $categoriesResult,
            'upcomingEvents' => $upcomingCount,
            'recentEvents' => $recentEventsCount
        ];
    }
} 