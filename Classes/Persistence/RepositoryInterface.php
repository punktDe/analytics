<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Persistence;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;

interface RepositoryInterface
{
    public function getEntityManager(): EntityManager;
}
