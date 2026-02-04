<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204174724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_achievement ADD app_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_achievement ADD achievement_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_achievement ADD CONSTRAINT FK_3F68B6644A3353D8 FOREIGN KEY (app_user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE user_achievement ADD CONSTRAINT FK_3F68B664B3EC99FE FOREIGN KEY (achievement_id) REFERENCES achievement (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_3F68B6644A3353D8 ON user_achievement (app_user_id)');
        $this->addSql('CREATE INDEX IDX_3F68B664B3EC99FE ON user_achievement (achievement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_achievement DROP CONSTRAINT FK_3F68B6644A3353D8');
        $this->addSql('ALTER TABLE user_achievement DROP CONSTRAINT FK_3F68B664B3EC99FE');
        $this->addSql('DROP INDEX IDX_3F68B6644A3353D8');
        $this->addSql('DROP INDEX IDX_3F68B664B3EC99FE');
        $this->addSql('ALTER TABLE user_achievement DROP app_user_id');
        $this->addSql('ALTER TABLE user_achievement DROP achievement_id');
    }
}
