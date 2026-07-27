<x-layouts.guest :title="__('sintoniza.general.login')">
    <div class="grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md space-y-4">
            @if (session('success'))
                <div class="alert-success" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert-error" role="alert">{{ session('error') }}</div>
            @endif

            @if ($hasToken)
                <div class="alert-warning" role="alert">{{ __('sintoniza.messages.app_requesting_access') }}</div>
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
                    <form method="post" action="{{ route('login', request()->query()) }}">
                        @csrf
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-800 text-center mb-6 dark:text-slate-100">
                            {{ __('sintoniza.general.login') }}
                        </h2>
                        <div class="mb-4">
                            <label for="login" class="label">{{ __('sintoniza.general.username') }}</label>
                            <input type="text" class="input" required name="login" id="login"
                                value="{{ old('login') }}" autofocus />
                        </div>
                        <div class="mb-6">
                            <label for="password" class="label">{{ __('sintoniza.general.password') }}</label>
                            <input type="password" class="input" required name="password" id="password" />
                        </div>
                        <button type="submit" class="btn-primary w-full">
                            <x-tabler-login class="w-4 h-4" />
                            {{ __('sintoniza.general.login') }}
                        </button>
                        <div class="mt-4 text-center text-sm">
                            <a href="{{ route('password.request') }}" class="link">{{ __('sintoniza.general.forgot_password') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
