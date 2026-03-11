<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create form table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "form" (
            id UUID NOT NULL,
            workspace_id UUID NOT NULL,
            created_by_id UUID DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'draft\',
            schema JSON NOT NULL DEFAULT \'[]\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX IDX_form_workspace_id ON "form" (workspace_id)');
        $this->addSql('CREATE INDEX IDX_form_created_by_id ON "form" (created_by_id)');
        $this->addSql('ALTER TABLE "form" ADD CONSTRAINT FK_form_workspace FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "form" ADD CONSTRAINT FK_form_created_by FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "form" DROP CONSTRAINT FK_form_workspace');
        $this->addSql('ALTER TABLE "form" DROP CONSTRAINT FK_form_created_by');
        $this->addSql('DROP TABLE "form"');
    }
}
