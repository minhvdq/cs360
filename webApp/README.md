## URL
https://cs.gettysburg.edu/~vudimi01/cs360/webApp/lobby.php

## How to Play
1. Register an account or log in.
2. From the lobby, select an opponent from the dropdown and click **Send Challenge**.
3. The challenged player logs in, sees the challenge under **Incoming Challenges**, and clicks **Accept**.
4. Both players are X and O — the challenger plays X and goes first.
5. On your turn, click any empty square on the 3×3 board to place your mark.
6. First player to get three in a row (horizontal, vertical, or diagonal) wins. If all 9 squares fill with no winner, the game is a draw.
7. Completed games appear under **Game History** in the lobby.

## SQL Schema Changes
Added two new relations: `challenges` to track game invitations between players, and `games` to store Tic-Tac-Toe game state.

```sql
CREATE TABLE IF NOT EXISTS challenges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  challenger_id INT UNSIGNED NOT NULL,
  challenged_id INT UNSIGNED NOT NULL,
  status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (challenger_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (challenged_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
```
