<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121103737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the session tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE performed_set (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, logged_at DATETIME NOT NULL, reps INTEGER DEFAULT NULL, seconds INTEGER DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, session_id INTEGER NOT NULL, exercise_id VARCHAR(60) NOT NULL, planned_id INTEGER DEFAULT NULL, CONSTRAINT FK_46B8E7F8613FECDF FOREIGN KEY (session_id) REFERENCES workout_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_46B8E7F8E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_46B8E7F82634E2A2 FOREIGN KEY (planned_id) REFERENCES session_exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_46B8E7F8613FECDF ON performed_set (session_id)');
        $this->addSql('CREATE INDEX IDX_46B8E7F8E934951A ON performed_set (exercise_id)');
        $this->addSql('CREATE INDEX IDX_46B8E7F82634E2A2 ON performed_set (planned_id)');
        $this->addSql('CREATE TABLE session_exercise (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, position INTEGER NOT NULL, sets INTEGER NOT NULL, reps INTEGER DEFAULT NULL, seconds INTEGER DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, session_id INTEGER NOT NULL, exercise_id VARCHAR(60) NOT NULL, CONSTRAINT FK_2576B574613FECDF FOREIGN KEY (session_id) REFERENCES workout_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2576B574E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2576B574613FECDF ON session_exercise (session_id)');
        $this->addSql('CREATE INDEX IDX_2576B574E934951A ON session_exercise (exercise_id)');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(255) NOT NULL, ended_at DATETIME DEFAULT NULL, paused_at DATETIME DEFAULT NULL, paused_seconds INTEGER NOT NULL, workout_name VARCHAR(120) NOT NULL, started_at DATETIME NOT NULL, workout_id INTEGER DEFAULT NULL, CONSTRAINT FK_AC82B97CA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AC82B97CA6CCCFC9 ON workout_session (workout_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE performed_set');
        $this->addSql('DROP TABLE session_exercise');
        $this->addSql('DROP TABLE workout_session');
    }
}
