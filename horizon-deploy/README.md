# Horizon Hypernode deploy toolkit

Central Magento 2 deploy configuration for [Hypernode Deploy](https://docs.hypernode.com/hypernode-deploy/applications/config-for-magento-2.html). The PHP runtime lives here; each application repo keeps **`deploy.settings.yml`**. CI installs **`deploy.php`** at the Magento project root from this toolkit.

## Files in this toolkit

| Path | Purpose |
|------|---------|
| `deploy.php` | Thin Hypernode entry; copied to the Magento project root by reusable workflows. |
| `bootstrap.php` | Loads `src/Bootstrap.php` (entry used by `deploy.php`). |
| `src/Bootstrap.php` | Builds `Hypernode\DeployConfiguration\Configuration` from YAML/JSON. |
| `defaults/magento2.yml` | Shared defaults (tasks, shared paths, deploy excludes, variables, …). |

## Quick setup in a new project

1. Depend on the central checkout (`happy-horizon/actions` → `horizon-deploy/`) via the reusable GitHub workflows, **or** copy **`horizon-deploy/`** into the Magento project for local use.
2. Add **`deploy.settings.yml`** with at least one **`environments`** entry (see below). Do **not** commit `deploy.php` — CI copies it from this toolkit to the project root.
3. Run deploy with the same stage name as in YAML, e.g. `hypernode-deploy deploy staging` (after copying `deploy.php` to the project root for local runs).
4. In CI, set **`DEPLOY_CONFIG_STAGE`** to that stage when you use flags or wrappers that hide the stage from argv (recommended in GitHub Actions).

Optional:

- Point **`HORIZON_DEPLOY_BOOTSTRAP`** at `…/horizon-deploy/bootstrap.php` when the toolkit is checked out outside the app repo.
- Point **`HORIZON_DEPLOY_DEFAULTS`** at `…/defaults/magento2.yml` to use a different central defaults file.
- Set **`DEPLOY_CONFIG_FILE`** if the project file is not `deploy.settings.yml` (or legacy `deploy.config.json`).

## `deploy.settings.yml` layout

```yaml
# Optional: merged ON TOP of horizon-deploy/defaults/magento2.yml (see “Layering” below)
defaults:
  php_version: "8.4"   # Deployer CLI + desired Hypernode platform PHP (synced on drift)
  variables:
    build:
      static_content_jobs: 8

# Required: every stage you deploy to (names must match `hypernode-deploy deploy <name>`)
environments:
  staging:
    domain: shop-staging.example.com
    username: app
    servers:
      - hostname: mystaging.hypernode.io
        options:
          deploy_path: /data/web/build-acceptance

  production:
    domain: shop.example.com
    username: app
    servers:
      - hostname: myprod.hypernode.io
```

### Environment block (Hypernode / `deploy.php`)

Each key under **`environments.<stage>`** is used as follows.

| Field | Required | Description |
|-------|----------|-------------|
| **`domain`** | Yes | Storefront domain for the stage. |
| **`username`** | No | SSH user (default `app`). |
| **`servers`** | One of servers or brancher | List of `{ hostname, roles?, options?, ssh_options? }`. Use **`options.deploy_path`** for a non-default release path. |
| **`brancher`** | Alternative to servers | Temporary Hypernode: **`parent_app`**, **`labels`**, optional **`append_ci_ref_label`**, **`ci_ref_env`**, **`ci_ref_fallback`**, **`settings`**. |

Keys that are **only for GitHub Actions** (ignored by PHP / Hypernode):

| Field | Description |
|-------|-------------|
| **`deploy_image`** | Docker image for `hypernode-deploy` when using **`.github/workflows/reusable-hypernode-magento-deploy.yml`** with an empty `deploy_image` input. |

All other keys on the environment are ignored by `Bootstrap` unless listed under “Active-stage overrides” below.

## Two layering rules (important)

### 1) Central defaults + project `defaults` (merge)

Loaded once for every run:

- **Central** `defaults/magento2.yml` → **`defaults`** map.
- **Project** `deploy.settings.yml` → optional **`defaults`** map merged on top.

Behavior:

- **Lists** `shared_files`, `shared_folders`, `deploy_excludes`, `build_tasks`, `deploy_tasks`, `hypernode_settings`: **appended** and **de-duplicated** (central first, then project).
- **`variables`**: per bucket **`all` / `build` / `deploy`**, keys from the project **override** the same keys from central (PHP `array_merge` on each bucket).
- **Scalars** such as **`recipe`**, **`php_version`**, **`public_folder`**, **`magento_dir`**: project value **replaces** central when set in project `defaults`.

Use project **`defaults`** to add an extra shared folder, bump `static_content_jobs`, or change PHP version without copying the whole central file.

**`php_version` and `hypernode_settings`:** the scalar `php_version` selects the Deployer CLI binary **and** the desired Hypernode platform PHP. Extra platform knobs (e.g. `mysql_version`) go under `hypernode_settings`. On every deploy, `hypernode:settings:sync` (after `deploy:setup`) compares live `hypernode-systemctl` values to the desired set; if anything differs it enables Magento maintenance, applies with `--block`, then disables maintenance. Already-matching settings are a no-op (no `update_node` job).

### 2) Active stage overrides

When **`DEPLOY_CONFIG_STAGE`** is set, or the CLI is `hypernode-deploy deploy <stage> …`, that **`<stage>`** block can override parts of the **already merged** defaults for **this run only**:

If the environment defines any of these keys **as an array**, the **entire** list or map is **replaced** (not merged with merged defaults):

- `deploy_excludes`, `shared_files`, `shared_folders`
- `build_tasks`, `deploy_tasks`, `hypernode_settings`

**`variables`** on the environment are **deep-merged** per bucket (`all` / `build` / `deploy`), same as project `defaults.variables`. So a stage can set `variables.deploy.deploy_path` without wiping `variables.build.env.MAGE_MODE` or static-content settings from defaults.

Scalars that can be overridden when set on the environment:

- `recipe`, `php_version`, `public_folder`
- `magento_dir` (including explicit `null`)

**Implication:** if you set `deploy_excludes` under `staging`, you must list **every** exclude you want for that deploy; the merged default exclude list for that key is **not** kept for that run. Omit these keys on the environment if you only need the shared merged defaults.

Hypernode’s own built-in excludes (e.g. `.git`, `deploy.php`) are still applied by the library in addition to your lists.

## Environment variables in YAML

Strings can use **`${VAR_NAME}`**; they are replaced from the process environment (empty string if unset). Example in central or project defaults:

```yaml
variables:
  deploy:
    maintenance_ip_whitelist: "${MAINTENANCE_IP_WHITELIST}"
```

`COMPOSER_PROCESS_TIMEOUT` is still read at runtime and overrides the build timeout when set in the environment.

## Magento in a subdirectory

In project **`defaults`** (or central defaults), set:

- **`magento_dir`**: e.g. `magento`
- **`public_folder`**: e.g. `magento/pub`
- Paths in **`shared_files`** / **`shared_folders`** relative to the repo root, e.g. `magento/app/etc/env.php`

## CI: reusable workflow and `deploy_image`

See **`.github/workflows/reusable-hypernode-magento-deploy.yml`**. Image resolution order:

1. Workflow input **`deploy_image`** (if non-empty).
2. Else **`environments.<stage>.deploy_image`** in `deploy.settings.yml`.
3. Else **`deploy_image_fallback`** (default Hypernode image tag).

## Legacy JSON

If **`deploy.settings.yml`** is missing, **`deploy.config.json`** is still accepted when it contains **`defaults`** and **`environments`**. Prefer YAML for new work.

## Further reading

- [Config for Magento 2 — Hypernode](https://docs.hypernode.com/hypernode-deploy/applications/config-for-magento-2.html)
