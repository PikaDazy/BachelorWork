<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

use App\Models\Client;

new class extends Component {
    use Toast, WithFileUploads;

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|digits:10')]
    public string $phone = '';

    #[Rule('required')]
    public string $address = '';
    // Optional

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [
        ];
    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        // Update
        if ($this->_isClientExists($data['email'])) {
            $this->error('Client already exists.', redirectTo: '/users/create');
            return;
        }

        $client = new Client();
        $client->name = $data['name'];
        $client->email = $this->email;
        $client->phone = $this->phone;
        $client->address = $this->address;
        $client->save();

        // You can toast and redirect to any route
        $this->success('Клієнт оновлен успішно.', redirectTo: '/clients/' . $client->id . '/edit');
    }

    private function _isClientExists(string $email): bool
    {
        if (
            Client::query()->where('email', $email)->first()
        ) {
            return true;
        }

        return false;
    }
}; ?>

<div>
    <x-header title="Створити клієнта" separator />
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-input label="Ім'я" wire:model="name" />
                <x-input label="Email" wire:model="email" />
                <x-input label="Телефон" wire:model="phone" />
                <x-input label="Адреса" wire:model="address" />

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users" />
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto" />
        </div>
    </div>
</div>

