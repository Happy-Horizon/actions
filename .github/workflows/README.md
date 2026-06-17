# GitHub Workflow Actions

This directory contains reusable and project-level GitHub Actions workflows used by Horizon projects.

## Workflows

### `horizon-backend-hypernode-deploy.yml`

Reusable workflow for Magento backend deploys via `hypernode-deploy`.

Typical use cases:
- staging deploy
- production deploy using a pre-built artifact
- acceptance/preview deploys (Brancher)

#### Basic usage

```yaml
jobs:
  deploy:
    uses: happy-horizon/actions/.github/workflows/horizon-backend-hypernode-deploy.yml@production
    with:
      deploy_stage: staging
      toolkit_repository: happy-horizon/actions
    secrets: inherit
```

#### Production with artifact promotion

```yaml
on:
  workflow_dispatch:
    inputs:
      run_id:
        required: true
        type: string

jobs:
  deploy:
    concurrency: production
    environment:
      name: production
    uses: happy-horizon/actions/.github/workflows/horizon-backend-hypernode-deploy.yml@production
    with:
      deploy_stage: production
      deploy_image: quay.io/hypernode/deploy:latest-php8.4-node22
      artifact_name: deployment-build-production
      artifact_run_id: ${{ inputs.run_id }}
      toolkit_repository: happy-horizon/actions
    secrets: inherit
```

#### Inputs

- `deploy_stage` (required): target stage name from `deploy.settings.yml` (for example: `staging`, `production`, `acceptance`)
- `deploy_image` (optional): explicit container image override
- `deploy_image_fallback` (optional): fallback image if no override is found
- `toolkit_repository` (optional): repository that contains `horizon-deploy/`
- `toolkit_ref` (optional): toolkit branch/tag, default `production`
- `toolkit_path_in_repo` (optional): path to toolkit root, default `horizon-deploy`
- `artifact_name` (optional): artifact name to download into `build/`
- `artifact_run_id` (optional): run ID used for artifact download
- `enable_preview_environment` (optional, default `false`): opt-in switch for `acceptance`/Brancher preview deployments
- `environment_name` / `environment_url` (optional): caller-level metadata only if you decide to pass it

#### Secrets

- `SSH_PRIVATE_KEY` (required)
- `DEPLOY_COMPOSER_AUTH` (optional)
- `COMPOSER_PROCESS_TIMEOUT` (optional)
- `MAINTENANCE_IP_WHITELIST` (optional)
- `HYPERNODE_API_TOKEN` (optional)
- Toolkit checkout and cross-run artifact download use the caller's built-in `GITHUB_TOKEN` (`github.token`). Grant the deploy job `actions: read` in the caller workflow, and allow the caller repo to access the toolkit repo via org Actions access settings.

#### Outputs

- `deployment_hostname`: hostname from `deployment-report.json` when available
- `deployment_url`: URL derived from that hostname when available

Use these outputs for PR comments in preview flows.

---

### `horizon-storefront-vercel-deployment-success.yml`

Reusable workflow for storefront deploy follow-up tasks after Vercel success events:
- production promote
- optional CSP header generation
- optional Fastly purge
- optional New Relic deployment marker

This workflow is commonly called from `repository_dispatch` handlers in storefront repos.

---

### `horizon-storefront-npm-publish.yml`

Reusable/publish workflow related to Horizon storefront npm release operations.

---

### `vercel-webhook-trigger.yml`

Repository-dispatch trigger workflow for Vercel deployment events.

## Conventions

- Keep workflows reusable-first where possible.
- Put environment protection and concurrency at the **caller** workflow when project-specific.
- Keep deploy logic centralized in reusable workflows to avoid drift between repos.
- If a caller needs project-specific behavior, prefer inputs over duplicating deploy scripts.

## Related config

- Magento deploy settings are expected in project `deploy.settings.yml`.
- Shared deploy defaults/tooling are in `horizon-deploy/` (typically in this `actions` repo).
