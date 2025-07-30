<?php

namespace App\Console\Commands;

use App\Models\SocialProvider;
use App\Services\SocialProviders\DiscordProvider;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class SetupDiscord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'control:setup-discord';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the Discord Social Provider';

    protected $promptText;
    protected $promptPassword;
    protected $promptConfirm;
    protected $promptTable;
    protected $promptInfo;

    public function __construct(
        $promptText = null,
        $promptPassword = null,
        $promptConfirm = null,
        $promptTable = null,
        $promptInfo = null
    ) {
        parent::__construct();
        $this->promptText = $promptText ?: fn(...$args) => text(...$args);
        $this->promptPassword = $promptPassword ?: fn(...$args) => password(...$args);
        $this->promptConfirm = $promptConfirm ?: fn(...$args) => confirm(...$args);
        $this->promptTable = $promptTable ?: fn(...$args) => table(...$args);
        $this->promptInfo = $promptInfo ?: fn(...$args) => \Laravel\Prompts\info(...$args);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $provider = SocialProvider::whereCode('discord')->first();
        if (!$provider) {
            $prov = new DiscordProvider();
            $provider = $prov->install();
        }

        $clientId = $provider->settings()->whereCode('client_id')->first();
        $secret = $provider->settings()->whereCode('client_secret')->first();

        $clientId->value = call_user_func(
            $this->promptText,
            label: 'Discord Client ID',
            hint: 'This can be found in your Discord Developer OAuth2 settings',
            default: $clientId->value ?? ''
        );

        if (!$secret->value || call_user_func($this->promptConfirm, 'Do you want to change the Client Secret?', false)) {
            $secret->value = call_user_func(
                $this->promptPassword,
                label: 'Discord Client Secret'
            );
        }

        $provider->enabled = call_user_func(
            $this->promptConfirm,
            label: 'Do you want to enable the Discord provider?'
        );

        $provider->auth_enabled = call_user_func(
            $this->promptConfirm,
            label: 'Do you want to enable login with Discord?'
        );

        $provider->save();
        $clientId->save();
        $secret->save();

        call_user_func($this->promptInfo, 'The provider has been updated');

        call_user_func(
            $this->promptTable,
            ['OAuth2 Redirect URLs'],
            [
                [route('login.return', 'discord')],
                [route('linkedaccounts.store', 'discord')],
            ],
        );
    }
}
