<div class="app-preloader fixed z-50 grid h-full w-full place-content-center bg-slate-50 dark:bg-navy-900">
    <div class="app-preloader-inner relative inline-block size-48"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var p = document.querySelector('.app-preloader');
    if (!p) return;
    p.classList.add('animate-[cubic-bezier(0.4,0,0.2,1)_fade-out_500ms_forwards]');
    setTimeout(function () { p.remove(); }, 600);
});
</script>
