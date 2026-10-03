<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
                <p class="font-medium">{{ __('Registration failed. Please check the following:') }}</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />
            @error('name') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                required
                autocomplete="username"
                placeholder="username"
            />
            @error('username') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />
            @error('email') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <div>
                <flux:select name="timezone" :label="__('Timezone')" :value="old('timezone', 'Asia/Jakarta')" required>
                    @foreach (\App\Livewire\Settings\Profile::timezones() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ __($label) }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('timezone') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            @error('password') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            @error('password_confirmation') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
