##  (2026-06-17)

[View Release](git@github.com:happy-horizon/actions.git/commits/tag/)

*  Create blank.yml *(Mr. Lewis)*
*  Update blank.yml *(Mr. Lewis)*
*  Update blank.yml *(Mr. Lewis)*
*  Update blank.yml *(Mr. Lewis)*
*  Modify deployment environment and main branch script *(Mr. Lewis)*
*  Update deployment environment and conditions *(Mr. Lewis)*
*  Modify workflow for Vercel deployment and promotion *(Mr. Lewis)*
*  Update GitHub Actions workflow for Vercel deployment *(Mr. Lewis)*
*  Add VERCEL_PROJECT_ID to workflow environment *(Mr. Lewis)*
*  Update blank.yml *(Mr. Lewis)*
*  Rename workflow and update VERCEL_ENV logic *(Mr. Lewis)*
*  Update blank.yml *(Mr. Lewis)*
*  Enhance GitHub Actions workflow with Fastly purge *(Mr. Lewis)*
*  Add Vercel deploy action workflow *(Mr. Lewis)*
*  Add Vercel webhook trigger workflow *(Mr. Lewis)*
*  Fix workflow action reference for Vercel deployment *(Mr. Lewis)*
*  Remove VERCEL_DEPLOYMENT_URL from workflow *(Mr. Lewis)*
*  Remove required input for VERCEL_DEPLOYMENT_URL *(Mr. Lewis)*
*  Clean up vercel-deploy-action inputs *(Mr. Lewis)*
*  Refactor Vercel Deploy Action inputs and env variables *(Mr. Lewis)*
*  Update condition for production deployment *(Mr. Lewis)*
*  Update vercel-deploy-action.yml *(Mr. Lewis)*
*  Add Vercel deploy action workflow *(Mr. Lewis)*
*  Update Vercel deploy action workflow reference *(Mr. Lewis)*
*  Delete .github/workflows/vercel-deploy-action.yml *(Mr. Lewis)*
*  Fix action reference in Vercel webhook trigger *(Mr. Lewis)*
*  Add Vercel deployment workflow for production *(Mr. Lewis)*
*  Delete .github/workflows/horizon-storefront directory *(Mr. Lewis)*
*  Update Vercel deployment action reference *(Mr. Lewis)*
*  Fix action path in Vercel webhook trigger *(Mr. Lewis)*
*  Add GitHub Actions workflow for NPM package publishing *(Mr. Lewis)*
*  Rename horizon-storefront-npm-publlish.yml to horizon-storefront-npm-publish.yml *(Mr. Lewis)*
*  Update horizon-storefront-vercel-deployment-success.yml *(Mr. Lewis)*
*  Update horizon-storefront-vercel-deployment-success.yml *(Mr. Lewis)*
*  [FEATURE] Added extra check to fastly purge, changed NEW_RELIC_DEPLOYMENT_ENTITY_GUID to var *(Collin Woerde)*
*  [BUGFIX] Corrected branch check *(Collin Woerde)*
*  [ENHANCEMENT] Improved environment variable handling in Vercel deployment workflow; added debug output and fallback logic for environment resolution *(Collin Woerde)*
*  [FEATURE] Removed debug, fixed Fullpurge (always run even if csp fails, but not when promote fails) *(Collin Woerde)*
*  [FEATURE] Added branch to vercel webhook trigger run-name *(Collin Woerde)*
*  [FEATURE] Refined conditions for deployment workflows and improved environment variable handling in Vercel webhook trigger *(Collin Woerde)*
*  [FEATURE] Updated github action title for client project deploys *(Collin Woerde)*
*  [FEATURE] Added skip New Relic if the guid is not set *(Collin Woerde)*
*  [FEATURE][NEXT-402] Added npm chache accross the diffrent steps, added generate csp skip on missing env, added vercel url to vercel-webhook-trigger.yml project actions *(Collin Woerde)*
*  [TEST][NEXT-402] Echo newrelic guid *(Collin Woerde)*
*  [FEATURE][NEXT-402] Removed test, added newrelic in parallel with fullpurge, updated cache version for newer node *(Collin Woerde)*
*  [FEATURE][NEXT-402] Removed deprecated set-output and added Vercel deploy to newrelic message *(Collin Woerde)*
*  [FEATURE][ILDT-2] Add reusable hypernode deploy workflow and toolkit *(Lewis Voncken)*
*  [FIX][ILDT-2] Apply environment within reusable deploy job *(Lewis Voncken)*
*  [FEATURE][ILDT-2] Add preview rollout toggle and GH token guard *(Lewis Voncken)*
*  [FIX][ILDT-2] Validate GH token without secrets context in if expression *(Lewis Voncken)*
*  [BUGFIX] fix invalid key *(Lewis Voncken)*
*  [FIX][ILDT-2] Restore application checkout in reusable deploy workflow *(Lewis Voncken)*
*  [FIX][ILDT-2] Pass GitHub run id into deploy container *(Lewis Voncken)*
*  [FEATURE] upgrade hypernode image to version 4 *(Lewis Voncken)*
*  [FEAT][ILDT-2] Add global bin scripts for Hypernode setup *(Lewis Voncken)*
*  feat(horizon-deploy): add global nginx sync task and cron/nginx wiring *(Lewis Voncken)*
*  feat(horizon-deploy): add performance tasks matching legacy deploy.sh behaviour *(Lewis Voncken)*
*  docs: voeg klantuitleg toe over GitHub deployment voordelen *(Lewis Voncken)*
*  docs: herschrijf klantdoc als evolutie ipv kritiek op oude aanpak *(Lewis Voncken)*
*  docs: verwijder punt over productiemoment (was al zo in vorige aanpak) *(Lewis Voncken)*
*  docs: verwijder punt over gedeelde bestanden (was al zo in vorige aanpak) *(Lewis Voncken)*
*  docs: verwijder punt over codekwaliteit (was al zo in vorige aanpak) *(Lewis Voncken)*
*  feat(nginx): skip recipe vhost task and make nginx sync authoritative *(Lewis Voncken)*
*  docs: voeg Hypernode Brancher toe als volgende stap in deployment doc *(Lewis Voncken)*
*  chore(deploy): use GITHUB_TOKEN for toolkit checkout *(Lewis Voncken)*
*  fix(deploy): restore GH_TOKEN for private toolkit checkout *(Lewis Voncken)*
*  fix(deploy): require GH_TOKEN for private toolkit checkout *(Lewis Voncken)*
*  fix(deploy): restore GH_TOKEN fallback to GITHUB_TOKEN for toolkit checkout *(Lewis Voncken)*
*  feat(build): add reusable Hypernode Magento build workflow *(Lewis Voncken)*


