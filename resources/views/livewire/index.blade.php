<?php

use App\Models\Storage;
use App\Models\Order;
use App\Models\Product;

use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast, WithPagination;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public function with(): array
    {
        $storages = $this->storages();
        $orders = new Order();
        return [
            'storages' => $storages,
            'storagesData' => $this->prepareStorageChartData($storages),
            'products' => $orders->mostOrderedProducts(),
            'statusCount' => $this->prepareStatusChartData($orders->statusCount()),
            'orders' => $this->overdueOrders(),
            'headers' => $this->overdueOrdersHeaders()
        ];
    }

    private function storages(): Collection
    {
        return Storage::all();
    }

    private function prepareStorageChartData(Collection $storages): array
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
            $data['categories'][] = 'склад ' . $storage->id;
            $data['capacity'][] = $storage->capasity;

            $data['series']['Матеріали'][] = $storage->materials()->sum('storage_quantity');
            $data['series']['Продукти'][] = $storage->products()->sum('storage_quantity');
            $data['series']['Вільне місце'][] = $storage->capacity - $storage->load;
        }

        return $data;
    }

    private function prepareStatusChartData(Collection $statuses): array
    {
        $data = [
            'series' => [],
            'labels' => []
        ];

        foreach ($statuses as $status) {
            $data['series'][] = $status->status_count;
            $data['labels'][] = __($status->status);
        }

        return $data;
    }

    private function overdueOrdersHeaders(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'created_at', 'label' => 'Дата створення', 'class' => 'w-1'],
            ['key' => 'due_date', 'label' => 'Очікування доставки', 'class' => 'w-1'],
            ['key' => 'client_id', 'label' => 'Отримувач', 'class' => 'w-1'],
            ['key' => 'total', 'label' => 'Загалом', 'class' => 'w-1'],
            ['key' => 'status', 'label' => 'Статус', 'class' => 'w-1'],
            ['key' => 'warnings', 'label' => 'Попередження', 'class' => 'w-1',  'sortBy' => 'due_date'],
        ];
    }

    private function overdueOrders(): LengthAwarePaginator
    {
        $nowDateTime = Carbon::now()->toDateTimeString();
        return Order
            ::where('due_date', '<', $nowDateTime)
            ->where('is_finalized', false)
            ->paginate(5);
    }

}; ?>

<div>
    <div class="mt-5">
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

    <div class="grid lg:grid-cols-4 gap-8 mt-8">
        <div class="col-span-2">
            <x-card shadow separator class="h-full">
                <x-slot:title>
                    <div class="pl-[1.2rem] text-2xl font-bold ">
                        Топ продуктів
                    </div>
                </x-slot:title>

                @foreach($products as $item)
                    <x-list-item :item="$item" no-hover>
                        <x-slot:avatar>
                            <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-16 !rounded-lg"/>
                        </x-slot:avatar>
                        <x-slot:value>
                            {{ $item->name }}
                        </x-slot:value>
                        <x-slot:sub-value>
                            Замовлено: {{$item->product_count}}
                        </x-slot:sub-value>
                        <x-slot:actions>
                            <x-badge :value="$item->product_price . 'грн.'"/>
                        </x-slot:actions>
                    </x-list-item>
                @endforeach
            </x-card>
        </div>
        <div class="col-span-2">
            <x-card shadow separator class="h-full">
                <x-slot:title>
                    <div class="pl-[1.2rem] text-2xl font-bold ">
                        Статуси замовлень
                    </div>
                </x-slot:title>

                <div id="statusCount"></div>
            </x-card>
        </div>
    </div>

    <x-card class="mt-5">
        <x-slot:title>
            <div class="pl-[1.2rem] text-2xl font-bold ">
                Просрочені замовлення
            </div>
        </x-slot:title>

        <x-table
            :headers="$headers"
            :rows="$orders"
            :sort-by="$sortBy"
            with-pagination
            link="/orders/{id}/view"
        >
            @scope('cell_client_id', $order)
            {{$order->client->name}}
            @endscope

            @scope('cell_status', $order)
            <x-badge :value="__($order->status)" class="badge-primary"/>
            @endscope

            @scope('cell_warnings', $order)
            @php
                $stamp = strtotime($order->due_date);
                $now = now();
                if (strtotime($order->due_date) > time() || $order->is_finalized) {
                    $error = "без попереджень";
                    $badgeClass = 'badge-success';
                } else {
                    $error = 'просрочене';
                    $badgeClass = 'badge-error';
                }
            @endphp
            <x-badge :value="$error" class="{{ $badgeClass }}"/>
            @endscope
        </x-table>
    </x-card>

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

            function statusCount() {
                let options = {
                    chart: {
                        type: 'pie'
                    },
                    series: {!! json_encode($statusCount['series']) !!},
                    labels: {!! json_encode($statusCount['labels']) !!},
                }

                let chart1 = new ApexCharts(document.querySelector("#statusCount"), options);

                chart1.render();
            }

            statusCount();
        })
    </script>
</div>
