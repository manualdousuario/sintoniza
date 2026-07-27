<div class="max-w-6xl mx-auto px-4 pb-8">
    <div class="page-header">
        <h2 class="page-title">{{ __('sintoniza.general.profile') }}</h2>
    </div>

    @if ($errorMessage)
        <div class="alert-error mb-6" role="alert">{{ $errorMessage }}</div>
    @elseif ($successMessage)
        <div class="alert-success mb-6" role="alert">{{ $successMessage }}</div>
    @endif

    <div x-data="{ tab: 'language' }" class="mb-8">
        <div class="flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-800">
            <button type="button" @click="tab = 'language'"
                :class="tab === 'language'
                    ? 'border-slate-200 border-b-white dark:border-slate-700 dark:border-b-slate-900 text-brand-600 dark:text-brand-400 font-semibold bg-white dark:bg-slate-900'
                    : 'border-transparent text-slate-500 hover:text-brand-600 dark:text-slate-400'"
                class="px-4 py-2 text-sm rounded-t-lg border border-b-0 -mb-px transition">
                {{ __('sintoniza.profile.language_settings') }}
            </button>
            <button type="button" @click="tab = 'timezone'"
                :class="tab === 'timezone'
                    ? 'border-slate-200 border-b-white dark:border-slate-700 dark:border-b-slate-900 text-brand-600 dark:text-brand-400 font-semibold bg-white dark:bg-slate-900'
                    : 'border-transparent text-slate-500 hover:text-brand-600 dark:text-slate-400'"
                class="px-4 py-2 text-sm rounded-t-lg border border-b-0 -mb-px transition">
                {{ __('sintoniza.profile.timezone_settings') }}
            </button>
            <button type="button" @click="tab = 'password'"
                :class="tab === 'password'
                    ? 'border-slate-200 border-b-white dark:border-slate-700 dark:border-b-slate-900 text-brand-600 dark:text-brand-400 font-semibold bg-white dark:bg-slate-900'
                    : 'border-transparent text-slate-500 hover:text-brand-600 dark:text-slate-400'"
                class="px-4 py-2 text-sm rounded-t-lg border border-b-0 -mb-px transition">
                {{ __('sintoniza.profile.change_password') }}
            </button>
            <button type="button" @click="tab = 'token'"
                :class="tab === 'token'
                    ? 'border-slate-200 border-b-white dark:border-slate-700 dark:border-b-slate-900 text-brand-600 dark:text-brand-400 font-semibold bg-white dark:bg-slate-900'
                    : 'border-transparent text-slate-500 hover:text-brand-600 dark:text-slate-400'"
                class="px-4 py-2 text-sm rounded-t-lg border border-b-0 -mb-px transition">
                {{ __('sintoniza.profile.api_token') }}
            </button>
        </div>

        <div class="bg-white border border-t-0 border-slate-200 rounded-b-2xl p-6 dark:bg-slate-900 dark:border-slate-800">
            {{-- ------------------------------------------------ Language --}}
            <div x-show="tab === 'language'">
                <form wire:submit="updateLanguage">
                    <div class="mb-4 max-w-sm">
                        <label for="language" class="label">{{ __('sintoniza.profile.select_language') }}</label>
                        <select wire:model="language" id="language" class="input">
                            @foreach (config('sintoniza.locales', ['en']) as $locale)
                                <option value="{{ $locale }}">
                                    {{ __('sintoniza.language_names.'.$locale) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="updateLanguage">
                        {{ __('sintoniza.general.save') }}
                    </button>
                </form>
            </div>

            {{-- ------------------------------------------------ Timezone --}}
            <div x-show="tab === 'timezone'" x-cloak>
                <form wire:submit="updateTimezone">
                    <div class="mb-4 max-w-sm">
                        <label for="timezone" class="label">{{ __('sintoniza.profile.select_timezone') }}</label>
                        <select wire:model="timezone" id="timezone" class="input" required>
                            @foreach ($timezoneGroups as $group => $identifiers)
                                <optgroup label="{{ $group }}">
                                    @foreach ($identifiers as $identifier)
                                        <option value="{{ $identifier }}">{{ $identifier }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="updateTimezone">
                        {{ __('sintoniza.general.save') }}
                    </button>
                </form>
            </div>

            {{-- ------------------------------------------------ Password --}}
            <div x-show="tab === 'password'" x-cloak>
                <form wire:submit="changePassword">
                    <div class="mb-4 max-w-sm">
                        <label for="current_password" class="label">{{ __('sintoniza.profile.current_password') }}:</label>
                        <input type="password" class="input" required wire:model="current_password"
                            id="current_password" autocomplete="current-password" />
                    </div>
                    <div class="mb-4 max-w-sm">
                        <label for="new_password" class="label">
                            {{ __('sintoniza.profile.new_password') }}:
                            ({{ __('sintoniza.profile.min_password_length') }}):
                        </label>
                        <input type="password" class="input" required wire:model="new_password" id="new_password"
                            minlength="8" autocomplete="new-password" />
                    </div>
                    <div class="mb-4 max-w-sm">
                        <label for="confirm_password" class="label">{{ __('sintoniza.profile.confirm_password') }}:</label>
                        <input type="password" class="input" required wire:model="confirm_password"
                            id="confirm_password" minlength="8" autocomplete="new-password" />
                    </div>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="changePassword">
                        {{ __('sintoniza.general.save') }}
                    </button>
                </form>
            </div>

            {{-- -------------------------------------------------- API token --}}
            <div x-show="tab === 'token'" x-cloak>
                <p class="text-sm text-slate-700 dark:text-slate-300">
                    {{ __('sintoniza.dashboard.secret_user') }}:
                    <strong class="font-mono select-all">{{ $userToken }}</strong>
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400 m-0">{{ __('sintoniza.dashboard.secret_user_note') }}</p>
            </div>
        </div>
    </div>
</div>
