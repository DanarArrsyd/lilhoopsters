<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.import.title') }}</h2>
            <p class="text-sm text-muted">{{ __('messages.admin.import.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.members.template') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-navy/8 text-navy font-bold text-sm hover:bg-navy/15 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            {{ __('messages.admin.import.download_template') }}
        </a>
    </div>

    {{-- How it works --}}
    <x-card padding="p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-muted mb-3">{{ __('messages.admin.import.how_it_works') }}</p>
        <ol class="space-y-1.5 text-sm text-ink list-decimal list-inside">
            <li>{{ __('messages.admin.import.step_1') }}</li>
            <li>{{ __('messages.admin.import.step_2') }}</li>
            <li>{!! __('messages.admin.import.step_3', ['cols' => '<strong>'.__('messages.admin.import.step_3_cols').'</strong>']) !!}</li>
            <li>{{ __('messages.admin.import.step_4') }}</li>
            <li>{{ __('messages.admin.import.step_5_pre') }} <strong>{{ __('messages.admin.import.import_btn') }}</strong>{{ __('messages.admin.import.step_5_post') }}</li>
        </ol>
        <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-100 text-xs text-amber-800">
            <strong>{{ __('messages.admin.import.note_label') }}</strong> {{ __('messages.admin.import.note_body') }}
        </div>
    </x-card>

    {{-- Upload form --}}
    @if (!$done)
        <x-card padding="p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-muted mb-4">{{ __('messages.admin.import.upload_file') }}</p>

            <div class="space-y-4">
                {{-- Drop zone --}}
                <label @class([
                    'flex flex-col items-center justify-center w-full py-10 rounded-2xl cursor-pointer transition-all duration-150 group',
                    'border-2 border-dashed border-navy/20 bg-navy/3 hover:border-navy/40 hover:bg-navy/5' => !$file,
                    'border-2 border-navy bg-navy/5' => $file,
                ])>
                    <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="hidden">

                    @if ($file)
                        <div class="flex items-center gap-4 px-6">
                            <div class="w-11 h-11 rounded-xl bg-navy flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-navy truncate">{{ $file->getClientOriginalName() }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ number_format($file->getSize() / 1024, 1) }} KB · Click to change file</p>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-3 text-center px-6">
                            <div class="w-11 h-11 rounded-xl border-2 border-dashed border-line group-hover:border-navy/30 flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5 text-muted group-hover:text-navy transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-ink">{{ __('messages.admin.import.click_to_upload') }} <span class="text-muted font-normal">{{ __('messages.admin.import.or_drag_drop') }}</span></p>
                                <p class="text-xs text-faint mt-0.5">.xlsx · .xls · .csv &nbsp;—&nbsp; max 5 MB</p>
                            </div>
                        </div>
                    @endif
                </label>

                @error('file')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex justify-end">
                    <button wire:click="import"
                            wire:loading.attr="disabled"
                            {{ !$file ? 'disabled' : '' }}
                            @class([
                                'inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150',
                                'bg-navy text-white hover:bg-navy/85 active:scale-95 cursor-pointer' => $file,
                                'bg-line text-muted cursor-not-allowed' => !$file,
                            ])>
                        <span wire:loading.remove wire:target="import" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            {{ __('messages.admin.import.import_btn') }}
                        </span>
                        <span wire:loading wire:target="import" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ __('messages.admin.import.processing') }}
                        </span>
                    </button>
                </div>
            </div>
        </x-card>
    @endif

    {{-- Results --}}
    @if ($done)
        {{-- Summary banner --}}
        <div @class([
            'flex items-center justify-between gap-4 px-5 py-4 rounded-2xl border',
            'bg-green-50 border-green-200'  => $errorCount === 0,
            'bg-amber-50 border-amber-200'  => $errorCount > 0 && $okCount > 0,
            'bg-red-50 border-red-200'      => $okCount === 0 && $errorCount > 0,
        ])>
            <div>
                <p @class([
                    'font-extrabold text-lg',
                    'text-green-700' => $errorCount === 0,
                    'text-amber-700' => $errorCount > 0 && $okCount > 0,
                    'text-red-700'   => $okCount === 0 && $errorCount > 0,
                ])>
                    @if ($okCount === 0 && $errorCount === 0)
                        No data rows found
                    @elseif ($errorCount === 0)
                        {{ $okCount }} {{ Str::plural('member', $okCount) }} imported successfully
                    @else
                        {{ $okCount }} imported · {{ $errorCount }} {{ Str::plural('error', $errorCount) }}
                    @endif
                </p>
                <p class="text-xs text-muted mt-0.5">Review the details below.</p>
            </div>
            <x-btn variant="secondary" wire:click="clearResults">{{ __('messages.admin.import.import_another') }}</x-btn>
        </div>

        {{-- Row-by-row results --}}
        @if (!empty($results))
            <x-card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line">
                                <th class="text-left py-2.5 px-4 font-bold text-muted w-16">{{ __('messages.admin.import.col_row') }}</th>
                                <th class="text-left py-2.5 px-4 font-bold text-muted">{{ __('messages.admin.import.col_child') }}</th>
                                <th class="text-left py-2.5 px-4 font-bold text-muted">{{ __('messages.admin.import.col_result') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($results as $r)
                                <tr @class(['hover:bg-off transition-colors', 'bg-red-50/50 hover:bg-red-50' => $r['status'] === 'error'])>
                                    <td class="py-2.5 px-4 text-faint text-xs font-mono">{{ $r['row'] }}</td>
                                    <td class="py-2.5 px-4 font-semibold text-ink">{{ $r['name'] ?? '—' }}</td>
                                    <td class="py-2.5 px-4">
                                        @if ($r['status'] === 'ok')
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                                                <span class="text-green-700 text-xs">{{ $r['message'] }}</span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                                <span class="text-red-700 text-xs">{{ $r['message'] }}</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    @endif

</div>
