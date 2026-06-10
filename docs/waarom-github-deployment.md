# Waarom wij deployen via GitHub — en wat dat voor jou betekent

Dit document legt uit waarom Happy Horizon is overgestapt van een Bitbucket-gebaseerde naar een GitHub-gebaseerde deploymentpipeline, en welke voordelen dat concreet oplevert voor jouw project.

---

## 1. Veiligere deploys — geen halve releases meer

**Voorheen:** bestanden werden direct overschreven op de live server. Als er iets misging halverwege een deploy, stond de website in een kapotte, half-bijgewerkte staat.

**Nu:** elke deploy wordt eerst volledig klaargezet in een tijdelijke map. Pas als alles succesvol is afgerond, wordt de website in één atomaire stap omgezet naar de nieuwe versie. Mislukt de deploy? De live site merkt er niets van.

---

## 2. Rollback in seconden

**Voorheen:** bij een probleem na een deploy moest er handmatig worden ingegrepen — bestanden terugzetten, configuratie controleren, databasewijzigingen terugdraaien.

**Nu:** de vorige versie staat nog gewoon op de server. Terugdraaien naar de vorige werkende release duurt seconden en vereist geen handmatig werk.

---

## 3. Bouwen en deployen zijn gescheiden

**Voorheen:** elke deploy bouwde de applicatie opnieuw op — composers installeren, bestanden genereren, alles compileren — en stuurde het daarna direct naar productie. Dit kostte veel tijd en betekende dat productie ook het bouwproces meemaakte.

**Nu:** de bouw gebeurt eenmalig en wordt opgeslagen als een kant-en-klaar pakket (artifact). Dat pakket kan meerdere keren worden ingezet zonder opnieuw te hoeven bouwen. Productiedeployments zijn daardoor sneller en stabieler.

---

## 4. Productie gaat live op jouw moment

**Voorheen:** een push naar de productiebranch triggerde direct een volledige deploy.

**Nu:** bouwen en deployen zijn losgekoppeld. De build wordt aangemaakt bij een push, maar de daadwerkelijke productiedeploy wordt handmatig gestart — op het moment dat jij (of wij) er klaar voor bent. Je kiest expliciet welke build naar productie gaat. Dit voorkomt onbedoelde deploys buiten kantooruren.

---

## 5. Eén deployproces voor alle projecten

**Voorheen:** elk project had zijn eigen kopie van de deploymentlogica. Een verbetering of bugfix in het deployproces moest per project handmatig worden doorgevoerd.

**Nu:** de kernlogica (bouwen, deployen, CI, nginx, cron) staat in één centrale gedeelde repository. Alle projecten delen dit. Een verbetering geldt automatisch voor iedereen.

---

## 6. Nginx en crontab worden meegedeployed

**Voorheen:** aanpassingen aan de webserverconfiguratie (nginx) of de geplande taken (crontab) vereisten handmatig inloggen op de server via SSH.

**Nu:** nginx-configuratie en crontabs staan in de repository en worden automatisch meegenomen bij elke deploy. Configuratiewijzigingen zijn traceerbaar, versiebaar en reproduceerbaar.

---

## 7. Gedeelde bestanden blijven intact tussen deploys

**Voorheen:** bestanden als uploadmappen, logbestanden en omgevingsconfiguratie moesten handmatig worden uitgesloten van de rsync om overschrijven te voorkomen.

**Nu:** bestanden die per omgeving anders zijn (zoals `env.php`, `pub/media`, `var/log`) zijn als "gedeeld" gedefinieerd en worden automatisch als symlink gekoppeld aan elke nieuwe release. Ze worden nooit overschreven.

---

## 8. Ingebouwde CI — codekwaliteit voor elke wijziging

Bij elke pull request en push worden automatisch statische codeanalyses uitgevoerd. Problemen in de code worden gesignaleerd vóórdat ze de server bereiken.

---

## Samenvatting

| | Bitbucket (oud) | GitHub (nieuw) |
|---|---|---|
| Deploystrategie | Directe rsync naar live map | Atomische releases met symlink-wissel |
| Bij mislukte deploy | Site kan kapot zijn | Live site onaangetast |
| Rollback | Handmatig, tijdrovend | Automatisch, seconden |
| Bouwen vs. deployen | Samen in één stap | Gescheiden — build eenmalig, deploy wanneer klaar |
| Productiedeploy | Direct bij push | Handmatig starten, jij bepaalt het moment |
| Deploylogica per project | Eigen kopie, moeilijk te onderhouden | Centraal gedeeld, één update geldt voor iedereen |
| Nginx / crontab beheer | Handmatig via SSH | Automatisch meegedeployed vanuit de repository |
| Codekwaliteit | Optioneel | Ingebouwd in elke pull request |
