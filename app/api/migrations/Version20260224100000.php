<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename organization table to workspace; label→name; drop domain; add slug';
    }

    public function up(Schema $schema): void
    {
        // Drop the domain unique index before dropping the column
        $this->addSql('DROP INDEX UNIQ_C1EE637CA7A91E0B');
        // Rename table
        $this->addSql('ALTER TABLE organization RENAME TO workspace');
        // Rename label → name, drop domain, add slug
        $this->addSql('ALTER TABLE workspace RENAME COLUMN label TO name');
        $this->addSql('ALTER TABLE workspace DROP COLUMN domain');
        $this->addSql('ALTER TABLE workspace ADD slug VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_workspace_slug ON workspace (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_workspace_slug');
        $this->addSql('ALTER TABLE workspace DROP COLUMN slug');
        $this->addSql('ALTER TABLE workspace ADD domain VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace RENAME COLUMN name TO label');
        $this->addSql('ALTER TABLE workspace RENAME TO organization');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1EE637CA7A91E0B ON organization (domain)');
    }
}
