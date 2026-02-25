<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ActionsClearCommand extends Command
{
    protected $signature = 'actions:clear';

    protected $description = 'Clear the cached action mappings';

    public function handle(): void
    {
        File::delete(config('actions.cache.path') ?? $this->laravel->bootstrapPath('cache/actions.php'));

        $this->components->info('Action cache cleared successfully.');
    }
}
