<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

use App\Models\Client;

new class extends Component {
    use Toast, WithFileUploads;

    public Client $client;

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

    public function mount(): void
    {
        $this->fill($this->client);
    }

    public function save(): void
    {
        // Validate
        $this->validate();

        $this->client->name = $this->name;
        $this->client->email = $this->email;
        $this->client->phone = $this->phone;
        $this->client->address = $this->address;
        $this->client->save();

        // You can toast and redirect to any route
        $this->success('Клієнт оновлен успішно.', redirectTo: '/clients/' . $this->client->id . '/edit');
    }

}; ?>

<div>
    <x-header title="Оновити клієнта" separator />
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-input label="Ім'я" wire:model="name" />
                <x-input label="Email" wire:model="email" />
                <x-input label="Телефон" wire:model="phone" />
                <x-input label="Адреса" wire:model="address" />

                <x-slot:actions>
                    <x-button label="Відмінити" link="/clients" />
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

