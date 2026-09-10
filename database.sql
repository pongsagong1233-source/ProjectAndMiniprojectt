CREATE DATABASE IF NOT EXISTS project_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE project_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category ENUM('Project', 'Mini Project') NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    advisor_name VARCHAR(255) NOT NULL,
    description TEXT,
    github_link VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- บัญชี Admin เริ่มต้น (Username: admin | Password: admin123)
INSERT INTO users (fullname, email, username, password) 
VALUES ('ผู้ดูแลระบบ', 'admin@sdu.ac.th', 'admin', '$2y$10$e0MYzXyjp3S7PD0RVvHwHe1e.5/t6J1XpE0vT3G4E2pG1Ua1g.0K6')
ON DUPLICATE KEY UPDATE id=id;