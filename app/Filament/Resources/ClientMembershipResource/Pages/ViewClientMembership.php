<?php

namespace App\Filament\Resources\ClientMembershipResource\Pages;

use App\Filament\Resources\ClientMembershipResource;
use App\Models\ClientMembership;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Support\HtmlString;

class ViewClientMembership extends ViewRecord
{
    protected static string $resource = ClientMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('confirm_payment')
                ->label('Confirm Payment')
                ->modalHeading('Manual Payment Confirmation')
                ->modalDescription('Confirm that the client has paid for this membership via cash or other manual methods. This will activate the membership.')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (ClientMembership $record): bool => $record->status === 'pending')
                ->form([
                    Forms\Components\TextInput::make('amount_paid')
                        ->label('Amount Paid')
                        ->numeric()
                        ->prefix('KES')
                        ->default(fn (ClientMembership $record) => $record->membershipTier->price)
                        ->required(),
                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank Transfer',
                            'card' => 'Card',
                        ])
                        ->default('cash')
                        ->required(),
                ])
                ->action(function (ClientMembership $record, array $data) {
                    $record->update([
                        'status' => 'active',
                        'total_paid' => $record->total_paid + $data['amount_paid'],
                    ]);

                    app(\App\Services\MembershipService::class)->recordPayment(
                        $record,
                        (float)$data['amount_paid'],
                        $data['payment_method'],
                        'VIEW-PAGE-CONFIRM'
                    );

                    Notification::make()
                        ->title('Payment Confirmed')
                        ->body("Membership for {$record->client->name} is now active.")
                        ->success()
                        ->send();
                    
                    $this->refreshFormData(['status', 'total_paid']);
                }),

            Actions\Action::make('mpesa_prompt')
                ->label('M-Pesa Prompt')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info')
                ->visible(fn (ClientMembership $record): bool => $record->status === 'pending')
                ->form([
                    Forms\Components\Hidden::make('stk_initiated')->default(false),
                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\Placeholder::make('loading_ui')
                                ->label('')
                                ->visible(fn (Forms\Get $get) => $get('stk_initiated'))
                                ->content(new HtmlString('
                                    <div class="flex flex-col items-center justify-center py-6 text-center">
                                        <div class="relative flex items-center justify-center w-20 h-20 mb-4">
                                            <div class="absolute w-full h-full border-4 border-green-200 rounded-full animate-ping opacity-25"></div>
                                            <div class="absolute w-full h-full border-4 border-green-500 rounded-full animate-spin border-t-transparent"></div>
                                            <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">Waiting for PIN Entry</h3>
                                        <p class="text-sm text-gray-500 max-w-xs mx-auto">An STK push has been sent. Once the client enters their PIN, click "Verify & Activate" below.</p>
                                    </div>
                                ')),
                            Forms\Components\Group::make([
                                Forms\Components\TextInput::make('client_name')->label('Client')->disabled(),
                                Forms\Components\TextInput::make('payable_amount')->label('Amount (KES)')->disabled(),
                                Forms\Components\TextInput::make('phone_number')->label('M-Pesa Number')->tel()->required(),
                            ])
                            ->columns(2)
                            ->hidden(fn (Forms\Get $get) => $get('stk_initiated')),
                        ])
                ])
                ->mountUsing(fn (Forms\ComponentContainer $form, ClientMembership $record) => $form->fill([
                    'client_name' => $record->client?->name,
                    'payable_amount' => $record->membershipTier?->price,
                    'phone_number' => $record->client?->phone,
                    'stk_initiated' => false,
                ]))
                ->modalHeading('M-Pesa Activation')
                ->modalWidth('md')
                ->modalSubmitActionLabel(fn (Forms\Get $get) => $get('stk_initiated') ? 'Verify & Activate' : 'Send STK Push')
                ->action(function (ClientMembership $record, array $data, Actions\Action $action, Forms\Set $set) {
                    if (!$data['stk_initiated']) {
                        $set('stk_initiated', true);
                        $action->halt();
                    } else {
                        $record->update([
                            'status' => 'active',
                            'total_paid' => $record->total_paid + $record->membershipTier->price,
                        ]);

                        app(\App\Services\MembershipService::class)->recordPayment(
                            $record,
                            (float)$record->membershipTier->price,
                            'mpesa',
                            'STK-VIEW-PAGE-VERIFIED'
                        );

                        Notification::make()
                            ->title('Membership Activated')
                            ->body("M-Pesa payment verified for {$record->client->name}.")
                            ->success()
                            ->send();
                        
                        $this->refreshFormData(['status', 'total_paid']);
                    }
                }),
        ];
    }
}
