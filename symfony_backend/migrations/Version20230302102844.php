<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230302102844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (email VARCHAR(64) NOT NULL, usergroup VARCHAR(16) NOT NULL, id VARCHAR(21) DEFAULT NULL, password VARCHAR(60) NOT NULL, name VARCHAR(64) NOT NULL, zip INT NOT NULL, place VARCHAR(64) NOT NULL, phone VARCHAR(15) NOT NULL, INDEX IDX_8D93D6494A647817 (usergroup), PRIMARY KEY(email)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (name VARCHAR(16) NOT NULL, priv_login TINYINT(1) NOT NULL, priv_delete TINYINT(1) NOT NULL, edit_own_cred TINYINT(1) NOT NULL, edit_own_pass TINYINT(1) NOT NULL, edit_own_priv TINYINT(1) NOT NULL, edit_oth_cred TINYINT(1) NOT NULL, edit_oth_pass TINYINT(1) NOT NULL, edit_oth_priv TINYINT(1) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494A647817 FOREIGN KEY (usergroup) REFERENCES user_group (name)');
        $this->addSql('ALTER TABLE userdata DROP FOREIGN KEY userdata_ibfk_1');
        $this->addSql('DROP TABLE userdata');
        $this->addSql('DROP TABLE usergroups');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE userdata (email VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, usergroup VARCHAR(16) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, id VARCHAR(21) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, name VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, zip INT UNSIGNED NOT NULL, place VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX id (id), INDEX privileges (usergroup), PRIMARY KEY(email)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE usergroups (name VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, priv_login TINYINT(1) DEFAULT NULL, edit_own_cred TINYINT(1) NOT NULL, edit_own_pass TINYINT(1) NOT NULL, edit_own_priv TINYINT(1) NOT NULL, edit_oth_cred TINYINT(1) NOT NULL, edit_oth_pass TINYINT(1) NOT NULL, edit_oth_priv TINYINT(1) NOT NULL, priv_delete TINYINT(1) DEFAULT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE userdata ADD CONSTRAINT userdata_ibfk_1 FOREIGN KEY (usergroup) REFERENCES usergroups (name)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6494A647817');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
