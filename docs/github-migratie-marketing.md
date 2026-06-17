# Vernieuwde deploymentaanpak — veiliger, reproduceerbaar, klaar voor de toekomst

Bij Happy Horizon hebben we onze deploymentpipeline vernieuwd. We zijn overgestapt van een Bitbucket-gebaseerde aanpak naar **GitHub Actions met Hypernode Deploy** — een modernere, betrouwbaardere manier van releasen die we succesvol hebben uitgerold op onze eigen Horizon-projecten.

Voor een standaard Magento-backend op Hypernode is de **technische migratie in circa 2 uur** gerealiseerd. Geen wekenlange trajecten, geen verstoring van jullie dagelijkse werk.

---

## Wat verandert er — en waarom

### 1. Atomische releases — veiliger bij onverwachte situaties

Onze vorige aanpak werkte via directe rsync naar de live map op de server. Snel en effectief, maar als er iets misging halverwege een deploy — een netwerkkieping, een fout in een script — kon de site tijdelijk in een inconsistente staat terechtkomen.

De nieuwe aanpak zet elke release volledig klaar vóórdat er iets wisselt. Pas als alles succesvol is afgerond, wordt de live versie in één stap omgewisseld. De site is altijd **volledig oud of volledig nieuw**, nooit half.

### 2. Rollback zonder handmatig werk

Vorige releases worden bewaard op de server. Als er na een deploy toch een probleem opduikt, kan in seconden worden teruggeschakeld naar de vorige werkende versie — zonder handmatig bestanden te herstellen of database-aanpassingen terug te draaien.

### 3. Bouwen en deployen zijn gescheiden

Voorheen vonden het bouwproces (composer install, compileren, static content genereren) en de deploy in één aaneengesloten stap plaats. Nu wordt de applicatie **eenmalig gebouwd** en opgeslagen als een kant-en-klaar pakket. Dat pakket wordt eerst op staging gezet ter validatie, en daarna op productie — zonder opnieuw te bouwen.

Dit maakt deploys reproduceerbaar: **staging en productie draaien exact dezelfde build**. Wat je goedkeurt op staging, is wat er live gaat.

### 4. Nginx en crontab worden meegedeployed

Aanpassingen aan de webserverconfiguratie (nginx) of geplande taken (crontab) staan voortaan in de repository en worden **automatisch meegenomen bij elke deploy**. Hierdoor zijn ook infrastructurele wijzigingen traceerbaar en reproduceerbaar — net als codewijzigingen.

### 5. Één centrale deploylogica voor alle projecten

Voorheen bevatte elk project zijn eigen kopie van de deploymentconfiguratie. Verbeteringen moesten per project worden doorgevoerd. Nu staat de kernlogica in één centrale gedeelde repository. **Een verbetering in het deployproces geldt automatisch voor elk project** dat de gedeelde toolkit gebruikt.

### 6. Voorbereiding op Hypernode Brancher

De nieuwe opzet vormt de basis voor [Hypernode Brancher](https://www.hypernode.com/nl/brancher/): straks een tijdelijke, volledig functionele kopie van jullie omgeving per pull request — inclusief database, configuratie en code — zodat een feature of bugfix getest kan worden vóór merge. Dit was met de vorige aanpak technisch niet haalbaar.

---

## Overzicht

| | Vorige aanpak | Nieuwe aanpak |
|---|---|---|
| Deploystrategie | Directe rsync naar live map | Atomische release met symlink-wissel |
| Onverwachte fout tijdens deploy | Tijdelijk inconsistente staat mogelijk | Live site onaangetast |
| Rollback | Handmatig | Automatisch, in seconden |
| Bouwen vs. deployen | Samen in één stap | Gescheiden — build eenmalig, deploy wanneer klaar |
| Staging en productie | Aparte builds | Zelfde build, gevalideerd op staging |
| Deploylogica per project | Eigen kopie | Centraal gedeeld |
| Nginx / crontab | Handmatig via SSH | Automatisch meegedeployed vanuit repository |
| Preview-omgevingen per PR | Niet beschikbaar | Voorbereid — Hypernode Brancher (volgende stap) |

---

## Migratie in circa 2 uur

Voor een standaard Magento-backend regelen wij in die tijd:

- Koppeling van jullie project aan de **gedeelde deploy-toolkit**
- Inrichting van **GitHub Actions** voor build, staging en productie
- Ophalen van **nginx- en cronconfiguratie** naar de repository
- Autorisatie van de **deploy-sleutel** op staging en productie
- Een **eerste succesvolle deploy** naar staging

Daarna volgt een korte validatie; productie gaat bewust en gecontroleerd live — **met exact de build die je op staging hebt goedgekeurd**.

---

*Gebaseerd op onze eigen rollout en de gedeelde Hypernode Deploy-toolkit van Happy Horizon.*
