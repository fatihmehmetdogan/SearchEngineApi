<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240722000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for search engine';
    }

    public function up(Schema $schema): void
    {
        // Create documents table
        $this->addSql('CREATE TABLE documents (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            url VARCHAR(500) DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            tags JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX idx_title (title),
            INDEX idx_category (category),
            FULLTEXT INDEX ft_title_content (title, content)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create search_queries table
        $this->addSql('CREATE TABLE search_queries (
            id INT AUTO_INCREMENT NOT NULL,
            query_text VARCHAR(500) NOT NULL,
            filters JSON DEFAULT NULL,
            results_count INT DEFAULT 0 NOT NULL,
            execution_time NUMERIC(10, 4) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX idx_query (query_text),
            INDEX idx_created (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insert sample data
        $this->addSql("INSERT INTO documents (title, content, url, category, tags, created_at, updated_at) VALUES
            ('Symfony Framework', 'Symfony is a PHP framework for web applications and a set of reusable PHP components. It provides structure, components and tools to create complex web applications more efficiently.', 'https://symfony.com', 'Framework', '[\"php\", \"web\", \"framework\", \"backend\"]', NOW(), NOW()),
            ('Docker Container Technology', 'Docker is a platform for developing, shipping, and running applications using containerization technology. It allows developers to package applications with their dependencies.', 'https://docker.com', 'Technology', '[\"container\", \"devops\", \"deployment\", \"virtualization\"]', NOW(), NOW()),
            ('MySQL Database Management', 'MySQL is an open-source relational database management system. It is widely used for web applications and is a central component of the LAMP web application software stack.', 'https://mysql.com', 'Database', '[\"database\", \"mysql\", \"sql\", \"relational\"]', NOW(), NOW()),
            ('REST API Design Principles', 'REST (Representational State Transfer) is an architectural style for designing networked applications. It relies on stateless, client-server communication protocols.', 'https://example.com/rest', 'API', '[\"api\", \"rest\", \"web-service\", \"http\"]', NOW(), NOW()),
            ('Search Algorithms and Indexing', 'Various algorithms and techniques for implementing efficient search functionality in applications, including full-text search, indexing strategies, and relevance scoring.', 'https://example.com/search', 'Algorithm', '[\"search\", \"algorithm\", \"indexing\", \"performance\"]', NOW(), NOW()),
            ('PHP Programming Language', 'PHP is a popular general-purpose scripting language that is especially suited to web development. It powers many of the worlds most popular websites and frameworks.', 'https://php.net', 'Programming', '[\"php\", \"programming\", \"web\", \"scripting\"]', NOW(), NOW()),
            ('Web Development Best Practices', 'Modern web development involves various best practices including responsive design, performance optimization, security considerations, and maintainable code structure.', 'https://example.com/webdev', 'Development', '[\"web\", \"development\", \"best-practices\", \"frontend\"]', NOW(), NOW()),
            ('API Authentication Methods', 'Different methods for securing APIs including JWT tokens, OAuth, API keys, and other authentication mechanisms used in modern web services.', 'https://example.com/auth', 'Security', '[\"api\", \"authentication\", \"security\", \"jwt\", \"oauth\"]', NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE search_queries');
        $this->addSql('DROP TABLE documents');
    }
}
