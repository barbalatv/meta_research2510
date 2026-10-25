<?php
declare(strict_types=1);

require __DIR__ . '/../app/config.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/db.php';

// ✅ include diretto del service (evita errori autoload)
require APP_PATH . '/Services/QuestionBankService.php';

use App\Services\QuestionBankService;

$bank = new QuestionBankService();

$questions = [
  [
    'id'=>uuid(),
    'topic'=>'Active Directory',
    'tags'=>'usuarios,grupos,gpo',
    'difficulty'=>'medio',
    'question_text'=>'¿Qué objeto se utiliza para aplicar configuraciones y restricciones a usuarios/equipos en un dominio?',
    'choices'=>['A'=>'OU', 'B'=>'GPO', 'C'=>'DNS', 'D'=>'DHCP'],
    'correct_choice'=>'B',
    'explanation'=>'Las GPO aplican políticas a usuarios y equipos.'
  ],
  [
    'id'=>uuid(),
    'topic'=>'Redes',
    'tags'=>'nat,pat',
    'difficulty'=>'medio',
    'question_text'=>'¿Cuál es la diferencia principal entre NAT y PAT?',
    'choices'=>['A'=>'PAT usa múltiples IP públicas', 'B'=>'PAT reutiliza una IP pública con puertos', 'C'=>'NAT no traduce direcciones', 'D'=>'PAT solo funciona en IPv6'],
    'correct_choice'=>'B'
  ],
  [
    'id'=>uuid(),
    'topic'=>'SQL',
    'tags'=>'joins,relaciones',
    'difficulty'=>'facil',
    'question_text'=>'¿Qué JOIN devuelve solo filas con coincidencia en ambas tablas?',
    'choices'=>['A'=>'LEFT JOIN', 'B'=>'RIGHT JOIN', 'C'=>'INNER JOIN', 'D'=>'FULL OUTER JOIN'],
    'correct_choice'=>'C'
  ],
  [
    'id'=>uuid(),
    'topic'=>'Hardening Windows',
    'tags'=>'uac,updates',
    'difficulty'=>'facil',
    'question_text'=>'¿Qué medida reduce el riesgo de ejecución accidental con privilegios elevados en Windows?',
    'choices'=>['A'=>'Desactivar UAC', 'B'=>'Activar UAC', 'C'=>'Desactivar Windows Update', 'D'=>'Usar cuenta Administrador siempre'],
    'correct_choice'=>'B'
  ],
  [
    'id'=>uuid(),
    'topic'=>'Linux',
    'tags'=>'permisos,chmod',
    'difficulty'=>'medio',
    'question_text'=>'¿Qué significa chmod 750 sobre un archivo/directorio?',
    'choices'=>['A'=>'rwxr-x---', 'B'=>'rwxrwx---', 'C'=>'rwx------', 'D'=>'rw-r-----'],
    'correct_choice'=>'A'
  ],
];

$inserted = $bank->importArray($questions);
echo "✅ Preguntas insertadas: $inserted" . PHP_EOL;
