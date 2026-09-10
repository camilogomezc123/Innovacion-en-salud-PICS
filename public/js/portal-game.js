/* POSUCI 360 Conecta — celebraciones del portal ("modo aventura"). Sin build: se sirve
   estático y usa canvas-confetti por CDN (ver portal/layout.blade.php). */
(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.posuciCelebrate = function () {
        if (prefersReducedMotion || typeof confetti !== 'function') {
            return;
        }

        confetti({
            particleCount: 90,
            spread: 70,
            startVelocity: 35,
            origin: { y: 0.7 },
            colors: ['#7c3aed', '#ec4899', '#facc15', '#22c55e', '#0ea5e9'],
        });
    };

    document.addEventListener('livewire:init', function () {
        if (typeof Livewire === 'undefined') {
            return;
        }

        Livewire.on('celebrate', function () {
            window.posuciCelebrate();
        });
    });
})();
