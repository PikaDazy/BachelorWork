<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use App\Enums\Roles;
use Livewire\Attributes\Session;

use App\Models\User;

new class extends Component {
    use Toast, WithFileUploads;

    #[Session(key: 'password')]
    public string $unhashedPassword = '';

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('nullable|image|max:1024')]
    public $photo;

    #[Rule('required')]
    public string $role;

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        $userRoles =[];
        foreach (Roles::cases() as $role) {
            $userRoles[] = [
                'id' => $role->name,
                'name' => $role->value,
            ];
        }

        return [
            'userRoles' => $userRoles,
        ];
    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        // Update
        if ($this->_isUserExists($data['email'])) {
            $this->error('User already exists.');
            return;
        }

        $user = new User();
//        $user = User::where('id', 55)->first();
        $user->name = $data['name'];
        $user->role = $data['role'];
        $user->email = $data['email'];
        $user->password = 'pls add me later';
        $user->save();

        if ($this->photo) {
            $url = $this->photo->store('users', 'public');
            $user->update(['avatar' => "/storage/$url"]);
        }

        $this->unhashedPassword = $user->randomPassword();

        // You can toast and redirect to any route
        $this->success('Користувач оновлено успішно.', redirectTo: '/users/' . $user->id . '/edit');
    }

    private function _isUserExists(string $email): bool
    {
        if (
            User::query()->where('email', $email)->first()
        ) {
            return true;
        }

        return false;
    }
}; ?>

<div>
    <x-header title="Створити користувача" separator />
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-file label="Аватар" wire:model="photo" accept="image/png, image/jpeg" crop-after-change>
                    <img src="/empty-user.jpg" class="h-40 rounded-lg" />
                </x-file>

                <x-input label="Ім'я" wire:model="name" />
                <x-input label="Email" wire:model="email" />

                <x-slot:actions>
                    <x-button label="Відміна" link="/users" />
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>

                <x-choices
                    label="Роль користувача"
                    wire:model="role"
                    :options="$userRoles"
                    icon="o-users"
                    single
                >
                </x-choices>
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto" />
        </div>
    </div>
</div>

