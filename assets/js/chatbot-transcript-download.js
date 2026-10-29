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

    var BLOCK_CLOSING_TAGS = {
        div: true,
        p: true,
        li: true,
        tr: true,
        h1: true,
        h2: true,
        h3: true,
        h4: true,
        h5: true,
        h6: true,
        blockquote: true,
        pre: true
    };

    // Walk the markup and copy only text. A replace that deletes "<script...></script>"
    // can be bypassed by "<scrip<script>t>" and misses end tags such as "</script >".
    function transcriptTextFromHtml(html) {
        var text = textFromTranscriptHtml(String(html || ''));
        text = text.replace(/\r\n?/g, '\n');
        text = text.replace(/[ \t]+\n/g, '\n');
        text = text.replace(/\n{3,}/g, '\n\n');
        return text.trim();
    }

    function textFromTranscriptHtml(html) {
        var out = '';
        var index = 0;
        while (index < html.length) {
            var open = html.indexOf('<', index);
            if (open === -1) {
                out += decodeHtmlEntities(html.slice(index));
                break;
            }
            if (open > index) {
                out += decodeHtmlEntities(html.slice(index, open));
            }
            var tag = readTag(html, open);
            if (!tag || tag.end <= open) {
                out += '<';
                index = open + 1;
                continue;
            }
            index = tag.end;
            if (tag.type !== 'tag') {
                continue;
            }
            if (!tag.closing && (tag.name === 'script' || tag.name === 'style')) {
                index = skipRawTextElement(html, tag.end, tag.name);
                continue;
            }
            if (!tag.closing && tag.name === 'div' && classListContains(tag.classValue, 'chatbot-typing-indicator')) {
                index = skipNestedElement(html, tag.end, 'div');
                continue;
            }
            if (!tag.closing && tag.name === 'br') {
                out += '\n';
                continue;
            }
            if (tag.closing && BLOCK_CLOSING_TAGS[tag.name]) {
                out += '\n';
            }
        }
        return out;
    }

    function readTag(html, start) {
        var length = html.length;
        var index = start + 1;
        if (index >= length) {
            return null;
        }
        if (html.slice(index, index + 3) === '!--') {
            return { type: 'comment', end: endOfComment(html, start) };
        }
        if (html.charAt(index) === '!' || html.charAt(index) === '?') {
            return { type: 'declaration', end: indexAfterCharacter(html, index, '>') };
        }
        var closing = false;
        if (html.charAt(index) === '/') {
            closing = true;
            index += 1;
        }
        if (!isAsciiAlpha(html.charAt(index))) {
            return null;
        }
        var nameStart = index;
        index += 1;
        while (index < length && isAsciiAlphaNumeric(html.charAt(index))) {
            index += 1;
        }
        var name = html.slice(nameStart, index).toLowerCase();
        var classValue = '';
        var classSeen = false;
        while (index < length) {
            var character = html.charAt(index);
            if (character === '>') {
                return {
                    type: 'tag',
                    name: name,
                    closing: closing,
                    classValue: classValue,
                    end: index + 1
                };
            }
            if (isHtmlWhitespace(character) || character === '/') {
                index += 1;
                continue;
            }
            var attributeStart = index;
            while (index < length) {
                var attributeCharacter = html.charAt(index);
                if (isHtmlWhitespace(attributeCharacter) || attributeCharacter === '=' || attributeCharacter === '>' || attributeCharacter === '/' || attributeCharacter === '"' || attributeCharacter === "'") {
                    break;
                }
                index += 1;
            }
            var attributeName = html.slice(attributeStart, index).toLowerCase();
            while (index < length && isHtmlWhitespace(html.charAt(index))) {
                index += 1;
            }
            var attributeValue = '';
            if (html.charAt(index) === '=') {
                index += 1;
                while (index < length && isHtmlWhitespace(html.charAt(index))) {
                    index += 1;
                }
                var quote = html.charAt(index);
                if (quote === '"' || quote === "'") {
                    index += 1;
                    var valueStart = index;
                    while (index < length && html.charAt(index) !== quote) {
                        index += 1;
                    }
                    attributeValue = html.slice(valueStart, index);
                    if (index < length) {
                        index += 1;
                    }
                } else {
                    var unquotedStart = index;
                    while (index < length && !isHtmlWhitespace(html.charAt(index)) && html.charAt(index) !== '>') {
                        index += 1;
                    }
                    attributeValue = html.slice(unquotedStart, index);
                }
            }
            if (attributeName === 'class' && !classSeen) {
                classValue = attributeValue;
                classSeen = true;
            }
        }
        return {
            type: 'tag',
            name: name,
            closing: closing,
            classValue: classValue,
            end: length
        };
    }

    function endOfComment(html, start) {
        var from = start + 4;
        var dashed = html.indexOf('-->', from);
        var bang = html.indexOf('--!>', from);
        var end = html.length;
        if (dashed !== -1) {
            end = dashed + 3;
        }
        if (bang !== -1 && bang + 4 < end) {
            end = bang + 4;
        }
        return end;
    }

    function indexAfterCharacter(html, start, character) {
        var found = html.indexOf(character, start);
        if (found === -1) {
            return html.length;
        }
        return found + character.length;
    }

    function skipRawTextElement(html, contentStart, name) {
        var needle = '</' + name;
        var from = contentStart;
        while (from < html.length) {
            var found = indexOfAsciiIgnoreCase(html, needle, from);
            if (found === -1) {
                return html.length;
            }
            var boundary = found + needle.length;
            var next = boundary < html.length ? html.charAt(boundary) : '';
            if (next !== '' && next !== '>' && next !== '/' && !isHtmlWhitespace(next)) {
                from = found + 1;
                continue;
            }
            var tag = readTag(html, found);
            if (tag && tag.type === 'tag' && tag.closing && tag.name === name) {
                return tag.end;
            }
            from = found + 1;
        }
        return html.length;
    }

    function skipNestedElement(html, contentStart, name) {
        var index = contentStart;
        var depth = 1;
        while (index < html.length && depth > 0) {
            var open = html.indexOf('<', index);
            if (open === -1) {
                return html.length;
            }
            var tag = readTag(html, open);
            if (!tag || tag.end <= open) {
                index = open + 1;
                continue;
            }
            if (tag.type === 'tag' && tag.name === name) {
                depth += tag.closing ? -1 : 1;
            }
            index = tag.end;
        }
        return index;
    }

    function classListContains(classValue, expected) {
        var normalized = '';
        var pendingSpace = false;
        var index;
        for (index = 0; index < classValue.length; index++) {
            var character = classValue.charAt(index);
            if (isHtmlWhitespace(character)) {
                pendingSpace = normalized.length > 0;
                continue;
            }
            if (pendingSpace) {
                normalized += ' ';
                pendingSpace = false;
            }
            normalized += character;
        }
        return (' ' + normalized + ' ').indexOf(' ' + expected + ' ') !== -1;
    }

    function indexOfAsciiIgnoreCase(html, needle, from) {
        var last = html.length - needle.length;
        var index = from;
        while (index <= last) {
            var offset = 0;
            while (offset < needle.length) {
                if (asciiLowerCode(html.charCodeAt(index + offset)) !== needle.charCodeAt(offset)) {
                    break;
                }
                offset += 1;
            }
            if (offset === needle.length) {
                return index;
            }
            index += 1;
        }
        return -1;
    }

    function asciiLowerCode(code) {
        if (code >= 65 && code <= 90) {
            return code + 32;
        }
        return code;
    }

    function isHtmlWhitespace(character) {
        return character === ' ' || character === '\t' || character === '\n' || character === '\r' || character === '\f';
    }

    function isAsciiAlpha(character) {
        var code = character.charCodeAt(0);
        return (code >= 65 && code <= 90) || (code >= 97 && code <= 122);
    }

    function isAsciiAlphaNumeric(character) {
        var code = character.charCodeAt(0);
        return isAsciiAlpha(character) || (code >= 48 && code <= 57);
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
