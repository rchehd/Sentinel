<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop user.organization_id FK; add user.must_change_password';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D64932C8A3DE');
        $this->addSql('DROP INDEX IDX_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE "user" DROP COLUMN organization_id');
        $this->addSql('ALTER TABLE "user" ADD must_change_password BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN must_change_password');
        $this->addSql('ALTER TABLE "user" ADD organization_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8D93D64932C8A3DE ON "user" (organization_id)');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES workspace (id) NOT DEFERRABLE');
    }
}
