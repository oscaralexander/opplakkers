import '@css/style.scss';

import App from '@js/App';
import Equinox from '@js/components/Equinox';
import Nav from '@js/components/Nav';
import Reveal from '@js/components/Reveal';
import TextSplit from '@js/components/TextSplit';
import { ScrollTrigger } from '@js/lib/gsap';

window.addEventListener('DOMContentLoaded', () => {
    window.__APP__ = new App([
        { name: 'nav', component: Nav },
        { name: 'equinox', component: Equinox },
        { name: 'reveal', component: Reveal },
        { name: 'textSplit', component: TextSplit },
        // Only some pages have these; their code loads when one is on the page.
        { name: 'accordion', component: () => import('@js/components/Accordion') },
        { name: 'ticker', component: () => import('@js/components/Ticker') },
        { name: 'service', component: () => import('@js/components/Service') },
        { name: 'projectHero', component: () => import('@js/components/ProjectHero') },
    ]);
});

// Late assets (images without dimensions, embeds) can move things around;
// re-measure every scroll-driven effect once the page has fully loaded.
window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });

if (import.meta.hot) {
    import.meta.hot.accept();
}
