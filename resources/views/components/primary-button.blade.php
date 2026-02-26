<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-primary text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus px-5 py-2']) }}>
    {{ $slot }}
</button>
