<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use App\Enums\Roles;
use Livewire\Attributes\Session;

use App\Models\User;
use App\Models\Language;

new class extends Component {
    use Toast, WithFileUploads;

    public User $user;

    #[Session(key: 'password')]
    public string $unhashedPassword = '';

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    // Optional
    #[Rule('nullable|image|max:1024')]
    public $photo;

    #[Rule('required')]
    public string $role;

    public array $userRoles = [];

    public function regeneratePassword()
    {
        $this->unhashedPassword = $this->user->randomPassword();
        $this->success('Пароль оновлено успішно.', redirectTo: '/users/' . $this->user->id . '/edit');

    }

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        $this->userRoles = [];
        foreach (Roles::cases() as $role) {
            $this->userRoles[] = [
                'id' => $role->name,
                'name' => $role->value,
            ];
        }

        $password = $this->unhashedPassword;
        $this->unhashedPassword = '';

        return [
            'userRoles' => $this->userRoles,
            'password' => $password
        ];
    }

    public function mount(): void
    {
        $this->fill($this->user);
    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        // Update
        $this->user->update($data);

        if ($this->photo) {
            $url = $this->photo->store('users', 'public');
            $this->user->update(['avatar' => "/storage/$url"]);
        }

        // You can toast and redirect to any route
        $this->success('Користувач оновлено успішно.', redirectTo: '/users/' . $this->user->id . '/edit');
    }
}; ?>

<div>
    <x-header title="Оновити {{ $user->name }}" separator />
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-file label="Аватар" wire:model="photo" accept="image/png, image/jpeg" crop-after-change>
                    <img src="{{ $user->avatar ?? '/empty-user.jpg' }}" class="h-40 rounded-lg" />
                </x-file>

                <x-input label="Ім'я" wire:model="name" />
                <x-input label="Email" wire:model="email" />

                <x-choices
                    label="Роль користувача"
                    wire:model="role"
                    :options="$userRoles"
                    icon="o-users"
                    single
                >
                </x-choices>

                <x-slot:actions>
                    <x-button label="Відміна" link="/users" />
                    <x-button label="Згенерувати пароль" wire:click="regeneratePassword()" />
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            @if($password)
                <x-alert title="Пароль: {{$password}}" description="Це повідомлення зникне після перезавантаження" icon="o-exclamation-triangle" class="alert-warning" />
{{--            @else--}}
{{--                <x-alert title="Пароль: {{$password}}" description="Це повідомлення зникне після перезавантаження" icon="o-home" class="alert-warning" />--}}
            @endif
        </div>
    </div>
</div>

