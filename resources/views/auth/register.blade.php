<x-layouts.guest :title="__('sintoniza.general.register')">
    <div class="grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md space-y-4">
            @if ($disabled)
                <div class="alert-warning" role="alert">{{ __('sintoniza.messages.subscriptions_disabled') }}</div>
            @endif

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

            @unless ($disabled)
                <div class="auth-card">
                    <div class="p-8">
                        <form method="post" action="{{ route('register') }}">
                            @csrf
                            <h2 class="text-xl font-extrabold tracking-tight text-slate-800 text-center mb-6 dark:text-slate-100">
                                {{ __('sintoniza.general.register') }}
                            </h2>
                            <div class="mb-4">
                                <label for="username" class="label">{{ __('sintoniza.general.username') }}</label>
                                <input type="text" class="input" name="username" required id="username"
                                    value="{{ old('username') }}" autofocus />
                            </div>
                            <div class="mb-4">
                                <label for="password" class="label">{{ __('sintoniza.general.min_password_length') }}</label>
                                <input type="password" class="input" minlength="8" required name="password" id="password" />
                            </div>
                            <div class="mb-4">
                                <label for="email" class="label">{{ __('sintoniza.general.email') }}</label>
                                <input type="email" class="input" required name="email" id="email"
                                    value="{{ old('email') }}" />
                            </div>
                            <button type="submit" class="btn-primary w-full">
                                <x-tabler-user-plus class="w-4 h-4" />
                                {{ __('sintoniza.general.register') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>
</x-layouts.guest>
