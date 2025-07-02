# 2048 Game Web Application

A modern, web-based implementation of the classic 2048 puzzle game, featuring user authentication, a leaderboard, and persistent score management. Built with HTML, CSS (including Tailwind), JavaScript, and PHP for backend services.

## Features

- **2048 Game Board**: Play the classic 2048 game with smooth animations and responsive design.
- **User Authentication**: Register, log in, and manage your profile.
- **Leaderboard**: Compete with other players and view high scores.
- **Score Management**: Track your current and high scores, with persistent storage.
- **How to Play Popup**: In-game instructions for new players.
- **Responsive UI**: Modern design using Tailwind CSS and custom styles.
- **Docker Support**: Easily run the application in a containerized environment.

## Project Structure

```
├── assets/                # Images and SVGs for UI
├── phpmailer/             # PHPMailer library for email features
├── Register/              # User registration, login, and password management (PHP)
│   ├── config.php
│   ├── forgot_password.php
│   ├── Login.php
│   ├── logout.php
│   ├── Register.php
│   ├── reset_password.php
│   ├── terms.html
│   └── withoutdockerconfig.php
├── Player/                # Player profile, leaderboard, and save game (PHP)
│   ├── edit.php
│   ├── index.php
│   ├── leaderboard.php
│   ├── profile.php
│   ├── save_game.php
│   ├── script.js
│   └── style.css
├── uploads/               # Uploaded user images and avatars
├── index.html             # Main game interface
├── style.css              # Custom styles
├── script.js              # Game logic
├── scoreManager.js        # Score management logic
├── popup.js               # How-to-play popup logic
├── docker-compose.yml     # Docker Compose configuration
├── dockerfile             # Dockerfile for containerization
├── init.sql               # Database initialization script
└── README.md              # Project documentation
```

## Getting Started

### Prerequisites
- [Docker](https://www.docker.com/) (for containerized setup)
- PHP 7.4+
- MySQL or MariaDB

### Running with Docker
1. Clone the repository:
   ```sh
   git clone <repo-url>
   cd 2048
   ```
2. Build and start the containers:
   ```sh
   docker-compose up --build
   ```
3. Access the app at `http://localhost:8080` (or your configured port).

### Manual Setup (Without Docker)
1. Ensure PHP and MySQL are installed.
2. Import `init.sql` into your MySQL database.
3. Configure database credentials in `Register/config.php`.
4. Serve the project directory with a PHP server:
   ```sh
   php -S localhost:8080
   ```
5. Open `http://localhost:8080` in your browser.

## Usage
- **Start Game**: Click the "Start Game" button to begin.
- **Move Tiles**: Use arrow keys to move tiles. Merge tiles to reach 2048.
- **Login/Register**: Access user features and leaderboard.
- **Leaderboard**: View top scores from all players.

## File Overview
- `index.html`: Main game UI and layout.
- `script.js`: Handles game logic and board updates.
- `scoreManager.js`: Manages score storage and retrieval.
- `popup.js`: Controls the how-to-play popup.
- `Register/`: User authentication and password management (PHP).
- `Player/`: Player profile, leaderboard, and save game (PHP).
- `phpmailer/`: Email sending library for password reset and notifications.
- `docker-compose.yml`, `dockerfile`: Docker configuration files.
- `init.sql`: SQL script to initialize the database.

## Credits
- Game logic and UI inspired by the original [2048](https://play2048.co/).
- Icons and images from [Flaticon](https://www.flaticon.com/) and [Unsplash](https://unsplash.com/).
- PHPMailer for email functionality.

## License
This project is for educational purposes. See individual file headers for third-party licenses.