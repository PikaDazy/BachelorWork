<?php

use App\Models\Manufacture;
use App\Models\Material;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use Toast, WithFileUploads;

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('required')]
    public string $address = '';

    #[Rule('sometimes')]
    public array $manufactureMaterials = [];

    #[Validate([
        'materialsPrice' => 'required',
        'materialsPrice.*' => 'required',
    ], )]
    public array $materialsPrice = [];

    public function save()
    {

        $this->validate();

        if ($this->_isManufacture($this->name)) {
            $this->error('Product already exists.', redirectTo: '/manufacture/create');
            return;
        }

        if(!$this->validatePrices()) {
            return;
        }

        // save manufacture data
        $manufacture = [
            'name' => $this->name,
            'address' => $this->address,
        ];

        $relatedMaterials = [];
        foreach ($this->materialsPrice as $materialId => $materialprice) {
            $materialprice = (int) $materialprice ?: null;

            $relatedMaterials[] = [
                'material_id' => $materialId,
                'price' => $materialprice
            ];
        }


        //save manufacture
        $manufacture = Manufacture::create($manufacture);
        // link materials
        $manufacture->materials()->sync($relatedMaterials);

        $this->success('Виробництво успішно додане', redirectTo: '/manufacture/' . $manufacture->id . '/edit');

    }


    private function validatePrices(): bool
    {
        $success = true;

        if (!empty($this->manufactureMaterials) && empty($this->materialsPrice)) {
            foreach ($this->manufactureMaterials as $materialId) {
                $this->addError(
                    'materialsPrice.' . $materialId,
                    'Необхідно ввести ціну матеріала'
                );
            }

            $success = false;
        }

        foreach ($this->materialsPrice as $materialId => $price) {
            if (is_null($price)) {
                $this->addError(
                    'materialsPrice.' . $materialId,
                    'Material price is required'
                );
                $success = false;
            }
            if (!is_numeric($price)) {
                $this->addError(
                    'materialsPrice.' . $materialId,
                    'Material price  must be a number'
                );
                $success = false;
            }
        }

        return $success;
    }

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [
            'materials' => Material::where('is_deleted', false)->get(),
        ];
    }

    public function materialsById(): Material
    {
        return Material::whereIn('id', $this->manufactureMaterials)->get();
    }

    private function _isManufacture(string $name): bool
    {
        return (bool)Manufacture::query()->where('name', $name)->first();
    }
}; ?>

<div>
    <x-header title="Створити Виробника" separator/>
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-input label="Ім'я" wire:model="name"/>
                <x-input label="Адреса" wire:model="address" />

                <x-choices-offline
                    label="Матеріали"
                    wire:model.live="manufactureMaterials"
                    :options="$materials"
                    searchable/>

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users"/>
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>

                @if($manufactureMaterials)
                    @php
                        $addedMaterials = Material::whereIn('id', $this->manufactureMaterials)->get();
                    @endphp

                    <x-header title="Додайте ціни матеріалів" size="text-xl" class="mt-8" separator />

                    @foreach($addedMaterials as $addedMaterial)
                        <div class="flex flex-row content-center space-x-2 items-center justify-items-stretch">
                            <div class="flex-initial w-64 text-lg font-extrabold">
                                {{$addedMaterial->name}}
                            </div>
                            <div class="flex-initial w-80">
                                <x-input
                                    label="Price"
                                    wire:model.defer="materialsPrice.{{$addedMaterial->id}}"
                                    suffix="грн."
                                    inline
                                    required
                                    />
                            </div>
                        </div>
                    @endforeach
                @endif
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto"/>
        </div>
    </div>
</div>
