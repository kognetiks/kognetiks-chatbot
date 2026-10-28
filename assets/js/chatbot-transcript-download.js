/**
 * Build a plain-text transcript from the conversation already visible in the page.
 *
 * Export every displayed message from the chatbot that contains the clicked
 * control, including Knowledge Navigator titles and summaries appended to an
 * assistant message. Leave out text that is not inside that conversation.
 * The result is plain text. This file does not read localStorage or
 * sessionStorage, and it does not send the transcript to WordPress.
 */
(function (root, factory) {
    var api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
    root.kognetiksTranscriptDownload = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var FILENAME_PATTERN = /^transcript_[A-Za-z0-9]{26}_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.txt$/;

    function transcriptTextFromHtml(html) {
        var source = String(html || '');
        source = source.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
        source = source.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '');
        source = source.replace(/<div\b[^>]*\bchatbot-typing-indicator\b[^>]*>[\s\S]*?<\/div>/gi, '');
        source = source.replace(/<br\s*\/?>/gi, '\n');
        source = source.replace(/<\/(div|p|li|tr|h[1-6]|blockquote|pre)>/gi, '\n');
        source = source.replace(/<[^>]+>/g, '');
        source = decodeHtmlEntities(source);
        source = source.replace(/\r\n?/g, '\n');
        source = source.replace(/[ \t]+\n/g, '\n');
        source = source.replace(/\n{3,}/g, '\n\n');
        return source.trim();
    }

    function decodeHtmlEntities(text) {
        var decoded = String(text || '');
        decoded = decoded.replace(/&#(\d+);/g, function (_, digits) {
            return characterFromCodePoint(parseInt(digits, 10));
        });
        decoded = decoded.replace(/&#x([0-9a-f]+);/gi, function (_, hex) {
            return characterFromCodePoint(parseInt(hex, 16));
        });
        decoded = decoded.replace(/&nbsp;/gi, ' ');
        decoded = decoded.replace(/&quot;/gi, '"');
        decoded = decoded.replace(/&#0*39;|&apos;/gi, "'");
        decoded = decoded.replace(/&lt;/gi, '<');
        decoded = decoded.replace(/&gt;/gi, '>');
        decoded = decoded.replace(/&amp;/gi, '&');
        return decoded;
    }

    function characterFromCodePoint(codePoint) {
        if (!isFinite(codePoint) || codePoint < 0 || codePoint > 0x10ffff) {
            return '';
        }
        if (codePoint >= 0xd800 && codePoint <= 0xdfff) {
            return '';
        }
        return String.fromCodePoint(codePoint);
    }

    function hasClass(node, className) {
        if (!node || node.nodeType === 3) {
            return false;
        }
        var value = node.className;
        if (!value) {
            return false;
        }
        if (typeof value !== 'string') {
            value = value.baseVal || '';
        }
        return (' ' + value + ' ').indexOf(' ' + className + ' ') !== -1;
    }

    function owningChatbotRoot(button) {
        var node = button;
        while (node) {
            if (node.nodeType === 1 && (hasClass(node, 'chatbot-chatgpt-root') || node.id === 'chatbot-chatgpt')) {
                return node;
            }
            node = node.parentElement || null;
        }
        return null;
    }

    function isInside(node, ancestor) {
        var current = node;
        while (current) {
            if (current === ancestor) {
                return true;
            }
            current = current.parentElement || null;
        }
        return false;
    }

    function walkElements(root, visitor) {
        var children = root && root.children ? root.children : [];
        var index;
        for (index = 0; index < children.length; index++) {
            visitor(children[index]);
            walkElements(children[index], visitor);
        }
    }

    function findConversationElement(button) {
        var root = owningChatbotRoot(button);
        if (!root) {
            return null;
        }
        var conversation = null;
        walkElements(root, function (node) {
            if (!conversation && (hasClass(node, 'chatbot-chatgpt-conversation') || node.id === 'chatbot-chatgpt-conversation')) {
                conversation = node;
            }
        });
        if (!conversation || !isInside(conversation, root)) {
            return null;
        }
        return conversation;
    }

    function transcriptForButton(button) {
        var conversation = findConversationElement(button);
        if (!conversation) {
            return null;
        }
        var parts = [];
        walkElements(conversation, function (node) {
            if (!hasClass(node, 'chat-message')) {
                return;
            }
            var text = transcriptTextFromHtml(typeof node.innerHTML === 'string' ? node.innerHTML : '');
            if (text) {
                parts.push(text);
            }
        });
        return parts.join('\n\n');
    }

    function randomTranscriptToken() {
        var alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        var bytes = new Uint8Array(26);
        var index;
        if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
            crypto.getRandomValues(bytes);
        } else {
            for (index = 0; index < bytes.length; index++) {
                bytes[index] = Math.floor(Math.random() * 256);
            }
        }
        var token = '';
        for (index = 0; index < bytes.length; index++) {
            token += alphabet[bytes[index] % alphabet.length];
        }
        return token;
    }

    function transcriptFilename(now, randomToken) {
        var token = typeof randomToken === 'string' && /^[A-Za-z0-9]{26}$/.test(randomToken)
            ? randomToken
            : randomTranscriptToken();
        var date = now instanceof Date && !isNaN(now.getTime()) ? now : new Date();
        var stamp = date.getUTCFullYear()
            + '-' + padTwo(date.getUTCMonth() + 1)
            + '-' + padTwo(date.getUTCDate())
            + '_' + padTwo(date.getUTCHours())
            + '-' + padTwo(date.getUTCMinutes())
            + '-' + padTwo(date.getUTCSeconds());
        return 'transcript_' + token + '_' + stamp + '.txt';
    }

    function padTwo(value) {
        return (value < 10 ? '0' : '') + value;
    }

    function startTextDownload(text, filename) {
        if (typeof Blob !== 'function' || typeof URL === 'undefined' || typeof URL.createObjectURL !== 'function') {
            return false;
        }
        if (typeof document === 'undefined' || !document.body || typeof document.createElement !== 'function') {
            return false;
        }
        if (!FILENAME_PATTERN.test(String(filename || ''))) {
            return false;
        }
        var blob = new Blob(['\uFEFF' + String(text || '')], { type: 'text/plain;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.rel = 'noopener';
        // Firefox drops a display:none link and can save the current page instead of the blob.
        link.style.position = 'fixed';
        link.style.left = '0';
        link.style.top = '0';
        link.style.width = '1px';
        link.style.height = '1px';
        link.style.opacity = '0';
        link.style.pointerEvents = 'none';
        document.body.appendChild(link);
        link.click();
        setTimeout(function () {
            if (link.parentNode) {
                link.parentNode.removeChild(link);
            }
            URL.revokeObjectURL(url);
        }, 60000);
        return true;
    }

    return {
        transcriptTextFromHtml: transcriptTextFromHtml,
        findConversationElement: findConversationElement,
        transcriptForButton: transcriptForButton,
        transcriptFilename: transcriptFilename,
        startTextDownload: startTextDownload
    };
});
