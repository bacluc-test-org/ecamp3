<?php

namespace App\Repository;

use App\Doctrine\QueryBuilderHelper;
use App\Entity\Day;
use App\Entity\DayResponsible;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|DayResponsible find($id, $lockMode = null, $lockVersion = null)
 * @method null|DayResponsible findOneBy(array $criteria, array $orderBy = null)
 * @method DayResponsible[]    findAll()
 * @method DayResponsible[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DayResponsibleRepository extends ServiceEntityRepository implements CanFilterByUserInterface {
    use FiltersByCampCollaboration;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, DayResponsible::class);
    }

    public function filterByUser(QueryBuilder $queryBuilder, User $user): void {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $dayQry = $queryBuilder->getEntityManager()->createQueryBuilder();
        $dayQry->from(Day::class, 'day')
            ->select('day')
            ->innerJoin('day.period', 'period')
            ->innerJoin('period.camp', 'camp')
        ;
        $this->filterByCampCollaboration($dayQry, $user);

        $queryBuilder->andWhere($queryBuilder->expr()->in("{$rootAlias}.day", $dayQry->getDQL()));
        QueryBuilderHelper::copyParameters($queryBuilder, $dayQry);
    }
}
