const filterBtns = document.querySelectorAll('.d-flex.gap-2 button');

filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        // Reset kabeh tombol ke abu-abu (btn-filter)
        filterBtns.forEach(b => {
            b.classList.remove('btn-filter-active');
            b.classList.add('btn-filter');
        });

        // ngubah tombol sek diklik dadi biru (btn-filter-active)
        this.classList.remove('btn-filter');
        this.classList.add('btn-filter-active');
    });
});