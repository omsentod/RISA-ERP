<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Concerns\HasSelectionToggle;
use App\Filament\Resources\RegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegistrations extends ListRecords
{
    use HasSelectionToggle;

    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSelectionToggleAction(),
            Actions\CreateAction::make(),
        ];
    }
}
