@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-input block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 ring-primary/50 hover:border-slate-400 focus:border-primary focus:ring-3 dark:border-navy-600 dark:bg-navy-900/90 dark:text-navy-100 dark:placeholder-navy-300 dark:ring-accent/50 dark:hover:border-navy-500 dark:focus:border-accent dark:focus:bg-navy-900']) }}>
