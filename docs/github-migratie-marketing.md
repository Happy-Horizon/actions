# Vernieuwde deployment — sneller, veiliger, klaar voor de toekomst

Bij Happy Horizon investeren we continu in betrouwbaardere releases. Daarom stappen we over op een **GitHub-gebaseerde deploymentpipeline** met **Hypernode Deploy** — dezelfde professionele aanpak die we succesvol hebben ingezet op onze eigen Horizon-projecten.

En het mooie? Voor een standaard Magento-backend is de **technische migratie naar deze nieuwe pipeline in ongeveer 2 uur** gerealiseerd. Geen wekenlange trajecten, geen verstoring van jullie dagelijkse werk — wél direct profiteren van een modernere, veiligere manier van releasen.

---

## Wat betekent dit voor jou?

### Veiliger live gaan
Elke release wordt **volledig klaargezet vóór de wissel**. Pas als alles succesvol is afgerond, gaat de nieuwe versie live. Geen half-geüpdatete shop meer als er onverwacht iets misgaat — de site is altijd **volledig oud of volledig nieuw**.

### Terugdraaien in seconden
Mocht er toch iets opduiken na een release, dan schakelen we **snel terug** naar de vorige werkende versie. Zonder handmatig geklungel op de server.

### Staging en productie op dezelfde build
We **bouwen één keer** en zetten dat pakket eerst op staging ter validatie, daarna op productie. Zo weet je zeker dat wat je goedkeurt op staging **exact hetzelfde** is wat live gaat.

### Alles onder controle in Git
Aanpassingen aan **nginx** en **crontab** staan voortaan in de repository en gaan **automatisch mee** bij elke deploy. Infrastructurele wijzigingen zijn net zo traceerbaar als codewijzigingen.

### Eén krachtige standaard voor alle projecten
De deploylogica staat centraal beheerd. Verbeteringen in ons deployproces profiteren **automatisch alle klantprojecten** — jullie hoeven niet telkens opnieuw het wiel uit te vinden.

### Klaar voor preview-omgevingen per pull request
Deze pipeline legt de basis voor **Hypernode Brancher**: straks een tijdelijke, volledige kopie van jullie omgeving per feature of bugfix — testen vóór merge, zonder extra handwerk per project.

---

## Wat verandert er praktisch?

| | Vroeger | Nu |
|---|---|---|
| Deploy | Direct naar live map | Atomische release met veilige wissel |
| Bij een fout halverwege | Risico op inconsistente site | Live site blijft intact |
| Rollback | Handmatig | Snel en eenvoudig |
| Staging vs. productie | Apart gebouwd | Dezelfde build, gevalideerd op staging |
| Nginx & cron | Handmatig op server | Automatisch vanuit repository |

---

## Migratie in circa 2 uur

Voor een **standaard Magento-backend** op Hypernode plannen we **ongeveer 2 uur** voor de technische overstap naar de nieuwe pipeline — mits repository-toegang en servergegevens beschikbaar zijn.

In die tijd regelen wij onder meer:

- Koppeling van jullie project aan onze **gedeelde deploy-toolkit**
- Inrichting van **GitHub Actions** voor build en deploy
- Synchronisatie van **nginx- en cronconfiguratie** naar de repository
- Autorisatie van de **deploy-sleutel** op staging en productie
- Een **eerste succesvolle deploy** naar staging

Daarna volgt een korte validatie op staging; productie zetten we bewust en gecontroleerd live — **met exact de build die je op staging hebt goedgekeurd**.

---

## Waarom nu?

Omdat jullie releaseproces **betrouwbaarder**, **transparanter** en **toekomstbestendiger** wordt — zonder dat het jullie team extra belast. Wij nemen de migratie uit handen; jullie merken vooral dat deploys **voorspelbaarder** worden en problemen **sneller** zijn op te lossen.

**Interesse of vragen?** Neem contact op met Happy Horizon — we lopen graag kort met jullie door wat dit concreet betekent voor jullie project.

---

*Gebaseerd op onze interne rollout (Horizon Backend) en de gedeelde Hypernode Deploy-toolkit van Happy Horizon.*
