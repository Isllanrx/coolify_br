<?php

namespace App\Livewire\Project\Database\Mongodb;

use App\Models\StandaloneMongodb;
use App\Traits\HasDatabaseStatusInfo;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class StatusInfo extends Component
{
    use AuthorizesRequests;
    use HasDatabaseStatusInfo;

    public StandaloneMongodb $database;

    protected function databaseLabel(): string
    {
        return 'Mongo';
    }

    protected function sslModeOptions(): array
    {
        return [
            'allow' => ['title' => __('Allow insecure connections'), 'label' => 'allow (insecure)'],
            'prefer' => ['title' => __('Prefer secure connections'), 'label' => 'prefer (secure)'],
            'require' => ['title' => __('Require secure connections'), 'label' => 'require (secure)'],
            'verify-full' => ['title' => __('Verify full certificate'), 'label' => 'verify-full (secure)'],
        ];
    }

    protected function sslModeHelper(): string
    {
        return __('Choose the SSL verification mode for MongoDB connections');
    }

    protected function afterRefresh(): void
    {
        $this->sslMode = $this->database->ssl_mode;
    }

    protected function applyExtraSslAttributes(): void
    {
        $this->database->ssl_mode = $this->sslMode;
    }

    public function updatedSslMode(): void
    {
        $this->instantSaveSSL();
    }
}
