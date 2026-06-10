<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">My Players</h2>
            <p class="text-sm text-slate-500">Manage your children's profiles</p>
        </div>
        <x-btn variant="primary" wire:click="openCreate">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Player
        </x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Players list --}}
    @if ($children->isEmpty())
        <x-empty-state
            title="No players yet"
            description="Add your child to get started with enrollment." />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($children as $child)
                @php $months = $child->ageInMonths(); @endphp
                <x-card padding="p-5" class="relative">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shrink-0
                            {{ $child->gender === 'male' ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600' }}">
                            {{ strtoupper(substr($child->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-slate-900">{{ $child->name }}</p>
                                <x-badge :status="$child->status">{{ ucfirst($child->status) }}</x-badge>
                            </div>
                            <p class="text-sm text-slate-500 mt-0.5">
                                @if ($months >= 12)
                                    {{ floor($months / 12) }}yr {{ $months % 12 > 0 ? ($months % 12) . 'mo' : '' }}
                                @else
                                    {{ $months }}mo
                                @endif
                                &middot; {{ ucfirst($child->gender) }}
                            </p>
                            @if ($child->jersey_name || $child->jersey_number)
                                <p class="text-xs text-slate-400 mt-1">
                                    Jersey: {{ $child->jersey_name ?? '—' }}
                                    @if ($child->jersey_number)
                                        #{{ $child->jersey_number }}
                                    @endif
                                </p>
                            @endif
                            @if ($child->school)
                                <p class="text-xs text-slate-400">{{ $child->school }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($child->status === 'unregistered')
                        <div class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <p class="text-xs text-amber-700 font-medium">Not yet registered</p>
                            <p class="text-xs text-amber-600 mt-0.5">Go to <a href="{{ route('parent.enroll') }}" class="underline">Enroll Player</a> to start registration.</p>
                        </div>
                    @elseif ($child->status === 'pending')
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <p class="text-xs text-blue-700 font-medium">Waiting for admin approval</p>
                            <p class="text-xs text-blue-600 mt-0.5">We'll notify you once approved.</p>
                        </div>
                    @endif

                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs text-slate-400">{{ $child->enrollments_count }} enrollment(s)</span>
                        <x-btn variant="secondary" size="sm" wire:click="openEdit({{ $child->id }})">Edit</x-btn>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Form modal --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">
                        {{ $editingId ? 'Edit Player' : 'Add New Player' }}
                    </h3>
                </div>

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-slate-700">Full Name <span class="text-red-500">*</span></label>
                            <x-input wire:model="name" placeholder="e.g. Budi Santoso" />
                            @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-slate-700">Date of Birth <span class="text-red-500">*</span></label>
                            <x-input type="date" wire:model="birthDate" />
                            @error('birthDate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-slate-700">Gender <span class="text-red-500">*</span></label>
                            <select wire:model="gender"
                                    class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                                <option value="">Select gender...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            @error('gender') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-slate-700">School</label>
                            <x-input wire:model="school" placeholder="e.g. SD Negeri 01" />
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-slate-700">Medical Notes</label>
                            <textarea wire:model="medicalNotes" rows="2"
                                      class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                      placeholder="Allergies, conditions, etc. (optional)"></textarea>
                        </div>

                        @if ($editingId && $children->find($editingId)?->status === 'active')
                            <div class="border-t border-slate-100 pt-4">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Jersey Info</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-slate-700">Jersey Name</label>
                                        <x-input wire:model="jerseyName" placeholder="e.g. BUDI" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-sm font-medium text-slate-700">Number</label>
                                        <x-input wire:model="jerseyNumber" placeholder="e.g. 23" />
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1 justify-center" wire:click="cancel">Cancel</x-btn>
                    <x-btn variant="primary" class="flex-1 justify-center" wire:click="save"
                           wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Add Player' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
