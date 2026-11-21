<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121100241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the workout tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workout (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_649FFB725E237E06 ON workout (name)');
        $this->addSql('CREATE TABLE workout_exercise (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, position INTEGER NOT NULL, sets INTEGER NOT NULL, reps INTEGER DEFAULT NULL, seconds INTEGER DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, workout_id INTEGER NOT NULL, exercise_id VARCHAR(60) NOT NULL, CONSTRAINT FK_76AB38AAA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_76AB38AAE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_76AB38AAA6CCCFC9 ON workout_exercise (workout_id)');
        $this->addSql('CREATE INDEX IDX_76AB38AAE934951A ON workout_exercise (exercise_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workout_exercise');
        $this->addSql('DROP TABLE workout');
    }
}
