<?php

namespace App\Console\Commands;

use App\Models\Clan;
use Illuminate\Console\Command;

class PruneClans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'control:prune-clans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune empty clans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Clan::doesntHave('members')->count();
        $this->output->writeln("{$count} clans with 0 members");
        if ($count > 0) {
            Clan::doesntHave('members')->delete();
        }
    }
}
