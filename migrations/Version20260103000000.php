<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add isActive column to skill table
 */
final class Version20260103000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isActive column to skill table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill DROP COLUMN is_active');
    }
}
