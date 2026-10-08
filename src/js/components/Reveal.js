import { reducedMotion } from '@js/lib/gsap';

/**
 * Reveal — one IntersectionObserver for every entrance on the page.
 *
 * The rest state is declared in CSS (4-components/reveal.scss); this only adds
 * .is-revealed once, then stops observing. Elements inside a common parent are
 * staggered by their index so a grid of cards arrives as a wave rather than all
 * at once.
 */
export default class Reveal {
    static observer = null;

    constructor($el, options = {}) {
        this.$el = $el;

        if (reducedMotion.matches) {
            this.$el.classList.add('is-revealed');
            return;
        }

        // Stagger siblings that share the same reveal group.
        const group = this.$el.closest('[data-reveal-group]');

        if (group) {
            const siblings = Array.from(group.querySelectorAll('.js-reveal'));
            const index = siblings.indexOf(this.$el);

            if (index > 0) {
                this.$el.style.setProperty('--reveal-delay', `${Math.min(index, 6) * 80}ms`);
            }
        }

        Reveal.observe(this.$el);
    }

    static observe($el) {
        if (!Reveal.observer) {
            // Opt the document into the hidden rest state only now, from the
            // script that is about to reveal things. See reveal.scss.
            document.documentElement.classList.add('is-revealReady');

            Reveal.observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-revealed');
                            Reveal.observer.unobserve(entry.target);
                        }
                    });
                },
                { rootMargin: '0px 0px -12% 0px', threshold: 0 }
            );
        }

        // Observe immediately rather than waiting on requestAnimationFrame: rAF
        // does not fire in a background tab, which would leave a page opened in
        // one blank until it is focused. The observer callback is itself async,
        // so there is still a frame between the rest state and .is-revealed.
        Reveal.observer.observe($el);
    }
}
