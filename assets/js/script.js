const filterBtns = document.querySelectorAll('.d-flex.gap-2 button');

filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        // 1. Reset semua tombol ke abu-abu (btn-filter)
        filterBtns.forEach(b => {
            b.classList.remove('btn-filter-active');
            b.classList.add('btn-filter');
        });

        // 2. Ubah tombol yang diklik jadi biru (btn-filter-active)
        this.classList.remove('btn-filter');
        this.classList.add('btn-filter-active');
    });
});