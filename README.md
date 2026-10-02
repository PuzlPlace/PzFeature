# PzFeature

Gerador de **scaffold DDD** (Module → Domain → Feature) instalável como pacote Composer
para projetos Laravel da Puzl. É a evolução da biblioteca interna `FeatureMaker` do
`puzl/api`, agora reutilizável e **100% configurável** (caminhos, namespaces, rotas,
comando e stubs) via `config/pzfeature.php`, no padrão dos demais pacotes `Pz*`.

---

## Documentação

**[Abrir documentação completa no navegador →](https://puzlplace.github.io/PzFeature/)**

Site estático (GitHub Pages) com API, exemplos, configuração e integração Laravel.
Fonte: [`docs/index.html`](docs/index.html).

---

## Instalação

O repositório `PuzlPlace/PzFeature` é **público**, mas o pacote **não está no
Packagist** — é distribuído pelo próprio repositório Git. São dois passos.

### 1. Declare o repositório

No `composer.json` da aplicação, no mesmo nível de `require`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/PuzlPlace/PzFeature.git"
    }
]
```

Já tem outros pacotes `Pz*`? É só acrescentar mais um objeto à lista existente.

### 2. Instale por versão

```bash
composer require puzl/pzfeature:^1.0
```

Resultado no `require`:

```json
"puzl/pzfeature": "^1.0"
```

O **auto-discovery** do Laravel registra o `Puzl\PzFeature\Laravel\PzFeatureServiceProvider`
automaticamente (declarado em `extra.laravel.providers`).

### Escolhendo a constraint

Cada release publicado é uma tag semver — veja as
[Releases](https://github.com/PuzlPlace/PzFeature/releases).

| Constraint | Resolve para |
|------------|--------------|
| `^1.0` | última `1.x`. **Recomendado**: pega correções e recursos novos, nunca uma major com breaking change. |
| `~1.2.0` | última `1.2.x`. Só correções de patch, sem subir de minor. |
| `v1.2.3` | exatamente essa tag. Reprodutível ao extremo; nenhuma correção chega sozinha. |
| `dev-production` | topo da branch, sem versão. Modo legado — sem rastreabilidade, evite. |

Atualizar depois:

```bash
composer update puzl/pzfeature
```

### Versionamento

Não há versão escrita em lugar nenhum deste pacote — o `composer.json` **não tem**
campo `version` de propósito. A fonte é a tag do Git.

Toda entrega na branch `production` que passa no CI gera automaticamente a próxima
tag *patch* e o release correspondente, com notas montadas a partir dos commits
(`.github/workflows/tests.yml`, job `Release`). Mudanças de *minor* e *major* são
deliberadas: criam-se à mão uma vez, e o autoincremento continua a partir delas.

---

## Compatibilidade

- PHP `^8.1`
- Laravel 10 / 11 / 12 (`illuminate/support` e `illuminate/console` `^10|^11|^12`)

---

## Uso

Gere um domínio CRUD completo com rotas:

```bash
php artisan feature Financial Finance --features=crud --register-routes
```

O nome do comando (`feature`) vem de `config('pzfeature.command.name')`. Argumentos:
`module`, `domain`; opções: `--features=`, `--force`, `--register-routes`,
`--aggregate-of=`.

Para customizar caminhos/namespaces/rotas/comando, publique a config:

```bash
php artisan vendor:publish --tag=pzfeature-config
```

Para sobrescrever os templates por projeto (o stub publicado tem precedência sobre o
do pacote), publique os stubs:

```bash
php artisan vendor:publish --tag=pzfeature-stubs
```

O passo a passo completo está no tutorial [`docs/index.html`](docs/index.html).

---

## Desenvolvimento

```bash
composer install
composer test
```

A suíte de testes usa **PHPUnit puro** (sem Orchestra Testbench), dividida em duas suítes:

- `Unit` — `tests/Unit`
- `Features` — `tests/Features` (E2E + equivalência)

### Equivalência por snapshot (RN-01)

`tests/Features/EquivalenceSnapshotTest.php` é o guardião da regra de ouro: com a
config default, a saída do PzFeature deve ser **idêntica** à do FeatureMaker do
`puzl/api`. O snapshot de referência fica em `tests/Features/__snapshots__/default_crud`
(domínio `Financial/Finance`, `--features=crud --register-routes`), com o timestamp
da migration normalizado para `0000_00_00_000000`.

**Atualizando os snapshots**: o snapshot só deve ser regenerado quando o
comportamento do FeatureMaker original mudar de forma legítima — nunca para
"fazer o teste passar" mascarando uma regressão (uma divergência é bug nas
classes do pacote, não no snapshot). Para regerar, execute o FeatureMaker do
`puzl/api` para o domínio de referência em um diretório temporário, normalize o
nome da migration (timestamp → `0000_00_00_000000`) e copie a árvore gerada para
`tests/Features/__snapshots__/default_crud`, preservando os caminhos relativos
(`app/…`, `tests/…`, `database/…`, `routes/…`).

---

## Licença

Proprietary — uso interno Puzl.
