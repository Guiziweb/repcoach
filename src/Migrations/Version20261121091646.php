<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121091646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the exercise table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE exercise (id VARCHAR(60) NOT NULL, name VARCHAR(60) NOT NULL, description CLOB NOT NULL, category VARCHAR(255) NOT NULL, body_part VARCHAR(255) NOT NULL, force_type VARCHAR(255) NOT NULL, mechanic VARCHAR(255) NOT NULL, difficulty VARCHAR(255) NOT NULL, primary_muscles CLOB NOT NULL, secondary_muscles CLOB NOT NULL, instructions CLOB NOT NULL, tags CLOB NOT NULL, goals CLOB NOT NULL, bodyweight BOOLEAN NOT NULL, unilateral BOOLEAN NOT NULL, met DOUBLE PRECISION NOT NULL, equipment VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exercise');
    }
}
