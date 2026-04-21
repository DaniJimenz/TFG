<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421193154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP CONSTRAINT fk_a3c664d34a3353d8');
        $this->addSql('DROP INDEX uniq_a3c664d34a3353d8');
        $this->addSql('ALTER TABLE subscription RENAME COLUMN app_user_id TO user_id');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D3A76ED395 ON subscription (user_id)');
        $this->addSql('ALTER TABLE "user" ALTER points_xp SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER continuity SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER level SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP CONSTRAINT FK_A3C664D3A76ED395');
        $this->addSql('DROP INDEX UNIQ_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription RENAME COLUMN user_id TO app_user_id');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT fk_a3c664d34a3353d8 FOREIGN KEY (app_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_a3c664d34a3353d8 ON subscription (app_user_id)');
        $this->addSql('ALTER TABLE "user" ALTER points_xp DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER continuity DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER level DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER created_at DROP NOT NULL');
    }
}
