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
 * Migra os valores de um campo de perfil de usuario (ex: "cpf_old") para o
 * campo do plugin profilefield_brcpf (ex: "cpf"), normalizando o valor para
 * apenas digitos, exatamente como field.class.php::normalize_cpf() faz.
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
        'from'    => 'cpf_old',
        'to'      => 'cpf',
        'dry-run' => false,
        'help'    => false,
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
    echo "Migra dados de um campo de perfil antigo para o campo profilefield_brcpf.

Uso:
    php cli/migrate_cpf.php [--from=cpf_old] [--to=cpf] [--dry-run]

Opcoes:
    --from     shortname do campo de perfil de origem (default: cpf_old)
    --to       shortname do campo de perfil de destino, do tipo brcpf (default: cpf)
    --dry-run  nao grava nada, apenas mostra o que seria feito
    -h, --help mostra esta ajuda

";
    exit(0);
}

/**
 * Aplica a mesma normalizacao usada em field.class.php::normalize_cpf().
 *
 * @param string $cpf
 * @return string
 */
function profilefield_brcpf_cli_normalize(string $cpf): string {
    return preg_replace('/[^0-9]/', '', $cpf);
}

$fromfield = $DB->get_record('user_info_field', ['shortname' => $options['from']]);
$tofield   = $DB->get_record('user_info_field', ['shortname' => $options['to']]);

if (!$fromfield) {
    cli_error("Campo de origem '{$options['from']}' nao encontrado em user_info_field.");
}
if (!$tofield) {
    cli_error("Campo de destino '{$options['to']}' nao encontrado em user_info_field.");
}
if ($tofield->datatype !== 'brcpf') {
    cli_error("Campo de destino '{$options['to']}' nao e do tipo brcpf (datatype atual: {$tofield->datatype}).");
}

$sourcedata = $DB->get_records('user_info_data', ['fieldid' => $fromfield->id]);

if (!$sourcedata) {
    cli_writeln("Nenhum dado encontrado para o campo '{$options['from']}'.");
    exit(0);
}

$migrated = 0;
$skipped  = 0;
$updated  = 0;

foreach ($sourcedata as $record) {
    $raw = trim((string) $record->data);
    if ($raw === '') {
        $skipped++;
        continue;
    }

    $normalized = profilefield_brcpf_cli_normalize($raw);
    if ($normalized === '') {
        cli_writeln("  [ignorado] userid {$record->userid}: valor '{$raw}' nao contem digitos.");
        $skipped++;
        continue;
    }

    $existing = $DB->get_record('user_info_data', ['userid' => $record->userid, 'fieldid' => $tofield->id]);

    if ($options['dry-run']) {
        if ($existing) {
            cli_writeln("  [dry-run] userid {$record->userid}: atualizaria '{$existing->data}' -> '{$normalized}'.");
        } else {
            cli_writeln("  [dry-run] userid {$record->userid}: criaria registro com '{$normalized}'.");
        }
        $migrated++;
        continue;
    }

    if ($existing) {
        $existing->data       = $normalized;
        $existing->dataformat = 0;
        $DB->update_record('user_info_data', $existing);
        $updated++;
    } else {
        $DB->insert_record('user_info_data', (object) [
            'userid'     => $record->userid,
            'fieldid'    => $tofield->id,
            'data'       => $normalized,
            'dataformat' => 0,
        ]);
        $migrated++;
    }
}

cli_writeln('');
cli_writeln("Concluido. Criados: {$migrated} | Atualizados: {$updated} | Ignorados: {$skipped}.");

if ($options['dry-run']) {
    cli_writeln('Nenhuma alteracao foi gravada (--dry-run).');
}
