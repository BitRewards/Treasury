<?php
/**
 * @var \omnilight\scheduling\Schedule $schedule
 */

$schedule->command('withdrawal/queue-pending')->everyMinute()->withoutOverlapping();
$schedule->command('withdrawal/queue-not-processed')->everyMinute()->withoutOverlapping();

$schedule->command('callback/queue')->everyMinute()->withoutOverlapping();

$schedule->command('eth/block-watch')->everyMinute()->withoutOverlapping();

$schedule->command('fiat/refresh-rates')->cron('0 */8 * * * *')->withoutOverlapping();
