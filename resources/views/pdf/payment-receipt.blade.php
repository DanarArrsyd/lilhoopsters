@php
    $navy   = '#1A2F5E';
    $green  = '#15803D';
    $line   = '#DDE4F0';
    $muted  = '#6B7280';
    $ink    = '#111827';

    $academy = $settings['academy_name']    ?? "Lil' Hoopsters";
    $addr    = $settings['academy_address']  ?? '';
    $phone   = $settings['academy_phone']    ?? '';
    $email   = $settings['academy_email']    ?? '';
    $website = $settings['academy_website']  ?? '';

    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: {{ $ink }}; font-size: 12px; line-height: 1.5; }
        .wrap { padding: 0; }
        table { width: 100%; border-collapse: collapse; }

        .header { background: {{ $navy }}; color: #fff; padding: 24px 32px; }
        .brand { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
        .brand-sub { font-size: 10px; color: #C7D2E8; margin-top: 3px; }
        .doc-title { font-size: 22px; font-weight: bold; text-align: right; letter-spacing: 1px; }
        .doc-sub { font-size: 10px; color: #C7D2E8; text-align: right; margin-top: 2px; }

        .body { padding: 24px 32px; }

        .meta td { padding: 2px 0; font-size: 12px; }
        .meta .k { color: {{ $muted }}; width: 90px; }
        .meta .v { font-weight: bold; color: {{ $ink }}; }

        .stamp { display: inline-block; background: {{ $green }}; color: #fff; font-size: 11px;
                 font-weight: bold; letter-spacing: 1px; padding: 6px 16px; border-radius: 4px; }

        .section-label { font-size: 9px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase;
                         color: {{ $muted }}; margin-bottom: 6px; }

        .rule { border: none; border-top: 1px solid {{ $line }}; margin: 20px 0; }

        .items th { text-align: left; font-size: 9px; letter-spacing: 1px; text-transform: uppercase;
                    color: {{ $muted }}; border-bottom: 2px solid {{ $navy }}; padding: 0 0 8px; }
        .items th.r, .items td.r { text-align: right; }
        .items td { padding: 12px 0; border-bottom: 1px solid {{ $line }}; vertical-align: top; }
        .items .desc { font-weight: bold; color: {{ $ink }}; font-size: 13px; }
        .items .meta-line { color: {{ $muted }}; font-size: 11px; margin-top: 2px; }

        .total-row td { padding-top: 14px; font-size: 15px; font-weight: bold; color: {{ $navy }}; }

        .payinfo td { padding: 3px 0; font-size: 11px; }
        .payinfo .k { color: {{ $muted }}; width: 110px; }
        .payinfo .v { color: {{ $ink }}; font-weight: bold; }

        .footer { margin-top: 28px; padding-top: 14px; border-top: 1px solid {{ $line }};
                  color: {{ $muted }}; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">

    {{-- Header --}}
    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: middle;">
                    <div class="brand">{{ strtoupper($academy) }}</div>
                    @if ($addr)   <div class="brand-sub">{{ $addr }}</div> @endif
                    <div class="brand-sub">
                        @if ($phone) {{ $phone }} @endif
                        @if ($phone && $email) &middot; @endif
                        @if ($email) {{ $email }} @endif
                    </div>
                </td>
                <td style="vertical-align: middle;">
                    <div class="doc-title">{{ __('messages.receipt.title') }}</div>
                    <div class="doc-sub">{{ __('messages.receipt.subtitle') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="body">

        {{-- Meta + stamp --}}
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <table class="meta">
                        <tr>
                            <td class="k">{{ __('messages.receipt.receipt_no') }}</td>
                            <td class="v">{{ $trx->transaction_code }}</td>
                        </tr>
                        <tr>
                            <td class="k">{{ __('messages.receipt.issued') }}</td>
                            <td class="v">{{ optional($trx->paid_at)->translatedFormat('d F Y') ?? '—' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top;">
                    <span class="stamp">&#10003; {{ __('messages.receipt.paid') }}</span>
                </td>
            </tr>
        </table>

        <hr class="rule">

        {{-- Billed to --}}
        <div class="section-label">{{ __('messages.receipt.billed_to') }}</div>
        <div style="font-weight: bold; font-size: 13px;">{{ $trx->user->name ?? '—' }}</div>
        @if ($trx->user?->email)
            <div style="color: {{ $muted }}; font-size: 11px;">{{ $trx->user->email }}</div>
        @endif
        @if ($trx->child)
            <div style="font-size: 11px; margin-top: 4px;">
                <span style="color: {{ $muted }};">{{ __('messages.receipt.player') }}:</span>
                <span style="font-weight: bold;">{{ $trx->child->name }}</span>
            </div>
        @endif

        <hr class="rule">

        {{-- Line item --}}
        <table class="items">
            <tr>
                <th>{{ __('messages.receipt.description') }}</th>
                <th class="r">{{ __('messages.receipt.amount') }}</th>
            </tr>
            <tr>
                <td>
                    <div class="desc">{{ $trx->package->name ?? __('messages.receipt.payment_item') }}</div>
                    <div class="meta-line">
                        @if ($trx->package?->location) {{ $trx->package->location->name }} @endif
                        @if ($trx->enrollment)
                            @if ($trx->package?->location) &middot; @endif
                            {{ ucfirst($trx->enrollment->type) }}
                        @endif
                    </div>
                </td>
                <td class="r" style="font-weight: bold;">{{ $rp($trx->amount) }}</td>
            </tr>
            <tr class="total-row">
                <td>{{ __('messages.receipt.total_paid') }}</td>
                <td class="r">{{ $rp($trx->amount) }}</td>
            </tr>
        </table>

        <hr class="rule">

        {{-- Payment info + QR --}}
        <table>
            <tr>
                <td style="width: 70%; vertical-align: top;">
                    <div class="section-label">{{ __('messages.receipt.payment_info') }}</div>
                    <table class="payinfo">
                        <tr>
                            <td class="k">{{ __('messages.receipt.method') }}</td>
                            <td class="v">{{ $trx->payment_method ? ucfirst($trx->payment_method) : __('messages.receipt.transfer') }}</td>
                        </tr>
                        <tr>
                            <td class="k">{{ __('messages.receipt.paid_at') }}</td>
                            <td class="v">{{ optional($trx->paid_at)->translatedFormat('d F Y, H:i') ?? '—' }}</td>
                        </tr>
                        @if ($trx->verifiedBy)
                        <tr>
                            <td class="k">{{ __('messages.receipt.verified_by') }}</td>
                            <td class="v">{{ $trx->verifiedBy->name }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
                <td style="width: 30%; text-align: right; vertical-align: top;">
                    <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR" style="width: 96px; height: 96px;">
                    <div style="font-size: 8px; color: {{ $muted }}; margin-top: 2px;">{{ __('messages.receipt.scan_verify') }}</div>
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <div class="footer">
            {{ __('messages.receipt.computer_generated') }}<br>
            {{ __('messages.receipt.generated_on') }} {{ now()->translatedFormat('d F Y, H:i') }}
            @if ($website) &middot; {{ $website }} @endif
        </div>

    </div>
</div>
</body>
</html>
