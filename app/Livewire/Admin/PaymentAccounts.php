<?php

namespace App\Livewire\Admin;

use App\Models\PaymentAccount;
use Livewire\Component;

class PaymentAccounts extends Component
{
    public bool    $showForm      = false;
    public ?int    $editingId     = null;

    public string  $bankName      = '';
    public string  $type          = 'bank';
    public string  $accountNumber = '';
    public string  $accountHolder = '';
    public bool    $isActive      = true;

    protected function rules(): array
    {
        return [
            'bankName'      => 'required|string|max:100',
            'type'          => 'required|in:bank,ewallet',
            'accountNumber' => 'required|string|max:100',
            'accountHolder' => 'required|string|max:100',
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $acc = PaymentAccount::findOrFail($id);
        $this->editingId     = $id;
        $this->bankName      = $acc->bank_name;
        $this->type          = $acc->type;
        $this->accountNumber = $acc->account_number;
        $this->accountHolder = $acc->account_holder;
        $this->isActive      = $acc->is_active;
        $this->showForm      = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'bank_name'      => $this->bankName,
            'type'           => $this->type,
            'account_number' => $this->accountNumber,
            'account_holder' => $this->accountHolder,
            'is_active'      => $this->isActive,
        ];

        if ($this->editingId) {
            PaymentAccount::findOrFail($this->editingId)->update($data);
        } else {
            $data['sort_order'] = PaymentAccount::max('sort_order') + 1;
            PaymentAccount::create($data);
        }

        session()->flash('accounts_success', 'Payment account saved.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        PaymentAccount::findOrFail($id)->delete();
        session()->flash('accounts_success', 'Account removed.');
    }

    public function toggleActive(int $id): void
    {
        $acc = PaymentAccount::findOrFail($id);
        $acc->update(['is_active' => !$acc->is_active]);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId     = null;
        $this->bankName      = '';
        $this->type          = 'bank';
        $this->accountNumber = '';
        $this->accountHolder = '';
        $this->isActive      = true;
        $this->showForm      = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.payment-accounts', [
            'accounts' => PaymentAccount::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
}
