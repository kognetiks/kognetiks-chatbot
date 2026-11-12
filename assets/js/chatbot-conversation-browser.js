/**
 * Browser transcript identity for OpenAI Responses widgets.
 *
 * The envelope is stored by WordPress user, external assistant id, and row
 * scope. HTML is restored only when its generation matches the server
 * generation on the widget. An empty server generation means the page could
 * not see this tab's conversation, so the caller may supply the saved one.
 * This file does not read credentials or create conversations.
 */
(function (root, factory) {
    var api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
    root.kognetiksConversationBrowser = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var GENERATION_PATTERN = /^[a-f0-9]{32}$/;
    var SCOPE_PATTERN = /^(absent|0|[1-9][0-9]*)$/;
    var ASSISTANT_PATTERN = /^(?:asst_|pmpt_)[A-Za-z0-9_-]{1,80}$/;

    function transcriptActor(actor) {
        var owner = actor === undefined || actor === null ? '' : String(actor);
        if (!/^[A-Za-z0-9]{1,64}$/.test(owner)) {
            return '';
        }
        return owner;
    }

    function storageKey(assistantId, rowScope, actor) {
        var owner = transcriptActor(actor);
        if (!owner || !ASSISTANT_PATTERN.test(String(assistantId || '')) || !SCOPE_PATTERN.test(String(rowScope || ''))) {
            return '';
        }
        return 'chatbot_chatgpt_conversation_' + owner + '_' + assistantId + '_' + rowScope;
    }

    function legacyStorageKey(assistantId, actor) {
        var owner = transcriptActor(actor);
        if (!owner) {
            return '';
        }
        return 'chatbot_chatgpt_conversation_' + owner + '_' + String(assistantId || '');
    }

    function encodeEnvelope(generation, html) {
        if (!GENERATION_PATTERN.test(String(generation || '')) || typeof html !== 'string') {
            return '';
        }
        return JSON.stringify({
            generation: generation,
            html: html
        });
    }

    function readEnvelope(raw) {
        if (typeof raw !== 'string' || raw.charAt(0) !== '{') {
            return null;
        }
        var parsed;
        try {
            parsed = JSON.parse(raw);
        } catch (error) {
            return null;
        }
        if (!parsed || typeof parsed !== 'object' || typeof parsed.generation !== 'string' || typeof parsed.html !== 'string') {
            return null;
        }
        if (!GENERATION_PATTERN.test(parsed.generation)) {
            return null;
        }
        return parsed;
    }

    function restoredHtml(raw, authoritativeGeneration) {
        var envelope = readEnvelope(raw);
        if (!envelope || !GENERATION_PATTERN.test(String(authoritativeGeneration || ''))) {
            return '';
        }
        if (envelope.generation !== authoritativeGeneration) {
            return '';
        }
        return envelope.html;
    }

    function replyMeta(data) {
        if (!data || typeof data !== 'object' || data.queued) {
            return null;
        }
        if (typeof data.message !== 'string' || !GENERATION_PATTERN.test(String(data.conversation_generation || ''))) {
            return null;
        }
        if (!ASSISTANT_PATTERN.test(String(data.assistant_id || '')) || !SCOPE_PATTERN.test(String(data.row_scope || ''))) {
            return null;
        }
        return {
            message: data.message,
            conversation_generation: data.conversation_generation,
            assistant_id: data.assistant_id,
            row_scope: data.row_scope
        };
    }

    function planReply(root, meta) {
        if (!meta || !root) {
            return 'ignore';
        }
        if (root.assistantId !== meta.assistant_id || root.rowScope !== meta.row_scope) {
            return 'ignore';
        }
        if (root.generation === meta.conversation_generation) {
            return 'append';
        }
        return 'rotate';
    }

    function applyReply(roots, meta, visitorHtml, replyHtml, actor) {
        var next = [];
        var writes = [];
        var applied = false;
        var index;
        var valid = replyMeta(meta);
        if (!Array.isArray(roots) || typeof visitorHtml !== 'string' || typeof replyHtml !== 'string' || !valid) {
            return { applied: false, roots: Array.isArray(roots) ? roots : [], writes: [] };
        }
        meta = valid;
        for (index = 0; index < roots.length; index++) {
            var root = roots[index];
            var plan = planReply(root, meta);
            if (plan === 'ignore') {
                next.push(root);
                continue;
            }
            applied = true;
            var html = plan === 'append' ? String(root.html || '') + replyHtml : visitorHtml + replyHtml;
            var updated = {
                assistantId: root.assistantId,
                rowScope: root.rowScope,
                generation: meta.conversation_generation,
                html: html
            };
            next.push(updated);
            var writeKey = storageKey(updated.assistantId, updated.rowScope, actor);
            if (writeKey !== '') {
                writes.push({
                    key: writeKey,
                    value: encodeEnvelope(updated.generation, updated.html)
                });
            }
        }
        return { applied: applied, roots: next, writes: writes };
    }

    function storageGet(storage, key) {
        if (!storage || typeof storage.getItem !== 'function' || !key) {
            return null;
        }
        try {
            return storage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function storageSet(storage, key, value) {
        if (!storage || typeof storage.setItem !== 'function' || !key || !value) {
            return false;
        }
        try {
            storage.setItem(key, value);
            return true;
        } catch (error) {
            return false;
        }
    }

    function scopeEpochKey(assistantId, rowScope, actor) {
        var key = storageKey(assistantId, rowScope, actor);
        return key === '' ? '' : 'epoch:' + key;
    }

    function beginRequest(epochs, assistantId, rowScope, actor) {
        var key = scopeEpochKey(assistantId, rowScope, actor);
        if (!key || !epochs || typeof epochs !== 'object') {
            return null;
        }
        if (!Object.prototype.hasOwnProperty.call(epochs, key)) {
            epochs[key] = 0;
        }
        return { key: key, epoch: epochs[key] };
    }

    function invalidateScope(epochs, assistantId, rowScope, actor) {
        var key = scopeEpochKey(assistantId, rowScope, actor);
        if (!key || !epochs || typeof epochs !== 'object') {
            return false;
        }
        var current = Object.prototype.hasOwnProperty.call(epochs, key) ? epochs[key] : 0;
        epochs[key] = current + 1;
        return true;
    }

    function requestIsCurrent(epochs, ticket) {
        if (!ticket || !epochs || typeof ticket.key !== 'string' || typeof ticket.epoch !== 'number') {
            return false;
        }
        return epochs[ticket.key] === ticket.epoch;
    }

    function storageRemove(storage, key) {
        if (!storage || typeof storage.removeItem !== 'function' || !key) {
            return false;
        }
        try {
            storage.removeItem(key);
            return true;
        } catch (error) {
            return false;
        }
    }

    return {
        storageKey: storageKey,
        legacyStorageKey: legacyStorageKey,
        encodeEnvelope: encodeEnvelope,
        readEnvelope: readEnvelope,
        restoredHtml: restoredHtml,
        replyMeta: replyMeta,
        planReply: planReply,
        applyReply: applyReply,
        scopeEpochKey: scopeEpochKey,
        beginRequest: beginRequest,
        invalidateScope: invalidateScope,
        requestIsCurrent: requestIsCurrent,
        storageGet: storageGet,
        storageSet: storageSet,
        storageRemove: storageRemove
    };
});
