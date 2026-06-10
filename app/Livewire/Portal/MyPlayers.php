<?php

namespace App\Livewire\Portal;

use App\Models\Child;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyPlayers extends Component
{
    public bool $showForm  = false;
    public ?int $editingId = null;

    public string $name          = '';
    public string $birthDate     = '';
    public string $gender        = '';
    public string $school        = '';
    public string $medicalNotes  = '';
    public string $jerseyName    = '';
    public string $jerseyNumber  = '';

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm  = true;
        $this->editingId = null;
    }

    public function openEdit(int $id): void
    {
        $child = $this->ownedChild($id);

        $this->editingId    = $id;
        $this->name         = $child->name;
        $this->birthDate    = $child->birth_date->format('Y-m-d');
        $this->gender       = $child->gender;
        $this->school       = $child->school ?? '';
        $this->medicalNotes = $child->medical_notes ?? '';
        $this->jerseyName   = $child->jersey_name ?? '';
        $this->jerseyNumber = $child->jersey_number ?? '';
        $this->showForm     = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'      => 'required|string|max:100',
            'birthDate' => 'required|date|before:today',
            'gender'    => 'required|in:male,female',
            'school'    => 'nullable|string|max:100',
        ]);

        $data = [
            'name'          => $this->name,
            'birth_date'    => $this->birthDate,
            'gender'        => $this->gender,
            'school'        => $this->school ?: null,
            'medical_notes' => $this->medicalNotes ?: null,
        ];

        if ($this->editingId) {
            $child = $this->ownedChild($this->editingId);

            // Only allow jersey edits if child is active
            if ($child->status === 'active') {
                $data['jersey_name']   = $this->jerseyName ?: null;
                $data['jersey_number'] = $this->jerseyNumber ?: null;
            }

            $child->update($data);
            session()->flash('success', 'Player updated.');
        } else {
            Auth::user()->children()->create($data);
            session()->flash('success', 'Player added. Please complete registration to enroll.');
        }

        $this->showForm  = false;
        $this->editingId = null;
        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->showForm  = false;
        $this->editingId = null;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.portal.my-players', [
            'children' => Auth::user()->children()
                ->withCount('enrollments')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->name         = '';
        $this->birthDate    = '';
        $this->gender       = '';
        $this->school       = '';
        $this->medicalNotes = '';
        $this->jerseyName   = '';
        $this->jerseyNumber = '';
    }

    private function ownedChild(int $id): Child
    {
        return Auth::user()->children()->findOrFail($id);
    }
}
