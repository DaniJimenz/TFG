<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204165704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE routine_exercise (routine_id INT NOT NULL, exercise_id INT NOT NULL, PRIMARY KEY (routine_id, exercise_id))');
        $this->addSql('CREATE INDEX IDX_50CE302AF27A94C7 ON routine_exercise (routine_id)');
        $this->addSql('CREATE INDEX IDX_50CE302AE934951A ON routine_exercise (exercise_id)');
        $this->addSql('ALTER TABLE routine_exercise ADD CONSTRAINT FK_50CE302AF27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routine_exercise ADD CONSTRAINT FK_50CE302AE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routine ADD owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE routine ADD CONSTRAINT FK_4BF6D8D67E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_4BF6D8D67E3C61F9 ON routine (owner_id)');
        $this->addSql('ALTER TABLE training ADD app_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE training ADD exercise_id INT NOT NULL');
        $this->addSql('ALTER TABLE training ADD routine_id INT NOT NULL');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F4A3353D8 FOREIGN KEY (app_user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8FE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8FF27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D5128A8F4A3353D8 ON training (app_user_id)');
        $this->addSql('CREATE INDEX IDX_D5128A8FE934951A ON training (exercise_id)');
        $this->addSql('CREATE INDEX IDX_D5128A8FF27A94C7 ON training (routine_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE routine_exercise DROP CONSTRAINT FK_50CE302AF27A94C7');
        $this->addSql('ALTER TABLE routine_exercise DROP CONSTRAINT FK_50CE302AE934951A');
        $this->addSql('DROP TABLE routine_exercise');
        $this->addSql('ALTER TABLE routine DROP CONSTRAINT FK_4BF6D8D67E3C61F9');
        $this->addSql('DROP INDEX IDX_4BF6D8D67E3C61F9');
        $this->addSql('ALTER TABLE routine DROP owner_id');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT FK_D5128A8F4A3353D8');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT FK_D5128A8FE934951A');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT FK_D5128A8FF27A94C7');
        $this->addSql('DROP INDEX IDX_D5128A8F4A3353D8');
        $this->addSql('DROP INDEX IDX_D5128A8FE934951A');
        $this->addSql('DROP INDEX IDX_D5128A8FF27A94C7');
        $this->addSql('ALTER TABLE training DROP app_user_id');
        $this->addSql('ALTER TABLE training DROP exercise_id');
        $this->addSql('ALTER TABLE training DROP routine_id');
    }
}
