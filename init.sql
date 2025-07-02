-- init.sql

-- 1) Create the database (if it doesn’t exist)
CREATE DATABASE IF NOT EXISTS game2048;
USE game2048;

-- 2) Create `users` table, defaulting profile_picture to 'uploads/user.png'
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    high_score INT NOT NULL DEFAULT 0,
    reset_token VARCHAR(255) DEFAULT NULL,
    token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    top_time TIME NOT NULL DEFAULT '00:00:00',
    profile_picture VARCHAR(255) NOT NULL DEFAULT 'uploads/user.png',
    time_taken INT NOT NULL DEFAULT 0
);

-- 3) Create `score_history` table with foreign key to users.id
CREATE TABLE IF NOT EXISTS score_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    time_taken TIME,
    played_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user
      FOREIGN KEY (user_id)
      REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE
);
