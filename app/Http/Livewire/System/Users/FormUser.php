<?php

namespace App\Http\Livewire\System\Users;

use App\Models\Profile;
use App\Models\User;
use Blockpc\App\Models\Role;
use Blockpc\Events\ReSendLinkToChangePasswordEvent;
use Blockpc\Events\SendEmailForNewUserEvent;
use Blockpc\App\Traits\AlertBrowserEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

class FormUser extends Component
{
    use AlertBrowserEvent;

    public User $user;
    public Profile $profile;
    public $role;
    public $type = 'new';
    public $title_loading;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->profile = $this->user->profile ?? new Profile;

        $this->title_loading = __('users.forms.messages.loading-create');
        if ( $this->user->exists ) {
            $this->role = $this->user->role_id;
            $this->type = 'edit';
            $this->title_loading = __('users.forms.messages.loading-edit');
        }
    }

    public function getRolesProperty()
    {
        if ( !current_user()->hasRole('sudo') ) {
            return Role::whereNotIn('name', ['sudo'])->pluck('display_name', 'id');
        }
        return Role::all()->pluck('display_name', 'id');
    }

    public function render()
    {
        return view('livewire.system.users.form-user', [
            'roles' => $this->roles,
        ]);
    }

    public function resend()
    {
        if ( $this->user->exists && $name = $this->user->name ) {
            $this->title_loading = "Enviando link...";

            ReSendLinkToChangePasswordEvent::dispatch($this->user);

            $this->alert("Se ha enviado un link al usuario {$name} para cambiar la contraseña");
        }
    }

    public function save()
    {
        $this->validate();
        $password = Str::random(8);
        $this->user->password = Hash::make($password);
        $this->user->save();
        $this->profile->user()->associate($this->user);
        $this->profile->save();
        $this->user->syncRoles($this->role);

        $message = 'El Usuario ha sido editado correctamente';
        if ( $this->type == 'new' ) {
            SendEmailForNewUserEvent::dispatch($this->user);
            $message = 'Usuario registrado correctamente';
        }

        redirect( route('users.index') )->with('success', $message);
    }

    protected function rules()
    {
        $unique_name = !$this->user->exists 
            ? Rule::unique('users', 'name') : Rule::unique('users', 'name')->ignore($this->user);
        $unique_email = !$this->user->exists 
            ? Rule::unique('users', 'email') : Rule::unique('users', 'email')->ignore($this->user);
        return [
            'user.name' => ['required', 'alpha_num', 'max:64', $unique_name],
            'user.email' => ['required', 'email', 'max:64', $unique_email],
            'role' => ['required', 'integer', Rule::exists('roles', 'id')],
            'profile.firstname' => ['nullable', 'max:64'],
            'profile.lastname' => ['nullable', 'max:64'],
            'profile.phone' => ['nullable', 'regex:/^(\+[\d]{1,2}+(\s)?)?+[\d]{8,10}$/', 'min:8', 'max:15'],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'user.name' => __('users.forms.attributes.user.name'),
            'user.email' => __('users.forms.attributes.user.email'),
            'role' => __('users.forms.attributes.role'),
            'profile.firstname' => __('users.forms.attributes.profile.firstname'),
            'profile.lastname' => __('users.forms.attributes.profile.lastname'),
            'profile.phone' => __('users.forms.attributes.profile.phone'),
        ];
    }
}
