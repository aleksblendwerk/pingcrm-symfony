<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * @return array<Organization>
     */
    public function findAllByAccountOrderedByName(Account $account): array
    {
        return $this->findBy(['account' => $account], ['name' => 'ASC']);
    }

    /**
     * @return array<int, Organization>
     */
    public function findAllMatchingFilter(
        Account $account,
        ?string $search,
        ?string $trashed,
        ?int $limit,
        ?int $offset
    ): array {
        $qb = $this->createQueryBuilderForFilter($account, $search, $trashed);

        $qb
            ->addOrderBy('organization.name')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $qb->getQuery()->execute();

        if (!is_array($results)) {
            throw new \RuntimeException('Error retrieving filtered results');
        }

        return $results;
    }

    public function countAllMatchingFilter(Account $account, ?string $search, ?string $trashed): int
    {
        $qb = $this->createQueryBuilderForFilter($account, $search, $trashed);
        $count = $qb->select('COUNT(organization.id)')->getQuery()->getSingleScalarResult();

        if (!is_int($count)) {
            throw new \RuntimeException('Error counnting filtered results');
        }

        return $count;
    }

    protected function createQueryBuilderForFilter(Account $account, ?string $search, ?string $trashed): QueryBuilder
    {
        $qb = $this->createQueryBuilder('organization');

        if ($search !== null) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('organization.name', ':searchTerm')
                    )
                )
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        if ($trashed === null) {
            $qb->andWhere($qb->expr()->isNull('organization.deletedAt'));
        } elseif ($trashed === 'only') {
            $qb->andWhere($qb->expr()->isNotNull('organization.deletedAt'));
        }

        $qb
            ->andWhere('organization.account = :account')
            ->setParameter('account', $account);

        return $qb;
    }
}
