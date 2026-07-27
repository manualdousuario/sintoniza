<x-layouts.guest :title="__('sintoniza.general.forgot_password')">
    <div class="grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md space-y-4">
            @if (session('message'))
                <div class="alert-success" role="alert">{{ session('message') }}</div>
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

            <div class="auth-card">
                <div class="p-8">
                    <form method="post" action="{{ route('password.email') }}">
                        @csrf
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-800 text-center mb-6 dark:text-slate-100">
                            {{ __('sintoniza.general.forgot_password') }}
                        </h2>
                        <div class="mb-6">
                            <label for="email" class="label">{{ __('sintoniza.general.email') }}</label>
                            <input type="email" class="input" required name="email" id="email"
                                value="{{ old('email') }}" autofocus />
                        </div>
                        <button type="submit" class="btn-primary w-full">
                            <x-tabler-mail class="w-4 h-4" />
                            {{ __('sintoniza.general.send_reset_link') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
