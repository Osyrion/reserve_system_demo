<?php

declare(strict_types=1);

namespace App\Infrastructure\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on stylist_id and start_time for barbershop_bookings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UQ_booking_stylist_start ON barbershop_bookings (stylist_id, start_time)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UQ_booking_stylist_start');
    }
}

