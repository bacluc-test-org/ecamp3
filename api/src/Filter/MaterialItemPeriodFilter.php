<?php

namespace App\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Activity;
use App\Entity\MaterialItem;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class MaterialItemPeriodFilter extends AbstractContextAwareFilter {
    public const PERIOD_QUERY_NAME = 'period';

    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array {
        $description = [];
        $description['period'] = [
            'property' => self::PERIOD_QUERY_NAME,
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
        ];

        return $description;
    }

    protected function filterProperty(string $property, $value, QueryBuilder $q, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null) {
        if (MaterialItem::class !== $resourceClass) {
            throw new \Exception("MaterialItemPeriodFilter can only be applies to entities of type MaterialItem (received: {$resourceClass}).");
        }

        if (self::PERIOD_QUERY_NAME !== $property) {
            return;
        }

        // load period from query parameter value
        $period = $this->iriConverter->getItemfromIri($value);

        // generate alias to avoid interference with other filters
        $periodParameterName = $queryNameGenerator->generateParameterName($property);
        $materialNodeJoinAlias = $queryNameGenerator->generateJoinAlias('materialNode');
        $rootJoinAlias = $queryNameGenerator->generateJoinAlias('root');
        $ownerJoinAlias = $queryNameGenerator->generateJoinAlias('owner');
        $activityJoinAlias = $queryNameGenerator->generateJoinAlias('activity');
        $scheduleEntryJoinAlias = $queryNameGenerator->generateJoinAlias('scheduleEntry');

        $rootAlias = $q->getRootAliases()[0];

        // build relations for materialItems attached via activities
        $q
            ->leftJoin("{$rootAlias}.materialNode", $materialNodeJoinAlias)
            ->leftJoin("{$materialNodeJoinAlias}.root", $rootJoinAlias)
            ->leftJoin("{$rootJoinAlias}.owner", $ownerJoinAlias)
            ->leftJoin(Activity::class, $activityJoinAlias, Join::WITH, "{$activityJoinAlias}.id = {$ownerJoinAlias}.id")
            ->leftJoin("{$activityJoinAlias}.scheduleEntries", $scheduleEntryJoinAlias)
        ;

        $q->andWhere($q->expr()->orX(
            $q->expr()->eq("{$rootAlias}.period", ":{$periodParameterName}"),              // item directly attached to Period
            $q->expr()->eq("{$scheduleEntryJoinAlias}.period", ":{$periodParameterName}")  // item part of scheduleEntry in Period
        ));

        $q->setParameter($periodParameterName, $period);
    }
}
