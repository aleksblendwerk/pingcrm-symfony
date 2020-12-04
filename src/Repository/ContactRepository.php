<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @return array<int, Contact>
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
            ->addOrderBy('contact.lastName')
            ->addOrderBy('contact.firstName')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->execute();
    }

    public function countAllMatchingFilter(Account $account, ?string $search, ?string $trashed): int
    {
        $qb = $this->createQueryBuilderForFilter($account, $search, $trashed);

        return (int) $qb->select('COUNT(contact.id)')->getQuery()->getSingleScalarResult();
    }

    protected function createQueryBuilderForFilter(Account $account, ?string $search, ?string $trashed): QueryBuilder
    {
        $qb = $this->createQueryBuilder('contact');

        if ($search !== null) {
            $qb
                ->leftJoin('contact.organization', 'organization')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('contact.firstName', ':searchTerm'),
                        $qb->expr()->like('contact.lastName', ':searchTerm'),
                        $qb->expr()->like('contact.email', ':searchTerm'),
                        $qb->expr()->like('organization.name', ':searchTerm')
                    )
                )
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        if ($trashed === null) {
            $qb->andWhere($qb->expr()->isNull('contact.deletedAt'));
        } elseif ($trashed === 'only') {
            $qb->andWhere($qb->expr()->isNotNull('contact.deletedAt'));
        }

        $qb
            ->andWhere('contact.account = :account')
            ->setParameter('account', $account);

        return $qb;
    }
}
