<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312214939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE form_revision ADD title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE form_revision ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE form_revision ADD status VARCHAR(20) DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_submission_anon_draft');
        $this->addSql('DROP INDEX uniq_submission_auth_draft');
        $this->addSql('CREATE UNIQUE INDEX uniq_submission_anon_draft ON submission (form_id, session_token) WHERE (status = \'draft\')');
        $this->addSql('CREATE UNIQUE INDEX uniq_submission_auth_draft ON submission (form_id, submitted_by_id) WHERE (status = \'draft\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE form_revision DROP title');
        $this->addSql('ALTER TABLE form_revision DROP description');
        $this->addSql('ALTER TABLE form_revision DROP status');
        $this->addSql('DROP INDEX uniq_submission_auth_draft');
        $this->addSql('DROP INDEX uniq_submission_anon_draft');
        $this->addSql('CREATE UNIQUE INDEX uniq_submission_auth_draft ON submission (form_id, submitted_by_id) WHERE ((status)::text = \'draft\'::text)');
        $this->addSql('CREATE UNIQUE INDEX uniq_submission_anon_draft ON submission (form_id, session_token) WHERE ((status)::text = \'draft\'::text)');
    }
}
