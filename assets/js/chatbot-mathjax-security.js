/**
 * Typeset chatbot math off-document and move it into the page only after
 * unsafe URLs have been removed.
 *
 * MathJax 3.2.0 ui/safe blocks ordinary javascript: and data: URLs, but its
 * scheme parser still allows a value when it cannot identify a protocol.
 * This script does not replace MathJax.typeset or MathJax.typesetPromise.
 * A foreign MathJax on the page is left alone, and chatbot math is not typeset.
 */
(function () {
    'use strict';

    var BLOCKED_PROTOCOLS = {
        'javascript:': true,
        'data:': true,
        'vbscript:': true,
        'file:': true
    };

    var URL_ATTRIBUTES = {
        href: true,
        src: true,
        srcset: true,
        action: true,
        formaction: true,
        poster: true,
        background: true,
        cite: true,
        longdesc: true,
        altimg: true,
        'xlink:href': true
    };

    // MathJax 3.2 CHTML uses mjx-*, MJX-TEX, and TEX-* classes for layout and fonts.
    var MATHJAX_TOKEN = /^(?:mjx|MJX|TEX|tex)-[-A-Za-z0-9_.]+$/;
    var eventProbe = null;

    function collapsedProtocol(value) {
        var collapsed = String(value).replace(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u200D\u2028\u2029\u202F\u205F\u2060\u3000\uFEFF\uFFFD]+/g, '').toLowerCase();
        var match = collapsed.match(/^([a-z][a-z0-9+.-]*):/);
        if (!match) {
            return '';
        }
        return match[1] + ':';
    }

    function browserProtocol(value) {
        var protocol = '';
        var base;
        try {
            if (typeof URL !== 'function') {
                return '';
            }
            // Same scheme parser used for link navigation, without writing the
            // untrusted value into an element URL property.
            base = document.baseURI || 'https://example.invalid/';
            protocol = String(new URL(value, base).protocol || '').replace(/[\u0000-\u0020]+/g, '').toLowerCase();
        } catch (error) {
            protocol = '';
        }
        return protocol;
    }

    function rawUrlIsBlocked(value) {
        if (typeof value !== 'string' || value === '') {
            return false;
        }
        return !!BLOCKED_PROTOCOLS[browserProtocol(value)] || !!BLOCKED_PROTOCOLS[collapsedProtocol(value)];
    }

    function urlIsBlocked(value) {
        var decoded = value;
        if (typeof value !== 'string' || value === '') {
            return false;
        }
        if (rawUrlIsBlocked(value)) {
            return true;
        }
        try {
            decoded = decodeURIComponent(value);
        } catch (error) {
            decoded = value;
        }
        return decoded !== value && rawUrlIsBlocked(decoded);
    }

    function styleIsUnsafe(styleText) {
        if (typeof styleText !== 'string' || styleText === '') {
            return false;
        }
        var compact = styleText.replace(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u200D\u2028\u2029\u202F\u205F\u2060\u3000\uFEFF]+/g, '').toLowerCase();
        if (compact.indexOf('expression(') !== -1 || compact.indexOf('-moz-binding') !== -1 || compact.indexOf('behavior:') !== -1 || compact.indexOf('@import') !== -1) {
            return true;
        }
        if (compact.indexOf('javascript:') !== -1 || compact.indexOf('vbscript:') !== -1 || compact.indexOf('data:') !== -1) {
            return true;
        }
        return false;
    }

    function isEventHandlerAttribute(name) {
        var property = String(name || '').toLowerCase();
        if (property.indexOf('on') !== 0 || property.length < 3) {
            return false;
        }
        if (!eventProbe) {
            eventProbe = document.createElement('div');
        }
        if (!(property in eventProbe)) {
            return false;
        }
        var current = eventProbe[property];
        return current === null || typeof current === 'function';
    }

    function attributeIsUrl(name) {
        var lower = String(name || '').toLowerCase();
        if (URL_ATTRIBUTES[lower]) {
            return true;
        }
        return lower.slice(-5) === ':href' || lower.slice(-4) === ':src';
    }

    function srcsetIsBlocked(value) {
        var parts = String(value).split(',');
        var index;
        var candidate;
        for (index = 0; index < parts.length; index++) {
            candidate = parts[index].trim().split(/\s+/)[0];
            if (candidate && urlIsBlocked(candidate)) {
                return true;
            }
        }
        return false;
    }

    function filterMathJaxTokens(element) {
        var className = element.getAttribute('class');
        var idValue = element.getAttribute('id');
        var kept = [];
        var tokens;
        var index;
        if (typeof className === 'string' && className !== '') {
            tokens = className.split(/\s+/);
            for (index = 0; index < tokens.length; index++) {
                if (tokens[index] && (tokens[index] === 'MathJax' || MATHJAX_TOKEN.test(tokens[index]))) {
                    kept.push(tokens[index]);
                }
            }
            if (kept.length) {
                element.setAttribute('class', kept.join(' '));
            } else {
                element.removeAttribute('class');
            }
        }
        if (typeof idValue === 'string' && idValue !== '' && !MATHJAX_TOKEN.test(idValue)) {
            element.removeAttribute('id');
        }
    }

    function neutralizeMathJaxElement(element, filterTokens) {
        var removals = [];
        var attributes;
        var index;
        var name;
        var value;
        if (!element || !element.attributes) {
            return;
        }
        attributes = element.attributes;
        for (index = 0; index < attributes.length; index++) {
            name = attributes[index].name;
            value = attributes[index].value;
            if (isEventHandlerAttribute(name)) {
                removals.push(name);
                continue;
            }
            if (String(name).toLowerCase() === 'style' && styleIsUnsafe(value)) {
                removals.push(name);
                continue;
            }
            if (attributeIsUrl(name)) {
                if (String(name).toLowerCase() === 'srcset' ? srcsetIsBlocked(value) : urlIsBlocked(value)) {
                    removals.push(name);
                }
            }
        }
        for (index = 0; index < removals.length; index++) {
            element.removeAttribute(removals[index]);
        }
        if (filterTokens) {
            filterMathJaxTokens(element);
        }
    }

    function neutralizeMathJaxOutput(root) {
        var containers;
        var index;
        var container;
        var nodes;
        var nodeIndex;
        var blocked;
        var parent;
        if (!root || typeof root.querySelectorAll !== 'function') {
            return;
        }
        containers = root.querySelectorAll('mjx-container');
        for (index = 0; index < containers.length; index++) {
            container = containers[index];
            parent = container.parentElement;
            if (parent && parent !== root && (parent.tagName === 'A' || parent.hasAttribute('href') || parent.hasAttribute('src'))) {
                neutralizeMathJaxElement(parent, false);
            }
            neutralizeMathJaxElement(container, true);
            nodes = container.querySelectorAll('*');
            for (nodeIndex = 0; nodeIndex < nodes.length; nodeIndex++) {
                neutralizeMathJaxElement(nodes[nodeIndex], true);
            }
            blocked = container.querySelectorAll('script, iframe, object, embed, form, link, meta, base');
            for (nodeIndex = blocked.length - 1; nodeIndex >= 0; nodeIndex--) {
                if (blocked[nodeIndex].parentNode) {
                    blocked[nodeIndex].parentNode.removeChild(blocked[nodeIndex]);
                }
            }
            unwrapBlockedAnchors(container);
        }
    }

    function unwrapBlockedAnchors(container) {
        var anchors = container.querySelectorAll('a');
        var index;
        var anchor;
        var href;
        var parent;
        for (index = anchors.length - 1; index >= 0; index--) {
            anchor = anchors[index];
            href = anchor.getAttribute('href');
            if (href && !urlIsBlocked(href)) {
                continue;
            }
            parent = anchor.parentNode;
            if (!parent) {
                continue;
            }
            while (anchor.firstChild) {
                parent.insertBefore(anchor.firstChild, anchor);
            }
            parent.removeChild(anchor);
        }
    }

    function mathJaxTarget(element) {
        if (!element || element.nodeType !== 1) {
            return null;
        }
        if (element.classList && element.classList.contains('chatbot-chatgpt-root')) {
            return element;
        }
        if (typeof element.closest === 'function' && element.closest('.chatbot-chatgpt-root')) {
            return element;
        }
        return null;
    }

    function safeReady() {
        var startup = window.MathJax && window.MathJax.startup;
        var doc = startup && startup.document;
        return !!(doc && doc.safe && typeof doc.safe.sanitize === 'function' && doc.safe.filterMethods);
    }

    function ownedMathJax() {
        return !!(
            window.MathJax &&
            window.MathJax.chatbotOwned === true &&
            window.chatbotMathJaxForeign !== true &&
            window.MathJax.startup &&
            typeof window.MathJax.typesetPromise === 'function'
        );
    }

    function elementHasDanger(element) {
        var attributes;
        var index;
        var name;
        var value;
        if (!element || !element.attributes) {
            return false;
        }
        if (element.matches && element.matches('script, iframe, object, embed, form, link, meta, base')) {
            return true;
        }
        attributes = element.attributes;
        for (index = 0; index < attributes.length; index++) {
            name = attributes[index].name;
            value = attributes[index].value;
            if (isEventHandlerAttribute(name)) {
                return true;
            }
            if (String(name).toLowerCase() === 'style' && styleIsUnsafe(value)) {
                return true;
            }
            if (attributeIsUrl(name) && (String(name).toLowerCase() === 'srcset' ? srcsetIsBlocked(value) : urlIsBlocked(value))) {
                return true;
            }
        }
        return false;
    }

    function treeHasDanger(root) {
        var nodes;
        var index;
        if (!root) {
            return true;
        }
        if (elementHasDanger(root)) {
            return true;
        }
        if (typeof root.querySelectorAll !== 'function') {
            return true;
        }
        nodes = root.querySelectorAll('*');
        for (index = 0; index < nodes.length; index++) {
            if (elementHasDanger(nodes[index])) {
                return true;
            }
        }
        return false;
    }

    function installStricterFilter() {
        var safe = window.MathJax && window.MathJax.startup && window.MathJax.startup.document && window.MathJax.startup.document.safe;
        var original;
        if (!safe || !safe.filterMethods || safe.filterMethods.__chatbotUrlFilter) {
            return;
        }
        original = safe.filterMethods.filterURL;
        safe.filterMethods.filterURL = function (safeObject, value) {
            if (typeof value !== 'string' || urlIsBlocked(value)) {
                return null;
            }
            if (typeof original === 'function') {
                return original.call(safe.filterMethods, safeObject, value);
            }
            return value;
        };
        safe.filterMethods.__chatbotUrlFilter = true;
    }

    function installGuard() {
        if (!ownedMathJax()) {
            return false;
        }
        if (window.MathJax.__chatbotGuardInstalled) {
            return true;
        }
        window.MathJax.__chatbotGuardInstalled = true;
        window.MathJax.startup.promise.then(function () {
            if (!ownedMathJax() || !safeReady()) {
                if (window.MathJax) {
                    window.MathJax.__chatbotSafeActive = false;
                }
                return;
            }
            installStricterFilter();
            window.MathJax.__chatbotSafeActive = true;
        }).catch(function () {
            if (window.MathJax) {
                window.MathJax.__chatbotSafeActive = false;
            }
        });
        return true;
    }

    function moveSanitized(target, scratch) {
        while (target.firstChild) {
            target.removeChild(target.firstChild);
        }
        while (scratch.firstChild) {
            target.appendChild(scratch.firstChild);
        }
    }

    function renderSafely(target) {
        var snapshot = target.innerHTML;
        var scratch = document.createElement('div');
        scratch.innerHTML = snapshot;
        return window.MathJax.startup.promise.then(function () {
            if (!ownedMathJax() || !safeReady()) {
                if (window.MathJax) {
                    window.MathJax.__chatbotSafeActive = false;
                }
                return false;
            }
            installStricterFilter();
            window.MathJax.__chatbotSafeActive = true;
            return window.MathJax.typesetPromise([scratch]);
        }).then(function (typesetResult) {
            if (typesetResult === false) {
                return false;
            }
            neutralizeMathJaxOutput(scratch);
            if (scratch.isConnected || treeHasDanger(scratch) || target.innerHTML !== snapshot) {
                if (scratch.parentNode) {
                    scratch.parentNode.removeChild(scratch);
                }
                return false;
            }
            moveSanitized(target, scratch);
            return true;
        }).catch(function () {
            if (scratch.parentNode) {
                scratch.parentNode.removeChild(scratch);
            }
            if (window.MathJax) {
                window.MathJax.__chatbotSafeActive = false;
            }
            return false;
        });
    }

    var pending = [];
    var pump = 0;

    function renderTarget(target) {
        return renderSafely(target).then(function (ok) {
            var children;
            var chain;
            if (ok) {
                return true;
            }
            children = target.children ? Array.prototype.slice.call(target.children) : [];
            if (!children.length) {
                return false;
            }
            if (children.length === 1) {
                return renderTarget(children[0]);
            }
            chain = Promise.resolve(false);
            children.forEach(function (child) {
                chain = chain.then(function (any) {
                    return renderTarget(child).then(function (childOk) {
                        return any || childOk;
                    });
                });
            });
            return chain;
        });
    }

    function flushPending() {
        var queued = pending.splice(0, pending.length);
        var index;
        for (index = 0; index < queued.length; index++) {
            queued[index].resolve(renderTarget(queued[index].target));
        }
    }

    function enqueueTypeset(target) {
        return new Promise(function (resolve) {
            pending.push({ target: target, resolve: resolve });
            if (pump) {
                return;
            }
            var tries = 0;
            pump = window.setInterval(function () {
                tries += 1;
                if (window.chatbotMathJaxForeign === true) {
                    window.clearInterval(pump);
                    pump = 0;
                    flushPending();
                    return;
                }
                if (ownedMathJax()) {
                    window.clearInterval(pump);
                    pump = 0;
                    installGuard();
                    flushPending();
                    return;
                }
                if (tries > 50) {
                    window.clearInterval(pump);
                    pump = 0;
                    pending.splice(0, pending.length).forEach(function (item) {
                        item.resolve(false);
                    });
                }
            }, 100);
        });
    }

    function typesetMath(element) {
        var target = mathJaxTarget(element);
        if (!target || window.chatbotMathJaxForeign === true) {
            return Promise.resolve(false);
        }
        if (!ownedMathJax()) {
            return enqueueTypeset(target);
        }
        installGuard();
        return renderTarget(target);
    }

    function installClickGuard() {
        if (window.__chatbotMathJaxClickGuard) {
            return;
        }
        window.__chatbotMathJaxClickGuard = true;
        document.addEventListener('click', function (event) {
            var node = event.target;
            var root;
            var href;
            var src;
            if (!node || typeof node.closest !== 'function') {
                return;
            }
            root = node.closest('.chatbot-chatgpt-root');
            if (!root) {
                return;
            }
            while (node && node !== root) {
                if (node.getAttribute) {
                    href = node.getAttribute('href');
                    src = node.getAttribute('src');
                    if ((href && urlIsBlocked(href)) || (src && urlIsBlocked(src))) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        neutralizeMathJaxElement(node, false);
                        return;
                    }
                }
                node = node.parentElement;
            }
        }, true);
    }

    window.chatbotUrlIsBlocked = urlIsBlocked;
    window.chatbotNeutralizeMathJaxOutput = neutralizeMathJaxOutput;
    window.chatbotInstallMathJaxGuard = installGuard;
    window.chatbotTypesetMath = typesetMath;
    installClickGuard();
}());
