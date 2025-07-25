<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Personal Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Full Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                            ]),
                    ])->columns(1),

                Infolists\Components\Section::make('Professional Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('specialties')
                                    ->badge()
                                    ->separator(',')
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('experience_years')
                                    ->label('Experience')
                                    ->formatStateUsing(fn (int $state): string => $state . ' years of experience')
                                    ->icon('heroicon-o-academic-cap')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('bio')
                                    ->label('Professional Bio')
                                    ->columnSpanFull()
                                    ->placeholder('No bio provided'),
                                Infolists\Components\TextEntry::make('hourly_rate')
                                    ->label('Hourly Rate')
                                    ->money('KES')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color('success')
                                    ->icon('heroicon-o-banknotes'),
                            ]),
                    ])->columns(1),

                Infolists\Components\Section::make('System Settings')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ColorEntry::make('color')
                                    ->label('Calendar Color')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        'on_leave' => 'warning',
                                        default => 'gray'
                                    }),
                            ]),
                    ])->columns(1),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Joined'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated'),
                            ]),
                    ])->columns(1)
                    ->collapsible(),
            ]);
    }
}