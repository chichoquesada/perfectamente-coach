<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-nofiel text-white border-0 rounded-full font-bold text-sm hover:bg-nofiel/90 focus:outline-none focus:ring-2 focus:ring-nofiel/50 focus:ring-offset-2 focus:ring-offset-bg transition']) }}>
    {{ $slot }}
</button>
