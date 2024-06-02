<?php

use App\Models\Material;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast, WithFileUploads;

    #[Rule('required')]
    public string $name = '';

    #[Rule('sometimes')]
    public string $description = '';


    #[Rule('nullable|image|max:1024')]
    public $photo;

    public function with(): array
    {
        return [];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->_isMaterialExists($data['name'])) {
            $this->error('Material already exists.', redirectTo: '/users/create');
            return;
        }

        $material = new Material();
        $material->name = $data['name'];
        $material->description = $data['description'];

        $material->save();

        if ($this->photo) {
            $url = $this->photo->store('materials', 'public');
            $material->update(['url' => "/storage/$url"]);
        }

        $this->success('Material created successfully.', redirectTo: '/materials/list');
    }

    private function _isMaterialExists(string $name): bool
    {
        return (bool) Material::query()->where('name', $name)->first();
    }

}; ?>

<div>
    <x-header title="Створити матеріал" separator />
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-file label="Зображення" wire:model="photo" accept="image/png, image/jpeg" crop-after-change>
                    <img src="/empty-product.png" class="h-40 rounded-lg" />
                </x-file>

                <x-input label="Ім'я" wire:model="name" />
                <x-textarea
                    label="Опис"
                    wire:model="description"
                    placeholder="Додайте опис тут ..."
                    hint="Max 1000 chars"
                    rows="5"
                    inline />

                <x-slot:actions>
                    <x-button label="Відмінити" link="/materials/create" />
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto" />
        </div>
    </div>
</div>
