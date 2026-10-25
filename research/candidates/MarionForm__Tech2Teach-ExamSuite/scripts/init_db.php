<?php
declare(strict_types=1);

require __DIR__ . '/../app/config.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/db.php';

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS intakes (
  id TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  raw_text TEXT NOT NULL,
  pack_json TEXT,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS questions (
  id TEXT PRIMARY KEY,
  topic TEXT NOT NULL,
  tags TEXT,
  difficulty TEXT NOT NULL,
  question_text TEXT NOT NULL,
  choices_json TEXT NOT NULL,
  correct_choice TEXT NOT NULL,
  explanation TEXT,
  times_seen INTEGER NOT NULL DEFAULT 0,
  times_wrong INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS exams (
  id TEXT PRIMARY KEY,
  intake_id TEXT NULL,
  variant TEXT NOT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY(intake_id) REFERENCES intakes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS exam_questions (
  exam_id TEXT NOT NULL,
  question_id TEXT NOT NULL,
  q_order INTEGER NOT NULL,
  PRIMARY KEY(exam_id, question_id),
  FOREIGN KEY(exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attempts (
  id TEXT PRIMARY KEY,
  exam_id TEXT NOT NULL,
  score_10 REAL NOT NULL,
  correct INTEGER NOT NULL,
  total INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  detail_json TEXT NOT NULL,
  FOREIGN KEY(exam_id) REFERENCES exams(id) ON DELETE CASCADE
);
");

echo "✅ DB lista en: " . DB_PATH . PHP_EOL;
