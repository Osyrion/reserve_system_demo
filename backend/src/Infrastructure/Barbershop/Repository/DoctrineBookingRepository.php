<?php

declare(strict_types=1);

namespace App\Infrastructure\Barbershop\Repository;

use App\Domain\Barbershop\Entity\Booking;
use App\Domain\Barbershop\Enum\BookingStatus;
use App\Domain\Barbershop\Exception\NotFoundException;
use App\Domain\Barbershop\Repository\BookingRepositoryInterface;
use App\Domain\ValueObject\Uuid;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineBookingRepository implements BookingRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getById(Uuid $id): Booking
    {
        return $this->em->find(Booking::class, $id)
            ?? throw new NotFoundException("Booking {$id} not found");
    }

    public function save(Booking $booking): void
    {
        $this->em->persist($booking);
        $this->em->flush();
    }

    public function hasOverlappingBooking(Uuid $stylistId, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(Booking::class, 'b')
            ->where('b.stylist = :stylist')
            ->andWhere('b.startTime < :end')
            ->andWhere('b.endTime > :start')
            ->andWhere('b.status != :rejected')
            ->setParameter('stylist', $stylistId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('rejected', BookingStatus::Rejected->value)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}

