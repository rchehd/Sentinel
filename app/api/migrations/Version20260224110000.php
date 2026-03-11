<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workspace_member pivot table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workspace_member (
            id UUID NOT NULL,
            workspace_id UUID NOT NULL,
            user_id UUID NOT NULL,
            role VARCHAR(20) NOT NULL,
            joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uq_workspace_user ON workspace_member (workspace_id, user_id)');
        $this->addSql('CREATE INDEX IDX_workspace_member_workspace ON workspace_member (workspace_id)');
        $this->addSql('CREATE INDEX IDX_workspace_member_user ON workspace_member (user_id)');
        $this->addSql('ALTER TABLE workspace_member ADD CONSTRAINT FK_workspace_member_workspace FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspace_member ADD CONSTRAINT FK_workspace_member_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_member DROP CONSTRAINT FK_workspace_member_workspace');
        $this->addSql('ALTER TABLE workspace_member DROP CONSTRAINT FK_workspace_member_user');
        $this->addSql('DROP TABLE workspace_member');
    }
}
