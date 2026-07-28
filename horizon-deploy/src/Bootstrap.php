<?php

declare(strict_types=1);

namespace HappyHorizon\Deploy;

use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\CronConfiguration;
use Symfony\Component\Yaml\Yaml;
use function Deployer\after;
use function Deployer\commandExist;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Deployer\upload;
use function Deployer\warning;
use function Deployer\writeln;

final class Bootstrap
{
    public static function run(string $projectRoot): Configuration
    {
        self::ensureAutoload($projectRoot);
        self::registerDeployerTasks();

        $config = self::loadMergedConfig($projectRoot);
        $config = self::expandEnv($config);

        $activeStage = self::detectActiveStage();
        $defaults = $config['defaults'];
        if ($activeStage !== null && isset($config['environments'][$activeStage]) && is_array($config['environments'][$activeStage])) {
            $defaults = self::mergeStageOverrides($defaults, $config['environments'][$activeStage]);
        }

        $configuration = new Configuration();
        self::applyDefaults($configuration, $defaults);

        foreach ($config['environments'] as $stageName => $envBlock) {
            if (!is_string($stageName) || $stageName === '' || !is_array($envBlock)) {
                continue;
            }
            self::addStage($configuration, $stageName, $envBlock);
        }

        return $configuration;
    }

    private static function toolkitRoot(): string
    {
        return dirname((new \ReflectionClass(self::class))->getFileName(), 2);
    }

    private static function centralDefaultsPath(): string
    {
        $env = \getenv('HORIZON_DEPLOY_DEFAULTS');
        if (is_string($env) && $env !== '' && is_readable($env)) {
            return $env;
        }

        return self::toolkitRoot() . '/defaults/magento2.yml';
    }

    private static function ensureAutoload(string $projectRoot): void
    {
        $autoload = $projectRoot . '/vendor/autoload.php';
        if (is_readable($autoload)) {
            require_once $autoload;
        }

        if (!class_exists(Yaml::class)) {
            throw new \RuntimeException(
                'Symfony Yaml is required for deploy settings (.yml). Ensure composer install has run (symfony/yaml).'
            );
        }
    }

    private static function registerDeployerTasks(): void
    {
        desc('Installs or updates ~/.hypernode/brancher-install-hook from the project repository');
        task('hypernode:install_brancher_hook', function () {
            $hookSource = '{{release_path}}/.hypernode/brancher-install-hook';
            $hookDest   = '~/.hypernode/brancher-install-hook';

            run("if [ ! -f {$hookSource} ]; then echo 'brancher-install-hook not found in release – skipping.'; exit 0; fi");
            run("mkdir -p ~/.hypernode");
            run("cp {$hookSource} {$hookDest}");
            run("chmod +x {$hookDest}");
            run("echo 'brancher-install-hook installed at {$hookDest}'");
        });

        desc('Enables maintenance mode');
        task('magento:maintenance:enable', function () {
            try {
                $ipWhitelist = \array_filter(\explode(',', (string) get('maintenance_ip_whitelist')));
            } catch (\Deployer\Exception\ConfigurationException $exception) {
                $ipWhitelist = [];
            }

            $ipWhitelistString = '';

            foreach ($ipWhitelist as $ip) {
                $ipWhitelistString .= " --ip={$ip}";
            }

            run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/{{magento_dir}}/bin/magento maintenance:enable {$ipWhitelistString}; fi");
        });

        desc('Disables maintenance mode');
        task('magento:maintenance:disable', function () {
            run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/{{magento_dir}}/bin/magento maintenance:disable; fi");
        });

        // No-op override: prevent any recipe-level nginx vhost task from running.
        // All nginx configuration is managed exclusively via hypernode:nginx:sync.
        desc('No-op: nginx vhost configuration skipped (managed via hypernode:nginx:sync)');
        task('hypernode:configure:nginx', function () {});

        desc('Syncs all nginx configs from repo to /data/web/nginx/ (ssl/ excluded, authoritative)');
        task('hypernode:nginx:sync', function () {
            $path = '';
            try {
                $path = (string) get('nginx_config_path');
            } catch (\Deployer\Exception\ConfigurationException $e) {
                // not configured
            }
            if ($path === '') {
                warning('hypernode:nginx:sync: nginx_config_path is not set, skipping.');
                return;
            }
            upload(rtrim($path, '/') . '/', '/data/web/nginx/', [
                'options' => ['--exclude=ssl/', '--delete'],
            ]);
        });

        desc('Regenerates the optimised APCu-aware autoloader after composer install');
        task('deploy:apcu-autoload', function () {
            run('cd {{release_or_current_path}} && {{bin/composer}} dump-autoload --optimize --apcu 2>&1');
        });

        desc('Enables all Magento caches');
        task('magento:cache:enable', function () {
            run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/{{magento_dir}}/bin/magento cache:enable; fi");
        });

        desc('Installs vendors');
        task('deploy:vendors', function () {
            if (!commandExist('unzip')) {
                warning('To speed up composer installation setup "unzip" command with PHP zip extension.');
            }

            try {
                $composerTimeout = get('composer_process_timeout') ?: 300;
            } catch (\Deployer\Exception\ConfigurationException $exception) {
                $composerTimeout = 300;
            }

            run("cd {{release_or_current_path}} && COMPOSER_PROCESS_TIMEOUT=$composerTimeout {{bin/composer}} {{composer_action}} {{composer_options}} 2>&1");
        });

        desc('Applies Hypernode platform settings only when they drift (maintenance-wrapped)');
        task('hypernode:settings:sync', function () {
            try {
                $settings = get('horizon_hypernode_settings');
            } catch (\Deployer\Exception\ConfigurationException $e) {
                $settings = [];
            }
            if (!is_array($settings) || $settings === []) {
                return;
            }

            $toApply = [];
            foreach ($settings as $row) {
                if (!is_array($row) || !isset($row['name'], $row['value'])) {
                    continue;
                }
                $name = (string) $row['name'];
                $desired = (string) $row['value'];
                if ($name === '' || $desired === '') {
                    continue;
                }
                if (!preg_match('/^[A-Za-z0-9_]+$/', $name) || !preg_match('/^[A-Za-z0-9._-]+$/', $desired)) {
                    warning("hypernode:settings:sync: skipping unsafe setting {$name}={$desired}");
                    continue;
                }

                try {
                    $output = run("hypernode-systemctl settings {$name}");
                } catch (\Throwable $e) {
                    warning("hypernode:settings:sync: could not read {$name} ({$e->getMessage()}); assuming drift");
                    $output = '';
                }
                $current = null;
                if (preg_match('/is set to value\s+(\S+)/i', $output, $m)) {
                    $current = $m[1];
                }
                if ($current !== null && $current === $desired) {
                    writeln("<info>hypernode:settings:sync: {$name} already {$desired}</info>");
                    continue;
                }

                $toApply[] = ['name' => $name, 'value' => $desired, 'current' => $current];
            }

            if ($toApply === []) {
                return;
            }

            foreach ($toApply as $row) {
                $from = $row['current'] ?? '(unknown)';
                writeln("<comment>hypernode:settings:sync: {$row['name']} {$from} → {$row['value']}</comment>");
            }

            // Use the node's default `php` binary for the maintenance wrap:
            // {{bin/php}} points at the *desired* PHP version, which is not
            // installed until after the platform update completes.
            $maintenance = function (string $action): void {
                $flags = '';
                if ($action === 'enable') {
                    try {
                        $ipWhitelist = \array_filter(\explode(',', (string) get('maintenance_ip_whitelist')));
                    } catch (\Deployer\Exception\ConfigurationException $e) {
                        $ipWhitelist = [];
                    }
                    foreach ($ipWhitelist as $ip) {
                        $flags .= " --ip={$ip}";
                    }
                }
                run("if [ -d $(echo {{current_path}}) ]; then php {{current_path}}/{{magento_dir}}/bin/magento maintenance:{$action}{$flags}; fi");
            };

            $maintenance('enable');
            try {
                foreach ($toApply as $row) {
                    run("yes | hypernode-systemctl settings {$row['name']} {$row['value']} --block");
                }
            } finally {
                $maintenance('disable');
            }
        });
        after('deploy:setup', 'hypernode:settings:sync');
    }

    /**
     * @return array{defaults: array<string, mixed>, environments: array<string, mixed>}
     */
    private static function loadMergedConfig(string $projectRoot): array
    {
        $centralPath = self::centralDefaultsPath();
        $central = is_readable($centralPath)
            ? self::loadConfigFile($centralPath)
            : ['defaults' => []];

        $projectPath = self::resolveProjectConfigPath($projectRoot);
        $project = self::loadConfigFile($projectPath);

        if (!isset($project['environments']) || !is_array($project['environments']) || $project['environments'] === []) {
            throw new \RuntimeException(
                sprintf('Project deploy config must define non-empty "environments" (%s).', $projectPath)
            );
        }

        $centralDefaults = isset($central['defaults']) && is_array($central['defaults']) ? $central['defaults'] : [];
        $projectDefaults = isset($project['defaults']) && is_array($project['defaults']) ? $project['defaults'] : [];

        return [
            'defaults' => self::mergeDefaultsLayers($centralDefaults, $projectDefaults),
            'environments' => $project['environments'],
        ];
    }

    private static function resolveProjectConfigPath(string $projectRoot): string
    {
        $fromEnv = \getenv('DEPLOY_CONFIG_FILE');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        $yml = $projectRoot . '/deploy.settings.yml';
        if (is_readable($yml)) {
            return $yml;
        }

        $legacy = $projectRoot . '/deploy.config.json';
        if (is_readable($legacy)) {
            return $legacy;
        }

        throw new \RuntimeException(
            sprintf(
                'No deploy settings found. Create deploy.settings.yml, deploy.config.json, or set DEPLOY_CONFIG_FILE (project root: %s).',
                $projectRoot
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadConfigFile(string $path): array
    {
        if (!is_readable($path)) {
            throw new \RuntimeException(sprintf('Deploy config not readable: %s', $path));
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['yml', 'yaml'], true)) {
            try {
                $data = Yaml::parseFile($path);
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf('Invalid YAML in %s: %s', $path, $e->getMessage()), 0, $e);
            }
        } else {
            $raw = file_get_contents($path);
            if ($raw === false) {
                throw new \RuntimeException(sprintf('Deploy config could not be read: %s', $path));
            }
            try {
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException(sprintf('Invalid JSON in %s: %s', $path, $e->getMessage()), 0, $e);
            }
        }

        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('Deploy config root must be an object (%s).', $path));
        }

        return $data;
    }

    /**
     * Central defaults layered with project defaults: lists merge (unique), variables merge per bucket, scalars replaced by project.
     *
     * @param array<string, mixed> $central
     * @param array<string, mixed> $project
     * @return array<string, mixed>
     */
    private static function mergeDefaultsLayers(array $central, array $project): array
    {
        $out = $central;

        $mergeListKeys = [
            'shared_files',
            'shared_folders',
            'deploy_excludes',
            'build_tasks',
            'deploy_tasks',
            'hypernode_settings',
        ];

        foreach ($project as $key => $val) {
            if ($key === 'variables') {
                if (is_array($val)) {
                    $out['variables'] = self::mergeVariablesLayers(
                        isset($out['variables']) && is_array($out['variables']) ? $out['variables'] : [],
                        $val
                    );
                }
                continue;
            }

            if (in_array($key, $mergeListKeys, true) && is_array($val)) {
                $base = isset($out[$key]) && is_array($out[$key]) ? $out[$key] : [];
                $out[$key] = array_values(array_unique(array_merge($base, $val)));
                continue;
            }

            $out[$key] = $val;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $central
     * @param array<string, mixed> $project
     * @return array<string, mixed>
     */
    private static function mergeVariablesLayers(array $central, array $project): array
    {
        $out = $central;
        foreach (['all', 'build', 'deploy'] as $stage) {
            if (!isset($project[$stage]) || !is_array($project[$stage])) {
                continue;
            }
            if (!isset($out[$stage]) || !is_array($out[$stage])) {
                $out[$stage] = [];
            }
            $out[$stage] = array_merge($out[$stage], $project[$stage]);
        }

        return $out;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function expandEnv($value)
    {
        if (is_string($value)) {
            return preg_replace_callback('/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/', static function (array $m): string {
                $v = \getenv($m[1]);

                return ($v === false || $v === '') ? '' : $v;
            }, $value);
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::expandEnv($v);
            }
        }

        return $value;
    }

    private static function detectActiveStage(): ?string
    {
        $fromEnv = \getenv('DEPLOY_CONFIG_STAGE');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        $argv = $_SERVER['argv'] ?? [];
        for ($i = 0, $n = count($argv); $i < $n; $i++) {
            if ($argv[$i] !== 'deploy') {
                continue;
            }
            for ($j = $i + 1; $j < $n; $j++) {
                $candidate = $argv[$j];
                if (!is_string($candidate) || $candidate === '' || $candidate[0] === '-') {
                    continue;
                }

                return $candidate;
            }

            return null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $envBlock
     * @return array<string, mixed>
     */
    private static function mergeStageOverrides(array $defaults, array $envBlock): array
    {
        $arrayReplaceKeys = [
            'deploy_excludes',
            'shared_files',
            'shared_folders',
            'build_tasks',
            'deploy_tasks',
            'hypernode_settings',
        ];
        foreach ($arrayReplaceKeys as $key) {
            if (\array_key_exists($key, $envBlock) && is_array($envBlock[$key])) {
                $defaults[$key] = $envBlock[$key];
            }
        }

        // Deep-merge variables buckets so a stage can set e.g. deploy.deploy_path
        // without wiping build.env.MAGE_MODE / static_content_* from defaults.
        if (\array_key_exists('variables', $envBlock) && is_array($envBlock['variables'])) {
            $defaults['variables'] = self::mergeVariablesLayers(
                isset($defaults['variables']) && is_array($defaults['variables']) ? $defaults['variables'] : [],
                $envBlock['variables']
            );
        }

        foreach (['recipe', 'php_version', 'public_folder', 'nginx_config', 'cron_config'] as $scalarKey) {
            if (\array_key_exists($scalarKey, $envBlock) && is_string($envBlock[$scalarKey]) && $envBlock[$scalarKey] !== '') {
                $defaults[$scalarKey] = $envBlock[$scalarKey];
            }
        }

        if (\array_key_exists('magento_dir', $envBlock)) {
            $defaults['magento_dir'] = $envBlock['magento_dir'];
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $defaults
     */
    private static function applyDefaults(Configuration $configuration, array $defaults): void
    {
        $recipe = (string) ($defaults['recipe'] ?? 'magento2');
        $configuration->setRecipe($recipe);

        $phpVersion = (string) ($defaults['php_version'] ?? 'php');
        $configuration->setPhpVersion($phpVersion);

        $publicFolder = (string) ($defaults['public_folder'] ?? 'pub');
        $configuration->setPublicFolder($publicFolder);

        $magentoDir = $defaults['magento_dir'] ?? null;
        if (is_string($magentoDir) && $magentoDir !== '') {
            $configuration->setVariable('magento_dir', $magentoDir, 'build');
            $configuration->setVariable('magento_dir', $magentoDir, 'deploy');
        }

        // Platform settings (php_version, mysql_version, …) are applied by
        // hypernode:settings:sync — idempotent + maintenance-wrapped. Do not
        // register HypernodeSettingConfiguration here: its built-in task always
        // runs --block even when already matching and can fail on API 502s.
        $settingsByName = [];
        if (preg_match('/^\d+\.\d+$/', $phpVersion)) {
            $settingsByName['php_version'] = $phpVersion;
        }
        foreach ($defaults['hypernode_settings'] ?? [] as $row) {
            if (!is_array($row) || !isset($row['name'], $row['value'])) {
                continue;
            }
            $name = (string) $row['name'];
            $value = (string) $row['value'];
            if ($name === '' || $value === '') {
                continue;
            }
            $settingsByName[$name] = $value;
        }
        $horizonSettings = [];
        foreach ($settingsByName as $name => $value) {
            $horizonSettings[] = ['name' => $name, 'value' => $value];
        }
        $configuration->setVariable('horizon_hypernode_settings', $horizonSettings, 'deploy');

        $platform = [];
        $cronConfig = $defaults['cron_config'] ?? null;
        if (is_string($cronConfig) && $cronConfig !== '') {
            $platform[] = new CronConfiguration($cronConfig);
        }

        if ($platform !== []) {
            $configuration->setPlatformConfigurations($platform);
        }

        $nginxConfig = $defaults['nginx_config'] ?? null;
        if (is_string($nginxConfig) && $nginxConfig !== '') {
            $configuration->setVariable('nginx_config_path', $nginxConfig, 'deploy');
            $configuration->addDeployTask('hypernode:nginx:sync');
        }

        $variables = $defaults['variables'] ?? [];
        if (is_array($variables)) {
            foreach (['all', 'build', 'deploy'] as $stage) {
                $bucket = $variables[$stage] ?? [];
                if (!is_array($bucket)) {
                    continue;
                }
                foreach ($bucket as $key => $val) {
                    $configuration->setVariable((string) $key, $val, $stage);
                }
            }
        }

        $composerTimeoutEnv = \getenv('COMPOSER_PROCESS_TIMEOUT');
        if ($composerTimeoutEnv !== false && $composerTimeoutEnv !== '') {
            $configuration->setVariable('composer_process_timeout', (int) $composerTimeoutEnv, 'build');
        }

        foreach ($defaults['shared_files'] ?? [] as $file) {
            if (is_string($file) && $file !== '') {
                $configuration->addSharedFile($file);
            }
        }

        foreach ($defaults['shared_folders'] ?? [] as $folder) {
            if (is_string($folder) && $folder !== '') {
                $configuration->addSharedFolder($folder);
            }
        }

        foreach ($defaults['deploy_excludes'] ?? [] as $exclude) {
            if (is_string($exclude) && $exclude !== '') {
                $configuration->addDeployExclude($exclude);
            }
        }

        foreach ($defaults['build_tasks'] ?? [] as $taskName) {
            if (is_string($taskName) && $taskName !== '') {
                $configuration->addBuildTask($taskName);
            }
        }

        foreach ($defaults['deploy_tasks'] ?? [] as $taskName) {
            if (is_string($taskName) && $taskName !== '') {
                $configuration->addDeployTask($taskName);
            }
        }
    }

    /**
     * @param array<string, mixed> $envConfig
     */
    private static function addStage(Configuration $configuration, string $name, array $envConfig): void
    {
        $domain = (string) ($envConfig['domain'] ?? '');
        if ($domain === '') {
            throw new \RuntimeException(sprintf('Environment "%s" is missing "domain".', $name));
        }

        $username = (string) ($envConfig['username'] ?? 'app');
        $stage = $configuration->addStage($name, $domain, $username);

        if (isset($envConfig['brancher']) && is_array($envConfig['brancher'])) {
            $b = $envConfig['brancher'];
            $parent = (string) ($b['parent_app'] ?? '');
            if ($parent === '') {
                throw new \RuntimeException(sprintf('Environment "%s" brancher.parent_app is required.', $name));
            }

            $brancher = $stage->addBrancherServer($parent);
            $labelList = [];
            $labels = $b['labels'] ?? [];
            if (is_array($labels)) {
                foreach ($labels as $l) {
                    if (is_string($l) && $l !== '') {
                        $labelList[] = $l;
                    }
                }
            }
            if (!empty($b['append_ci_ref_label'])) {
                $envKey = (string) ($b['ci_ref_env'] ?? 'GITHUB_RUN_ID');
                $ref = \getenv($envKey);
                $fallback = (string) ($b['ci_ref_fallback'] ?? 'none');
                $labelList[] = 'ci_ref=' . (($ref !== false && $ref !== '') ? $ref : $fallback);
            }
            $brancher->setLabels($labelList);

            $settings = $b['settings'] ?? [];
            if (is_array($settings) && $settings !== []) {
                $brancher->setSettings($settings);
            }

            return;
        }

        $servers = $envConfig['servers'] ?? [];
        if (!is_array($servers) || $servers === []) {
            throw new \RuntimeException(sprintf('Environment "%s" needs non-empty "servers" or a "brancher" block.', $name));
        }

        foreach ($servers as $server) {
            if (!is_array($server)) {
                continue;
            }
            $hostname = (string) ($server['hostname'] ?? '');
            if ($hostname === '') {
                continue;
            }
            $roles = $server['roles'] ?? null;
            $roles = is_array($roles) ? $roles : null;
            $options = is_array($server['options'] ?? null) ? $server['options'] : [];
            $sshOptions = is_array($server['ssh_options'] ?? null) ? $server['ssh_options'] : [];
            $stage->addServer($hostname, $roles, $options, $sshOptions);
        }
    }
}
