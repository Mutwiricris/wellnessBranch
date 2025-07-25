<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

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
                Infolists\Components\Section::make('Service Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('category.name')
                                    ->label('Category')
                                    ->badge()
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Service Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        default => 'gray'
                                    }),
                            ]),
                    ])->columns(2),

                Infolists\Components\Section::make('Pricing & Duration')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('price')
                                    ->money('KES')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('Duration')
                                    ->formatStateUsing(fn (int $state): string => $state . ' minutes')
                                    ->icon('heroicon-o-clock'),
                                Infolists\Components\TextEntry::make('buffer_time_minutes')
                                    ->label('Buffer Time')
                                    ->formatStateUsing(fn (?int $state): string => $state ? $state . ' minutes' : 'Not set')
                                    ->icon('heroicon-o-pause'),
                                Infolists\Components\TextEntry::make('max_advance_booking_days')
                                    ->label('Advance Booking')
                                    ->formatStateUsing(fn (?int $state): string => $state ? $state . ' days' : 'Not set')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ])->columns(1),

                Infolists\Components\Section::make('Service Options')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('requires_consultation')
                                    ->boolean()
                                    ->label('Requires Consultation')
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                Infolists\Components\IconEntry::make('is_couple_service')
                                    ->boolean()
                                    ->label('Couple Service')
                                    ->trueIcon('heroicon-o-heart')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('danger')
                                    ->falseColor('gray'),
                            ]),
                    ])->columns(1),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated'),
                            ]),
                    ])->columns(1)
                    ->collapsible(),
            ]);
    }
}
