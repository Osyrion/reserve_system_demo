<?php

declare(strict_types=1);

namespace App\Application\Barbershop\Command\CreateBooking;

use App\Application\CommandResult;
use App\Domain\Barbershop\Entity\Booking;
use App\Domain\Barbershop\Entity\Service;
use App\Domain\Barbershop\Entity\Stylist;
use App\Domain\Barbershop\Repository\BookingRepositoryInterface;
use App\Domain\ValueObject\UuidFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class CreateBookingCommandHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private EntityManagerInterface $em,
        private UuidFactory $uuidFactory,
    ) {}

    public function handle(CreateBookingCommand $command): CommandResult
    {
        $service = $this->em->find(Service::class, $this->uuidFactory->fromString($command->serviceId))
            ?? throw new DomainException("Service {$command->serviceId} not found");

        $stylist = $this->em->find(Stylist::class, $this->uuidFactory->fromString($command->stylistId))
            ?? throw new DomainException("Stylist {$command->stylistId} not found");

        $start = new DateTimeImmutable($command->startTime);
        $end   = $start->modify("+{$service->getDurationMinutes()} minutes");

        if ($this->bookingRepository->hasOverlappingBooking(
            $this->uuidFactory->fromString($command->stylistId), $start, $end
        )) {
            throw new DomainException('This time slot is no longer available.');
        }

        $booking = new Booking(
            $this->uuidFactory->generate(),
            $service,
            $stylist,
            $start,
            $end,
            $command->customerName,
            $command->customerContact,
        );

        try {
            $this->bookingRepository->save($booking);
        } catch (UniqueConstraintViolationException $e) {
            throw new DomainException('This time slot is no longer available.', previous: $e);
        }

        return new CommandResult($booking->getId()->toString());
    }
}
