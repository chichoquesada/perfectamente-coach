<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-gold text-black border-0 rounded-lg font-bold text-sm hover:bg-gold/90 active:bg-gold/80 focus:outline-none focus:ring-2 focus:ring-gold/50 focus:ring-offset-2 focus:ring-offset-bg transition']) }}>
    {{ $slot }}
</button>
