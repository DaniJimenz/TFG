<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204175512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meal ADD app_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE meal ADD CONSTRAINT FK_9EF68E9C4A3353D8 FOREIGN KEY (app_user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_9EF68E9C4A3353D8 ON meal (app_user_id)');
        $this->addSql('ALTER TABLE meal_food ADD meal_id INT NOT NULL');
        $this->addSql('ALTER TABLE meal_food ADD food_id INT NOT NULL');
        $this->addSql('ALTER TABLE meal_food ADD CONSTRAINT FK_CEE6FA03639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE meal_food ADD CONSTRAINT FK_CEE6FA03BA8E87C4 FOREIGN KEY (food_id) REFERENCES food (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_CEE6FA03639666D6 ON meal_food (meal_id)');
        $this->addSql('CREATE INDEX IDX_CEE6FA03BA8E87C4 ON meal_food (food_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meal DROP CONSTRAINT FK_9EF68E9C4A3353D8');
        $this->addSql('DROP INDEX IDX_9EF68E9C4A3353D8');
        $this->addSql('ALTER TABLE meal DROP app_user_id');
        $this->addSql('ALTER TABLE meal_food DROP CONSTRAINT FK_CEE6FA03639666D6');
        $this->addSql('ALTER TABLE meal_food DROP CONSTRAINT FK_CEE6FA03BA8E87C4');
        $this->addSql('DROP INDEX IDX_CEE6FA03639666D6');
        $this->addSql('DROP INDEX IDX_CEE6FA03BA8E87C4');
        $this->addSql('ALTER TABLE meal_food DROP meal_id');
        $this->addSql('ALTER TABLE meal_food DROP food_id');
    }
}
