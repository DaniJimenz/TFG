<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204154104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_allergen (user_id INT NOT NULL, allergen_id INT NOT NULL, PRIMARY KEY (user_id, allergen_id))');
        $this->addSql('CREATE INDEX IDX_C532ECCEA76ED395 ON user_allergen (user_id)');
        $this->addSql('CREATE INDEX IDX_C532ECCE6E775A4A ON user_allergen (allergen_id)');
        $this->addSql('ALTER TABLE user_allergen ADD CONSTRAINT FK_C532ECCEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_allergen ADD CONSTRAINT FK_C532ECCE6E775A4A FOREIGN KEY (allergen_id) REFERENCES allergen (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_allergen DROP CONSTRAINT FK_C532ECCEA76ED395');
        $this->addSql('ALTER TABLE user_allergen DROP CONSTRAINT FK_C532ECCE6E775A4A');
        $this->addSql('DROP TABLE user_allergen');
    }
}
