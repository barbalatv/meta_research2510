<?php
namespace App\Controllers;

use App\Models\Intake;
use App\Services\DidacticPackService;

class IntakeController {
  public function new(): void {
    view('intake_new', []);
  }

  public function create(): void {
    $title = trim((string) req('title', ''));
    $raw   = trim((string) req('raw_text', ''));

    if ($title === '' || $raw === '') {
      view('intake_new', ['error' => 'Título y texto son obligatorios.']);
      return;
    }

    $intakeId = Intake::create($title, $raw);

    // Genera pack didáctico
    $pack = (new DidacticPackService())->buildFromRaw($title, $raw);
    Intake::savePack($intakeId, $pack);

    redirect('/pack/ver?id=' . urlencode($intakeId));
  }
}
