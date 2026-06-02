<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-transparent border border-line/15 text-text-primary rounded-lg font-medium text-sm hover:bg-line/5 focus:outline-none focus:ring-2 focus:ring-line/20 disabled:opacity-25 transition']) }}>
    {{ $slot }}
</button>
