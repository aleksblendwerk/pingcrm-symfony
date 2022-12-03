<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array<int, User>
     */
    public function findAllMatchingFilter(
        Account $account,
        ?string $search,
        ?string $role,
        ?string $trashed
    ): array {
        $results = $this->createQueryBuilderForFilter($account, $search, $role, $trashed)
            ->addOrderBy('user.lastName')
            ->addOrderBy('user.firstName')
            ->getQuery()
            ->execute();

        if (!is_array($results)) {
            throw new \RuntimeException('Error retrieving filtered results');
        }

        return $results;
    }

    protected function createQueryBuilderForFilter(
        Account $account,
        ?string $search,
        ?string $role,
        ?string $trashed
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('user');

        if ($search !== null) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('user.firstName', ':searchTerm'),
                        $qb->expr()->like('user.lastName', ':searchTerm'),
                        $qb->expr()->like('user.email', ':searchTerm')
                    )
                )
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        if (in_array($role, ['user', 'owner'], true)) {
            $qb
                ->andWhere('user.owner = :role')
                ->setParameter('role', $role === 'owner');
        }

        if ($trashed === null) {
            $qb->andWhere($qb->expr()->isNull('user.deletedAt'));
        } elseif ($trashed === 'only') {
            $qb->andWhere($qb->expr()->isNotNull('user.deletedAt'));
        }

        $qb
            ->andWhere('user.account = :account')
            ->setParameter('account', $account);

        return $qb;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newEncodedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
