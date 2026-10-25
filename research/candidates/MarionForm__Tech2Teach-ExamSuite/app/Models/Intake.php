<?php
namespace App\Models;

use PDO;

class Intake {

  public static function create(string $title, string $raw): string {
    $id = \uuid();
    $stmt = \db()->prepare("INSERT INTO intakes(id,title,raw_text,created_at) VALUES(?,?,?,datetime('now'))");
    $stmt->execute([$id, $title, $raw]);
    return $id;
  }

  public static function savePack(string $id, array $pack): void {
    $stmt = \db()->prepare("UPDATE intakes SET pack_json=? WHERE id=?");
    $stmt->execute([json_encode($pack, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), $id]);
  }

  public static function find(string $id): ?array {
    $stmt = \db()->prepare("SELECT * FROM intakes WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['pack'] = $row['pack_json'] ? json_decode($row['pack_json'], true) : null;
    return $row;
  }
}
