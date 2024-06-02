<?php

use App\Models\Product;
use App\Models\Material;
use App\Models\Storage;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Validate;

new class extends Component {
    use Toast, WithFileUploads;

    public Storage $storage;

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $address = '';

    #[Rule('nullable|numeric')]
    public int $square;

    #[Rule('nullable|numeric')]
    public int $height;

    #[Validate([
        'materialsCount.*' =>  'required'
    ])]
    public array $materialsCount = [];

    #[Validate([
        'productsCount.*' =>  'required|numeric',
    ])]
    public array $productsCount = [];

    public array $materialIds = [];
    public array $productIds = [];

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [
            'storage' => $this->storage,
            'materials' => Material::where('is_deleted', false)->get(),
            'products' => Product::where('is_deleted', false)->get(),
            'selectedMaterials' => $this->materialIds ? $this->getSelectedMaterials() : null,
            'selectedProducts' => $this->productIds ? $this->getSelectedProducts() : null,
        ];
    }

    public function mount(): void
    {
        $this->fill($this->storage);

        $materials = $this->storage->materials;
        $products = $this->storage->products;

        foreach ($materials as $material) {
            $this->materialIds[] = $material->id;
            $this->materialsCount[$material->id] = $material->pivot->storage_quantity;
        }

        foreach ($products as $product) {
            $this->productIds[] = $product->id;
            $this->productsCount[$product->id] = $product->pivot->storage_quantity;
        }
        $this->fill(['materialsCount' => $this->materialsCount]);
        $this->fill(['productsCount' => $this->productsCount]);
    }

    public function save(): void
    {
        // Validate
        $this->validate();

        $this->updateLoad();
        if (!$this->checkLoad()) {
            return;
        }

        $this->storage->address = $this->address;
        $this->storage->height = $this->height;
        $this->storage->square = $this->square;
        $this->storage->capacity = $this->height * $this->square;

        $load = 0;
        $materialData = [];
        foreach ($this->materialIds as $materialId) {
            $load += $this->materialsCount[$materialId];
            $materialData[$materialId] = [
                'storage_quantity' => $this->materialsCount[$materialId]
            ];
        }

        $productData = [];
        foreach ($this->productIds as $productId) {
            $load += $this->productsCount[$productId];
            $productData[$productId] = [
                'storage_quantity' => $this->productsCount[$productId],
            ];
        }

        if (!$this->checkLoad()) {
            return;
        }
        $this->storage->load = $load;

//        dd(
//            $this->materialsCount,
//            $this->productsCount,
//            $materialData,
//            $productData
//        );
        $this->storage->save();
        $this->storage->materials()->sync($materialData);
        $this->storage->products()->sync($productData);

        $this->success('Склад оновлен успішно.', redirectTo: '/storage/' . $this->storage->id . '/edit');
    }

    private function getSelectedMaterials(): Collection
    {
        return Material::query()
            ->when(!empty($this->materialIds), fn(Builder $q) => $q->whereIn('id', $this->materialIds))
            ->get();
    }

    private function getSelectedProducts(): Collection
    {
        return Product::query()
            ->when(!empty($this->productIds), fn(Builder $q) => $q->whereIn('id', $this->productIds))
            ->get();
    }

    public function updateLoad($itemId = 0, $count = 0, $isMaterial = true): void
    {
        if ($itemId === 0 || $count === 0) {
            $this->storage->capacity = $this->square * $this->height;
        } elseif ($isMaterial) {
            $this->materialsCount[$itemId] = $count;
        } else {
            $this->productsCount[$itemId] = $count;
        }

        $load = 0;
        foreach ($this->materialsCount as $item) {
            $load += $item;
        }
        foreach ($this->productsCount as $item) {
            $load += $item;
        }

        $this->storage->load = $load;

        $this->checkLoad();
    }

    private function checkLoad(): bool
    {
        if ($this->storage->load > $this->height * $this->square) {
            $this->addError('overload', 'Недостатньо вільного місця в сховищі');
            return false;
        }

        return true;
    }
}; ?>

<div>
    <x-header title="Редагувати Склад" separator/>
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
                    wire:change="updateLoad()"
                    type="number"
                />

                <x-input
                    label="Висота"
                    wire:model="height"
                    wire:change="updateLoad()"
                    type="number"
                />

                <x-choices-offline
                    label="Матеріали"
                    wire:model.live="materialIds"
                    :options="$materials"
                    height="max-h-96"
                    hint="Search for product name"
                    searchable
                >
                    @scope('item', $item)
                    <x-list-item :item="$item" no-hover>
                        <x-slot:avatar>
                            <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg" />
                        </x-slot:avatar>
                        <x-slot:value>
                            {{ $item->name }}
                        </x-slot:value>
                    </x-list-item>
                    @endscope
                </x-choices-offline>

                <x-choices-offline
                    label="Продукти"
                    wire:model.live="productIds"
                    :options="$products"
                    height="max-h-96"
                    hint="Search for product name"
                    searchable
                >
                    @scope('item', $item)
                    <x-list-item :item="$item" no-hover>
                        <x-slot:avatar>
                            <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg" />
                        </x-slot:avatar>
                        <x-slot:value>
                            {{ $item->name }}
                        </x-slot:value>
                    </x-list-item>
                    @endscope
                </x-choices-offline>

                <x-header title="Завантаження складу" size="text-xl" class="!mb-4 mt-8" separator />
                <div class="grid grid-flow-col auto-cols-auto">
                    <div class="mr-2 min-w-8">
                        <x-progress value="{{$storage->load}}" max="{{$storage->capacity}}" class="{{ $errors->has('overload') ? 'progress-error' : 'progress-warning'}} h-3" />
                    </div>
                    <div class="min-w-8">
                        {{$storage->load}} / {{$storage->capacity}}
                    </div>
                </div>
                @error('overload') <span class="error text-red-500">{{ $message }}</span> @enderror


                @if($selectedMaterials)
                    <x-header title="Додати матеріал" size="text-xl" class="!mb-4 mt-8" separator />
                    @foreach($selectedMaterials as $item)
                        <x-list-item :item="$item" no-hover>
                            <x-slot:avatar>
                                <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg" />
                            </x-slot:avatar>
                            <x-slot:value>
                                {{ $item->name }}
                            </x-slot:value>
                            <x-slot:actions>
                                <x-input
                                    placeholder="кількість"
                                    wire:model="materialsCount.{{$item->id}}"
                                    wire:change="updateLoad({{$item->id}}, $event.target.value, false)"
                                    type="number"
                                />
                            </x-slot:actions>
                        </x-list-item>
                    @endforeach

                @endif

                @if($selectedProducts)
                    <x-header title="Додати продкт" size="text-xl" class="!mb-4 mt-8" separator />
                    @foreach($selectedProducts as $item)
                        <x-list-item :item="$item" no-hover>
                            <x-slot:avatar>
                                <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg" />
                            </x-slot:avatar>
                            <x-slot:value>
                                {{ $item->name }}
                            </x-slot:value>
                            <x-slot:actions>
                                <x-input
                                    placeholder="кількість"
                                    wire:model="productsCount.{{$item->id}}"
                                    wire:change="updateLoad({{$item->id}}, $event.target.value, false)"
                                    type="number"
                                />
                            </x-slot:actions>
                        </x-list-item>
                    @endforeach
                @endif

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users"/>
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </div>
    </div>
</div>
