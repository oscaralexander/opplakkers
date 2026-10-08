/**
 * One place that registers GSAP plugins, so every component imports the same
 * configured instance and registration never runs twice.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, SplitText);

export const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

export { gsap, ScrollTrigger, SplitText };
