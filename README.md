CPF Profile Field
===
[![Moodle Plugin CI](https://github.com/gfariasonline/moodle-profilefield_brcpf/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/gfariasonline/moodle-profilefield_brcpf/actions/workflows/moodle-ci.yml)

This is a CPF field to user profiles into moodle.

Compatibility
---
This plugin supports Moodle 4.5 through 5.2.

The Cadastro de Pessoas Físicas (CPF) – Portuguese for Natural Persons Register – is a number attributed by the Brazilian revenue agency (Receita Federal – Federal Revenue) to both Brazilians and resident aliens who pay taxes or take part, directly or indirectly, in activities that provide revenue for any of the dozens of different types of taxes existing in Brazil.

More about CPF: http://en.wikipedia.org/wiki/Cadastro_de_Pessoas_F%C3%ADsicas

For Everyone
---
This plugin was made to add possibility to add CPF field in the user profile form.

Moodle documentation about profilefields -> https://docs.moodle.org/en/User_profile_fields

The plugin includes Cleave.js to format the CPF field automatically as
`999.999.999-99` while the user types.
CPF validation is enabled by default. It can be disabled in the profile field
settings when mathematical CPF validation is not required.

Para Brasileiros
---
Como CPF é um padrão brasileiro, a explicação será em português. :)
Este plugin foi criado para possibilitar a inclusão do campo cpf nos formulários de criação de novos usuários e também na alteração dos dados do usuário.

Documentação de como utilizar os profilefields -> https://docs.moodle.org/en/User_profile_fields

O plugin inclui o Cleave.js e formata automaticamente o campo CPF como
`999.999.999-99` enquanto o usuário digita.
A validação do CPF vem habilitada por padrão, mas pode ser desabilitada nas
configurações do campo quando a validação matemática não for necessária.

Installation
---

As usual:

* Download the source code (zip or git clone)
* Uncompress to user/profile/field/brcpf
* Go to **Notifications**

Migrating data from an old CPF field
---

If your site already had a custom user profile field storing CPF values
(for example, a legacy `profilefield_cpf` field, or any plain text field),
you can migrate that data into a `brcpf` field using the CLI scripts under
`cli/`. They must be run from the Moodle root.

1. Create the new profile field with type **CPF** in
   **Site admin > Users > User profile fields** and note its shortname
   (e.g. `cpf`).
2. Run the read-only check first — it never writes to the database:
   ```
   php user/profile/field/brcpf/cli/check_cpf_migration.php --from=cpf_old --to=cpf
   ```
   `--from` is the shortname of the old field, `--to` is the shortname of
   the new `brcpf` field. It reports whether both fields exist, whether the
   destination field is really of type `brcpf`, how many records would be
   migrated, and any conflicts (users who already have a different value in
   the destination field).
3. Resolve anything it flags as blocking (missing field, wrong datatype) or
   worth reviewing (conflicts, values that don't normalize to 11 digits).
4. Do a dry run of the migration — it only prints what it would do:
   ```
   php user/profile/field/brcpf/cli/migrate_cpf.php --from=cpf_old --to=cpf --dry-run
   ```
5. Back up your database, then run it for real:
   ```
   php user/profile/field/brcpf/cli/migrate_cpf.php --from=cpf_old --to=cpf
   ```
   Values are normalized to digits only before being saved, matching what
   `field.class.php` does when a user submits the profile form.

On a fresh install with no legacy field, none of this is needed — just
create the profile field with type CPF and it starts empty.

### Migrando dados de um campo CPF antigo

Se o seu site já tinha um campo de perfil customizado guardando CPF (por
exemplo, um campo antigo do `profilefield_cpf`, ou qualquer campo de texto
livre), dá pra migrar esses dados para um campo `brcpf` usando os scripts
CLI em `cli/`. Eles devem ser executados a partir da raiz do Moodle.

1. Crie o novo campo de perfil do tipo **CPF** em
   **Site admin > Users > User profile fields** e anote o shortname dele
   (ex: `cpf`).
2. Rode primeiro a checagem, que é somente leitura e nunca grava nada:
   ```
   php user/profile/field/brcpf/cli/check_cpf_migration.php --from=cpf_old --to=cpf
   ```
   `--from` é o shortname do campo antigo, `--to` é o shortname do campo
   `brcpf` novo. Ele reporta se os dois campos existem, se o campo de
   destino realmente é do tipo `brcpf`, quantos registros seriam migrados
   e eventuais conflitos (usuários que já têm um valor diferente no campo
   de destino).
3. Corrija o que for apontado como bloqueante (campo faltando, tipo
   errado) ou revise o que for só aviso (conflitos, valores que não têm
   11 dígitos após normalizar).
4. Faça um dry-run da migração — ele só mostra o que seria feito, sem
   gravar nada:
   ```
   php user/profile/field/brcpf/cli/migrate_cpf.php --from=cpf_old --to=cpf --dry-run
   ```
5. Faça backup do banco e rode de verdade:
   ```
   php user/profile/field/brcpf/cli/migrate_cpf.php --from=cpf_old --to=cpf
   ```
   Os valores são normalizados para apenas dígitos antes de serem
   gravados, igual ao que `field.class.php` faz quando o usuário envia o
   formulário de perfil.

Numa instalação nova, sem campo legado, nada disso é necessário — basta
criar o campo de perfil do tipo CPF, que já nasce vazio e correto.

Repository and support
---

Source code: https://github.com/gfariasonline/moodle-profilefield_brcpf

Issue tracker: https://github.com/gfariasonline/moodle-profilefield_brcpf/issues

Moodle documentation: https://docs.moodle.org/en/User_profile_fields

License
---

It is released as GPL v3.

This project was initially forked from [moodle-profilefield_cpf](https://github.com/willianmano/moodle-profilefield_cpf).

Authors:
* Thiago Serrao

Copyright 2026 Thiago Serrao
