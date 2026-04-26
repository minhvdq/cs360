-- PostgreSQL schema (local development)
-- For production MySQL schema see schema.sql

CREATE TABLE IF NOT EXISTS users (
  id            SERIAL PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash CHAR(64)     NOT NULL
);

CREATE TABLE IF NOT EXISTS challenges (
  id            SERIAL PRIMARY KEY,
  challenger_id INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  challenged_id INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  status        VARCHAR(10)  NOT NULL DEFAULT 'pending'
                             CHECK (status IN ('pending','accepted','declined')),
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tic-tac-toe games
-- board: 9-char string, '-' = empty, 'X'/'O' per cell (positions 0-8, row-major)
CREATE TABLE IF NOT EXISTS games (
  id            SERIAL PRIMARY KEY,
  player_x_id   INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  player_o_id   INTEGER      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  board         CHAR(9)      NOT NULL DEFAULT '---------',
  current_turn  CHAR(1)      NOT NULL DEFAULT 'X' CHECK (current_turn IN ('X','O')),
  status        VARCHAR(10)  NOT NULL DEFAULT 'active'
                             CHECK (status IN ('active','completed','draw')),
  winner_id     INTEGER      NULL     REFERENCES users(id) ON DELETE SET NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
