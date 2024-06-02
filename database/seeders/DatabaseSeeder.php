<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Client;
use App\Models\Manufacture;
use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MaterialSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(StorageSeeder::class);

        $this->call(AdminSeeder::class);
        User::factory(50)->create();

        Manufacture::factory(5)->create();
        $this->syncRndItems(Manufacture::class, Material::class);

        $this->call(ClientsSeeder::class);
        Client::factory(10)->create();

        Order::factory(20)->create();

        foreach (Order::cursor() as $order) {
            $this->orderItems($order);
        }
    }

    private function syncRndItems($owner, $element): void
    {
        // init models
        $ownerModel =  app($owner);
        $elementModel = app($element);

        //get tied table name
        $elementTableName = $elementModel->getTable();

        // get all owner entries
        $dbOwners = $ownerModel->all();

        // generate random relationships
        $path = explode('\\', $element);
        $elementTiedName = strtolower($path[count($path) - 1]);
        foreach ($dbOwners as $dbOwner) {
            $elements = $elementModel->inRandomOrder()->limit(rand(1, 3))->pluck('id')->toArray();
            $data = [];
            foreach ($elements as $el) {
                $data[] = [
                    $elementTiedName . '_id' => $el,
                    'price' => round(fake()->randomFloat(max: 1000), 2),
                ];
            }

            $dbOwner->$elementTableName()->sync($data);
        }
    }

    private function orderItems(Order $order)
    {
        $randSelect = rand(1,10);
        $quantity = rand(1, 5);
        $orderItems = [];
        $total = 0;

        if ($randSelect === 1) {
            $clientId = 1;
            $manufacture = Manufacture::inRandomOrder()->first();
            $manufactureId = $manufacture->id;

            //order materials
            $syncType = 'materials';
            $items = $manufacture->materials()->inRandomOrder()->limit(rand(2, 4))->get();

            foreach ($items as $item) {
                $orderTotal = $quantity * $item->pivot->price;
                $orderItems[] = [
                    'material_id' => $item->id,
                    'count' => $quantity,
                    'price' => $item->pivot->price,
                    'total' => $orderTotal
                ];

                $total += $orderTotal;
            }
        } else {
            $clientId = Client::where('id', '>', 1 )->inRandomOrder()->first()->id;
            $manufactureId = null;

            $syncType = 'products';
            $items = Product::inRandomOrder()->limit(rand(2, 4))->get();

            foreach ($items as $item) {
                $orderTotal = $quantity * $item->price;
                $orderItems[] = [
                    'product_id' => $item->id,
                    'count' => $quantity,
                    'price' => $item->price,
                    'total' => $orderTotal,
                ];

                $total += $orderTotal;
            }
        }

        $order->manufacture_id = $manufactureId;
        $order->client_id = $clientId;
        $order->total = $total;

        $order->save();

        $order->$syncType()->sync($orderItems);
    }
}
