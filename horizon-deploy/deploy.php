<?php

declare(strict_types=1);

/**
 * Thin Hypernode Deploy entry. Lives in happy-horizon/actions (horizon-deploy/).
 * CI copies this file to the Magento project root before build/deploy.
 *
 * @see HappyHorizon\Deploy\Bootstrap
 *
 * Environment variables:
 * - HORIZON_DEPLOY_BOOTSTRAP  Path to horizon-deploy/bootstrap.php when toolkit is outside the project
 * - HORIZON_DEPLOY_DEFAULTS   Path to central defaults YAML (e.g. defaults/magento2.yml)
 * - DEPLOY_CONFIG_FILE        Path to project deploy.settings.yml / deploy.config.json
 * - DEPLOY_CONFIG_STAGE       Active stage for per-environment overrides (optional; also inferred from CLI)
 *
 * Environment YAML and overrides: horizon-deploy/README.md
 */

$bootstrap = \getenv('HORIZON_DEPLOY_BOOTSTRAP');
if (!is_string($bootstrap) || $bootstrap === '' || !is_readable($bootstrap)) {
    $bootstrap = __DIR__ . '/horizon-deploy/bootstrap.php';
}

require_once $bootstrap;

return \HappyHorizon\Deploy\Bootstrap::run(__DIR__);
