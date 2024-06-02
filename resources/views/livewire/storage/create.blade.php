<?php

use App\Models\Storage;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast, WithFileUploads;

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $address = '';

    #[Rule('nullable|numeric')]
    public int $square;

    #[Rule('nullable|numeric')]
    public int $height;

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [];
    }

    public function save(): void
    {
        // Validate
        $this->validate();

        $storage = new Storage;
        $storage->address = $this->address;
        $storage->height = $this->height;
        $storage->square = $this->square;
        $storage->capacity = $this->height * $this->square;
        $storage->save();

        // You can toast and redirect to any route
        $this->success('Склад оновлено успішно.', redirectTo: '/storage/' . $storage->id . '/edit');
    }
}; ?>

<div>
    <x-header title="Створити Склад" separator/>
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-textarea
                    label="Адреса"
                    wire:model="address"
                    placeholder="Add storage address here ..."
                    hint="Max 1000 chars"
                    rows="5"
                    inline/>

                <x-input
                    label="Площа"
                    wire:model="square"
                    type="number"
                />

                <x-input
                    label="Висота"
                    wire:model="height"
                    type="number"
                />

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users"/>
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto"/>
        </div>
    </div>
</div>
