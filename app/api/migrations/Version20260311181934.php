<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311181934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE form ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE form ALTER schema DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_form_workspace_id RENAME TO IDX_5288FD4F82D40A1F');
        $this->addSql('ALTER INDEX idx_form_created_by_id RENAME TO IDX_5288FD4FB03A8386');
        $this->addSql('DROP INDEX uniq_c1ee637cea750e8');
        $this->addSql('ALTER INDEX uniq_workspace_slug RENAME TO UNIQ_8D940019989D9B62');
        $this->addSql('ALTER INDEX idx_workspace_member_workspace RENAME TO IDX_40242BD082D40A1F');
        $this->addSql('ALTER INDEX idx_workspace_member_user RENAME TO IDX_40242BD0A76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "form" ALTER status SET DEFAULT \'draft\'');
        $this->addSql('ALTER TABLE "form" ALTER schema SET DEFAULT \'[]\'');
        $this->addSql('ALTER INDEX idx_5288fd4f82d40a1f RENAME TO idx_form_workspace_id');
        $this->addSql('ALTER INDEX idx_5288fd4fb03a8386 RENAME TO idx_form_created_by_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_c1ee637cea750e8 ON workspace (name)');
        $this->addSql('ALTER INDEX uniq_8d940019989d9b62 RENAME TO uniq_workspace_slug');
        $this->addSql('ALTER INDEX idx_40242bd082d40a1f RENAME TO idx_workspace_member_workspace');
        $this->addSql('ALTER INDEX idx_40242bd0a76ed395 RENAME TO idx_workspace_member_user');
    }
}
