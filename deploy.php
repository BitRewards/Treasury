<?php

namespace Deployer;

require_once 'recipe/common.php';

host('production')
    ->hostname('bitrewards-treasury-production')
    ->user('bitrewards')
    ->stage('production')
    ->set('deploy_path', '/treasury');

set('branch', getenv('BRANCH') ?: 'master');

set('repository', 'git@github.com:bitrewards/treasury.git');

/**
 * Yii 2 Advanced Project Template configuration
 */

// Yii 2 Advanced Project Template shared dirs
set('shared_dirs', [
    'frontend/runtime',
    'api/runtime',
    'console/runtime',
]);

// Yii 2 Advanced Project Template shared files
set('shared_files', [
//    'api/config/main-local.php',
//    'api/config/params-local.php',
//    'common/config/main-local.php',
//    'common/config/params-local.php',
//    'console/config/main-local.php',
//    'console/config/params-local.php',
    'console/node/master-key-file',
//    'console/node/.env'
]);

/**
 * Initialization
 */
task('deploy:init', function () {
    run('{{bin/php}} {{release_path}}/init --env=Production --overwrite=n');
})->desc('Initialization');

/**
 * Run migrations
 */
task('deploy:run_migrations', function () {
    run('{{bin/php}} {{release_path}}/yii migrate up --interactive=0');
})->desc('Run migrations');

/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:init',
    'deploy:node',
    'deploy:shared',
    'deploy:run_migrations',
    'deploy:symlink',
    'deploy:owner',
    'deploy:restart_workers',
    'reload:php-fpm',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

task('deploy:node', function () {
    run('cd {{release_path}}/console/node && npm install');
})->desc('NPM Install');

task('deploy:restart_workers', function () {
    run('{{bin/php}} {{release_path}}/yii background/redo');
})->desc('Restart workers');

task('deploy:owner', function () {
    run('chown -R www-data:www-data {{release_path}}');
});

task('reload:php-fpm', function () {
    run('service php7.0-fpm reload');
});

after('deploy', 'success');