<?php

use App\Models\Material;
use App\Models\Product;

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Roles;

new class extends Component {
    use Toast, WithPagination;

    public bool $drawer = false;

    public string $search = '';
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'url', 'label' => 'Зображ.', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Ім\'я.', 'class' => 'w-16'],
            ['key' => 'quantity', 'label' => 'Кількість', 'class' => 'w-8', 'sortable' => false],
            ['key' => 'description', 'label' => 'Опис', 'sortable' => false],
            ['key' => 'materials', 'label' => 'Матеріали', 'sortable' => false],
        ];
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->success('Фільтри скинуті.', position: 'toast-bottom');
    }

    public function filterCount(): int
    {
        $count = 0;
        if ($this->search) {
            $count += 1;
        }

        return $count;
    }

    public function delete(Product $product): void
    {
        $oldName = $product->name;

        $product->fakeDelete()
            ? $this->warning("$oldName видален", position: 'toast-bottom')
            : $this->error("$oldName не можливо видалити", 'Наявні не фіналізовані замовлення з цим продуктом', position: 'toast-bottom');
//        $product->materials()->detach();
//        $product->name .= '(видален)';
//        $product->is_deleted = true;
//        $product->save();

//        $this->warning("$oldName видален", 'Good bye!', position: 'toast-bottom');
    }

    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->where('is_deleted', false)
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'products' => $this->products(),
            'filterCount' => $this->filterCount(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Продукти" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Пошук..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"/>
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Фільтри" @click="$wire.drawer = true" responsive icon="o-funnel"
                      badge="{{ $filterCount ?: null }}"/>
            <x-button label="Створити" link="/products/create" responsive icon="o-plus" class="btn-primary"/>
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card>
        <x-table :headers="$headers" :rows="$products" :sort-by="$sortBy" with-pagination link="/products/{id}/edit">
            @if(auth()->user()->role === Roles::admin->name)
                @scope('actions', $product)
                <x-button icon="o-trash" wire:click="delete({{$product['id']}})" wire:confirm="Ви впевнені?" spinner
                          class="btn-ghost btn-sm text-red-500"/>
                @endscope
            @endif

            @scope('cell_url', $product)
            <x-avatar :image="$product->url ?: '/empty-product.png'" class="!w-8 !rounded-lg"/>
            @endscope

            @scope('cell_materials', $product)
                @foreach($product->materials as $material)
                    <x-badge :value="$material->name" class="badge-primary" />
                @endforeach
            @endscope

            @scope('cell_quantity', $material)
                {{ $material->storages->sum('pivot.storage_quantity') }}
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Фільтри" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Пошук..." wire:model.live.debounce="search" icon="o-magnifying-glass"
                 @keydown.enter="$wire.drawer = false"/>

        <x-slot:actions>
            <x-button label="Скинути" icon="o-x-mark" wire:click="clear" spinner/>
            <x-button label="Застосувати" icon="o-check" class="btn-primary" @click="$wire.drawer = false"/>
        </x-slot:actions>
    </x-drawer>
</div>
