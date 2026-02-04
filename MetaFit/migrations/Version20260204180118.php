<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204180118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise_training ADD routine_id INT NOT NULL');
        $this->addSql('ALTER TABLE exercise_training ADD exercise_id INT NOT NULL');
        $this->addSql('ALTER TABLE exercise_training ADD CONSTRAINT FK_D37E2A67F27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE exercise_training ADD CONSTRAINT FK_D37E2A67E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D37E2A67F27A94C7 ON exercise_training (routine_id)');
        $this->addSql('CREATE INDEX IDX_D37E2A67E934951A ON exercise_training (exercise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise_training DROP CONSTRAINT FK_D37E2A67F27A94C7');
        $this->addSql('ALTER TABLE exercise_training DROP CONSTRAINT FK_D37E2A67E934951A');
        $this->addSql('DROP INDEX IDX_D37E2A67F27A94C7');
        $this->addSql('DROP INDEX IDX_D37E2A67E934951A');
        $this->addSql('ALTER TABLE exercise_training DROP routine_id');
        $this->addSql('ALTER TABLE exercise_training DROP exercise_id');
    }
}
