<x-filament-panels::page>
    @php
        $tenant = \Filament\Facades\Filament::getTenant();
    @endphp

    {{-- Google Font for High-End Typography --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <div class="pos-terminal-canvas" @if ($activeTransactionId) wire:poll.2s="checkMpesaPaymentStatus" @endif>
        
        <style>
            /* Custom CSS Reset & Layout to Override Filament Base Grid */
            .pos-terminal-canvas {
                --pos-bg: #F8F9FD;
                --pos-card-bg: #FFFFFF;
                --pos-text-main: #1E1E1E;
                --pos-text-muted: #9E9E9E;
                --pos-primary: #1E1E1E;
                --pos-primary-hover: #000000;
                --pos-shadow: 0 8px 30px rgba(0, 0, 0, 0.02);
                --pos-border: 1px solid rgba(0, 0, 0, 0.04);
                
                font-family: 'Inter', -apple-system, sans-serif;
                background-color: var(--pos-bg);
                color: var(--pos-text-main);
                min-height: calc(100vh - 4rem);
                margin: -24px;
                padding: 24px;
                box-sizing: border-box;
            }

            .pos-terminal-canvas *, .pos-terminal-canvas *:before, .pos-terminal-canvas *:after {
                box-sizing: border-box;
            }

            /* Container Layout */
            .pos-container {
                display: grid;
                grid-template-columns: 200px 1fr 350px;
                gap: 24px;
                align-items: start;
            }

            /* Left Sidebar */
            .pos-sidebar {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }

            /* Search Box */
            .pos-search-wrapper {
                position: relative;
                width: 100%;
            }

            .pos-search-input {
                width: 100%;
                padding: 14px 16px 14px 44px;
                background-color: var(--pos-card-bg);
                border: 1px solid rgba(0,0,0,0.02);
                border-radius: 20px;
                box-shadow: var(--pos-shadow);
                font-size: 13px;
                font-weight: 600;
                color: var(--pos-text-main);
                outline: none;
                transition: all 0.3s ease;
            }

            .pos-search-input:focus {
                border-color: rgba(0,0,0,0.1);
                box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            }

            .pos-search-icon {
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--pos-text-muted);
                pointer-events: none;
            }

            /* Menu Grid */
            .pos-menu-grid {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .pos-menu-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background-color: rgba(255, 255, 255, 0.7);
                border: var(--pos-border);
                border-radius: 28px;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                color: var(--pos-text-muted);
                width: 100%;
                outline: none;
            }

            .pos-menu-btn:hover {
                background-color: var(--pos-card-bg);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.01);
            }

            .pos-menu-btn.active {
                background-color: var(--pos-card-bg);
                border: 2px solid var(--pos-primary);
                box-shadow: 0 10px 25px rgba(0,0,0,0.04);
                color: var(--pos-text-main);
            }

            .pos-menu-btn svg {
                width: 26px;
                height: 26px;
                margin-bottom: 8px;
            }

            .pos-menu-btn span {
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.5px;
            }

            /* Promo Code Banner */
            .pos-promo-banner {
                background: linear-gradient(135deg, #FFFCE7 0%, #FFF9D2 100%);
                border: 1px solid rgba(222, 187, 10, 0.2);
                border-radius: 28px;
                padding: 20px;
                box-shadow: var(--pos-shadow);
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .pos-promo-text {
                font-size: 11px;
                font-weight: 800;
                color: #856404;
                line-height: 1.6;
            }

            .pos-promo-input-group {
                display: flex;
                background-color: var(--pos-card-bg);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
                border: var(--pos-border);
            }

            .pos-promo-input {
                width: 100%;
                padding: 10px 12px;
                border: none;
                background: transparent;
                font-size: 11px;
                outline: none;
                color: var(--pos-text-main);
                font-weight: 600;
            }

            .pos-promo-btn {
                background-color: #DEBB0A;
                color: #FFFFFF;
                border: none;
                padding: 10px 14px;
                cursor: pointer;
                transition: background 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .pos-promo-btn:hover {
                background-color: #C0A107;
            }

            /* Brand Header */
            .pos-brand-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
            }

            .pos-brand-title {
                font-size: 24px;
                font-weight: 900;
                color: var(--pos-text-main);
                letter-spacing: -0.5px;
                margin: 0;
            }

            /* Catalog Area */
            .pos-catalog {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }

            .pos-categories-scroller {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                padding-bottom: 8px;
            }

            .pos-categories-scroller::-webkit-scrollbar {
                display: none;
            }

            .pos-category-pill {
                padding: 10px 22px;
                border-radius: 24px;
                font-size: 12px;
                font-weight: 700;
                background-color: var(--pos-card-bg);
                border: none;
                cursor: pointer;
                transition: all 0.3s ease;
                color: var(--pos-text-muted);
                white-space: nowrap;
            }

            .pos-category-pill.active {
                background-color: var(--pos-primary);
                color: #FFFFFF;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .pos-items-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
                gap: 24px;
                margin-top: 40px;
            }

            /* Circular Card Style matching Mockup */
            .pos-item-card {
                position: relative;
                background-color: var(--pos-card-bg);
                border: var(--pos-border);
                border-radius: 36px;
                padding: 24px;
                text-align: center;
                box-shadow: var(--pos-shadow);
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                height: 240px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                margin-top: 40px;
            }

            .pos-item-card:hover {
                transform: translateY(-6px);
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.05);
            }

            .pos-item-avatar {
                position: absolute;
                top: -45px;
                left: 50%;
                transform: translateX(-50%);
                width: 90px;
                height: 90px;
                border-radius: 50%;
                overflow: hidden;
                border: 4px solid var(--pos-card-bg);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
                background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--pos-text-main);
                font-weight: 800;
                font-size: 20px;
            }

            .pos-item-avatar.service {
                background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
                color: #0369A1;
            }

            .pos-item-avatar.product {
                background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
                color: #047857;
            }

            .pos-item-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .pos-item-details {
                margin-top: 45px;
                display: flex;
                flex-direction: column;
                flex-grow: 1;
                justify-content: space-between;
            }

            .pos-item-category {
                font-size: 10px;
                font-weight: 800;
                color: var(--pos-text-muted);
                text-transform: uppercase;
                letter-spacing: 1.5px;
                margin-bottom: 6px;
            }

            .pos-item-name {
                font-size: 13px;
                font-weight: 700;
                color: var(--pos-text-main);
                line-height: 1.4;
                margin: 0;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .pos-item-price-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 12px;
            }

            .pos-item-price-label {
                font-size: 10px;
                font-weight: 800;
                color: var(--pos-text-muted);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .pos-item-price {
                font-size: 15px;
                font-weight: 900;
                color: var(--pos-text-main);
            }

            /* Clients Tab View */
            .pos-clients-panel {
                background-color: var(--pos-card-bg);
                border-radius: 36px;
                padding: 32px;
                box-shadow: var(--pos-shadow);
            }

            .pos-clients-title {
                font-size: 16px;
                font-weight: 900;
                margin-bottom: 20px;
            }

            .pos-clients-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 16px;
                margin-top: 24px;
            }

            .pos-client-card {
                background-color: var(--pos-bg);
                border-radius: 24px;
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border: var(--pos-border);
                transition: all 0.2s ease;
            }

            .pos-client-card:hover {
                background-color: #FFFFFF;
                box-shadow: var(--pos-shadow);
            }

            .pos-client-avatar {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background-color: #E0F2FE;
                color: #0369A1;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 14px;
                text-transform: uppercase;
            }

            .pos-client-info {
                flex-grow: 1;
                margin-left: 12px;
            }

            .pos-client-name {
                font-size: 13px;
                font-weight: 700;
                margin: 0;
            }

            .pos-client-meta {
                font-size: 10px;
                color: var(--pos-text-muted);
                margin: 2px 0 0 0;
            }

            .pos-client-select-btn {
                background-color: var(--pos-primary);
                color: #FFFFFF;
                border: none;
                padding: 8px 16px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                transition: opacity 0.2s;
            }

            .pos-client-select-btn:hover {
                opacity: 0.9;
            }

            .pos-client-selected-badge {
                font-size: 10px;
                font-weight: 800;
                color: #15803D;
                background-color: #DCFCE7;
                padding: 6px 12px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            /* Right Ticket Panel */
            .pos-ticket {
                background-color: var(--pos-card-bg);
                border-radius: 36px;
                padding: 24px;
                box-shadow: var(--pos-shadow);
                display: flex;
                flex-direction: column;
                min-height: 580px;
                justify-content: space-between;
            }

            .pos-ticket-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 11px;
                font-weight: 800;
                color: var(--pos-text-muted);
                margin-bottom: 24px;
            }

            .pos-ticket-time {
                font-size: 13px;
                font-weight: 900;
                color: var(--pos-text-main);
            }

            .pos-ticket-customer-row {
                margin-bottom: 16px;
            }

            .pos-ticket-customer-pill {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background-color: rgba(3, 105, 161, 0.05);
                border: 1px solid rgba(3, 105, 161, 0.1);
                border-radius: 16px;
                padding: 10px 14px;
            }

            .pos-ticket-customer-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background-color: #BAE6FD;
                color: #0369A1;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 11px;
                text-transform: uppercase;
            }

            .pos-ticket-customer-info {
                flex-grow: 1;
                margin-left: 10px;
            }

            .pos-ticket-customer-name {
                font-size: 11px;
                font-weight: 800;
                margin: 0;
            }

            .pos-ticket-customer-sub {
                font-size: 8px;
                color: var(--pos-text-muted);
                margin: 1px 0 0 0;
            }

            .pos-ticket-customer-close {
                background: transparent;
                border: none;
                cursor: pointer;
                color: var(--pos-text-muted);
            }

            .pos-ticket-customer-walkin {
                text-align: center;
                padding: 10px;
                background-color: var(--pos-bg);
                border: var(--pos-border);
                border-radius: 16px;
                font-size: 10px;
                font-weight: 800;
                color: var(--pos-text-muted);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .pos-walkin-card {
                background-color: var(--pos-bg);
                border: var(--pos-border);
                border-radius: 20px;
                padding: 14px;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .pos-walkin-title {
                font-size: 9px;
                font-weight: 800;
                color: var(--pos-text-muted);
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin: 0 0 2px 0;
            }

            .pos-walkin-inputs {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .pos-walkin-input {
                width: 100%;
                padding: 8px 12px;
                border-radius: 10px;
                border: var(--pos-border);
                background-color: #FFFFFF;
                font-size: 11px;
                outline: none;
                color: var(--pos-text-main);
                font-weight: 600;
                transition: border-color 0.2s;
            }

            .pos-walkin-input:focus {
                border-color: rgba(0, 0, 0, 0.1);
            }

            /* Cart Items */
            .pos-ticket-cart {
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-height: 240px;
                overflow-y: auto;
                padding-right: 4px;
                margin-bottom: 20px;
            }

            .pos-ticket-cart::-webkit-scrollbar {
                width: 4px;
            }

            .pos-ticket-cart::-webkit-scrollbar-thumb {
                background-color: #E5E7EB;
                border-radius: 2px;
            }

            .pos-cart-row {
                background-color: var(--pos-bg);
                border-radius: 20px;
                padding: 14px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                border: var(--pos-border);
            }

            .pos-cart-main {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .pos-cart-type-badge {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background-color: #FFFFFF;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--pos-text-muted);
                box-shadow: 0 2px 6px rgba(0,0,0,0.02);
                flex-shrink: 0;
            }

            .pos-cart-type-badge.service {
                color: #0284C7;
            }

            .pos-cart-type-badge.product {
                color: #059669;
            }

            .pos-cart-info {
                flex-grow: 1;
            }

            .pos-cart-name {
                font-size: 11px;
                font-weight: 800;
                margin: 0;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .pos-cart-price-qty {
                font-size: 9px;
                color: var(--pos-text-muted);
                margin: 2px 0 0 0;
                font-weight: 600;
            }

            .pos-cart-right {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .pos-cart-row-total {
                font-size: 11px;
                font-weight: 800;
            }

            .pos-cart-remove-btn {
                background: transparent;
                border: none;
                cursor: pointer;
                color: #D1D5DB;
                transition: color 0.2s;
                padding: 2px;
            }

            .pos-cart-remove-btn:hover {
                color: #EF4444;
            }

            .pos-cart-staff-select {
                width: 100%;
                font-size: 9px;
                padding: 6px;
                border-radius: 8px;
                border: none;
                background-color: #FFFFFF;
                color: var(--pos-text-muted);
                outline: none;
                font-weight: 600;
            }

            /* Dashed Ticket Divider */
            .pos-ticket-divider {
                border-top: 2px dashed #E5E7EB;
                margin: 20px 0;
            }

            /* Totals Section */
            .pos-totals-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .pos-totals-label {
                font-size: 11px;
                font-weight: 800;
                color: var(--pos-text-muted);
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .pos-totals-value {
                font-size: 20px;
                font-weight: 900;
                color: var(--pos-text-main);
            }

            /* Payment Methods buttons */
            .pos-payment-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-bottom: 20px;
            }

            .pos-payment-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 10px;
                border-radius: 16px;
                border: var(--pos-border);
                background-color: transparent;
                cursor: pointer;
                transition: all 0.2s ease;
                color: var(--pos-text-muted);
            }

            .pos-payment-btn:hover {
                background-color: var(--pos-bg);
            }

            .pos-payment-btn.active {
                background-color: var(--pos-primary);
                border: 2px solid var(--pos-primary);
                color: #FFFFFF;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }

            .pos-payment-btn.active svg {
                color: #FFFFFF;
            }

            .pos-payment-btn svg {
                width: 20px;
                height: 20px;
                margin-bottom: 4px;
            }

            .pos-payment-btn span {
                font-size: 9px;
                font-weight: 800;
            }

            /* Checkout Block */
            .pos-checkout-block {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .pos-mpesa-card {
                background-color: var(--pos-bg);
                border-radius: 20px;
                padding: 16px;
                border: var(--pos-border);
            }

            .pos-mpesa-label {
                font-size: 9px;
                font-weight: 800;
                color: var(--pos-text-muted);
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 6px;
                display: block;
            }

            .pos-mpesa-input {
                width: 100%;
                padding: 8px 12px;
                border-radius: 10px;
                border: var(--pos-border);
                background-color: #FFFFFF;
                font-size: 11px;
                outline: none;
                color: var(--pos-text-main);
            }

            .pos-stk-btn {
                width: 100%;
                padding: 12px;
                border-radius: 16px;
                border: none;
                background-color: var(--pos-primary);
                color: #FFFFFF;
                font-weight: 800;
                font-size: 12px;
                cursor: pointer;
                transition: opacity 0.2s;
                margin-top: 10px;
            }

            .pos-stk-btn:hover {
                opacity: 0.9;
            }

            .pos-polling-spinner-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 16px;
                text-align: center;
                gap: 12px;
            }

            .pos-spinner {
                border: 3px solid rgba(0, 0, 0, 0.1);
                width: 28px;
                height: 28px;
                border-radius: 50%;
                border-left-color: var(--pos-primary);
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .pos-polling-text {
                font-size: 11px;
                font-weight: 800;
                margin: 0;
            }

            .pos-polling-sub {
                font-size: 9px;
                color: var(--pos-text-muted);
                margin: 2px 0 0 0;
            }

            .pos-polling-message {
                font-size: 9px;
                font-weight: 700;
                color: #D97706;
                margin-top: 4px;
            }

            .pos-polling-cancel-btn {
                background-color: #EF4444;
                color: #FFFFFF;
                border: none;
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 9px;
                font-weight: 700;
                cursor: pointer;
                margin-top: 8px;
            }

            .pos-action-btn {
                width: 100%;
                padding: 14px;
                border-radius: 20px;
                border: none;
                background-color: var(--pos-primary);
                color: #FFFFFF;
                font-weight: 900;
                font-size: 12px;
                cursor: pointer;
                transition: opacity 0.2s;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .pos-action-btn:hover {
                opacity: 0.9;
            }

            .pos-action-btn:disabled {
                opacity: 0.4;
                cursor: not-allowed;
            }

            /* Custom Modal Styles */
            .pos-modal-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(4px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            .pos-modal-content {
                background: var(--pos-card-bg);
                border-radius: 32px;
                width: 100%;
                max-width: 420px;
                padding: 24px;
                box-shadow: 0 20px 50px rgba(0,0,0,0.15);
                border: var(--pos-border);
                animation: modalFadeIn 0.3s ease;
            }
            @keyframes modalFadeIn {
                from { transform: scale(0.95); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .pos-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .pos-modal-header h3 {
                font-size: 16px;
                font-weight: 900;
                margin: 0;
            }
            .pos-modal-close-btn {
                background: transparent;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: var(--pos-text-muted);
                line-height: 1;
            }
            .pos-modal-footer {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                margin-top: 24px;
            }
            .pos-modal-print-btn {
                padding: 12px;
                border-radius: 16px;
                border: none;
                background-color: #22C55E;
                color: #FFFFFF;
                font-weight: 800;
                font-size: 12px;
                cursor: pointer;
                transition: opacity 0.2s;
            }
            .pos-modal-print-btn:hover {
                opacity: 0.9;
            }
            .pos-modal-dismiss-btn {
                padding: 12px;
                border-radius: 16px;
                border: var(--pos-border);
                background-color: transparent;
                color: var(--pos-text-main);
                font-weight: 800;
                font-size: 12px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .pos-modal-dismiss-btn:hover {
                background-color: var(--pos-bg);
            }

            /* Responsive tweaks */
            @media (max-width: 1024px) {
                .pos-container {
                    grid-template-columns: 1fr;
                }
                .pos-sidebar {
                    flex-direction: row;
                    overflow-x: auto;
                    padding-bottom: 8px;
                }
                .pos-sidebar::-webkit-scrollbar {
                    display: none;
                }
                .pos-menu-grid {
                    flex-direction: row;
                    width: auto;
                }
                .pos-menu-btn {
                    width: 120px;
                }
                .pos-promo-banner {
                    display: none;
                }
            }

            /* Thermal Receipt Screen Styles */
            .thermal-receipt {
                background: #FFFFFF;
                color: #000000;
                font-family: 'Courier New', Courier, monospace;
                padding: 24px;
                border-radius: 4px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 1px solid #E0E0E0;
                width: 100%;
                max-width: 320px;
                margin: 0 auto;
                font-size: 11px;
                line-height: 1.4;
            }
            .thermal-receipt-divider {
                border-top: 1px dashed #000;
                margin: 12px 0;
            }
            .thermal-receipt-header {
                text-align: center;
                margin-bottom: 16px;
            }
            .thermal-receipt-header h2 {
                font-size: 16px;
                font-weight: bold;
                margin: 0 0 4px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .thermal-receipt-header p {
                font-size: 9px;
                margin: 2px 0;
                color: #000000;
            }
            .thermal-receipt-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 6px;
            }
            .thermal-receipt-item-name {
                flex-grow: 1;
                max-width: 70%;
            }
            .thermal-receipt-item-total {
                font-weight: bold;
                white-space: nowrap;
            }
            .thermal-receipt-total-row {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
                font-weight: bold;
                margin-top: 6px;
                padding-top: 6px;
                border-top: 1px dashed #000;
            }
            .thermal-receipt-footer {
                text-align: center;
                margin-top: 16px;
                font-size: 9px;
            }
            .thermal-receipt-barcode {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                margin-top: 16px;
                gap: 4px;
            }
            .thermal-receipt-barcode svg {
                width: 140px;
                height: 30px;
            }
            .thermal-receipt-barcode span {
                font-size: 8px;
                letter-spacing: 1px;
            }

            /* Thermal Print CSS Stylesheet */
            @media print {
                body, html, .pos-terminal-canvas, .pos-container, .pos-sidebar, .pos-catalog, .pos-ticket, .pos-modal-header, .pos-modal-footer, header, aside, footer {
                    display: none !important;
                    visibility: hidden !important;
                }
                .pos-modal-backdrop {
                    position: static !important;
                    background: transparent !important;
                    backdrop-filter: none !important;
                    display: block !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .pos-modal-content {
                    position: static !important;
                    box-shadow: none !important;
                    border: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    background: #FFF !important;
                    display: block !important;
                }
                .pos-modal-body {
                    padding: 0 !important;
                    margin: 0 !important;
                    display: block !important;
                }
                .thermal-receipt {
                    display: block !important;
                    visibility: visible !important;
                    width: 72mm !important; /* Standard 80mm roll width print boundary */
                    margin: 0 auto !important;
                    padding: 10px 0 !important;
                    background: #FFF !important;
                    color: #000 !important;
                    font-family: 'Courier New', Courier, monospace !important;
                    font-size: 11px !important;
                    line-height: 1.3 !important;
                    border: none !important;
                    box-shadow: none !important;
                }
                .thermal-receipt * {
                    color: #000 !important;
                    background: transparent !important;
                }
                @page {
                    size: auto;
                    margin: 0;
                }
            }
        </style>

        <div class="pos-container">
            
            {{-- Section 1: Left Navigation Pane --}}
            <div class="pos-sidebar">
                {{-- Search Box --}}
                <div class="pos-search-wrapper">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchTerm"
                        placeholder="Search..."
                        class="pos-search-input"
                    />
                    <span class="pos-search-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </div>

                {{-- Vertical Menu Items --}}
                <div class="pos-menu-grid">
                    <button
                        wire:click="$set('activeTab', 'services')"
                        @disabled($activeTransactionId !== null)
                        class="pos-menu-btn {{ $activeTab === 'services' ? 'active' : '' }}"
                    >
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Home</span>
                    </button>

                    <button
                        wire:click="$set('activeTab', 'products')"
                        @disabled($activeTransactionId !== null)
                        class="pos-menu-btn {{ $activeTab === 'products' ? 'active' : '' }}"
                    >
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Products</span>
                    </button>

                    <button
                        wire:click="$set('activeTab', 'clients')"
                        @disabled($activeTransactionId !== null)
                        class="pos-menu-btn {{ $activeTab === 'clients' ? 'active' : '' }}"
                    >
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Clients</span>
                    </button>
                </div>

                {{-- Promo Code Box --}}
                <div class="pos-promo-banner">
                    <span class="pos-promo-text">
                        Don't forget to enter your promo code for a special discount on your purchase!
                    </span>
                    <div class="pos-promo-input-group">
                        <input
                            type="text"
                            wire:model="couponCode"
                            placeholder="Enter..."
                            class="pos-promo-input"
                        />
                        <button
                            wire:click="applyCoupon"
                            @disabled($activeTransactionId !== null)
                            class="pos-promo-btn"
                        >
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5.17l-.83 1.66V17a1 1 0 11-2 0v-5.34l-.83-1.66H3a2 2 0 110-4h1.17C4.06 5.68 4.5 5.25 5 5zm4 3H5v1h4V8zm2 0h4v1h-4V8zM8.34 6H5a1 1 0 000 2h3.34l-.83-1.66A.996.996 0 017.5 6a1 1 0 01.84 0zm3.32 0h3.34a1 1 0 010 2h-3.34l.83-1.66a1 1 0 01.84-.34.996.996 0 01-.67 2z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Section 2: Center Grid View --}}
            <div class="pos-catalog">
                @if ($activeTab !== 'clients')
                    {{-- Premium Brand Header --}}
                    <div class="pos-brand-header">
                        <h1 class="pos-brand-title">{{ $tenant ? $tenant->name : 'Wellness' }} POS</h1>
                    </div>

                    {{-- Categories horizontal bar --}}
                    @php
                        $categories = [];
                        if ($tenant) {
                            if ($activeTab === 'services') {
                                $categories = \App\Models\ServiceCategory::whereHas('services', function ($q) use ($tenant) {
                                    $q->whereHas('branches', function ($qb) use ($tenant) {
                                        $qb->where('branches.id', $tenant->id);
                                    });
                                })->get();
                            } else {
                                $categories = \App\Models\ProductCategory::all();
                            }
                        }
                    @endphp

                    <div class="pos-categories-scroller">
                        <button
                            wire:click="$set('selectedCategoryId', null)"
                            class="pos-category-pill {{ $selectedCategoryId === null ? 'active' : '' }}"
                        >
                            All Items
                        </button>
                        @foreach ($categories as $category)
                            <button
                                wire:click="$set('selectedCategoryId', {{ $category->id }})"
                                class="pos-category-pill {{ $selectedCategoryId === $category->id ? 'active' : '' }}"
                            >
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Items Cards Grid --}}
                    <div class="pos-items-grid">
                        @if ($activeTab === 'services')
                            @forelse ($this->services as $service)
                                <div wire:click="addService({{ $service->id }})" class="pos-item-card">
                                    <div class="pos-item-avatar service">
                                        {{ strtoupper(substr($service->name, 0, 2)) }}
                                    </div>
                                    <div class="pos-item-details">
                                        <div>
                                            <p class="pos-item-category">{{ $service->category?->name ?? 'Service' }}</p>
                                            <h3 class="pos-item-name">{{ $service->name }}</h3>
                                        </div>
                                        <div class="pos-item-price-row">
                                            <span class="pos-item-price-label">Price</span>
                                            <span class="pos-item-price">KES {{ number_format($service->price, 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 text-gray-500">
                                    No services found matching filters.
                                </div>
                            @endforelse
                        @else
                            @forelse ($this->products as $product)
                                <div wire:click="addProduct({{ $product->id }})" class="pos-item-card">
                                    <div class="pos-item-avatar product">
                                        @if ($product->getMainImage())
                                            <img src="{{ Storage::url($product->getMainImage()) }}" />
                                        @else
                                            {{ strtoupper(substr($product->name, 0, 2)) }}
                                        @endif
                                    </div>
                                    <div class="pos-item-details">
                                        <div>
                                            <p class="pos-item-category">{{ $product->category?->name ?? 'Product' }}</p>
                                            <h3 class="pos-item-name">{{ $product->name }}</h3>
                                        </div>
                                        <div class="pos-item-price-row">
                                            <span class="pos-item-price-label">Price</span>
                                            <span class="pos-item-price">KES {{ number_format($product->price, 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 text-gray-500">
                                    No products found matching filters.
                                </div>
                            @endforelse
                        @endif
                    </div>
                @else
                    {{-- Clients Directory --}}
                    <div class="pos-clients-panel">
                        <h3 class="pos-clients-title">Client Lookup</h3>
                        <div class="pos-search-wrapper">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="customerSearch"
                                placeholder="Search by name, email, or phone number..."
                                class="pos-search-input"
                            />
                            <span class="pos-search-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                        </div>

                        @if (!empty($customerSearch))
                            <div class="pos-clients-grid">
                                @forelse ($customerSearchResults as $result)
                                    <div class="pos-client-card">
                                        <div class="pos-client-avatar">
                                            {{ strtoupper(substr($result->name, 0, 2)) }}
                                        </div>
                                        <div class="pos-client-info">
                                            <h4 class="pos-client-name">{{ $result->name }}</h4>
                                            <p class="pos-client-meta">{{ $result->phone }} • {{ $result->email }}</p>
                                        </div>
                                        @if ($customerId === $result->id)
                                            <span class="pos-client-selected-badge">
                                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                Selected
                                            </span>
                                        @else
                                            <button
                                                wire:click="selectCustomer({{ $result->id }})"
                                                class="pos-client-select-btn"
                                            >
                                                Select
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <div style="text-align: center; padding: 32px; color: var(--pos-text-muted); font-size: 13px;">
                                        No clients found matching directory search.
                                    </div>
                                @endforelse
                            </div>
                        @else
                            <div style="text-align: center; padding: 48px; color: var(--pos-text-muted);">
                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin: 0 auto 16px auto; opacity: 0.5;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p style="font-size: 13px; font-weight: 600;">Search for a client above to link them to this order.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Section 3: Right Sidebar / Receipt Pane --}}
            <div class="pos-ticket">
                <div>
                    {{-- Time & Date --}}
                    <div class="pos-ticket-header">
                        <span class="pos-ticket-time" 
                              x-data="{ time: '{{ now()->format('H:i') }}' }" 
                              x-init="setInterval(() => { time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false }) }, 1000)" 
                              x-text="time"></span>
                        <span>{{ now()->format('d.m.y') }}</span>
                    </div>

                    {{-- Customer status bar --}}
                    <div class="pos-ticket-customer-row">
                        @if ($customerId)
                            <div class="pos-ticket-customer-pill">
                                <div class="pos-ticket-customer-avatar">
                                    {{ strtoupper(substr($customerName, 0, 2)) }}
                                </div>
                                <div class="pos-ticket-customer-info">
                                    <p class="pos-ticket-customer-name">{{ $customerName }}</p>
                                    <p class="pos-ticket-customer-sub">Linked Client</p>
                                </div>
                                <button wire:click="$set('customerId', null)" @disabled($activeTransactionId !== null) class="pos-ticket-customer-close">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div class="pos-walkin-card">
                                <p class="pos-walkin-title">Walk-in Customer Details</p>
                                <div class="pos-walkin-inputs">
                                    <input 
                                        type="text" 
                                        wire:model="walkinName" 
                                        placeholder="Customer Name (Default: Walk-in)" 
                                        @disabled($activeTransactionId !== null)
                                        class="pos-walkin-input"
                                    />
                                    <input 
                                        type="text" 
                                        wire:model="walkinPhone" 
                                        placeholder="Phone Number (optional)" 
                                        @disabled($activeTransactionId !== null)
                                        class="pos-walkin-input"
                                    />
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Cart Items --}}
                    <div class="pos-ticket-cart">
                        @forelse ($cart as $index => $item)
                            <div class="pos-cart-row">
                                <div class="pos-cart-main">
                                    <div class="pos-cart-type-badge {{ $item['item_type'] === 'service' ? 'service' : 'product' }}">
                                        @if ($item['item_type'] === 'service')
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="pos-cart-info">
                                        <h4 class="pos-cart-name">{{ $item['name'] }}</h4>
                                        <p class="pos-cart-price-qty">{{ $item['quantity'] }}x @ KES {{ number_format($item['unit_price'], 0) }}</p>
                                    </div>
                                    <div class="pos-cart-right">
                                        <span class="pos-cart-row-total">KES {{ number_format($item['unit_price'] * $item['quantity'], 0) }}</span>
                                        <button wire:click="removeItem({{ $index }})" @disabled($activeTransactionId !== null) class="pos-cart-remove-btn">
                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                @if ($item['item_type'] === 'service')
                                    <select
                                        wire:model.change="cart.{{ $index }}.staff_id"
                                        @disabled($activeTransactionId !== null)
                                        class="pos-cart-staff-select"
                                    >
                                        <option value="">Assign Specialist (Optional)</option>
                                        @foreach ($this->staff as $staffMember)
                                            <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @empty
                            <div style="text-align: center; padding: 48px 0; color: var(--pos-text-muted);">
                                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin: 0 auto 12px auto; opacity: 0.3;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                </svg>
                                <span style="font-size: 11px; font-weight: 600;">Your cart is empty.</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div>
                    {{-- Ticket Dashed separator --}}
                    <div class="pos-ticket-divider"></div>

                    {{-- Ticket Total --}}
                    <div class="pos-totals-row">
                        <span class="pos-totals-label">Total</span>
                        <span class="pos-totals-value">KES {{ number_format($totals['total'], 0) }}</span>
                    </div>

                    {{-- Payment grid layout --}}
                    <div class="pos-payment-grid">
                        <button
                            wire:click="$set('paymentMethod', 'cash')"
                            @disabled($activeTransactionId !== null)
                            class="pos-payment-btn {{ $paymentMethod === 'cash' ? 'active' : '' }}"
                        >
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Cash</span>
                        </button>

                        <button
                            wire:click="$set('paymentMethod', 'card')"
                            @disabled($activeTransactionId !== null)
                            class="pos-payment-btn {{ $paymentMethod === 'card' ? 'active' : '' }}"
                        >
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span>Card</span>
                        </button>

                        <button
                            wire:click="$set('paymentMethod', 'mpesa')"
                            @disabled($activeTransactionId !== null)
                            class="pos-payment-btn {{ $paymentMethod === 'mpesa' ? 'active' : '' }}"
                        >
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>M-Pesa</span>
                        </button>
                    </div>

                    {{-- Checkout buttons block --}}
                    <div class="pos-checkout-block">
                        @if ($paymentMethod === 'mpesa')
                            <div class="pos-mpesa-card">
                                @if ($activeTransactionId === null)
                                    <label class="pos-mpesa-label">M-Pesa Phone Number</label>
                                    <input
                                        type="text"
                                        wire:model="mpesaPhone"
                                        placeholder="e.g. 0712345678"
                                        class="pos-mpesa-input"
                                    />
                                    <button
                                        wire:click="processPayment"
                                        :disabled="empty($cart)"
                                        class="pos-stk-btn"
                                    >
                                        Send STK Push
                                    </button>
                                @else
                                    <div class="pos-polling-spinner-container">
                                        <div class="pos-spinner"></div>
                                        <div>
                                            <p class="pos-polling-text">Waiting for approval...</p>
                                            <p class="pos-polling-sub">Please enter your PIN on your Safaricom device.</p>
                                            @if ($paymentMessage)
                                                <p class="pos-polling-message">{{ $paymentMessage }}</p>
                                            @endif
                                        </div>
                                        <button
                                            wire:click="resetCart"
                                            class="pos-polling-cancel-btn"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <button
                                wire:click="processPayment"
                                :disabled="empty($cart)"
                                class="pos-action-btn"
                            >
                                Complete Checkout
                            </button>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Custom Neomorphic Receipt View Modal --}}
        @if ($showReceiptModal)
            <div class="pos-modal-backdrop">
                <div class="pos-modal-content">
                    <div class="pos-modal-header">
                        <h3>Receipt Generated</h3>
                        <button wire:click="$set('showReceiptModal', false)" class="pos-modal-close-btn">&times;</button>
                    </div>
                    <div class="pos-modal-body">
                        @if ($lastReceipt)
                            <div class="thermal-receipt">
                                <div class="thermal-receipt-header">
                                    <h2>{{ $lastReceipt['branch'] }}</h2>
                                    <p>Wellness & Spa Center</p>
                                    <p>Date: {{ $lastReceipt['date'] }}</p>
                                    <p>Receipt No: {{ $lastReceipt['transaction_number'] }}</p>
                                </div>

                                <div class="thermal-receipt-divider"></div>

                                <div class="thermal-receipt-items">
                                    @foreach ($lastReceipt['items'] as $item)
                                        <div class="thermal-receipt-row">
                                            <span class="thermal-receipt-item-name">
                                                {{ $item['quantity'] }}x {{ $item['name'] }}
                                            </span>
                                            <span class="thermal-receipt-item-total">
                                                KES {{ number_format($item['total'], 0) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="thermal-receipt-divider"></div>

                                <div class="thermal-receipt-totals">
                                    <div class="thermal-receipt-row">
                                        <span>Subtotal</span>
                                        <span>KES {{ number_format($lastReceipt['subtotal'], 0) }}</span>
                                    </div>
                                    <div class="thermal-receipt-row">
                                        <span>Sales Tax (16%)</span>
                                        <span>KES {{ number_format($lastReceipt['tax'], 0) }}</span>
                                    </div>
                                    <div class="thermal-receipt-total-row">
                                        <span>TOTAL PAID</span>
                                        <span>KES {{ number_format($lastReceipt['total'], 0) }}</span>
                                    </div>
                                </div>

                                <div class="thermal-receipt-divider"></div>

                                <div class="thermal-receipt-footer">
                                    <p>Payment Method: <span style="text-transform: uppercase; font-weight: bold;">{{ $lastReceipt['payment_method'] }}</span></p>
                                    <p>Operator: {{ $lastReceipt['staff'] }}</p>
                                    <p style="font-weight: bold; margin-top: 10px; font-style: italic;">Thank you for visiting Ascend Wellness!</p>
                                </div>

                                <div class="thermal-receipt-barcode">
                                    <svg class="barcode-svg" viewBox="0 0 100 20">
                                        <rect x="5" y="0" width="2" height="20" fill="black"/>
                                        <rect x="8" y="0" width="1" height="20" fill="black"/>
                                        <rect x="11" y="0" width="3" height="20" fill="black"/>
                                        <rect x="16" y="0" width="1" height="20" fill="black"/>
                                        <rect x="18" y="0" width="2" height="20" fill="black"/>
                                        <rect x="22" y="0" width="4" height="20" fill="black"/>
                                        <rect x="28" y="0" width="1" height="20" fill="black"/>
                                        <rect x="30" y="0" width="2" height="20" fill="black"/>
                                        <rect x="34" y="0" width="1" height="20" fill="black"/>
                                        <rect x="36" y="0" width="3" height="20" fill="black"/>
                                        <rect x="41" y="0" width="2" height="20" fill="black"/>
                                        <rect x="45" y="0" width="1" height="20" fill="black"/>
                                        <rect x="48" y="0" width="4" height="20" fill="black"/>
                                        <rect x="54" y="0" width="2" height="20" fill="black"/>
                                        <rect x="57" y="0" width="1" height="20" fill="black"/>
                                        <rect x="60" y="0" width="3" height="20" fill="black"/>
                                        <rect x="65" y="0" width="1" height="20" fill="black"/>
                                        <rect x="68" y="0" width="2" height="20" fill="black"/>
                                        <rect x="72" y="0" width="4" height="20" fill="black"/>
                                        <rect x="78" y="0" width="1" height="20" fill="black"/>
                                        <rect x="80" y="0" width="2" height="20" fill="black"/>
                                        <rect x="84" y="0" width="3" height="20" fill="black"/>
                                        <rect x="89" y="0" width="1" height="20" fill="black"/>
                                        <rect x="92" y="0" width="2" height="20" fill="black"/>
                                    </svg>
                                    <span>{{ $lastReceipt['transaction_number'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="pos-modal-footer">
                        <button onclick="window.print()" class="pos-modal-print-btn">Print Receipt</button>
                        <button wire:click="$set('showReceiptModal', false)" class="pos-modal-dismiss-btn">Dismiss</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
