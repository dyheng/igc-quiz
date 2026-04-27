<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Login Admin')]
class AdminLogin extends Component
{
    #[Validate('required|string', as: 'Password', message: ['required' => 'Password wajib diisi.'])]
    public string $password = '';

    public function login()
    {
        $this->validate();

        $hash = config('quiz.admin_password_hash');

        if (! is_string($hash) || $hash === '' || ! Hash::check($this->password, $hash)) {
            $this->addError('password', 'Password salah.');

            return;
        }

        session()->regenerate();
        session()->put(config('quiz.admin_session_key'), true);

        return $this->redirectRoute('admin.quizzes.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin-login');
    }
}
