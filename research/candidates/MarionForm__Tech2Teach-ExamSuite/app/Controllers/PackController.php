<?php
namespace App\Controllers;

use App\Models\Intake;

class PackController {
  public function view(): void {
    $id = (string) req('id', '');
    $intake = Intake::find($id);
    if (!$intake) {
      http_response_code(404);
      echo "Pack no encontrado";
      return;
    }
    view('pack_view', ['intake' => $intake]);
  }
}
