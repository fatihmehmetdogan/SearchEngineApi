-- Initialize search engine database
CREATE DATABASE IF NOT EXISTS search_engine_db;
USE search_engine_db;

-- Create tables for search functionality
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    url VARCHAR(500),
    category VARCHAR(100),
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_category (category),
    FULLTEXT(title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS search_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_text VARCHAR(500) NOT NULL,
    filters JSON,
    results_count INT DEFAULT 0,
    execution_time DECIMAL(10,4),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_query (query_text),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO documents (title, content, url, category, tags) VALUES
('Symfony Framework', 'Symfony is a PHP framework for web applications and a set of reusable PHP components.', 'https://symfony.com', 'Framework', '["php", "web", "framework"]'),
('Docker Container', 'Docker is a platform for developing, shipping, and running applications using containerization.', 'https://docker.com', 'Technology', '["container", "devops", "deployment"]'),
('MySQL Database', 'MySQL is an open-source relational database management system.', 'https://mysql.com', 'Database', '["database", "mysql", "sql"]'),
('REST API Design', 'REST is an architectural style for designing networked applications.', 'https://example.com/rest', 'API', '["api", "rest", "web-service"]'),
('Search Algorithms', 'Various algorithms for implementing search functionality in applications.', 'https://example.com/search', 'Algorithm', '["search", "algorithm", "indexing"]');
