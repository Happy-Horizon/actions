# Jira — GitHub-migratie & vernieuwde deploymentaanpak (klanten)

## Samenvatting (Jira description)

Happy Horizon migreert klantprojecten van **Bitbucket + rsync-deploy** naar **GitHub Actions + Hypernode Deploy**, met gedeelde deploylogica in de centrale `actions`-repository. Dit levert atomische releases, snelle rollback, gescheiden build/deploy (zelfde build op staging én productie), en nginx/cron uit version control. De aanpak is uitgerold op Horizon Backend als referentie-implementatie; per klantproject volgt configuratie van repo, secrets, Hypernode-toegang en een gecontroleerde staging → productie cutover.

**Inschatting:** ~2 uur technische migratie per standaard Magento-backend (mits repository-toegang, Hypernode-namen en secrets beschikbaar zijn). Zie "Inschatting" voor variaties en story points.

**Epic-doel:** klanten veilig en reproduceerbaar deployen via GitHub, met één centrale toolkit voor onderhoud op schaal.

---

## Waarde voor de klant (kort)

| Thema | Voordeel |
|--------|----------|
| Atomische releases | Geen half-deployed live site meer bij netwerk- of scriptfouten |
| Rollback | Terug naar vorige release in seconden, zonder handmatig herstel |
| Build ≠ deploy | Eén build, meerdere keren inzetbaar; staging en productie identiek |
| Centrale toolkit | Verbeteringen in deploy/CI gelden voor alle projecten |
| Nginx & cron in repo | Infrastructurele wijzigingen traceerbaar en automatisch meegedeployed |
| Brancher (toekomst) | Basis voor preview-omgevingen per pull request |

Uitgebreide klantuitleg: [`waarom-github-deployment.md`](./waarom-github-deployment.md).

---

## Scope per klantproject

### Backend (Magento / Hypernode) — verplicht

1. **Repository**
   - Bitbucket → GitHub (mirror of verhuizing)
   - Branches: minimaal `staging` en `production` (of equivalent)

2. **Deployconfiguratie in projectrepo**
   - `deploy.settings.yml` (staging + production: domain, servers, `deploy_image`, optioneel `nginx_config` / `cron_config`)
   - Dunne `deploy.php` stub (toolkit wordt at runtime uit `happy-horizon/actions` geladen)

3. **Hypernode**
   - Deploy-SSH-sleutel autoriseren op staging + production
   - Nginx + crontab ophalen naar repo (`.hypernode/<stage>/…`) indien nog niet aanwezig
   - PHP/Node-versie detecteren → juiste `deploy_image` in `deploy.settings.yml`

4. **GitHub**
   - Repo secrets: `SSH_PRIVATE_KEY`, `DEPLOY_COMPOSER_AUTH`, optioneel `COMPOSER_PROCESS_TIMEOUT`, `MAINTENANCE_IP_WHITELIST`, `HYPERNODE_API_TOKEN`
   - Org secret `GH_TOKEN` (PAT met read op `actions`) indien nodig voor private toolkit-checkout
   - Workflows: CI, build (production), deploy staging (push), deploy production (workflow_dispatch + build run ID), preview (uit, via `enabled: false`)

5. **Validatie & cutover**
   - Eerste deploy staging → smoke test
   - Production build → handmatige promote met artifact run ID
   - Bitbucket-pipeline uitzetten na succesvolle parallel run

### Storefront (indien van toepassing) — apart traject

- Vercel / bestaande GitHub-flows blijven grotendeels intact
- Eventuele `repository_dispatch`-koppelingen naar gedeelde actions-workflows
- Niet in de standaard backend-inschatting meegenomen

### Optioneel / later

- Hypernode Brancher / PR-preview (`horizon-backend-preview-environment.yml`, nu disabled)
- Klantspecifieke afwijkingen (extra deploy stages, custom nginx, Brancher-labels)

---

## Implementatiestappen (checklist)

Geautomatiseerd waar mogelijk via `bin/prepare-github-migration`:

```bash
bin/prepare-github-migration <pad-naar-backend-repo> \
  --staging-node <hypernode-staging> \
  --production-node <hypernode-production> \
  --ssh-public-key <pad-naar-deploy.pub>
```

| # | Taak | Eigenaar | Done when |
|---|------|----------|-----------|
| 1 | GitHub-repo aanmaken / mirror sync | Platform | Code op GitHub, branches kloppen |
| 2 | `prepare-github-migration` draaien | DevOps / dev | `deploy.settings.yml`, workflows, `.hypernode/` aanwezig |
| 3 | GitHub secrets configureren | DevOps | Staging deploy slaagt |
| 4 | Eerste staging deploy + QA | Dev + klant | Functionele check op staging-URL |
| 5 | Production build + promote | DevOps | Productie draait op nieuwe pipeline |
| 6 | Bitbucket pipeline deactiveren | DevOps | Alleen GitHub deploy actief |
| 7 | Documentatie / overdracht klant | PM / lead | Klant begrijpt build → promote flow |

---

## Inschatting

### Standaard Magento-backend (referentie: Horizon Backend)

Aannames: Hypernode-namen bekend, geen uitzonderlijke custom deploy, nginx/cron pullbaar, secrets tijdig beschikbaar.

| Fase | Inschatting |
|------|-------------|
| Voorbereiding (repo, secrets, SSH, migration script) | **0,5–1 dag** |
| Eerste staging deploy + fixes | **0,5–1 dag** |
| Production cutover + monitoring | **0,5 dag** |
| **Totaal per klant (backend)** | **1,5–2,5 dagen** |

### Variatie

| Situatie | Extra effort |
|----------|----------------|
| Eerste klant in cohort (learnings, secrets/org setup) | +1 dag eenmalig |
| Geen bestaande `deploy.settings.yml` / onbekende Hypernode-layout | +0,5–1 dag |
| Zware nginx/cron customisatie of handmatige server-drift | +1–2 dagen |
| Storefront + backend gezamenlijke cutover | +1–2 dagen |
| Brancher / PR-preview activeren | +2–3 dagen ( aparte story ) |

### Cohort / meerdere klanten

Door gedeelde workflows in `happy-horizon/actions` daalt de marginale effort na de eerste implementatie naar circa **1–1,5 dag per backend**, mits Hypernode en secrets proces gestandaardiseerd zijn.

**Story points (indicatie):** 5–8 SP per standaard backend-migratie; 13 SP bij complexe of dual-repo cutover.

---

## Risico's & afhankelijkheden

- Org/repo **GitHub Actions permissions** en **`GH_TOKEN`** correct voor private `actions`-checkout
- **`SSH_PRIVATE_KEY`** moet op elke Hypernode geautoriseerd zijn vóór eerste deploy
- Lege **`nginx_config`** / **`cron_config`**: deploy past server nginx/cron niet aan (bestaande config blijft staan)
- Production promote vereist bewuste **workflow_dispatch** met build **run ID** (geen automatische prod deploy op push)

---

## Acceptatiecriteria (Definition of Done)

- [ ] Push naar `staging` triggert succesvolle deploy via GitHub Actions
- [ ] Push naar `production` bouwt artifact; handmatige production deploy gebruikt dat artifact
- [ ] Rollback getest of gedocumenteerd op Hypernode (vorige release)
- [ ] Nginx/cron in repo gesynchroniseerd (indien van toepassing)
- [ ] Bitbucket deploy uitgeschakeld
- [ ] Klant heeft korte uitleg ontvangen (zie [`waarom-github-deployment.md`](./waarom-github-deployment.md))

---

## Jira titel (voorstel)

**GitHub-migratie & Hypernode Deploy rollout — [klantnaam / project]**

## Jira labels (voorstel)

`github-migration`, `hypernode-deploy`, `devops`, `customer-rollout`
