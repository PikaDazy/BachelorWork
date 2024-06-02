<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\Storage;

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Roles;

new class extends Component {
    use Toast, WithPagination;

    public bool $drawer = false;
    public array $expanded = [];

    public string $search = '';
    public array $sortBy = ['column' => 'address', 'direction' => 'asc'];

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'address', 'label' => 'Адреса', 'class' => 'w-32'],
            ['key' => 'height', 'label' => 'Висота', 'class' => 'w-4'],
            ['key' => 'square', 'label' => 'Площа', 'class' => 'w-4'],
            ['key' => 'capacity', 'label' => 'Місткість', 'class' => 'w-32', 'sortable' => false],
        ];
    }

    public function headersItems(): array
    {
        return [
            ['key' => 'url', 'label' => 'Зображ.', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Ім\'я.', 'class' => 'w-16'],
            ['key' => 'type', 'label' => 'Тип', 'class' => 'w-16'],
            ['key' => 'capacity', 'label' => 'Кількість', 'class' => 'w-8'],
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

    public function delete(Storage $storage): void
    {
        $storage->materials()->detach();
        $storage->products()->detach();

//        $storage->is_deleted = true;
        $storage->delete();

        $this->warning("Склад №$storage->id видален", 'Good bye!', position: 'toast-bottom');
    }

    public function storage(): LengthAwarePaginator
    {
        return Storage::query()
            ->where('is_deleted', false)
            ->when($this->search, fn( $q) => $q->where('address', 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    private function prepareStorageChartData(LengthAwarePaginator $storages): array
    {
        /*
         * {
         *      name: 'Marine Sprite',
         *      data: [44, 55, 41, 37, 22, 43, 21]
         *  }
         */
        $data = [
            'series' => [
                'Матеріали' => [],
                'Продукти' => [],
                'Вільне місце' => [],
            ],
            'categories' => [],
            'capacity' => [],

        ];
        foreach ($storages as $storage) {
            $data['categories'][] = 'Склад ' . $storage->id;
            $data['capacity'][] = $storage->capasity;

            $data['series']['Матеріали'][] = $storage->materials()->sum('storage_quantity');
            $data['series']['Продукти'][] = $storage->products()->sum('storage_quantity');
            $data['series']['Вільне місце'][] = $storage->capacity - $storage->load;
        }

        return $data;
    }

    public function with(): array
    {
        $storages = $this->storage();
        return [
            'headers' => $this->headers(),
            'storages' => $storages,
            'storagesData' => $this->prepareStorageChartData($storages),
            'filterCount' => $this->filterCount(),
            'headersItems' => $this->headersItems(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Сховища" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Пошук..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"/>
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Фільтри" @click="$wire.drawer = true" responsive icon="o-funnel"
                      badge="{{ $filterCount ?: null }}"/>
            <x-button label="Створити" link="/storage/create" responsive icon="o-plus" class="btn-primary"/>
        </x-slot:actions>
    </x-header>

    <div class="my-5">
        <x-card shadow separator>
            <x-slot:title>
                <div class="pl-[1.2rem] text-2xl font-bold ">
                    Місткість сховищ
                </div>
            </x-slot:title>
            <div class="" id="storageCapacity">

            </div>
        </x-card>
    </div>


    <script>
        $(document).ready(function () {
            function storages() {
                let options = {
                    series: [
                            @foreach($storagesData['series'] as $categoryName => $data)

                        {
                            name: '{{ $categoryName }}',
                            data: {!! json_encode($data) !!}
                        },
                        @endforeach
                    ],
                    chart: {
                        type: 'bar',
                        height: 250,
                        stacked: true,
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            dataLabels: {
                                total: {
                                    enabled: true,
                                    offsetX: 0,
                                    style: {
                                        fontSize: '13px',
                                        fontWeight: 900
                                    }
                                }
                            }
                        },
                    },
                    stroke: {
                        width: 1,
                        colors: ['#fff']
                    },
                    xaxis: {
                        categories: {!! json_encode($storagesData['categories']) !!},
                    },
                    yaxis: {
                        title: {
                            text: undefined
                        },
                    },
                    fill: {
                        opacity: 1
                    },
                    legend: {
                        position: 'bottom',
                        horizontalAlign: 'center',
                        offsetX: 40
                    }
                };

                let chart = new ApexCharts(document.querySelector("#storageCapacity"), options);
                chart.render();
            }

            storages();
        })
    </script>

    <!-- TABLE  -->
    <x-card>
        <x-table
        :headers="$headers"
        :rows="$storages"
        :sort-by="$sortBy"
        wire:model="expanded"
        expandable
        with-pagination
        link="/storage/{id}/edit">
            @scope('cell_capacity', $storage)
                @php
                    $capacity = $storage->height * $storage->square;
                    $load = $storage->items->sum('storage_quantity');
                    $load = (int) ($load * 100 / $capacity);
                @endphp
                <div class="grid grid-flow-col auto-cols-auto">
                    <div class="mr-2 min-w-8">
                        <x-progress value="{{$load}}" max="100" class="progress-warning h-3" />
                    </div>
                    <div class="min-w-8">
                        {{$load}} / 100
                    </div>
                </div>
            @endscope

            @if(auth()->user()->role === Roles::admin->name)
                @scope('actions', $storage)
                <x-button
                    icon="o-trash"
                    wire:click="delete({{$storage['id']}})"
                    wire:confirm="Ви впевнені?"
                    spinner
                    class="btn-ghost btn-sm text-red-500"/>
                @endscope
            @endif

            @scope('expansion', $storage, $headersItems)
                <x-table
                :headers="$headersItems"
                :rows="$storage->items"
                >
                    @scope('cell_url', $item)
                        <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-8 !rounded-lg"/>
                    @endscope

                    @scope('cell_capacity', $item, $storage)
                        @php
                            $capacity = $storage->height * $storage->square;
                        @endphp
                        <div class="grid grid-flow-col auto-cols-auto">
                            <div class="mr-2">
                                <x-progress value="{{$item->storage_quantity}}" max="{{$capacity}}" class="progress-warning h-3" />
                            </div>
                            <div class="">
                                {{$item->storage_quantity}}
                            </div>
                        </div>
                    @endscope
                </x-table>
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
