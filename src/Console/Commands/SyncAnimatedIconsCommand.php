<?php

namespace Aotr\AnimatedIcons\Console\Commands;

use Aotr\AnimatedIcons\Actions\SyncAnimatedIconAction;
use Illuminate\Console\Command;
use Throwable;

class SyncAnimatedIconsCommand extends Command
{
    protected $signature = 'animateicons:sync
        {names* : Lucide icon names (the lu- prefix is optional)}
        {--path= : Generated icon file to write (defaults to the animated-icons.generated_path config)}
        {--dry-run : Print the generated entry without writing}';

    protected $description = 'Import AnimateIcons registry icons into the generated icon config';

    public function handle(SyncAnimatedIconAction $sync): int
    {
        $path = $this->option('path');
        $path = is_string($path) && $path !== ''
            ? $path
            : (string) config('animated-icons.generated_path', config_path('animated-icons-generated.php'));
        $dryRun = (bool) $this->option('dry-run');
        $configCached = ! $dryRun && app()->configurationIsCached();
        $failed = false;

        foreach ((array) $this->argument('names') as $name) {
            try {
                $result = $sync->handle((string) $name, ! $dryRun, $path);

                $this->info(sprintf(
                    '%s: %d part%s %s',
                    $result['name'],
                    $result['parts'],
                    $result['parts'] === 1 ? '' : 's',
                    $dryRun ? '(dry run)' : '→ '.$result['path'],
                ));

                if ($dryRun) {
                    $this->newLine();
                    $this->line($sync->renderEntry($result['name'], $result['entry']));
                    $this->newLine();
                }
            } catch (Throwable $e) {
                $this->error("{$name}: {$e->getMessage()}");
                $failed = true;
            }
        }

        if ($configCached) {
            $this->warn('Configuration is cached — run `php artisan config:clear` to use the new icons.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
