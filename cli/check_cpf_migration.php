<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script SOMENTE LEITURA. Nao grava nada no banco.
 *
 * Verifica o estado dos campos de perfil "de" e "para" antes de rodar
 * cli/migrate_cpf.php, para decidir com seguranca qual acao tomar em cada
 * ambiente (dev, staging, producao).
 *
 * @package   profilefield_brcpf
 * @copyright 2026 Thiago Serrao
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'from' => 'cpf_old',
        'to'   => 'cpf',
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognized));
}

if ($options['help']) {
    echo "Verifica (somente leitura) o estado dos campos de perfil antes de migrar.

Uso:
    php cli/check_cpf_migration.php [--from=cpf_old] [--to=cpf]

Opcoes:
    --from     shortname do campo de perfil de origem (default: cpf_old)
    --to       shortname do campo de perfil de destino (default: cpf)
    -h, --help mostra esta ajuda

Nao grava nada no banco.

";
    exit(0);
}

/**
 * Remove tudo que nao for digito do valor informado.
 *
 * @param string $cpf
 * @return string
 */
function profilefield_brcpf_check_normalize(string $cpf): string {
    return preg_replace('/[^0-9]/', '', $cpf);
}

cli_writeln(str_repeat('=', 70));
cli_writeln("Verificacao de migracao: '{$options['from']}' -> '{$options['to']}'");
cli_writeln('Servidor: ' . (gethostname() ?: '?') . ' | dbtype: ' . $CFG->dbtype . ' | prefix: ' . $CFG->prefix);
cli_writeln(str_repeat('=', 70));
cli_writeln('');

// 1. Existencia e tipo dos dois campos.
$fromfield = $DB->get_record('user_info_field', ['shortname' => $options['from']]);
$tofield   = $DB->get_record('user_info_field', ['shortname' => $options['to']]);

$problems = [];
$warnings = [];

if (!$fromfield) {
    $problems[] = "Campo de origem '{$options['from']}' NAO existe em mdl_user_info_field.";
} else {
    cli_writeln("Campo de origem '{$options['from']}': id={$fromfield->id}, datatype={$fromfield->datatype}");
}

if (!$tofield) {
    $problems[] = "Campo de destino '{$options['to']}' NAO existe em mdl_user_info_field.";
} else {
    cli_writeln("Campo de destino '{$options['to']}': id={$tofield->id}, datatype={$tofield->datatype}");
    if ($tofield->datatype !== 'brcpf') {
        $problems[] = "Campo de destino '{$options['to']}' tem datatype='{$tofield->datatype}', esperado 'brcpf'. " .
            "migrate_cpf.php vai recusar rodar ate isso ser corrigido " .
            "(ver Site admin > Users > User profile fields, ou trocar o datatype diretamente se o campo estiver vazio).";
    }
}

if ($problems) {
    cli_writeln('');
    cli_writeln('PROBLEMAS BLOQUEANTES:');
    foreach ($problems as $p) {
        cli_writeln('  - ' . $p);
    }
    cli_writeln('');
    cli_writeln('Corrija o(s) item(ns) acima antes de rodar migrate_cpf.php.');
    exit(1);
}

// 2. Contagens gerais.
$fromtotal = $DB->count_records('user_info_data', ['fieldid' => $fromfield->id]);
$tototal   = $DB->count_records('user_info_data', ['fieldid' => $tofield->id]);

cli_writeln('');
cli_writeln("Registros em '{$options['from']}': {$fromtotal}");
cli_writeln("Registros em '{$options['to']}':   {$tototal}");

if ($tototal > 0) {
    $warnings[] = "O campo de destino '{$options['to']}' ja tem {$tototal} registro(s). " .
        "migrate_cpf.php vai ATUALIZAR (sobrescrever) o valor de quem ja tiver dado la, " .
        "para quem tambem tiver dado em '{$options['from']}'.";
}

// 3. Qualidade dos dados de origem: vazios, nao numericos, tamanho fora do padrao (11 digitos).
$sourcedata = $DB->get_records('user_info_data', ['fieldid' => $fromfield->id]);

$empty      = 0;
$nodigits   = 0;
$wronglen   = [];
$valid      = 0;
$conflicts  = [];

foreach ($sourcedata as $record) {
    $raw = trim((string) $record->data);
    if ($raw === '') {
        $empty++;
        continue;
    }

    $normalized = profilefield_brcpf_check_normalize($raw);
    if ($normalized === '') {
        $nodigits++;
        continue;
    }

    if (strlen($normalized) !== 11) {
        $wronglen[] = "userid {$record->userid}: '{$raw}' -> '{$normalized}' (" . strlen($normalized) . ' digitos)';
    }

    $valid++;

    // Verifica se o destino ja tem um valor DIFERENTE do que seria migrado.
    $existing = $DB->get_record('user_info_data', ['userid' => $record->userid, 'fieldid' => $tofield->id]);
    if ($existing && trim((string) $existing->data) !== '' && $existing->data !== $normalized) {
        $conflicts[] = "userid {$record->userid}: destino='{$existing->data}' vs origem normalizada='{$normalized}'";
    }
}

cli_writeln('');
cli_writeln('Analise dos valores de origem:');
cli_writeln("  Validos (11 digitos, serao migrados sem aviso): " . ($valid - count($wronglen)));
cli_writeln("  Vazios (serao ignorados): {$empty}");
cli_writeln("  Sem nenhum digito (serao ignorados): {$nodigits}");
cli_writeln('  Com quantidade de digitos != 11 (migrados mesmo assim, sem validar formato): ' . count($wronglen));

if ($wronglen) {
    cli_writeln('');
    cli_writeln('  Detalhe (tamanho invalido):');
    foreach (array_slice($wronglen, 0, 20) as $line) {
        cli_writeln('    - ' . $line);
    }
    if (count($wronglen) > 20) {
        cli_writeln('    ... e mais ' . (count($wronglen) - 20) . '.');
    }
    $warnings[] = count($wronglen) . ' registro(s) de origem nao tem 11 digitos apos normalizar. ' .
        'Confira se sao CPFs validos antes de migrar.';
}

if ($conflicts) {
    cli_writeln('');
    cli_writeln('  CONFLITOS (destino ja tem valor diferente do que seria migrado):');
    foreach (array_slice($conflicts, 0, 20) as $line) {
        cli_writeln('    - ' . $line);
    }
    if (count($conflicts) > 20) {
        cli_writeln('    ... e mais ' . (count($conflicts) - 20) . '.');
    }
    $warnings[] = count($conflicts) . ' usuario(s) tem valor diferente no campo de destino. ' .
        'migrate_cpf.php vai SOBRESCREVER com o valor de origem.';
}

// 4. Resumo final.
cli_writeln('');
cli_writeln(str_repeat('-', 70));
if ($warnings) {
    cli_writeln('AVISOS (nao bloqueiam, mas revise antes de rodar):');
    foreach ($warnings as $w) {
        cli_writeln('  - ' . $w);
    }
} else {
    cli_writeln('Nenhum problema ou aviso encontrado. Ambiente parece pronto para:');
    cli_writeln("  php cli/migrate_cpf.php --from={$options['from']} --to={$options['to']} --dry-run");
}
cli_writeln(str_repeat('-', 70));
