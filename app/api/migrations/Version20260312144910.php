<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312144910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE form_revision (id UUID NOT NULL, schema JSON NOT NULL, version INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, form_id UUID NOT NULL, created_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DC5990885FF69B7D ON form_revision (form_id)');
        $this->addSql('CREATE INDEX IDX_DC599088B03A8386 ON form_revision (created_by_id)');
        $this->addSql('CREATE TABLE submission (id UUID NOT NULL, session_token VARCHAR(128) DEFAULT NULL, status VARCHAR(20) NOT NULL, answers JSON NOT NULL, score_total INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, form_id UUID NOT NULL, revision_id UUID NOT NULL, submitted_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DB055AF35FF69B7D ON submission (form_id)');
        $this->addSql('CREATE INDEX IDX_DB055AF31DFA7C8F ON submission (revision_id)');
        $this->addSql('CREATE INDEX IDX_DB055AF379F7D87D ON submission (submitted_by_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_submission_auth_draft ON submission (form_id, submitted_by_id) WHERE (status = \'draft\')');
        $this->addSql('CREATE UNIQUE INDEX uniq_submission_anon_draft ON submission (form_id, session_token) WHERE (status = \'draft\')');
        $this->addSql('ALTER TABLE form_revision ADD CONSTRAINT FK_DC5990885FF69B7D FOREIGN KEY (form_id) REFERENCES "form" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE form_revision ADD CONSTRAINT FK_DC599088B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF35FF69B7D FOREIGN KEY (form_id) REFERENCES "form" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF31DFA7C8F FOREIGN KEY (revision_id) REFERENCES form_revision (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF379F7D87D FOREIGN KEY (submitted_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE form ADD current_revision_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE form DROP schema');
        $this->addSql('ALTER TABLE form ADD CONSTRAINT FK_5288FD4FA32ED756 FOREIGN KEY (current_revision_id) REFERENCES form_revision (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_5288FD4FA32ED756 ON form (current_revision_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE form_revision DROP CONSTRAINT FK_DC5990885FF69B7D');
        $this->addSql('ALTER TABLE form_revision DROP CONSTRAINT FK_DC599088B03A8386');
        $this->addSql('ALTER TABLE submission DROP CONSTRAINT FK_DB055AF35FF69B7D');
        $this->addSql('ALTER TABLE submission DROP CONSTRAINT FK_DB055AF31DFA7C8F');
        $this->addSql('ALTER TABLE submission DROP CONSTRAINT FK_DB055AF379F7D87D');
        $this->addSql('DROP TABLE form_revision');
        $this->addSql('DROP TABLE submission');
        $this->addSql('ALTER TABLE "form" DROP CONSTRAINT FK_5288FD4FA32ED756');
        $this->addSql('DROP INDEX IDX_5288FD4FA32ED756');
        $this->addSql('ALTER TABLE "form" ADD schema JSON NOT NULL');
        $this->addSql('ALTER TABLE "form" DROP current_revision_id');
    }
}
