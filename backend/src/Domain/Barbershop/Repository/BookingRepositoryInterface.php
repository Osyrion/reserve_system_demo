<?php

declare(strict_types=1);

namespace App\Domain\Barbershop\Repository;

use App\Domain\Barbershop\Entity\Booking;
use App\Domain\Barbershop\Exception\NotFoundException;
use App\Domain\ValueObject\Uuid;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

interface BookingRepositoryInterface
{
    /** @throws NotFoundException */
    public function getById(Uuid $id): Booking;

    /** @throws UniqueConstraintViolationException if the time slot was taken concurrently */
    public function save(Booking $booking): void;

    public function hasOverlappingBooking(Uuid $stylistId, DateTimeImmutable $start, DateTimeImmutable $end): bool;
}
