/**
 * Laravel Animated Icons — plain-JS runtime.
 *
 * The page carries one inert JSON manifest (`#animated-icons-manifest`); each
 * wrapper carries only its icon name. One delegated listener pair on the
 * document resolves the trigger from the event target, so server-rendered,
 * Livewire-inserted and future icons all behave the same.
 *
 * Triggers: hover (default), click, auto, visible, none. `auto` and `visible`
 * play once when the icon enters the viewport. `window.AnimateIcons.play()`
 * and the `animated-icon:play` event cover manual/event-driven use.
 * Respects prefers-reduced-motion.
 */

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const active = new WeakMap();
const speedFactors = { fast: 0.65, normal: 1, slow: 1.5 };

let manifestSource = null;
let manifest = {};

function iconSpec(name) {
    const element = document.getElementById('animated-icons-manifest');

    if (element && element.textContent !== manifestSource) {
        manifestSource = element.textContent;

        try {
            manifest = JSON.parse(manifestSource || '{}');
        } catch {
            manifest = {};
        }

        window.__animatedIcons = manifest;
    }

    return manifest[name] ?? window.__animatedIcons?.[name] ?? null;
}

function modeOf(wrapper) {
    return wrapper.dataset.animatedIconOn || 'hover';
}

function iconsIn(trigger, mode = null) {
    const icons = trigger.matches('[data-animated-icon]')
        ? [trigger]
        : [...trigger.querySelectorAll('[data-animated-icon]')];

    return mode === null ? icons : icons.filter((icon) => modeOf(icon) === mode);
}

function triggerFor(target) {
    if (! (target instanceof Element)) return null;

    const icon = target.closest('[data-animated-icon]');

    for (let node = target; node; node = node.parentElement) {
        if (! node.matches('[data-animated-icon-trigger], button, a')) continue;

        if (node.querySelector('[data-animated-icon]') && (icon === null || node.contains(icon))) {
            return node;
        }
    }

    return icon;
}

function play(wrapper) {
    if (reducedMotion.matches) return;

    const spec = iconSpec(wrapper.dataset.animatedIcon);

    if (! spec) return;

    const svg = wrapper.querySelector('svg');

    if (! svg) return;

    wrapper.dataset.animatedIconActive = 'true';

    const factor = speedFactors[wrapper.dataset.animatedIconSpeed] ?? 1;

    Object.entries(spec).forEach(([part, config]) => {
        const tracks = Array.isArray(config) ? config : [config];
        const targets = part === 'root'
            ? [svg]
            : [...wrapper.querySelectorAll(`[data-part="${part}"]`)];

        targets.forEach((target) => {
            target.getAnimations().forEach((animation) => animation.cancel());

            tracks.forEach((track) => {
                target.animate(track.keyframes, {
                    duration: (track.duration || 600) * factor,
                    delay: (track.delay || 0) * factor,
                    easing: track.easing || 'ease-in-out',
                    fill: 'none',
                });
            });
        });
    });
}

function reset(wrapper) {
    delete wrapper.dataset.animatedIconActive;

    wrapper.querySelectorAll('svg, [data-part]').forEach((element) => {
        element.getAnimations().forEach((animation) => animation.cancel());
    });
}

function activate(trigger, source) {
    const icons = iconsIn(trigger, 'hover');

    if (icons.length === 0) return;

    const sources = active.get(trigger) ?? new Set();

    if (sources.size === 0) icons.forEach(play);

    sources.add(source);
    active.set(trigger, sources);
}

function deactivate(trigger, source) {
    const sources = active.get(trigger);

    if (! sources) return;

    sources.delete(source);

    if (sources.size > 0) return;

    active.delete(trigger);
    iconsIn(trigger, 'hover').forEach(reset);
}

function handleHover(event, source, entering) {
    const trigger = triggerFor(event.target);

    if (! trigger) return;

    if (event.relatedTarget instanceof Node && trigger.contains(event.relatedTarget)) return;

    if (entering) activate(trigger, source);
    else deactivate(trigger, source);
}

document.addEventListener('pointerover', (event) => handleHover(event, 'pointer', true));
document.addEventListener('pointerout', (event) => handleHover(event, 'pointer', false));
document.addEventListener('focusin', (event) => handleHover(event, 'focus', true));
document.addEventListener('focusout', (event) => handleHover(event, 'focus', false));

document.addEventListener('click', (event) => {
    const trigger = triggerFor(event.target);

    if (! trigger) return;

    iconsIn(trigger, 'click').forEach(play);
});

const observer = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) return;

            observer.unobserve(entry.target);
            play(entry.target);
        });
    }, { threshold: 0.35 })
    : null;

function observeIcon(icon) {
    const mode = modeOf(icon);

    if (mode !== 'auto' && mode !== 'visible') return;

    if (observer) observer.observe(icon);
    else play(icon);
}

document.querySelectorAll('[data-animated-icon]').forEach(observeIcon);

const mutations = new MutationObserver((records) => {
    records.forEach((record) => {
        record.addedNodes.forEach((node) => {
            if (! (node instanceof Element)) return;

            if (node.matches('[data-animated-icon]')) observeIcon(node);
            node.querySelectorAll('[data-animated-icon]').forEach(observeIcon);
        });
    });
});

mutations.observe(document.documentElement, { childList: true, subtree: true });

function resolveIcon(target) {
    if (target instanceof Element) {
        return target.closest('[data-animated-icon]') ?? target.querySelector('[data-animated-icon]');
    }

    if (typeof target === 'string') {
        const element = document.querySelector(target);

        return element
            ? (element.closest('[data-animated-icon]') ?? element.querySelector('[data-animated-icon]'))
            : null;
    }

    return null;
}

window.AnimateIcons = {
    play(target) {
        const icon = resolveIcon(target);

        if (icon) play(icon);
    },
    playAll(root = document) {
        root.querySelectorAll?.('[data-animated-icon]').forEach(play);
    },
};

document.addEventListener('animated-icon:play', (event) => {
    const detail = event.detail ?? {};

    if (detail.name) {
        document.querySelectorAll(`[data-animated-icon="${CSS.escape(detail.name)}"]`).forEach(play);
    } else if (detail.selector) {
        document.querySelectorAll(detail.selector).forEach((element) => {
            const icon = resolveIcon(element);

            if (icon) play(icon);
        });
    }
});

// Readiness flag for browser tests and integrations that act immediately.
window.__animatedIconsReady = true;
