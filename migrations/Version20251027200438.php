<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251027200438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes on word and score columns in word_record table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE word_record (
            id SERIAL NOT NULL,
            word VARCHAR(255) NOT NULL,
            score INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX idx_word_record_word ON word_record (word)');
        $this->addSql('CREATE INDEX idx_word_record_score ON word_record (score)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_word_record_word');
        $this->addSql('DROP INDEX IF EXISTS idx_word_record_score');

        $this->addSql('DROP TABLE word_record');
    }
}
