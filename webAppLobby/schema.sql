-- Users (from previous homework)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash CHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Game challenges between players
CREATE TABLE IF NOT EXISTS challenges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  challenger_id INT UNSIGNED NOT NULL,
  challenged_id INT UNSIGNED NOT NULL,
  status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (challenger_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (challenged_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tic-tac-toe games
-- board: 9-char string, '-' = empty, 'X' or 'O' for each cell (positions 0-8)
-- player_x goes first
CREATE TABLE IF NOT EXISTS games (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  player_x_id INT UNSIGNED NOT NULL,
  player_o_id INT UNSIGNED NOT NULL,
  board CHAR(9) NOT NULL DEFAULT '---------',
  current_turn ENUM('X','O') NOT NULL DEFAULT 'X',
  status ENUM('active','completed','draw') NOT NULL DEFAULT 'active',
  winner_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (player_x_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (player_o_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
