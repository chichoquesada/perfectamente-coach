<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-2xl text-text-primary leading-tight">
            Mi cuenta
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 sm:p-8 bg-card border border-line/10 rounded-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="p-6 sm:p-8 bg-card border border-line/10 rounded-2xl">
                @include('profile.partials.update-password-form')
            </div>

            <div class="p-6 sm:p-8 bg-card border border-red-500/20 rounded-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
