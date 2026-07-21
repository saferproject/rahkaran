<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateApiClient extends Command
{
    protected $signature = 'api-client:create {name} {--ability=* : Abilities granted to the client}';

    protected $description = 'Create credentials for a trusted backend API client';

    public function handle(): int
    {
        $abilities = $this->option('ability') ?: config('backend-api.default_abilities');
        $clientId = (string) Str::uuid();
        $clientSecret = Str::random(64);

        ApiClient::query()->create([
            'name' => $this->argument('name'),
            'client_id' => $clientId,
            'client_secret_hash' => Hash::make($clientSecret),
            'abilities' => array_values($abilities),
        ]);

        $this->components->info('API client created. Store the secret now; it will not be shown again.');
        $this->components->twoColumnDetail('Client ID', $clientId);
        $this->components->twoColumnDetail('Client secret', $clientSecret);
        $this->components->twoColumnDetail('Abilities', implode(', ', $abilities));

        return self::SUCCESS;
    }
}
