# Jira — Magento 2 ↔ Dimco integratie (inschatting & takenlijst)

## Bronnen & status

| Bron | Status | Opmerking |
|------|--------|-----------|
| [Google Drive map](https://drive.google.com/drive/folders/1c_AEbSfO25QOpvnCzGODOVmcXNsQ0-aq) | **Niet toegankelijk** | Map vereist Google-login; geen publieke link-toegang |
| Jira (`happyhorizon.atlassian.net`) | **Niet toegankelijk** | Geen API-credentials / geen publieke issues gevonden |
| Happy Horizon referentiecases | Beschikbaar | o.a. PPC (Business Central), Alumio-partnership, B2B Magento-patronen |

> **Actie voor refinement:** Zodra Drive en Jira toegankelijk zijn, valideer onderstaande aannames tegen functioneel ontwerp, API-documentatie en bestaande Jira-epics/stories. Dit document is opgesteld als **werkversie** op basis van standaard Magento 2 ↔ backend-integratiepatronen bij Happy Horizon.

---

## Samenvatting (Jira description)

Realisatie van een **bidirectionele integratie tussen Magento 2 (Adobe Commerce / Open Source)** en **Dimco** als bronsysteem voor product-, voorraad- en klantdata, en als doelsysteem voor order- en statusinformatie. Doel: handmatige data-invoer elimineren, voorraad en prijzen actueel houden, en orders betrouwbaar doorzetten naar Dimco voor fulfilment en administratie.

**Epic-doel:** Magento 2 en Dimco communiceren automatisch en fouttolerant, met monitoring, retry-logica en een gecontroleerde go-live op staging → productie.

**Globale inschatting (indicatief):** **35–55 werkdagen** development + **8–12 werkdagen** discovery/QA/project — afhankelijk van gekozen architectuur (Alumio vs. maatwerk), API-volwassenheid van Dimco, en scope (B2B-prijslijsten, multi-warehouse, returns).

---

## Architectuurkeuzes (te bevestigen)

| Optie | Beschrijving | Voordeel | Nadeel | Indicatie effort |
|-------|--------------|----------|--------|------------------|
| **A — Alumio iPaaS** | Middleware-platform (Happy Horizon-partner) | Sneller live, visueel beheer, herbruikbaar voor toekomstige koppelingen | Licentiekosten, afhankelijkheid platform | **−25% dev-tijd** t.o.v. maatwerk |
| **B — Maatwerk Magento-module + queue** | Custom module op Hypernode + async jobs (RabbitMQ/cron) | Volledige controle, geen middleware-licentie | Meer onderhoud, langere initiele bouw | Baseline-inschatting hieronder |
| **C — Hybride** | Alumio voor zware transformaties + lichte Magento-module voor triggers | Balans flexibiliteit/snelheid | Twee systemen beheren | Variabel |

**Aanbeveling (voorlopig):** Optie A of C als Dimco geen stabiele REST API heeft of veel transformatieregels nodig zijn; Optie B als Dimco een goed gedocumenteerde API heeft en scope beperkt blijft tot catalogus + orders.

---

## Datadomeinen & richting

| Domein | Richting | Prioriteit | Complexiteit | Opmerking |
|--------|----------|------------|--------------|-----------|
| Producten / SKU's | Dimco → Magento | Must-have | Hoog | Attributen, categorieën, media, configurables |
| Voorraad / beschikbaarheid | Dimco → Magento | Must-have | Medium | Multi-source inventory (MSI) indien meerdere locaties |
| Prijzen (B2C) | Dimco → Magento | Must-have | Medium | Incl. BTW, special prices, valuta |
| Prijzen (B2B / klantgroepen) | Dimco → Magento | Should-have | Hoog | Contractprijzen, staffels, klant-specifiek |
| Klanten / bedrijven | Dimco → Magento (+ evt. terug) | Should-have | Hoog | B2B company structure, adressen, BTW-nr. |
| Orders | Magento → Dimco | Must-have | Hoog | Incl. regels, kortingen, verzendmethode |
| Orderstatus / tracking | Dimco → Magento | Must-have | Medium | Status mapping, track & trace |
| Facturen / creditnota's | Dimco → Magento | Could-have | Medium | Afhankelijk van Dimco-output |
| Retouren (RMA) | Magento → Dimco → Magento | Could-have | Hoog | Vaak aparte fase |

---

## Implementatiefasen & takenlijst

### Fase 0 — Discovery & ontwerp (8–12 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 0.1 | Kick-off met klant/Dimco: scope, prioriteiten, SLA's | PM + SA | 0,5 dag | Scope-document ondertekend |
| 0.2 | Inventarisatie Dimco API's (REST/SOAP/EDI/bestanden) | SA + Dimco | 1–2 dagen | API-spec + sandbox-toegang |
| 0.3 | Data mapping workshop (velden, entiteiten, ID-strategie) | SA + Dev | 1 dag | Mapping-document per entiteit |
| 0.4 | Magento impact-analyse (MSI, B2B, ElasticSuite, storeviews) | Magento dev | 1 dag | Technisch impactdocument |
| 0.5 | Integratie-architectuur (Alumio vs. maatwerk) + sequencediagram | SA | 1 dag | Architectuur goedgekeurd |
| 0.6 | Foutafhandeling, retry, dead-letter, monitoring-ontwerp | DevOps + Dev | 0,5 dag | Runbook + alerting-plan |
| 0.7 | Testplan + acceptatiecriteria per datastroom | QA + PM | 1 dag | Testplan in Jira |
| 0.8 | Jira-epic breakdown in stories + afhankelijkheden | PM | 0,5 dag | Backlog ready for sprint |

---

### Fase 1 — Basisinfrastructuur (5–8 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 1.1 | Integratie-omgeving opzetten (staging Dimco + Magento staging) | DevOps | 1 dag | Endpoints bereikbaar |
| 1.2 | Authenticatie & secrets (API keys, OAuth, IP-whitelist) | DevOps | 0,5 dag | Secrets in GitHub/Hypernode |
| 1.3 | Middleware/connectors skeleton (Alumio flows of Magento module) | Dev | 2–3 dagen | Hello-world sync werkt |
| 1.4 | Logging, correlation IDs, New Relic/CloudWatch dashboards | DevOps | 1 dag | Dashboards + alerts actief |
| 1.5 | Message queue / cron-scheduling (async verwerking) | Dev | 1–2 dagen | Jobs verwerken zonder blocking |

---

### Fase 2 — Productcatalogus (8–12 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 2.1 | Product sync: aanmaken / bijwerken / deactiveren | Dev | 3–4 dagen | Nieuwe SKU in Magento binnen SLA |
| 2.2 | Categorie-mapping (Dimco hiërarchie → Magento categories) | Dev | 1–2 dagen | Categorieën correct genest |
| 2.3 | Attribuut-mapping (specificaties, filters, ElasticSuite) | Dev | 2–3 dagen | Filterbare attributen in PLP |
| 2.4 | Afbeeldingen / media (URL-import of binary sync) | Dev | 1–2 dagen | Productafbeeldingen zichtbaar |
| 2.5 | Configurable/bundle/grouped productondersteuning | Dev | 1–2 dagen | Varianten correct gekoppeld |
| 2.6 | Initial load + delta-sync strategie (full vs. incremental) | Dev | 1 dag | Eerste volledige import geslaagd |

---

### Fase 3 — Voorraad & prijzen (5–8 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 3.1 | Voorraadsync (per SKU, per bron indien MSI) | Dev | 2–3 dagen | Webshop toont actuele voorraad |
| 3.2 | Backorder / out-of-stock gedrag configureren | Dev + klant | 0,5 dag | Gedrag per producttype vastgelegd |
| 3.3 | Basisprijzen + special prices sync | Dev | 1–2 dagen | PDP toont juiste prijs |
| 3.4 | B2B prijslijsten / klantgroep-prijzen (indien scope) | Dev | 2–3 dagen | Ingelogde B2B-klant ziet contractprijs |
| 3.5 | Valuta / BTW / prijsweergave (incl./excl.) | Dev | 0,5–1 dag | Checkout BTW correct |

---

### Fase 4 — Klanten (4–7 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 4.1 | Klant-sync Dimco → Magento (B2C) | Dev | 1–2 dagen | Bestaande klant kan inloggen |
| 4.2 | Company / shared catalog (B2B, indien scope) | Dev | 2–3 dagen | B2B-bedrijfsstructuur correct |
| 4.3 | Adresboek & standaard verzend-/factuuradres | Dev | 1 dag | Adressen gesynchroniseerd |
| 4.4 | Nieuwe klantregistratie → Dimco (indien scope) | Dev | 1 dag | Nieuwe klant in beide systemen |

---

### Fase 5 — Orders & fulfilment (8–12 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 5.1 | Order export Magento → Dimco (real-time of batch) | Dev | 3–4 dagen | Testorder in Dimco zichtbaar |
| 5.2 | Orderregels, kortingen, verzendkosten, BTW-mapping | Dev | 1–2 dagen | Financiële totalen kloppen |
| 5.3 | Betalingsstatus / betaalmethode mapping | Dev | 0,5–1 dag | Betaalinfo correct doorgezet |
| 5.4 | Orderstatus terugkoppeling Dimco → Magento | Dev | 2–3 dagen | Klant ziet statusupdate in account |
| 5.5 | Verzending / track & trace terugkoppeling | Dev | 1–2 dagen | Trackinglink in Magento |
| 5.6 | Idempotentie & duplicate-order preventie | Dev | 1 dag | Dubbele orders onmogelijk |

---

### Fase 6 — Testen, UAT & go-live (8–12 dagen)

| # | Taak | Eigenaar | Inschatting | Done when |
|---|------|----------|-------------|-----------|
| 6.1 | Integratietests per datastroom (happy path + edge cases) | QA + Dev | 2–3 dagen | Testrapport groen |
| 6.2 | Loadtest / bulk-import (15k+ SKU's indien van toepassing) | Dev + QA | 1–2 dagen | Performance binnen SLA |
| 6.3 | Failover-test (API down, timeout, partiële data) | Dev + QA | 1 dag | Retry + alerting werkt |
| 6.4 | UAT met klant op staging | PM + klant | 2–3 dagen | UAT-akkoord |
| 6.5 | Go-live plan + rollback-scenario | PM + DevOps | 0,5 dag | Runbook goedgekeurd |
| 6.6 | Productie-deploy + hypercare (2 weken) | Dev + PM | 2–3 dagen | Stabiele productie-sync |

---

## Totale inschatting

### Scenario A — Standaard scope (catalogus + voorraad + orders + status)

| Fase | Min (dagen) | Max (dagen) |
|------|-------------|-------------|
| 0 — Discovery & ontwerp | 8 | 12 |
| 1 — Infrastructuur | 5 | 8 |
| 2 — Productcatalogus | 8 | 12 |
| 3 — Voorraad & prijzen (basis) | 5 | 6 |
| 4 — Klanten (basis B2C) | 4 | 5 |
| 5 — Orders & fulfilment | 8 | 12 |
| 6 — Test & go-live | 8 | 12 |
| **Totaal development + QA** | **46** | **67** |

*Met Alumio en een volwassen Dimco API: reken op **~35–45 dagen** (ca. 20% reductie op dev-fases 2–5).*

### Scenario B — Uitgebreide B2B-scope (+ prijslijsten, companies, retouren)

| Extra scope | Extra effort |
|-------------|--------------|
| B2B contractprijzen & staffels | +5–8 dagen |
| Company/shared catalog volledig | +3–5 dagen |
| Retouren / creditnota's | +5–8 dagen |
| Multi-warehouse (MSI, 3+ bronnen) | +3–5 dagen |
| PIM-tussenschakel (Akeneo e.d.) | +8–15 dagen |

### Story points (indicatie voor Jira)

| Epic / Story-groep | SP (indicatie) |
|--------------------|----------------|
| Discovery & architectuur | 13 |
| Infrastructuur & monitoring | 8 |
| Productcatalogus sync | 21 |
| Voorraad & prijzen | 13 |
| Klanten (B2B) | 13 |
| Orders & fulfilment | 21 |
| Test, UAT & go-live | 13 |
| **Totaal (standaard scope)** | **~100 SP** |

*1 SP ≈ 0,5–1 dag afhankelijk van teamsnelheid.*

---

## Jira-structuur (voorstel)

### Epic

**Titel:** `Magento 2 ↔ Dimco integratie — [klantnaam]`

**Labels:** `magento`, `dimco`, `integration`, `erp`, `b2b` (indien van toepassing)

### Stories (hoofdniveau)

| Story | Samenvatting | SP |
|-------|--------------|-----|
| DIMCO-1 | Discovery, API-analyse & data mapping | 13 |
| DIMCO-2 | Integratie-infrastructuur & monitoring | 8 |
| DIMCO-3 | Productcatalogus sync (Dimco → Magento) | 21 |
| DIMCO-4 | Voorraad- en prijssync | 13 |
| DIMCO-5 | Klantsync (B2B/B2C) | 13 |
| DIMCO-6 | Order export & status-terugkoppeling | 21 |
| DIMCO-7 | Integratietest, UAT & go-live | 13 |

### Sub-tasks per story

Gebruik de taken uit de fasetabellen hierboven (bijv. `DIMCO-3.1 Product sync CRUD`, `DIMCO-6.4 Track & trace`).

---

## Risico's & afhankelijkheden

| Risico | Impact | Mitigatie |
|--------|--------|-----------|
| Dimco API onvolledig of ongedocumenteerd | +30–50% effort | Vroege API-spike in Fase 0; fallback via bestandsuitwisseling |
| Geen sandbox-omgeving Dimco | Vertraging testen | Sandbox of testbedrijf vooraf afspreken |
| Grote initiële catalogus (15k+ SKU's) | Performance/importtijd | Bulk-import buiten piekuren; batching + MSI |
| B2B-prijslogica complex in Dimco | Scope creep | Prijsregels expliciet documenteren in mapping |
| Magento customisaties (ElasticSuite, B2B modules) | Mapping-conflicten | Impact-analyse in 0.4 |
| Geen idempotente order-API in Dimco | Dubbele orders | Idempotency keys + status-check vóór export |

**Externe afhankelijkheden:**
- Dimco: API-documentatie, sandbox, testdata, contactpersoon technisch
- Klant: UAT-beschikbaarheid, acceptatiecriteria, besluit B2B-scope
- Happy Horizon: Hypernode-staging, GitHub Actions deploy (indien nog niet gemigreerd: +1,5–2,5 dagen, zie [`jira-github-migratie-klanten.md`](./jira-github-migratie-klanten.md))

---

## Acceptatiecriteria (Definition of Done)

- [ ] Producten uit Dimco verschijnen in Magento binnen afgesproken SLA (bijv. < 15 min delta, < 4 uur full sync)
- [ ] Voorraad op PDP en checkout komt overeen met Dimco (max. 1% afwijking door timing)
- [ ] Prijzen (incl. B2B indien scope) kloppen met Dimco voor testset van ≥ 50 SKU's
- [ ] Magento-testorder wordt exact één keer aangemaakt in Dimco met juiste totalen
- [ ] Statuswijziging in Dimco is zichtbaar in Magento klantaccount
- [ ] Fouten worden gelogd, gealert en automatisch geretried (min. 3 pogingen)
- [ ] Runbook en monitoring-dashboard beschikbaar voor beheer
- [ ] UAT-akkoord klant op staging
- [ ] Productie go-live zonder P1-incidenten in hypercare-periode

---

## Open punten (te valideren via Drive & Jira)

1. **Wat is Dimco precies?** ERP, WMS, maatwerk of klantnaam — bepaalt API-aanpak.
2. **Exacte datadomeinen in scope** — welke flows staan in de Drive-map / bestaande Jira-tickets?
3. **B2B vs. B2C** — zijn company accounts, shared catalog en contractprijzen vereist?
4. **Bestaande integratie** — greenfield of vervanging/uitbreiding van huidige koppeling?
5. **Alumio-licentie** — is middleware al beschikbaar bij de klant?
6. **Volume & SLA** — aantal SKU's, orders/dag, maximale vertraging sync.
7. **Bestaande Jira-epic** — project key en epic-link voor koppeling van deze stories.

---

## Volgende stappen

1. **Drive-map** publiek toegankelijk maken of documenten delen in Jira/Confluence.
2. **Jira-toegang** verlenen aan projectteam of bestaande epic + stories exporteren.
3. **Refinement-sessie** (2 uur) om open punten af te vinken en inschatting te finaliseren.
4. **Go/no-go** op architectuurkeuze (Alumio vs. maatwerk) vóór start Fase 1.
