<?php

declare(strict_types=1);

/*
 * Development smoke test for the happy-horizon/actions horizon-deploy toolkit.
 *
 * Boots a Deployer application context (as the real hypernode-deploy binary does),
 * loads horizon-deploy/deploy.php against the fixture project in .cursor/fixtures,
 * and asserts the resulting Hypernode DeployConfiguration was built from YAML.
 *
 * Run: php .cursor/verify-toolkit.php
 * Requires: bash .cursor/install.sh (provisions .cursor/horizon-deploy-runtime/vendor).
 */

use Deployer\Deployer;
use Hypernode\DeployConfiguration\Configuration;
use Symfony\Component\Console\Application;

$repoRoot = dirname(__DIR__);
$runtime = __DIR__ . '/horizon-deploy-runtime';
$autoload = $runtime . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, "Runtime dependencies missing. Run: bash .cursor/install.sh\n");
    exit(2);
}

require $autoload;

// deployer/deployer ships as a CLI phar with an empty composer autoload; the real
// hypernode-deploy binary bundles it. Register its namespace + global helper files
// so we can boot the same Deployer context the toolkit expects.
$deployerSrc = $runtime . '/vendor/deployer/deployer/src';
spl_autoload_register(static function (string $class) use ($deployerSrc): void {
    if (str_starts_with($class, 'Deployer\\')) {
        $file = $deployerSrc . '/' . str_replace('\\', '/', substr($class, strlen('Deployer\\'))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
require $deployerSrc . '/Support/helpers.php';
require $deployerSrc . '/functions.php';

// hypernode-deploy creates the Deployer instance and loads its base recipes before
// the project deploy.php, so tasks like deploy:setup already exist.
new Deployer(new Application('horizon-deploy-verify'));
require $deployerSrc . '/../recipe/common.php';

// Assemble a throwaway project: the thin deploy.php entry + the fixture settings.
$project = sys_get_temp_dir() . '/horizon-deploy-verify-' . getmypid();
@mkdir($project, 0777, true);
copy($repoRoot . '/horizon-deploy/deploy.php', $project . '/deploy.php');
copy(__DIR__ . '/fixtures/deploy.settings.yml', $project . '/deploy.settings.yml');

putenv('HORIZON_DEPLOY_BOOTSTRAP=' . $repoRoot . '/horizon-deploy/bootstrap.php');
putenv('MAINTENANCE_IP_WHITELIST=10.0.0.1,10.0.0.2');
putenv('GITHUB_RUN_ID=4242');

$config = require $project . '/deploy.php';

$errors = [];
$assert = static function (bool $cond, string $msg) use (&$errors): void {
    echo '  [' . ($cond ? 'PASS' : 'FAIL') . "] {$msg}\n";
    if (!$cond) {
        $errors[] = $msg;
    }
};

echo "== Type ==\n";
$assert($config instanceof Configuration, 'deploy.php returns a Hypernode DeployConfiguration');

echo "== Merged defaults (central + project) ==\n";
$assert($config->getRecipe() === 'magento2', 'recipe resolves to magento2 (central default)');
$assert($config->getPhpVersion() === '8.3', 'php_version overridden by project defaults (8.3)');
$assert($config->getPublicFolder() === 'pub', 'public_folder from central default (pub)');

$buildVars = $config->getVariables('build') ?? [];
$deployVars = $config->getVariables('deploy') ?? [];
$assert(($buildVars['static_content_jobs'] ?? null) === 8, 'project overrides static_content_jobs -> 8');
$assert(($buildVars['static_content_locales'] ?? null) === 'en_US nl_NL', 'central static_content_locales retained after merge');
$assert(isset($buildVars['magento_themes']['HappyHorizon/hyva']), 'project magento_themes locale-map replaces central list');
$assert(($buildVars['split_static_deployment'] ?? null) === true, 'locale-mapped themes enable split_static_deployment');
$assert(($deployVars['keep_releases'] ?? null) === 5, 'project overrides deploy.keep_releases -> 5');
$assert(($deployVars['maintenance_ip_whitelist'] ?? null) === '10.0.0.1,10.0.0.2', '${MAINTENANCE_IP_WHITELIST} expanded from environment');

echo "== Shared paths / excludes ==\n";
$assert(in_array('var/log', $config->getSharedFolders(), true), 'central shared_folder var/log present');
$assert(in_array('var/custom_extra', $config->getSharedFolders(), true), 'project shared_folder var/custom_extra appended');
$assert(in_array('app/etc/env.php', $config->getSharedFiles(), true), 'central shared_file app/etc/env.php present');
$assert(in_array('./horizon-deploy', $config->getDeployExclude(), true), 'central deploy_exclude ./horizon-deploy present');

echo "== Build tasks ==\n";
foreach (['deploy:vendors', 'magento:compile', 'hyva:tailwind:build', 'magento:deploy:assets'] as $t) {
    $assert(in_array($t, $config->getBuildTasks(), true), "build task {$t} registered");
}

echo "== Stages built from environments ==\n";
$stages = [];
foreach ($config->getStages() as $stage) {
    $stages[$stage->getName()] = $stage;
}
$assert(isset($stages['staging'], $stages['production'], $stages['acceptance']), 'staging, production and acceptance stages created');
if (isset($stages['staging'])) {
    $assert($stages['staging']->getDomain() === 'shop-staging.example.com', 'staging domain parsed');
    $assert(count($stages['staging']->getServers()) === 1, 'staging has one server');
}
if (isset($stages['acceptance'])) {
    $assert(count($stages['acceptance']->getServers()) === 1, 'acceptance brancher server created');
}

echo "== Deployer tasks (registerDeployerTasks) ==\n";
$tasks = Deployer::get()->tasks;
foreach (['hypernode:nginx:sync', 'magento:build:remove-env', 'hyva:tailwind:build', 'deploy:vendors', 'hypernode:settings:sync'] as $t) {
    $assert($tasks->has($t), "custom Deployer task {$t} defined");
}

echo "== Post-initialize callbacks ==\n";
$assert(count($config->getPostInitializeCallbacks()) >= 1, 'guardStaticContentEnv registered a post-initialize callback');

// Cleanup throwaway project.
@unlink($project . '/deploy.php');
@unlink($project . '/deploy.settings.yml');
@rmdir($project);

echo "\n";
if ($errors === []) {
    echo "VERIFY RESULT: ALL ASSERTIONS PASSED\n";
    exit(0);
}
echo 'VERIFY RESULT: ' . count($errors) . " FAILED\n";
exit(1);
