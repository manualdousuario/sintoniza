<x-layouts.guest :title="__('sintoniza.general.reset_password')">
    <div class="grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md space-y-4">
            @if (session('message'))
                <div class="alert-success" role="alert">{{ session('message') }}</div>
                <a href="{{ route('login') }}" class="btn-primary w-full">
                    <x-tabler-login class="w-4 h-4" />
                    {{ __('sintoniza.general.login') }}
                </a>
            @else
                @if (session('error'))
                    <div class="alert-error" role="alert">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert-error" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="auth-card">
                    <div class="p-8">
                        <form method="post" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">
                            <h2 class="text-xl font-extrabold tracking-tight text-slate-800 text-center mb-6 dark:text-slate-100">
                                {{ __('sintoniza.general.reset_password') }}
                            </h2>
                            <div class="mb-6">
                                <label for="new_password" class="label">{{ __('sintoniza.general.new_password') }}</label>
                                <input type="password" class="input" minlength="8" required name="new_password"
                                    id="new_password" autofocus />
                            </div>
                            <button type="submit" class="btn-primary w-full">
                                <x-tabler-lock class="w-4 h-4" />
                                {{ __('sintoniza.general.reset_password') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.guest>
